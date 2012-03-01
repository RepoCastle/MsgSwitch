<?php
require_once 'mail.php';

try {
    $ctl = $_REQUEST['CTL'];

    if ($ctl == '102') {
        $sender = $_REQUEST['SENDER'];
        $receiver = $_REQUEST['RECEIVER'];
        $vendor = $_REQUEST['VENDOR'];
        $content = $_REQUEST['CONTENT'];

        $mailSender = new Sender();
        $mailSender->mail($sender, $vendor, $receiver, $content);
        echo $sender;
        echo $vendor . "\n";
        echo $receiver . "\n";
        echo $content . "\n";
    } else {
        echo "Wrong CTL";
    }
} catch (Exception $e) {
    echo $e;
}
?>
