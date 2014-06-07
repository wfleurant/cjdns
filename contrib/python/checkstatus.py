#!/usr/bin/env python2
import os
import sys
import socket

# SETTINGS N SHIT #
# You shouldn't need to touch these
# But if you know what you're doing you can
clearnetDNShost = "uppit.us"
clearnetDNSresult = "fc3a:956e:4b69:1c1e:5ebc:11a5:3e71:3e7e"
###################


print "Checking yo shit for cjdns..."
issues = 0

libraries = "Error"
connection = "Error"
try:
    import cjdnsadmin
    libraries = "Success"
    # cjdns = cjdnsadmin.connectWithAdminInfo()
    from cjdnsadmin.cjdnsadmin import connectWithAdminInfo;
    cjdns = connectWithAdminInfo();
    connection = "Success"
except ImportError:
    libraries = "Fail!"
    issues += 1
except UnboundLocalError:
    connection = "Fail!"
    issues += 1
print "Checking for python libraries... %s" % libraries
print "Trying to connect to cjdns... %s" % connection

dnslookup = socket.getaddrinfo(clearnetDNShost, 80)
dnsAAAA = "Error"
dnsAccurate = "Error"
if len(dnslookup) == 0:
    dnsAAAA = "Fail!"
    dnsAccurate = "Fail! (no results)"
for record in dnslookup:
    if record[0] != 10:
        dnsAAAA = "Fail"
        issues += 1
    elif dnsAAAA != "Fail":
        dnsAAAA = "Success"

    if record[4][0] != clearnetDNSresult:
        dnsAccurate = "Fail! (inaccurate result)"
    else:
        if not "Fail!" in dnsAccurate:
            dnsAccurate = "Success"

print "Checking for ability to lookup AAAA records.... %s" % dnsAAAA
print "Checking for accurate fc::/8 response.... %s" % dnsAccurate

if connection == "Success":
    peers = []
    more = True
    i = 0
    while more:
        peertable = cjdns.InterfaceController_peerStats(i)
        more = "more" in peertable
        i += 1
        peers += peertable['peers']

    totalPeers = {"UNRESPONSIVE": 0, "total": 0, "ESTABLISHED": 0}
    for peer in peers:
        if not peer['state'] in totalPeers:
            totalPeers[peer['state']] = 0
        totalPeers[peer['state']] += 1
        totalPeers['total'] += 1

    unresponsivePeers = "Error"
    if totalPeers["UNRESPONSIVE"] > 0:
        unresponsivePeers = "Fail (%i unresponsive peers):" % totalPeers['UNRESPONSIVE']
        issues += 1
    else:
        unresponsivePeers = "Success"

    connectedPeers = "Error"
    if totalPeers['ESTABLISHED'] == 0:
        connectedPeers = "Fail"
        issues += 1
    else:
        connectedPeers = "Success (%i established connections)" % totalPeers['ESTABLISHED']

    actuallyHasPeers = "Error"
    if totalPeers['total'] == 0:
        actuallyHasPeers = "Fail"
        issues += 1
    else:
        actuallyHasPeers = "Success"
    print "Checking for peers... %s" % actuallyHasPeers
    print "Checking for unresponsive peers... %s" % unresponsivePeers
    for peer in peers:
        if peer['state'] == "UNRESPONSIVE":
            print "  * %s" % peer['publicKey']
    print "Checking for active peers... %s" % connectedPeers
    print "Displaying peer totals for fun:"
    print totalPeers

exit(issues)
