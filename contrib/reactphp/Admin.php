<?php

Class cjdnsadmin {

	public function __construct($file=false) {
		// $c = [ 'password', 'config', 'addr', 'port' ];
		$data = json_decode(file_get_contents($file), true);
		$this->password = $data['password'];
		$this->config = $data['config'];
		$this->addr = $data['addr'];
		$this->port = $data['port'];
		unset($data);
	}

}
