<?php

class Sender {
    private $username = 'msgswitch@gmail.com';
    private $password = 'hctiwsgsm';

    public function mail($sender, $vendor, $receiver, $content) {
        $retCode = 0;

        $mail = new SaeMail();
        $toaddr = $receiver;

        if ($vendor == "CM") {
            $toaddr .= "@139.com";
	} else if ($vendor == "CU") {
            $toaddr .= "@wo.com.cn";
        } else if ($vendor == "CT") {
            $toaddr .= "@189.cn";
        } else {
            echo "VENDOR_NOT_SUPPORT";
            return -1;
        }
        $subject = "[msgs]" . $sender . "[/msgs]";
        $ret = $mail->quickSend( $toaddr , $subject , $content , $this->username , $this->password );

        if ($ret === false) {
            var_dump($mail->errno(), $mail->errmsg());
            $retCode = -1;
        }
        $mail->clean();
        return $retCode;
    }
}
?>
