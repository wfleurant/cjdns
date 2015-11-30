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

            foreach ([ 'count', 'deprecation', 'peers' ] as $u) {
                if (isset($array[$u])) {
                    $array[$u] = array_unique($array[$u]);
                }
            }
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

    static function sqlite_column_fetch($database, $table = 'peerstats') {

        $table_info = $database->query("PRAGMA table_info($table)")->fetchAll();

        foreach ($table_info as $sqlite => $column) {
            $sqlcol[$column['name']] = true;
        }

        return $sqlcol;
    }


}

