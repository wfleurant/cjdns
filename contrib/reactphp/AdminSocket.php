<?php

include('Bencode.php');
include('Admin.php');
include('vendor/autoload.php');

$cjdfile="./.cjdnsadmin";
$admindata = new cjdnsadmin($cjdfile);
$cjdnsadmin = $admindata;

// $loop = React\EventLoop\Factory::create();
// $socket = new React\Socket\Server($loop);
/*
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
$connector = new React\SocketClient\Connector($loop, $dns);

*/

$loop = React\EventLoop\Factory::create();
$factory = new React\Datagram\Factory($loop);
$Bencode = new Bencode;

$api = (array) [

	'txid' => function() {
		return strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=5, $cstrong)));
	},

    'qPing' => function($txid) use ($Bencode) {
    	$r = $Bencode->encode([ 'q'=> 'ping', 'txid'=> $txid ]);
    	return($r);
    },

    'qCookie' => function($txid) use ($Bencode) {
    	$r = $Bencode->encode([ 'q'=> 'cookie', 'txid'=> $txid ]);
    	return($r);
    },

    'qAdmin_availableFunctions' => function($txid) use ($Bencode) {
    	$r = $Bencode->encode([ 'q'=> 'Admin_availableFunctions', 'txid'=> $txid ]);
    	return($r);
    },

    'qInterfaceController_peerStats' => function($txid) use ($Bencode) {
    	$r = $Bencode->encode([ 'q'=> 'qInterfaceController_peerStats', 'txid'=> $txid ]);
    	return($r);
    },


];


$BCode = function($message=false) use ($Bencode) {

	// echo 'RECV: "' . $message . PHP_EOL;
	if ( (!$message) || (!is_array($message)) || (!isset($message[0]))) {
		throw new Exception("Error Processing message: FALSE or not Array()", 1);
	}

	/* variables */
	$decoded = ($Bencode->decode($message[0], 'TYPE_ARRAY'));
	$quotant = (isset($decoded['q'])) ? $decoded['q'] : false;

	/* ordered list */
	$cookie  = (isset($decoded['cookie'])) ? $decoded['cookie'] : false;
	$pong 	 = ($quotant == 'pong') ? true : false;

	return (object) [
		/* variables */
		'decoded'=> $decoded,
		'quotant'=> $quotant,
		/* ordered list */
		'cookie'=> 	$cookie,
		'pong'=> 	$pong,
	];


};


$factory->createClient('localhost:11234')->then(
  function (React\Datagram\Socket $client) use ($api, $Bencode, $BCode, $cjdnsadmin) {

	/* Generates Transaction Identifier */
	$txid = $api['txid']();
	$qA_AF = $api['qAdmin_availableFunctions'];
	$client->send($qA_AF($txid));

	/* Send qAdmin_availableFunctions */

	/* Send ping request to cjdns API */
	$txid = $api['txid']();
	$qPing = $api['qPing'];
	$client->send($qPing($txid));

	/* Send cookie request to cjdns API */
	$txid = $api['txid']();
	$qCookie = $api['qCookie'];
	$client->send($qCookie($txid));

	/* Perform cookie helo, call admin with password */
	// $hash = hash('sha256', $cjdnsadmin->password.$qCookie);
	$enable='adminCall';
	/* Send cookie request to cjdns API */
	$txid = $api['txid']();
	$qCookie = $api['qInterfaceController_peerStats'];
	$client->send($qCookie($txid));

	// $request['hash'] = hash("sha256", $requestBencoded);
    $client->on('message', function($message, $serverAddress, $client, $enable='priv') use ($Bencode, $BCode)
    {
		try {
			// print_r($BCode([$message]));
			// return $BCode([$message]);
			$tried = $BCode([$message]);
			echo "\n I tried to decode and got: \n";
			print_r($tried);

			/*********************/
		} catch (Exception $e) {
			echo ('Doh! ->');
			print($e->getMessage() . PHP_EOL);
		}
    });




});

echo PHP_EOL;
echo "*!* Starting..";
echo PHP_EOL;

$loop->run();

?>