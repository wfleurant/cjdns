<?php

include('vendor/autoload.php');
include('Admin.php');
// use \Rych\Bencode\Bencode;
include('Bencode.php');

class api {

	public $api;
	public $BCode;
	public $Bencode;

	function __construct() {
		$this->Bencode = new Bencode;

		$this->BCode = function($message=false) {

			if ( (!$message) || (!is_array($message)) || (!isset($message[0]))) {
				throw new Exception("Error Processing message: FALSE or not Array()", 1);
			}

			/* variables */
			$decoded = ($this->Bencode->decode($message[0], 'TYPE_ARRAY'));
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

		$this->api = (array) [

			'txid' => function() {
				return strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=10, $cstrong)));
			},

		    'Ping' => function($txid) {
		    	$r = $this->Bencode->encode([ 'q'=> 'ping', 'txid'=> $txid ]);
		    	return($r);
		    },

		    'Cookie' => function($txid) {
		    	$r = $this->Bencode->encode([ 'q'=> 'cookie', 'txid'=> $txid ]);
		    	return($r);
		    },

		    'Admin_availableFunctions' => function($txid) {
		    	$r = $this->Bencode->encode([ 'q'=> 'Admin_availableFunctions', 'txid'=> $txid ]);
		    	return($r);
		    },

		    'InterfaceController_peerStats' => function($txid) {
		    	$r = $this->Bencode->encode([ 'q'=> 'InterfaceController_peerStats', 'txid'=> $txid ]);
		    	return($r);
		    },


		];
	}
}

class aConn extends api {

	public function __construct() {

        parent::__construct();
		$api = &$this->api;
		$BCode = &$this->BCode;
		$Bencode = &$this->Bencode;

		$cjdfile = "./.cjdnsadmin";
		$admindata = new cjdnsadmin($cjdfile);
		$this->cjdnsadmin = $admindata;

	}

	public function aCall($method=false) {

		$api = $this->api;
		$cjdnsadmin = $this->cjdnsadmin;
		$Bencode = $this->Bencode;
		$BCode = $this->BCode;
		$server = $this->cjdnsadmin->addr . ':' . $this->cjdnsadmin->port;

		/* ReactPHP */
		$loop = React\EventLoop\Factory::create();
		$factory = new React\Datagram\Factory($loop);


		$factory->createClient($server)->then(
		  function (React\Datagram\Socket $client) use ($api, $cjdnsadmin, $Bencode, $BCode, $method) {
		  	$client->send($method);

		    $client->on('message', function($message, $serverAddress, $client)
			  use ($Bencode, $BCode, $method) {

				try {

					print_r($BCode([$message]));

				} catch (Exception $e) {

					print($e->getMessage() . PHP_EOL);
				}

	            $client->end();
		    });

		});

		$loop->run();

	}
}


?>