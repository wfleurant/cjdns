<?php namespace Cjdns\Admin;

use Cjdns\Bencode\Bencode;
use Cjdns\Toolshed\Toolshed;
use Cjdns\Config\Admin;

use React\EventLoop\Factory;
use React\Datagram\Exception;
/*use React\Promise\Deferred;*/


class Socket {

    protected $addr, $cfgfile, $config, $password, $port;
    protected $server;

    function __construct(Admin $cfg) {
        $this->server = $cfg->addr . ':' . $cfg->port;
    }

    public function put($method=null) {
        echo Toolshed::logger('Socket->put('.$method.')');

        $txid = Bencode::decode($method)['txid'];
        $this->txid = Bencode::decode($method)['txid'];

        $server = $this->server;
        /* ReactPHP */
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);

        /* $deferred = new Deferred; */

        $factory->createClient($server)
                ->then(function (\React\Datagram\Socket $client) use ($method, $server)
        {
            echo Toolshed::logger('Socket->createClient('.$server.')');
            $client->send($method);

            $client->on('message', function($message, $serverAddress, $client) use ($method)
            {
                try {
                    $this->message = $message;
                    echo Toolshed::logger('Socket->onMessage('.$message.')');
                } catch (Exception $e) {
                    echo Toolshed::logger('Error: Socket->onMessage('.$e->getMessage().')');
                }

                $client->end();

            });


            /*
            $client->on('error', function ($err) use ($deferred)
            {
                $deferred->reject($err);
            });
            */

            /* throw exception if deffered is rejected */
            /* $deferred->done(); */



        },function($error) {
            echo Toolshed::logger('Error: Socket->createClient('.$error->getMessage().')');
        });

        $loop->run();

    }

}


?>