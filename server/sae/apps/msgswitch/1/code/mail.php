<?php

class Sender {
    private $username = 'msgswitch@gmail.com';
    private $password = 'hctiwsgsm';

    public function mail($sender, $vendor, $receiver, $content) {
        $mail = new SaeMail();
        $toaddr = $receiver;
        if ($vendor == "CM") {
            $toaddr .= "@139.com";
	}
        $subject = "[msgs]" . $sender . "[/msgs]";
        $ret = $mail->quickSend( $toaddr , $subject , $content , $this->username , $this->password );

        if ($ret === false)
            var_dump($mail->errno(), $mail->errmsg());
 
        $mail->clean();
    }
}
?>
