<?php

require 'vendor/autoload.php';

use Cjdns\Admin\Socket;
use Cjdns\Api\Api;
use Cjdns\Config\Admin;
use Cjdns\Toolshed\Toolshed;

use React\Espresso\Application as Espresso;

$app  = new Espresso;
$addr = '127.0.0.1';
$port = 1337;

/* Either set cjdns admin connection details in-file */
$cfg = new Admin([
    'addr' => '127.0.0.1',
    'password' => 'YRZNhY4QCgUUFDnwQXGxoyuh9aaXvrNW6Tyl78JG',
    'port' => 11234,
]);

/* or use cjdns admin connection details in file as json object  */
$cfg = new Admin(['cfgfile' => '/home/igel/.cjdnsadmin']);

$app->get('/nodes', function ($request, $response) {

    echo Toolshed::logger('Incoming: /nodes');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $data = $Auth(Api::InterfaceController_peerStats());
    echo PHP_EOL;
    return $response->end($data);

});

/* Available Functions */
$app->get('/help', function ($request, $response) {

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
    return $response->end(Toolshed::cleanresp($result, true));

});


/* Authenticated Ping */
$app->get('/authping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /ping');

    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $Socket = new Socket($cfg);
    $data = $Socket->authput(Api::AuthPing());

    echo PHP_EOL;
    return $response->end($data);
});

/* Un-Authenticated Ping */
$app->get('/ping', function ($request, $response) use ($cfg) {

    echo Toolshed::logger('Incoming: /ping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $data = Api::Ping();

    $Socket = new Socket($cfg);

    $Socket->put($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));

});

echo Toolshed::logger('ReactPHP Admin started http://'.$addr.':'.$port);

$stack = new React\Espresso\Stack($app);
$stack->listen($port, $addr);
