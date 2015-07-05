<?php

include('Bencode.php');
include('vendor/autoload.php');


// $loop = React\EventLoop\Factory::create();
// $socket = new React\Socket\Server($loop);
/*
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
$connector = new React\SocketClient\Connector($loop, $dns);

*/

$loop = React\EventLoop\Factory::create();
$factory = new React\Datagram\Factory($loop);
$bencode = new Bencode;

$api = (array) [

	'txid' => function() {
		return strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=5, $cstrong)));
	},

    'qPing' => function($txid) use ($bencode) {
    	$r = $bencode->encode([ 'q'=> 'ping', 'txid'=> $txid ]);
    	return($r);
    },

    'qCookie' => function($txid) use ($bencode) {
    	$r = $bencode->encode([ 'q'=> 'cookie', 'txid'=> $txid ]);
    	return($r);
    },

    'qAdmin_availableFunctions' => function($txid) use ($bencode) {
    	$r = $bencode->encode([ 'q'=> 'Admin_availableFunctions', 'txid'=> $txid ]);
    	return($r);
    },


];


// Admin_availableFunctions

$BCode = function($message=false) use ($bencode) {
	$decoded = $bencode->decode($message, 'TYPE_ARRAY');

	// echo 'RECV: "' . $message . PHP_EOL;
	if ( (!$message) || (!is_array($message)) ) {
		throw new Exception("Error Processing message: FALSE or not Array()", 1);
	}

	$cookie = (isset($decoded['cookie'])) ? $decoded['cookie'] : false;

	$quotant = (isset($decoded['q'])) ? $decoded['q'] : false;

	$pong = ($quotant == 'pong') ? true : false;

	return (object) [ $cookie, $quotant, $pong ];
};


$factory->createClient('localhost:11234')->then(
  function (React\Datagram\Socket $client) use ($api, $bencode, $BCdee, $un='') {

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


	/*
	$request = [
		'q'=> 		'auth',
		'aq'=> 		$function,
		'hash'=> 	hash('sha256', $this->password.$cookie),
		'cookie'=> 	$cookie,
		'args'=> 	$args,
		'txid'=> 	$txid
	];
	*/

	// $request['hash'] = hash("sha256", $requestBencoded);
    $client->on('message', function($message, $serverAddress, $client) use ($bencode, $BCdee)
    {

		try {
			// print_r($BCode['d0000006:cookie10:14023973204:txid10:C42129414Ae']));
			$tried = $try_to_decode([$message]);
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