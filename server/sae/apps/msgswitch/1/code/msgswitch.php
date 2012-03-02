<?php
require_once 'mail.php';
require_once 'vendor.php';

try {
    $ctl = $_REQUEST['CTL'];

    $vendor = new Vendor();
    if ($ctl == '102') {
        $sender = $_REQUEST['SENDER'];
        $receiver = $_REQUEST['RECEIVER'];
        $vendorCode = $vendor->parse($receiver);
        $content = $_REQUEST['CONTENT'];

        $mailSender = new Sender();
        echo $mailSender->mail($sender, $vendorCode, $receiver, $content);
    } else if ($ctl = '103') {
        $sender = $_REQUEST['SENDER'];
        $receiver = $_REQUEST['RECEIVER'];
        $vendorCode = $vendor->parse($receiver);
        $content = $_REQUEST['CONTENT'];

        echo $sender;
        echo $vendor->parse($receiver) . "\n";
        echo $receiver . "\n";
        echo $content . "\n";
    } else {
        echo "Wrong CTL";
    }
} catch (Exception $e) {
    echo $e;
}
?>
