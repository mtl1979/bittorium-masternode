<?php
require("config.php");

function daemonrpc_get($path) {
  global $daemonHost, $daemonPort;
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "http://" . $daemonHost . ":" . $daemonPort . $path,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "cache-control: no-cache"
    )
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);
  $response = json_decode($response);
  return $response;
}

function walletrpc_post($method, $params = NULL) {
  global $walletHost, $walletPort, $walletPassword;
  if (is_null($params)) {
    $params = (Object) Array();
  }
  $curl = curl_init();
  $fields = array("jsonrpc" => "2.0",
                  "method" => $method,
                  "password" => $walletPassword,
                  "params" => $params,
                  "id" => "1");
  $fields = json_encode((object) $fields);
  curl_setopt_array($curl, array(
    CURLOPT_URL => "http://" . $walletHost . ":" . $walletPort . "/json_rpc",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $fields,
    CURLOPT_HTTPHEADER => array(
      "cache-control: no-cache"
    )
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);
  $response = json_decode($response);
  return $response->result;
}
$copyright = "<div>&copy; 2018 Bittorium Project</div>";

?>
<html>
<head>
<title>Bittorium Masternode</title>
<link rel="stylesheet" href="style.css" />
<link rel="stylesheet" href="admin.css" />
</head>
<body>
<div class="logo"><img src="images/logo.png" width="32px"></div>
<div class="banner">Bittorium Masternode</div>
<div class="clear-left"></div>
<?php
if(!isset($_COOKIE["admin_password"]) || $_COOKIE["admin_password"] != $walletPassword) {
  if($_POST["password"] != $walletPassword) {
    setcookie("admin_password", "", time() - 3600);
    echo "<form action='admin.php' method='post'>";
    echo "Password:";
    echo "<input name='password' type='password' />";
    echo "<input name='submit' type='submit' value='Login' />";
    echo "</form>";
    echo "<hr />";
    echo $copyright;
    echo "</body>";
    echo "</html>";
    exit();
  }
}
// Expire session 5 minutes after latest page load
setcookie("admin_password", $walletPassword, time() + 300);
$info = daemonrpc_get("/getinfo");
$height = $info->height;
echo "<table id='info'>";
echo "<tr><th>Blockchain height</th><td>" . $height . "</td></tr>";
$balance = walletrpc_post("getBalance");
$balance = $balance->availableBalance;
echo "<tr><th>Balance</th><td>" . $balance / 100 . " BTOR</td></tr>";
echo "<tr><th>Number of transactions</th><td>";
ob_start();
$getAddresses = walletrpc_post("getAddresses");
$addresses = $getAddresses->addresses;
echo "<tr><th rowspan=" . count($addresses) . " style='vertical-align:top;'>Wallet addresses</th><td>";
echo implode("</td></tr><tr><td>", $addresses);
echo "</td></tr>";
echo "</table>";
$txs_params = Array("addresses" => $addresses, "firstBlockIndex" => 0, "blockCount" => $height);
$txs = walletrpc_post("getTransactions", $txs_params);
$blocks = $txs->items;
echo "<h3>Transactions</h3>";
echo "<table id='transactions'>";
echo "<tr><th>Time</th><th>Amount</th></tr>";
$ntrans = 0;
$skip = is_numeric($_POST["skip"]) ? $_POST["skip"] : 0;
if ($skip < 0) {
  $skip = 0;
}
// List transactions in reverse order, from newest to oldest
$blocks = array_reverse($blocks);
foreach ($blocks as $block) {
  $transactions = array_reverse($block->transactions);
  foreach ($transactions as $transaction) {
    if ($transaction->amount > 0) {
      if ($ntrans >= $skip && $ntrans < $skip + 20) {
        echo "<tr>";
        echo "<td>" . date("D, d M y H:i:s", $transaction->timestamp) . "</td>";
        echo "<td class='amount'>" . number_format($transaction->amount / 100, 2) . "</td>";
        echo "</tr>";
      }
      $ntrans++;
    }
  }
}
echo "</table>";
echo "<table>";
echo "<tr>";
if ($skip > 0) {
  echo "<td><form action='admin.php' method='post'>";
  echo "<input name='skip' type='hidden' value='0' />";
  echo "<input name='submit' type='submit' value='First 20' />";
  echo "</form></td>";
  echo "<td><form action='admin.php' method='post'>";
  echo "<input name='skip' type='hidden' value='" . ($skip - 20) . "' />";
  echo "<input name='submit' type='submit' value='Previous 20' />";
  echo "</form></td>";
}
if ($ntrans > $skip + 20) {
  echo "<td><form action='admin.php' method='post'>";
  echo "<input name='skip' type='hidden' value='" . ($skip + 20) . "' />";
  echo "<input name='submit' type='submit' value='Next 20' />";
  echo "</form></td>";
  echo "<td><form action='admin.php' method='post'>";
  echo "<input name='skip' type='hidden' value='" . ($ntrans - 20) . "' />";
  echo "<input name='submit' type='submit' value='Last 20' />";
  echo "</form></td>";
}
echo "</tr>";
echo "</table>";
$buf = ob_get_contents();
ob_end_clean();
echo $ntrans . "</td></tr>";
echo $buf;
echo "<hr />";
echo $copyright;
?>
</body>
</html>
