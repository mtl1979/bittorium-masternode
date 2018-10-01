<?php
$daemonHost = "127.0.0.1";
$daemonPort = 34916;
$walletHost = "127.0.0.1";
$walletPort = 8070;
$walletPassword = "masternode";
$balancePayload = '{"jsonrpc": "2.0", "method": "getBalance", "password": "' . $walletPassword . '", "params": {}, "id": "1"}';
?>
