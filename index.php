<?php
require("config.php");
?>
<html>
<head>
<title>Bittorium Masternode</title>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="logo"><img src="images/logo.png" width="32px"></div>
<div class="banner">Bittorium Masternode</div>
<div class="clear-left"></div>
<table id="info">
<tr><th>Daemon address:</th><td><?php echo $daemonHost . ":" . $daemonPort; ?></td></tr>
<tr><th>Status:</th><td>
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
<tr><th>Collected fees:</th><td>
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
  echo number_format(intval($response->result->availableBalance) / 100, 2);
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
