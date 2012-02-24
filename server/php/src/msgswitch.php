<?php
require_once 'common.php';

echo '(';
try {
        $service=new ServiceHandle('MsgSwitchServiceClient');
        $client=$service->getClient();
        if (!array_key_exists('CTL', $_REQUEST)) {
        } elseif ($_REQUEST['CTL'] == '100') {
		$sender = $_REQUEST['SENDER']; 
		$vendor = $_REQUEST['RECVVENDOR'];
		$receiver = $_REQUEST['RECVNUMBER'];
		$content = $_REQUEST['CONTENT'];
		$client->sendsms($sender, $vendor, $receiver, $content);
	} elseif ($_REQUEST['CTL'] == '9999') {
		$client->test();
	} else {
                echo 'Error CTL';
        }
        $service->close();
} catch (FWException $fe) {
        echo $fe->errCode;
}
echo ')';
?>

