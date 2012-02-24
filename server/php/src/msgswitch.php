<?php
require_once 'common.php';

echo '(';
try {
        $service=new ServiceHandle('MsgSwitchServiceClient');
        $client=$service->getClient();
        if (!array_key_exists('CTL', $_REQUEST)) {
        } elseif ($_REQUEST['CTL'] == '100') {

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

