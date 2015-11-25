<?php namespace Cjdns\Toolshed;

use Cjdns\Bencode\Bencode;

class Toolshed {

    static function logger($mesg=null) {
        if ($mesg) {
            echo Toolshed::logo() . $mesg . PHP_EOL;
        } else {
            return;
        }
    }

    static function logo() {
        return '[ᴄᴊᴅɴꜱ] ';
    }

    static function cleanresp(Array $array, $flatten=true, $droptxrx=true) {

        /* return a single array instead of many subarray / nested results  */
        if ($flatten) {
            $array = call_user_func_array('array_merge_recursive', $array);
        }

        /* drops multiple keys (txid, more) */
        if ($droptxrx) {
            unset($array['txid']);
            unset($array['more']);
        }

        return json_encode($array);

    }

    static function msgtrim($string) {
        return substr(json_encode(Bencode::decode($string)), 0, 50);
    }
}

