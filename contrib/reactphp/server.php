<?php

/********************************************/
/* Display hinting reminder this application
 * depends on external external  networks to
 * update it's dependencies  and  additional
 * cjdns build-time languages such as nodejs
/********************************************/

try {

    $libmgmt = [
        'vendor/autoload.php' => [
            'hint' => "Note: PHP Dependency Manager: "
                      ."//getcomposer.org/download/"
        ],

        '../../publictoip6'   => [
            'hint' => "Compile with: ../../cjdns/do"
        ],

        '../../cjdroute'   => [
            'hint' => "Compile with: ../../cjdns/do"
        ],

        'server.php'           => [
            'hint' => "Execute within: ". $_SERVER['HOME']
                      ."/cjdns/contrib/reactphp"
        ],

    ];

    $notready = function ($v=false) use ($libmgmt) {
        foreach ($libmgmt as $file => $r) {
            if (!realpath(getcwd() . '/' . $file)) {
                return $file;
            }
        }
    };

    if ($notready()) {
        echo $libmgmt[$notready()]['hint'] . PHP_EOL;
        throw new \Exception('Missing: ' . $notready(), 1);
    }

} catch (\Exception $e) {
    trigger_error($e->getMessage());exit;
}

/********************************************/

require 'vendor/autoload.php';

use Cjdns\Admin\Socket;
use Cjdns\Api\Api;
use Cjdns\Config\Admin;
use Cjdns\Toolshed\Toolshed;
use Cjdns\Toolshed\SQLite;

/* Database */
$database = new SQLite;

/* Authorization to Admin-API can be set in-script */
$cfg = new Admin([
    'addr'      => '127.0.0.1',
    'port'      => 11234,
    'password'  => 'YRZNhY4QCgUUFDnwQXGxoyuh9aaXvrNW6Tyl78JG',
    'publicKey' => 'rzvr2764sfb3lg8d9sd1d6c8jh656jky35cy86xnq52f7xqxftq0.k',
]);

/* Authorization by default is set via ../../cjdroute.conf */
$cfg = new Admin(['cfgfile' => '../../cjdroute.conf']);

/* HTTP Server Address */
$addr = Toolshed::publictoip6($cfg->publicKey);
$port = 1337;

/*******************************************************************/

$app = new Phluid\App([ 'default_layout' => 'layout' ]);

$app->inject(new \Phluid\Middleware\ExceptionHandler('exception'));

$app->inject(new \Phluid\Middleware\RequestTimer());

$app->inject(function($request, $response, $next) use ($app) {
    $response->once('end', function() use ($request, $response, $app) {
        // https://github.com/reactphp/react/pull/228/files
        // var_dump($request->request->remoteAddress);

        Toolshed::logger_http("\t".
            "Log\t\t"  . $request.' ('.$response . ")\n\t\t".
            "Time\t\t" . $request->duration . " ms" . "\n\t\t".
            "Path\t\t" . $request->getPath() . "\n\t\t"
        );

        /* save to db (?)
            $array = [
                'host' => $request->getHost(),
                'log'  => [ 'request' => $request, 'response' => $response ],
                'time' => $request->duration
            ];

            Toolshed::logger_http_save($array);
        */

    });
    $next();
});

$app->inject(function($req, $res, $next) {
    $res->setHeader('X-Powered-By', 'ReactPHP');
    $next();
});

/*******************************************************************/
$app->createServer();

$app->loop->addPeriodicTimer($databasetimer = 1, function () use ($cfg, $database) {

    $more = true; $page=0;
    $Socket = new Socket($cfg);
    $Socket->verbose = false;

    while ($more) {

        $data = Api::InterfaceController_peerStats(null, $page);
        $Socket->authput($data);
        $respdata[$page] = Api::decode($Socket->message);
        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    $alloc = Toolshed::ramusage($cfg);
    $peers = ', Peers: ' . $respdata[0]['total'];
    Toolshed::logger($alloc . ' ' . $peers);

    $columns = $database->sqlite_column_fetch($database, 'peerstats');

    /* Write out peerstats table */
    $database->write('nodes', $columns, $respdata);

});

/* for middleware files in ./views/public/
    $app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/public'));
   see: https://github.com/beaucollins/phluid-php#middleware
*/

$app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/views/js'));
$app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/views/css'));
$app->inject(new \Phluid\Middleware\StaticFiles(__DIR__ . '/views/img'));

/*******************************************************************/

$app->get('/', function($req, $res) use ($cfg) {

    $obj = new stdclass();
    $obj->Toolshed = new Toolshed;
    $res->render('layout', [ 'obj' => $obj ]);

});

$app->get('/exit', function ($request, $response) use ($cfg) {
    Toolshed::logger_http('Incoming: /exit', $request);
    exit();
});

$app->get('/report', function ($request, $response, $pubkey) use ($cfg, $database) {

    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    /* ... print available report dates (?) */
    $v = 'Please use: /report/[publicKey] with parameters: ';
    return $response->end($v);

});

/*******************************************************************/
$app->get('/report/:pubkey', function ($request, $response, $pubkey) use ($cfg, $database) {

    /* public key of node */
    $pubkey = $request->param('pubkey');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $method = $request->param('date');
    if (!$method) {
        $phrase = 'first day of this month';
    } else {
        /* choose default or print available report dates */
    }

    /* use between dates, or a phrase */

    $phrase = 'first day of this month';

    $from = \Carbon\Carbon::today('UTC');
    $from = $from->modify($phrase)->toIso8601String();

    $until = \Carbon\Carbon::yesterday('UTC')->toIso8601String();

    $date = [
        'from'   => $from,
        'until'  => $until,
        'phrase' => $phrase
    ];

    /************/
    if ($pubkey) {
        $var = SQLite::report($database, $date, $pubkey);

        $v = json_encode($var);
        /* debug */
        // $v = \Vardump::singleton()->dump($var);

        return $response->end($v);
    } else {
        return $response->end(json_encode(['error']));
    }

});

/*******************************************************************/

$app->get('/nodes', function ($request, $response) use ($cfg, $database) {

    /******/
    $method = $request->param('q');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    /******/

    $more = true; $page=0;
    $Socket = new Socket($cfg);

    while ($more) {

        $data = Api::InterfaceController_peerStats(null, $page);
        $Socket->authput($data);
        $respdata[$page] = Api::decode($Socket->message);
        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    if ($method) {

        echo Toolshed::logger('Incoming: /nodes');

        if ($method == 'nodes') {
            /* This method returns a parsed-for-humans view for a front-end */

            for ($i=0; $i <= $page; $i++) {

                if (!isset($respdata[$i]['peers'])) { continue; }

                $peers = $respdata[$i]['peers'];

                foreach ($peers as $idx => $value) {

                    $nodes[] = [

                        // Connectivity
                        'state' => $value['state'],

                        // cjdns Address
                        'ipv6' => Toolshed::parse_peerstats($value['addr'])['ipv6'],

                        // Total RX
                        'bytesIn' => \ByteUnits\bytes($value['bytesIn'])->format(null, '&nbsp;'),

                        // Total TX
                        'bytesOut' => \ByteUnits\bytes($value['bytesOut'])->format(null, '&nbsp;'),

                        // RX Speed
                        'recvKbps' => number_format($value['recvKbps']/1000, 3) . ' mbps',

                        // TX Speed
                        'sendKbps' => number_format($value['sendKbps']/1000, 3) . ' mbps',

                        // Last Pkt
                        'last' => Carbon\Carbon::now('UTC')->diffForHumans(
                                                Carbon\Carbon::createFromTimeStampUTC(
                                                  round($value['last'] / 1000)), TRUE),

                        // Public Key
                        'publicKey' => $value['publicKey'],

                        // User
                        'user' => $value['user']


                    ];

                }
            }

            $response->end(json_encode([ 'peers' => $nodes ]));

        } else if ($method == 'chodes') {
            /* do blah */
            $response->end(json_encode($respdata));

        } else {
            echo Toolshed::logger('Incoming: /nodes');
            $response->end(json_encode([[ 'response' => 'Unknown Method' ]]));
        }

    } else {

        echo Toolshed::logger('Incoming: /nodes');

        $data = Api::InterfaceController_peerStats();
        $Socket = new Socket($cfg);
        $Socket->authput($data);

        $response->end(json_encode(Api::decode($Socket->message)));

    }

    return;
});

/*******************************************************************/
/* Available Functions */
/*******************************************************************/
$app->get('/help', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /help');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $more = true; $page=0;

    $Socket = new Socket($cfg);
    while ($more) {
        $data = Api::Admin_availableFunctions(null, $page);
        $Socket->put($data);

        $result[$page] = Api::decode($Socket->message);

        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    echo PHP_EOL;
    return $response->end(Toolshed::cleanresp($result, $flatten=true, $droptxrx=true));
});

/*******************************************************************/
/* Authenticated Ping */
/*******************************************************************/
$app->get('/authping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /authping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AuthPing();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

/*******************************************************************/
/* Un-Authenticated Ping */
/*******************************************************************/
$app->get('/ping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /ping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Ping();
    $Socket = new Socket($cfg);
    $Socket->put($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

/*******************************************************************/

/*******************/
/* no parameters() */
/*******************/

$app->get('/Admin_asyncEnabled', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /Admin_asyncEnabled');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Admin_asyncEnabled();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/AdminLog_subscriptions', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /AdminLog_subscriptions');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AdminLog_subscriptions();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Allocator_bytesAllocated', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /Allocator_bytesAllocated');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Allocator_bytesAllocated();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/AuthorizedPasswords_list', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /AuthorizedPasswords_list');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AuthorizedPasswords_list();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Core_exit', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /Core_exit');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Core_exit();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Core_pid', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /Core_pid');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Core_pid();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/NodeStore_dumpTable', function ($request, $response) use ($cfg) {
    /* Parameters NodeStore_dumpTable(page) */

    echo Toolshed::logger('Incoming: /NodeStore_dumpTable');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $Socket = new Socket($cfg);

    $more = true; $page=0;

    while ($more) {
        $data = Api::NodeStore_dumpTable(null, $page);
        $Socket->authput($data);

        $result[$page] = Api::decode($Socket->message);

        if (isset(Api::decode($Socket->message)['more'])) {
            $Socket->verbose = false;
            $page++;
        } else {
            $more = null;
        }
    }

    echo PHP_EOL;
    return $response->end(Toolshed::cleanresp($result, $flatten=true, $droptxrx=true));

});

$app->get('/IpTunnel_listConnections', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /IpTunnel_listConnections');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::IpTunnel_listConnections();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/memory', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /memory');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::memory();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/ping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /ping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::ping();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

/*********************/
/* with parameters() */
/*********************/

$app->get('/AdminLog_logMany', function ($request, $response) use ($cfg) {
    /* Parameters AdminLog_logMany(count) */
    echo Toolshed::logger('Incoming: /AdminLog_logMany');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AdminLog_logMany();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;

    $data = Api::memory();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/AdminLog_subscribe', function ($request, $response) use ($cfg) {
    /* Parameters AdminLog_subscribe(line='', file=0, level=0) */

    echo Toolshed::logger('Incoming: /AdminLog_subscribe');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AdminLog_subscribe();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/AdminLog_unsubscribe', function ($request, $response) use ($cfg) {
    /* Parameters AdminLog_unsubscribe(streamId) */

    echo Toolshed::logger('Incoming: /AdminLog_unsubscribe');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AdminLog_unsubscribe();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Allocator_snapshot', function ($request, $response) use ($cfg) {
    /* Parameters Allocator_snapshot(includeAllocations='') */

    echo Toolshed::logger('Incoming: /Allocator_snapshot');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Allocator_snapshot();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/AuthorizedPasswords_add', function ($request, $response) use ($cfg) {
    /* Parameters AuthorizedPasswords_add(password, user=0, ipv6=0) */

    echo Toolshed::logger('Incoming: /AuthorizedPasswords_add');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AuthorizedPasswords_add();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/AuthorizedPasswords_remove', function ($request, $response) use ($cfg) {
    /* Parameters AuthorizedPasswords_remove(user) */

    echo Toolshed::logger('Incoming: /AuthorizedPasswords_remove');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::AuthorizedPasswords_remove();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/ETHInterface_beacon', function ($request, $response) use ($cfg) {
    /* Parameters ETHInterface_beacon(interfaceNumber='', state='') */

    echo Toolshed::logger('Incoming: /ETHInterface_beacon');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::ETHInterface_beacon();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/ETHInterface_beginConnection', function ($request, $response) use ($cfg) {
    /* Parameters ETHInterface_beginConnection(publicKey, macAddress, interfaceNumber='', login=0, password=0) */

    echo Toolshed::logger('Incoming: /ETHInterface_beginConnection');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::ETHInterface_beginConnection();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/ETHInterface_new', function ($request, $response) use ($cfg) {
    /* Parameters ETHInterface_new(bindDevice) */

    echo Toolshed::logger('Incoming: /ETHInterface_new');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::ETHInterface_new();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/InterfaceController_disconnectPeer', function ($request, $response) use ($cfg) {
    /* Parameters InterfaceController_disconnectPeer(pubkey) */

    echo Toolshed::logger('Incoming: /InterfaceController_disconnectPeer');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::InterfaceController_disconnectPeer();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/IpTunnel_allowConnection', function ($request, $response) use ($cfg) {
    /* Parameters IpTunnel_allowConnection(publicKeyOfAuthorizedNode, ip4Prefix='', ip6Prefix='', ip6Address=0, ip4Address=0) */

    echo Toolshed::logger('Incoming: /IpTunnel_allowConnection');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::IpTunnel_allowConnection();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/IpTunnel_connectTo', function ($request, $response) use ($cfg) {
    /* Parameters IpTunnel_connectTo(publicKeyOfNodeToConnectTo) */

    echo Toolshed::logger('Incoming: /IpTunnel_connectTo');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::IpTunnel_connectTo();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/IpTunnel_removeConnection', function ($request, $response) use ($cfg) {
    /* Parameters IpTunnel_removeConnection(connection) */

    echo Toolshed::logger('Incoming: /IpTunnel_removeConnection');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::IpTunnel_removeConnection();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/IpTunnel_showConnection', function ($request, $response) use ($cfg) {
    /* Parameters IpTunnel_showConnection(connection) */

    echo Toolshed::logger('Incoming: /IpTunnel_showConnection');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::IpTunnel_showConnection();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Janitor_dumpRumorMill', function ($request, $response) use ($cfg) {
    /* Parameters Janitor_dumpRumorMill(mill, page) */

    echo Toolshed::logger('Incoming: /Janitor_dumpRumorMill');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Janitor_dumpRumorMill();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/NodeStore_getLink', function ($request, $response) use ($cfg) {
    /* Parameters NodeStore_getLink(linkNum, parent=0) */

    echo Toolshed::logger('Incoming: /NodeStore_getLink');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::NodeStore_getLink();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/NodeStore_getRouteLabel', function ($request, $response) use ($cfg) {
    /* Parameters NodeStore_getRouteLabel(pathParentToChild, pathToParent) */

    echo Toolshed::logger('Incoming: /NodeStore_getRouteLabel');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::NodeStore_getRouteLabel();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/NodeStore_nodeForAddr', function ($request, $response) use ($cfg) {
    /* Parameters NodeStore_nodeForAddr(ip=0) */

    echo Toolshed::logger('Incoming: /NodeStore_nodeForAddr');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::NodeStore_nodeForAddr();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/RouterModule_findNode', function ($request, $response) use ($cfg) {
    /* Parameters RouterModule_findNode(nodeToQuery, target, timeout='') */

    echo Toolshed::logger('Incoming: /RouterModule_findNode');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::RouterModule_findNode();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/RouterModule_getPeers', function ($request, $response) use ($cfg) {
    /* Parameters RouterModule_getPeers(path, nearbyPath=0, timeout='') */

    echo Toolshed::logger('Incoming: /RouterModule_getPeers');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::RouterModule_getPeers();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/RouterModule_lookup', function ($request, $response) use ($cfg) {
    /* Parameters RouterModule_lookup(address) */

    echo Toolshed::logger('Incoming: /RouterModule_lookup');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::RouterModule_lookup();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/RouterModule_nextHop', function ($request, $response) use ($cfg) {
    /* Parameters RouterModule_nextHop(nodeToQuery, target, timeout='') */

    echo Toolshed::logger('Incoming: /RouterModule_nextHop');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::RouterModule_nextHop();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/RouterModule_pingNode', function ($request, $response) use ($cfg) {
    /* Parameters RouterModule_pingNode(path, timeout='') */

    echo Toolshed::logger('Incoming: /RouterModule_pingNode');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::RouterModule_pingNode();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/SearchRunner_search', function ($request, $response) use ($cfg) {
    /* Parameters SearchRunner_search(ipv6, maxRequests='') */

    echo Toolshed::logger('Incoming: /SearchRunner_search');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::SearchRunner_search();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/SearchRunner_showActiveSearch', function ($request, $response) use ($cfg) {
    /* Parameters SearchRunner_showActiveSearch(number) */

    echo Toolshed::logger('Incoming: /SearchRunner_showActiveSearch');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::SearchRunner_showActiveSearch();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Security_chroot', function ($request, $response) use ($cfg) {
    /* Parameters Security_chroot(root) */

    echo Toolshed::logger('Incoming: /Security_chroot');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Security_chroot();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Security_getUser', function ($request, $response) use ($cfg) {
    /* Parameters Security_getUser(user=0) */

    echo Toolshed::logger('Incoming: /Security_getUser');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Security_getUser();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/Security_setUser', function ($request, $response) use ($cfg) {
    /* Parameters Security_setUser(keepNetAdmin, uid, gid='') */

    echo Toolshed::logger('Incoming: /Security_setUser');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::Security_setUser();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/SessionManager_getHandles', function ($request, $response) use ($cfg) {
    /* Parameters SessionManager_getHandles(page='') */

    echo Toolshed::logger('Incoming: /SessionManager_getHandles');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::SessionManager_getHandles();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/SessionManager_sessionStats', function ($request, $response) use ($cfg) {
    /* Parameters SessionManager_sessionStats(handle) */

    echo Toolshed::logger('Incoming: /SessionManager_sessionStats');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::SessionManager_sessionStats();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/SwitchPinger_ping', function ($request, $response) use ($cfg) {
    /* Parameters SwitchPinger_ping(path, data=0, keyPing='', timeout='') */

    echo Toolshed::logger('Incoming: /SwitchPinger_ping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::SwitchPinger_ping();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

$app->get('/UDPInterface_beginConnection', function ($request, $response) use ($cfg) {
    /* Parameters UDPInterface_beginConnection(publicKey, address, interfaceNumber='', login=0, password=0) */

    echo Toolshed::logger('Incoming: /UDPInterface_beginConnection');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));

    $data = Api::UDPInterface_beginConnection();
    $Socket = new Socket($cfg);
    $Socket->authput($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));
});

Toolshed::logger('Phluid HTTP server available via cjdns @ http://['.$addr.']:'.$port);

$app->listen($port, $addr);
