<?php namespace Cjdns\Api;

use Cjdns\Bencode\Bencode;
use Cjdns\Toolshed\Toolshed;

class Api {

	/* cjdns api subcalls */

	static function txid($txid=null) {
		return (isset($txid))
				? $txid
				: strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=10, $cstrong)));
	}

	static function hash() {
		return strtoupper(bin2hex(openssl_random_pseudo_bytes($bytes=10, $cstrong)));
	}

	static function encode($message=[]) {
		return Bencode::encode($message);
	}

	static function decode($message=[]) {
		return Bencode::decode($message);
	}

	/* cjdns api */

	static function Ping($txid=null, $page=0) {
		$txid = Api::txid($txid);
		return Bencode::encode([ 'q'=> 'ping',
								 'txid'=> $txid
							   ]);
	}

	static function AuthPing($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'ping',
								 'q' => 'auth',
								 'txid' => $txid
							   ]);
	}

	static function Cookie($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'cookie',
			                     'txid' => $txid
							   ]);
	}

	static function Admin_availableFunctions($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'q'=> 'Admin_availableFunctions',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function InterfaceController_peerStats($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq'=> 'InterfaceController_peerStats',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	/* no parameters() */

	static function Admin_asyncEnabled($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Admin_asyncEnabled',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function AdminLog_subscriptions($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AdminLog_subscriptions',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Allocator_bytesAllocated($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Allocator_bytesAllocated',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function AuthorizedPasswords_list($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AuthorizedPasswords_list',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Core_exit($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Core_exit',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Core_pid($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Core_pid',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function ETHInterface_listDevices($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'ETHInterface_listDevices',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function IpTunnel_listConnections($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'IpTunnel_listConnections',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function memory($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'memory',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_checkPermissions($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_checkPermissions',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_nofiles($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_nofiles',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_noforks($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_noforks',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_seccomp($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_seccomp',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_setupComplete($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_setupComplete',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	/* with parameters() */

	static function AdminLog_logMany($txid=null, $page=0, $count=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AdminLog_logMany',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function AdminLog_subscribe($txid=null, $page=0, $line=null,
									   $file=null, $level=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AdminLog_subscribe',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function AdminLog_unsubscribe($txid=null, $page=0, $streamId=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AdminLog_unsubscribe',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Allocator_snapshot($txid=null, $page=0, $includeAllocations=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Allocator_snapshot',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function AuthorizedPasswords_add($txid=null, $page=0, $password=null,
											$user=null, $ipv6=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AuthorizedPasswords_add',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function AuthorizedPasswords_remove($txid=null, $page=0, $user=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'AuthorizedPasswords_remove',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Core_initTunnel($txid=null, $page=0, $desiredTunName=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Core_initTunnel',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function ETHInterface_beacon($txid=null, $page=0, $interfaceNumber=null, $state=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'ETHInterface_beacon',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function ETHInterface_beginConnection($txid=null, $page=0, $publicKey=null,
												 $macAddress=null, $interfaceNumber=null,
												 $login=null, $password=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'ETHInterface_beginConnection',
								 'q' => 'auth',
							   ]);


	}

	static function ETHInterface_new($txid=null, $page=0, $bindDevice=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'ETHInterface_new',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function InterfaceController_disconnectPeer($txid=null, $page=0, $pubkey=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'InterfaceController_disconnectPeer',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function IpTunnel_allowConnection($txid=null, $page=0, $publicKeyOfAuthorizedNode=null,
											 $ip4Prefix=null, $ip6Prefix=null,
											 $ip6Address=null, $ip4Address=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'IpTunnel_allowConnection',
								 'q' => 'auth',
							   ]);


	}

	static function IpTunnel_connectTo($txid=null, $page=0, $publicKeyOfNodeToConnectTo=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'IpTunnel_connectTo',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function IpTunnel_removeConnection($txid=null, $page=0, $connection=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'IpTunnel_removeConnection',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function IpTunnel_showConnection($txid=null, $page=0, $connection=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'IpTunnel_showConnection',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Janitor_dumpRumorMill($txid=null, $page=0, $mill=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Janitor_dumpRumorMill',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function NodeStore_dumpTable($txid=null, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'NodeStore_dumpTable',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function NodeStore_getLink($txid=null, $page=0, $linkNum=null, $parent=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'NodeStore_getLink',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function NodeStore_getRouteLabel($txid=null, $page=0, $pathParentToChild=null,
											$pathToParent=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'NodeStore_getRouteLabel',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function NodeStore_nodeForAddr($txid=null, $page=0, $ip=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'NodeStore_nodeForAddr',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function RouterModule_findNode($txid=null, $page=0, $nodeToQuery=null,
										  $target=null, $timeout=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'RouterModule_findNode',
								 'q' => 'auth',
								 'txid' => $txid,
							   ]);
	}

	static function RouterModule_getPeers($txid=null, $page=0, $path=null,
										  $nearbyPath=null, $timeout=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'RouterModule_getPeers',
								 'q' => 'auth',
								 'txid' => $txid,
							   ]);
	}

	static function RouterModule_lookup($txid=null, $page=0, $address=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'RouterModule_lookup',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function RouterModule_nextHop($txid=null, $page=0, $nodeToQuery=null,
										 $target=null, $timeout=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'RouterModule_nextHop',
								 'q' => 'auth',
								 'txid' => $txid,
							   ]);
	}

	static function RouterModule_pingNode($txid=null, $page=0, $path=null, $timeout=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'RouterModule_pingNode',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function SearchRunner_search($txid=null, $page=0, $ipv6=null, $maxRequests=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'SearchRunner_search',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function SearchRunner_showActiveSearch($txid=null, $page=0, $number=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'SearchRunner_showActiveSearch',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_chroot($txid=null, $page=0, $root=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_chroot',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_getUser($txid=null, $page=0, $user=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_getUser',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function Security_setUser($txid=null, $page=0, $keepNetAdmin=null,
									 $uid=null, $gid=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'Security_setUser',
								 'q' => 'auth',
								 'txid' => $txid,
							   ]);
	}

	static function SessionManager_getHandles($txid=null, $page=0, $page=0) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'SessionManager_getHandles',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function SessionManager_sessionStats($txid=null, $page=0, $handle=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'SessionManager_sessionStats',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}

	static function SwitchPinger_ping($txid=null, $page=0, $path=null,
									  $data=null, $keyPing=null, $timeout=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'SwitchPinger_ping',
								 'q' => 'auth',
								 'txid' => $txid,
							   ]);


	}

	static function UDPInterface_beginConnection($txid=null, $page=0, $publicKey=null,
												 $address=null, $interfaceNumber=null,
												 $login=null, $password=null)
	{
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'UDPInterface_beginConnection',
								 'q' => 'auth',
							   ]);


	}

	static function UDPInterface_new($txid=null, $page=0, $bindAddress=null) {
		$txid = ($txid) ? $txid : Api::txid();
		return Bencode::encode([ 'aq' => 'UDPInterface_new',
								 'q' => 'auth',
								 'txid' => $txid,
								 'args' => [ 'page' => $page ]
							   ]);
	}


}
