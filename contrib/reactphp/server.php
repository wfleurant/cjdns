<?php

require 'vendor/autoload.php';

use Cjdns\Admin\Socket;
use Cjdns\Api\Api;
use Cjdns\Config\Admin;
use Cjdns\Toolshed\Toolshed;

/*use React\Promise\Deferred;*/
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

echo Toolshed::logger('ReactPHP Admin started http://'.$addr.':'.$port);

$app->get('/favicon.ico', function ($request, $response) {
    $response->writeHead(204); $response->end();
});

$Auth = function($method) use ($cfg) {

    $Socket = new Socket($cfg);

    $txid = Api::txid();

    /* Step 1: Request a cookie from the server. */

    $Socket->put(Api::Cookie($txid));
    $verona = Api::decode($Socket->message)['cookie'];
    echo Toolshed::logger('Received Cookie: ' . $verona);

    /* Step 2: Calculate the SHA-256 of your admin password cookie and the cookie,
    place this hash and cookie in the request. */

    $sha2auth = hash('sha256', $cfg->password . $verona);
    echo Toolshed::logger('Hash Cookie: ' . $sha2auth);

    /* Step 3: Calculate the SHA-256 of the entire request with
    the hash and cookie added, replace the hash in the request with this result. */

    $hashcookie = [ 'hash' => $sha2auth, 'cookie' => $verona ];

    $prequest = array_merge($hashcookie, Api::decode($method));
    $prequest['hash'] = hash('sha256', Api::encode($prequest));
    $prequest = Api::encode($prequest);

    $Socket->put($prequest);

    return json_encode(Api::decode($Socket->message));
};

$app->get('/nodes', function ($request, $response) use ($Auth) {

    echo Toolshed::logger('Incoming: /nodes');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $data = $Auth(Api::InterfaceController_peerStats());
    echo PHP_EOL;
    return $response->end($data);

});

/* Available Functions */
$app->get('/help', function ($request, $response) use ($Auth, $cfg) {

    echo Toolshed::logger('Incoming: /help');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $data = Api::Admin_availableFunctions();

    $Socket = new Socket($cfg);
    $Socket->put($data);

    echo PHP_EOL;
    return $response->end(json_encode(Api::decode($Socket->message)));

});


/* Authenticated Ping */
$app->get('/authping', function ($request, $response) use ($Auth) {

    echo Toolshed::logger('Incoming: /ping');
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $data = $Auth(Api::AuthPing());
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

$stack = new React\Espresso\Stack($app);
$stack->listen($port, $addr);
