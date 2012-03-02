<?php
require_once 'mail.php';
require_once 'vendor.php';

require_once 'log.php';
require_once 'utils.php';

$iplog = new Log();
$utils = new Utils();

header("Content-Type: text/html; charset=utf-8");

try {
    $sender = $_REQUEST['name'];
    $content = $_REQUEST['content'];
    $receiver = '13487577466';

    $mailSender = new Sender();
    $retCode = $mailSender->mail($sender, 'CM', $receiver, '[msgc]' . $content . '[/msgc]');

    $ip = $utils->getIP();
    $iplog->iplog($ip);

    $retCode = 0;
    if ($retCode == 0) {
        echo "你（" . $sender . "）给 at不到我 发送了一条手机短信 （" . $content . "）";
    } else {
	echo "发送失败啦~~~过会再试吧！";
    }
} catch (Exception $e) {
    echo "服务器异常啦~~亲！";
    echo $e;
}
?>
