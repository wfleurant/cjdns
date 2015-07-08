<?php

require('AdminSocket.php');

$Admin = new aConn();

/* Send cookie request to cjdns API */
$txid = $Admin->api['txid']();
$qCookie = $Admin->api['InterfaceController_peerStats'];
$Admin->aCall($qCookie($txid));

/* Send Admin_availableFunctions */
$txid = $Admin->api['txid']();
$qCookie = $Admin->api['Admin_availableFunctions'];
$Admin->aCall($qCookie($txid));

/* Send ping request to cjdns API */
$txid = $Admin->api['txid']();
$qPing = $Admin->api['Ping'];
$Admin->aCall($qPing($txid));

/* Perform cookie helo, call admin with password */
$txid = $Admin->api['txid']();
$qCookie = $Admin->api['Cookie'];
$Admin->aCall($qCookie($txid));

// $hash = hash('sha256', $cjdnsadmin->password.$qCookie);

