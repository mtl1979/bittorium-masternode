<?php
header("Content-Type: application/json\n");

require("../config.php");
?>
{
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
  echo '"status" : "OK"';
} else {
  echo '"status" : "ERROR", ' . "\n";
  echo '"error" : "' . $err . '"';
}

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
  echo ",\n" . '"fees" : ' . intval($response->result->availableBalance) . "\n";
} else {
  echo "\n";
}
?>
}
