<?php
require("config.php");
?>
<html>
<head>
<title>Bittorium Masternode</title>
</head>
<body>
<div style="float:left;"><img src="images/logo.png" width="32px"></div>
<div style="font-size: 25px;">Bittorium Masternode</div>
<div style="clear:left;"></div>
<table>
<tr><td>Daemon address:</td><td><?php echo $daemonHost . ":" . $daemonPort; ?></td></tr>
<tr><td>Status:</td><td>
<?php
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://" . $daemonHost . ":" . $daemonPort . "/feeaddress",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);
$response = json_decode($response);
if ($response->fee_address != "") {
  echo "OK";
} else {
  echo "ERROR: " . $err;
}
?>
</td></tr>
<tr><td>Collected fees:</td><td>
<?php
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://" . $walletHost . ":" . $walletPort . "/json_rpc",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $balancePayload,
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);
$response = json_decode($response);
if ($response->result->availableBalance) {
  echo intval($response->result->availableBalance) / 100;
  echo " BTOR";
} else {
echo "0.00 BTOR";
}
?>
</td></tr>
</table>
<hr />
<div>&copy; 2018 Bittorium Project</div>
</body>
