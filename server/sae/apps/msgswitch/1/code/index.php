<?php
require_once 'log.php';
require_once 'utils.php';

$iplog = new Log();
$utils = new Utils();

$ip = $utils->getIP();
echo "Your IP is " . $ip;
?>
