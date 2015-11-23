<?php namespace Cjdns\Config;

use Cjdns\Admin\Socket;

class Admin {

	public function __construct($cfg=false) {

        $setparams = function($data) {
            $this->addr     = $data['addr'];
            $this->password = $data['password'];
            $this->port     = $data['port'];
        };

        if ($cfg) {
            if (isset($cfg['cfgfile'])) {
                $this->cfgfile = $cfg['cfgfile'];
                $cfg = json_decode(file_get_contents($this->cfgfile), true);
            }
        } else {
            /* TODO: import connection details from ../../cjdroute.conf */
            throw new \Exception("Error Processing Cjdns\Admin\Socket Request", 1);
        }

        $setparams($cfg);

	}

}
