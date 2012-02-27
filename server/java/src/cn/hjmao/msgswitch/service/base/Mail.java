package cn.hjmao.msgswitch.service.base;

import org.apache.commons.mail.DefaultAuthenticator;
import org.apache.commons.mail.EmailException;
import org.apache.commons.mail.SimpleEmail;

import cn.hjmao.msgswitch.utils.Constant;
import cn.hjmao.msgswitch.utils.Constant.Vendor;

public class Mail {

	public static int send(String sender, Vendor recvVendor, String recvNumber,
			String content) {
		int retCode = 0;
		SimpleEmail email = new SimpleEmail();
		email.setSSL(true); 
		email.setTLS(true); 
		email.setHostName("smtp.gmail.com");
		email.setAuthenticator(new DefaultAuthenticator("msgswitch@gmail.com", "hctiwsgsm"));
		try {
			email.addTo(recvNumber + "@139.com");
			email.setFrom("msgswitch@gmail.com");
			email.setSubject("msgs.--" + sender + "--");
			email.setMsg("To:--" + recvVendor + recvNumber + "\n" + content);
			email.send();
		} catch (EmailException e) {
			e.printStackTrace();
			retCode = Constant.ERRCODE.SEND_MAIL_FAIL;
		}
		return retCode;
	}

	public static void main(String[] args) {
		int retCode = Mail.send("13487577466", Vendor.CM, "13487577466",
				"This is a test mail by MsgSwitch");
		if (retCode == Constant.ERRCODE.SEND_MAIL_FAIL) {
			System.out.println("send mail failed!");
		} else {
			System.out.println("mail successfully send!");
		}
	}
}
