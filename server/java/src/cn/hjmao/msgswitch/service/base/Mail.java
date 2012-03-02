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
			String toAddr = recvNumber;
			if (recvVendor.equals(Vendor.CM)) {
				toAddr += "@139.com";
			} else if (recvVendor.equals(Vendor.CT)) {
				toAddr += "@189.cn";
			} else if (recvVendor.equals(Vendor.CU)) {
				toAddr += "@wo.com.cn";
			} else {
				retCode = Constant.ERRCODE.VENDOR_NOT_SUPPORT;
				return retCode;
			}
			System.out.println("Will send mail to " + toAddr);
			email.addTo(toAddr);
			email.setFrom("msgswitch@gmail.com");
			email.setSubject("[msgs]" + sender + "[/msgs]");
			email.setMsg(content);
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
