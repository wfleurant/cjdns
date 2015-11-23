<?php namespace Cjdns\Toolshed;

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

}

