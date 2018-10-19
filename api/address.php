<?php
header("Content-Type: application/json\n");

require("../config.php");
?>
{
"host": "<?php echo $daemonHost;?>",
"port": <?php echo $daemonPort . "\n";?>
}
