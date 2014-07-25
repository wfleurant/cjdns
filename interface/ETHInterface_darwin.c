/* vim: set expandtab ts=4 sw=4: */
/*
 * You may redistribute this program and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
#include "exception/Except.h"
#include "interface/Interface.h"
#include "interface/ETHInterface.h"
#include "memory/Allocator.h"
#include "interface/InterfaceController.h"
#include "interface/MultiInterface.h"
#include "wire/Headers.h"
#include "wire/Message.h"
#include "wire/Error.h"
#include "wire/Ethernet.h"
#include "util/Assert.h"
#include "util/platform/Socket.h"
#include "util/events/Event.h"
#include "util/Identity.h"
#include "util/AddrTools.h"
#include "util/version/Version.h"
#include "util/events/Timeout.h"

#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <stdio.h>
#include <fcntl.h>
#include <net/bpf.h>
#include <net/if.h>
#include <net/if_dl.h>
#include <net/ethernet.h>
#include <sys/socket.h>
#include <sys/sysctl.h>
#include <sys/types.h>
#include <sys/ioctl.h>
#include <sys/uio.h>
#include <sys/errno.h>
#include <sys/time.h>

#define MAX_PACKET_SIZE 1496
#define MIN_PACKET_SIZE 46

#define PADDING 512

// 2 last 0x00 of .sll_addr are removed from original size (20)
#define SOCKADDR_LL_LEN 18

/** Wait 16 seconds between sending beacon messages. */
#define BEACON_INTERVAL 32768

 int buf_len = 1;

struct ETHInterface
{
    struct Interface generic;

    int bpf;

    uint8_t messageBuff[PADDING + MAX_PACKET_SIZE];

    struct Log* logger;

    struct InterfaceController* ic;

    struct MultiInterface* multiIface;

    uint8_t ethAddr[6];

    int beaconState;

    /**
     * A unique(ish) id which will be different every time the router starts.
     * This will prevent new eth frames from being confused with old frames from an expired session.
     */
    uint16_t id;

    Identity
};

static uint8_t sendMessage(struct Message* message, struct Interface* ethIf)
{
    struct ETHInterface* context = Identity_check((struct ETHInterface*) ethIf);

    struct ether_header ethHdr = {.ether_type = Ethernet_TYPE_CJDNS};
    Message_pop(message, &ethHdr.ether_dhost, ETHER_ADDR_LEN, NULL);
    Message_shift(message, -2, NULL);
    // .ether_shost gets filled in by kernel

    uint8_t buff[ETHER_ADDR_LEN*2 + 1] = {0};
    Hex_encode(buff, sizeof(buff), (uint8_t*)ethHdr.ether_dhost, ETHER_ADDR_LEN);
    Log_debug(context->logger, "Sending ethernet frame to [%s]", buff);

    // Check if we will have to pad the message and pad if necessary.
    int pad = 0;
    for (int length = message->length; length+2 < MIN_PACKET_SIZE; length += 8) {
        pad++;
    }
    if (pad > 0) {
        int length = message->length;
        Message_shift(message, pad*8, NULL);
        Bits_memset(message->bytes, 0, pad*8);
        Bits_memmove(message->bytes, &message->bytes[pad*8], length);
    }
    Assert_true(pad < 8);
    uint16_t padAndId_be = Endian_hostToBigEndian16((context->id << 3) | pad);
    Message_push(message, &padAndId_be, 2, NULL);

    // add ethernet header to packet
    Message_push(message, &ethHdr, ETHER_HDR_LEN, NULL);

    int ret = write(context->bpf, message->bytes, message->length);
    if (ret == -1) {
        switch (errno) {
            case EMSGSIZE:
                return Error_OVERSIZE_MESSAGE;

            case ENOBUFS:
            case EAGAIN:
                return Error_LINK_LIMIT_EXCEEDED;

            default:;
                Log_info(context->logger, "Got error sending to bpf [%s]",
                         strerror(errno));
        }
    }
    return 0;
}

static void handleBeacon(struct Message* msg, struct ETHInterface* context)
{
    /*
    if (!context->beaconState) {
        // accepting beacons disabled.
        Log_debug(context->logger, "Dropping beacon because beaconing is disabled");
        return;
    }

    struct sockaddr_ll addr;
    Bits_memcpyConst(&addr, &context->addrBase, sizeof(struct sockaddr_ll));
    Message_pop(msg, addr.sll_addr, 8, NULL);
    if (msg->length < Headers_Beacon_SIZE) {
        // Oversize messages are ok because beacons may contain more information in the future.
        Log_debug(context->logger, "Dropping wrong size beacon, expected [%d] got [%d]",
                  Headers_Beacon_SIZE, msg->length);
        return;
    }
    struct Headers_Beacon* beacon = (struct Headers_Beacon*) msg->bytes;

    uint32_t theirVersion = Endian_bigEndianToHost32(beacon->version_be);
    if (!Version_isCompatible(theirVersion, Version_CURRENT_PROTOCOL)) {
        #ifdef Log_DEBUG
            uint8_t mac[18];
            AddrTools_printMac(mac, addr.sll_addr);
            Log_debug(context->logger, "Dropped beacon from [%s] which was version [%d] "
                      "our version is [%d] making them incompatable",
                      mac, theirVersion, Version_CURRENT_PROTOCOL);
        #endif
        return;
    }

    #ifdef Log_DEBUG
        uint8_t mac[18];
        AddrTools_printMac(mac, addr.sll_addr);
        Log_debug(context->logger, "Got beacon from [%s]", mac);
    #endif

    String passStr = { .bytes = (char*) beacon->password, .len = Headers_Beacon_PASSWORD_LEN };
    struct Interface* iface = MultiInterface_ifaceForKey(context->multiIface, addr.sll_addr);
    int ret = InterfaceController_registerPeer(context->ic,
                                               beacon->publicKey,
                                               &passStr,
                                               false,
                                               true,
                                               iface);
    if (ret != 0) {
        uint8_t mac[18];
        AddrTools_printMac(mac, addr.sll_addr);
        Log_info(context->logger, "Got beacon from [%s] and registerPeer returned [%d]", mac, ret);
    }
    */
}

static void sendBeacon(void* vcontext)
{
    struct ETHInterface* context = Identity_check((struct ETHInterface*) vcontext);
    if (context->beaconState != ETHInterface_beacon_ACCEPTING_AND_SENDING) {
        // beaconing disabled
        return;
    }

    struct {
        uint8_t dhost[8];
        struct Headers_Beacon beacon;
    } content;

    Bits_memset(content.dhost, 0xff, 6);
    InterfaceController_populateBeacon(context->ic, &content.beacon);

    struct Message m = {
        .bytes=content.dhost,
        .padding=ETHER_HDR_LEN,
        .length=sizeof(struct Headers_Beacon) + 8
    };

    int ret;
    if ((ret = sendMessage(&m, &context->generic)) != 0) {
        Log_info(context->logger, "Got error [%d] sending beacon [%s]", ret, strerror(errno));
    }
}

static void handleEvent2(struct ETHInterface* context, struct Allocator* messageAlloc)
{
    struct Message* msg = Message_new(MAX_PACKET_SIZE, PADDING, messageAlloc);

    // Knock it out of alignment by 2 bytes so that it will be
    // aligned when the idAndPadding is shifted off.
    Message_shift(msg, 2, NULL);

    int rc = read(context->bpf, msg->bytes, buf_len); // buf_len = 1

    if (rc < 0) {
        Log_debug(context->logger, "Failed to receive eth frame: %s", strerror(errno));
        return;
    }

    Log_debug(context->logger, "Read %d bytes", rc);

    // extract bpf header
    struct bpf_hdr bpfhdr;
    Message_pop(msg, &bpfhdr, ((struct bpf_hdr*)msg->bytes)->bh_hdrlen, NULL);

    // extract ethernet header
    struct ether_header ethHdr;
    Message_pop(msg, &ethHdr, sizeof(struct ether_header), NULL);

    // Pop the first 2 bytes of the message containing the node id and amount of padding.
    uint16_t idAndPadding = Message_pop16(msg, NULL);
    msg->length = rc - 2 - ((idAndPadding & 7) * 8);
    uint16_t id = idAndPadding >> 3;

    Message_push(msg, &id, 2, NULL);
    Message_push(msg, &ethHdr.ether_shost, 6, NULL);

    // check if a broadcast packet (dest addr is FF:FF:FF:FF:FF:FF)
    int i;
    for (i = 0; i < ETHER_ADDR_LEN; i++) {
        if (ethHdr.ether_dhost[i] != 0xFF) {
            break;
        }
    }

    // assume beacon if broadcast packet
    if (i == ETHER_ADDR_LEN) {
        handleBeacon(msg, context);
        return;
    }

    uint8_t buff[sizeof(ethHdr.ether_shost) * 2 + 1] = {0};
    Hex_encode(buff, sizeof(buff), ethHdr.ether_shost, sizeof(ethHdr.ether_shost));
    Log_debug(context->logger, "Got ethernet frame from [%s]", buff);

    Interface_receiveMessage(&context->generic, msg);
}

static void handleEvent(void* vcontext)
{
    struct ETHInterface* context = Identity_check((struct ETHInterface*) vcontext);
    Log_debug(context->logger, "handleEvent()");
    struct Allocator* messageAlloc = Allocator_child(context->generic.allocator);
    handleEvent2(context, messageAlloc);
    Allocator_free(messageAlloc);
}

int ETHInterface_beginConnection(const char* macAddress,
                                 uint8_t cryptoKey[32],
                                 String* password,
                                 struct ETHInterface* ethIf)
{ /*
    Identity_check(ethIf);
    struct sockaddr_ll addr;
    Bits_memcpyConst(&addr, &ethIf->addrBase, sizeof(struct sockaddr_ll));
    if (AddrTools_parseMac(addr.sll_addr, (const uint8_t*)macAddress)) {
        return ETHInterface_beginConnection_BAD_MAC;
    }

    struct Interface* iface = MultiInterface_ifaceForKey(ethIf->multiIface, &addr);
    int ret = InterfaceController_registerPeer(ethIf->ic, cryptoKey, password, false, false, iface);
    if (ret) {
        Allocator_free(iface->allocator);
        switch(ret) {
            case InterfaceController_registerPeer_BAD_KEY:
                return ETHInterface_beginConnection_BAD_KEY;

            case InterfaceController_registerPeer_OUT_OF_SPACE:
                return ETHInterface_beginConnection_OUT_OF_SPACE;

            default:
              return ETHInterface_beginConnection_UNKNOWN_ERROR;
        }
    }
    */
    return 0;
}

int ETHInterface_beacon(struct ETHInterface* ethIf, int* state)
{
    Identity_check(ethIf);
    if (state) {
        ethIf->beaconState = *state;
        // Send out a beacon right away so we don't have to wait.
        if (ethIf->beaconState == ETHInterface_beacon_ACCEPTING_AND_SENDING) {
            sendBeacon(ethIf);
        }
    }
    return ethIf->beaconState;
}

struct ETHInterface* ETHInterface_new(struct EventBase* base,
                                      const char* bindDevice,
                                      struct Allocator* allocator,
                                      struct Except* exHandler,
                                      struct Log* logger,
                                      struct InterfaceController* ic)
{
    struct ETHInterface* context = Allocator_clone(allocator, (&(struct ETHInterface) {
        .generic = {
            .sendMessage = sendMessage,
            .allocator = allocator
        },
        .logger = logger,
        .ic = ic,
        .id = getpid()
    }));

    Identity_set(context);

    // Use Berkley Packet Filter bpf(4) to send/receive ethernet packets
    uint8_t buf[ 11 ] = { 0 };

    for (int i = 0; i < 99; i++) {
        sprintf( buf, "/dev/bpf%i", i );
        context->bpf = open(buf, O_RDWR);
        if (context->bpf != -1) {
            break;
        }
    }

    if (context->bpf == -1) {
        Except_throw(exHandler, "failed to open bpf device.");
    }

    // activate immediate mode (therefore, buf_len is initially set to "1")
    if (ioctl(context->bpf, BIOCIMMEDIATE, &buf_len) == -1) {
        Except_throw(exHandler, "BIOCIMMEDIATE failed [%s]", strerror(errno));
    }

    // request buffer length
    if (ioctl(context->bpf, BIOCGBLEN, &buf_len) == -1) {
        Except_throw(exHandler, "BIOCGBLEN failed [%s]", strerror(errno));
    }
    Log_debug(context->logger, "ioctl BIOCGBLEN buf_len = %i", buf_len);

    // filter for cjdns ethertype (0xfc00)
    struct bpf_insn insns[] = {
        BPF_STMT(BPF_LD+BPF_H+BPF_ABS, 12),
        BPF_JUMP(BPF_JMP+BPF_JEQ+BPF_K, 0xfc00, 0, 1),
        //BPF_JUMP(BPF_JMP+BPF_JEQ+BPF_K, Ethernet_TYPE_CJDNS, 0, 1),
        BPF_STMT(BPF_RET+BPF_K, MAX_PACKET_SIZE),
        BPF_STMT(BPF_RET+BPF_K, 0),
    };

    struct bpf_program bpf_cjdns = {
        .bf_len = (sizeof(insns) / sizeof(struct bpf_insn)),
        .bf_insns = insns,
    };

    if (ioctl(context->bpf, BIOCSETF, &bpf_cjdns) == -1) {
        Except_throw(exHandler, "ioctl BIOCSETF failed [%s]", strerror(errno));
    }
    Log_debug(context->logger, "Opened bpf device [%s]", buf);

    struct ifreq ifr;
    strncpy(ifr.ifr_name, bindDevice, IFNAMSIZ - 1);

    if (ioctl(context->bpf, BIOCSETIF, &ifr) == -1) {
        Except_throw(exHandler, "failed to find interface index [%s]", strerror(errno));
    }

    // get MAC address
    int mgmtInfoBase[6];
    char* msgBuffer = NULL;
    size_t length;
    struct sockaddr_dl* socketStruct;

    // Setup the management Information Base (mib)
    mgmtInfoBase[0] = CTL_NET;        // Request network subsystem
    mgmtInfoBase[1] = AF_ROUTE;       // Routing table info
    mgmtInfoBase[2] = 0;
    mgmtInfoBase[3] = AF_LINK;        // Request link layer information
    mgmtInfoBase[4] = NET_RT_IFLIST;  // Request all configured interfaces

    // With all configured interfaces requested, get handle index
    if ((mgmtInfoBase[5] = if_nametoindex(ifr.ifr_name)) == 0) {
        Except_throw(exHandler, "failed to get mac address [%s]", strerror(errno));
    } else {
        // Get the size of the data available (store in len)
        if (sysctl(mgmtInfoBase, 6, NULL, &length, NULL, 0) < 0) {
            Except_throw(exHandler, "failed to get mac address [%s]", strerror(errno));
        } else {
            // Alloc memory based on above call
            if ((msgBuffer = malloc(length)) == NULL) {
                Except_throw(exHandler, "failed to get mac address [%s]", strerror(errno));
            } else {
                // Get system information, store in buffer
                if (sysctl(mgmtInfoBase, 6, msgBuffer, &length, NULL, 0) < 0) {
                    Except_throw(exHandler, "failed to get mac address [%s]", strerror(errno));
                }
            }
        }
    }

    // Map to link-level socket structure
    socketStruct = (struct sockaddr_dl *) (((struct if_msghdr *) msgBuffer) + 1);

    // Copy link layer address data in socket structure to an array
    Bits_memcpy(context->ethAddr, socketStruct->sdl_data + socketStruct->sdl_nlen, 6);

    // TODO(cjd): is the node's mac addr private information?
    uint8_t buff[13] = {0};
    Hex_encode(buff, sizeof(buff), context->ethAddr, sizeof(context->ethAddr));
    Log_info(context->logger, "found MAC for device %s: %s", bindDevice, buf);

    Event_socketRead(handleEvent, context, context->bpf, base, allocator, exHandler);

    // size of key is 8, 6 for mac + 2 for id.
    context->multiIface = MultiInterface_new(8, &context->generic, ic, logger);

    Timeout_setInterval(sendBeacon, context, BEACON_INTERVAL, base, allocator);

    return context;
}
