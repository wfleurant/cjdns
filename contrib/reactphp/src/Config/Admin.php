<?php namespace Cjdns\Config;

use Cjdns\Admin\Socket;

class Admin {

	public function __construct($cfg=false) {

        $setparams = function($data) {
            $this->addr     = $data['addr'];
            $this->password = $data['password'];
            $this->port     = $data['port'];
        };

        if (isset($cfg['cfgfile'])) {
            $this->cfgfile = $cfg['cfgfile'];
            $cfg = json_decode(file_get_contents($this->cfgfile), true);
            try {
                if (!$cfg) {
                    throw new \Exception("Error Processing cjdns configuration "
                                        . "($this->cfgfile) Exception @ ", 1);
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        $setparams($cfg);

	}

}
