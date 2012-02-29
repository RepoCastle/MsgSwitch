package cn.hjmao.msgswitch.service.gateway;

import inc.thrift.FWException;
import inc.thrift.MsgSwitchService;

import org.apache.thrift.TException;

import cn.hjmao.msgswitch.service.ThriftServiceBase;
import cn.hjmao.msgswitch.service.base.Mail;
import cn.hjmao.msgswitch.utils.Constant;
import cn.hjmao.msgswitch.utils.Constant.Vendor;
import cn.hjmao.msgswitch.utils.Logging;

public class MsgSwitch extends ThriftServiceBase implements
		MsgSwitchService.Iface {

	@Override
	public void test() throws FWException, TException {
		Logging.info("MsgSwitch::test ...");
	}

	public int sendsms(String sender, String recvVendorCode, String recvNumber,
			String content) throws FWException, TException {
		Logging.info("MsgSwitch::sendsms to " + recvNumber + " with content of: [" + content + "]");
		int retCode = 0;

		if (sender == null || sender == "") {
			retCode = Constant.ERRCODE.SENDER_NOT_CORRECT;
		}
		if (recvNumber == null || recvNumber.length() != 11) {
			retCode = Constant.ERRCODE.RECEIVER_NOT_CORRECT;
		}
		Vendor vendor = null;
		try {
			vendor = Vendor.valueOf(recvVendorCode);
		} catch (Exception e) {
			retCode = Constant.ERRCODE.RECEIVER_NOT_CORRECT;
		}
		if (content == null || content == "") {
			retCode = Constant.ERRCODE.CONTENT_IS_NULL;
		}

		if (retCode != 0) {
			return retCode;
		}

		retCode = Mail.send(sender, vendor, recvNumber, content);

		return retCode;
	}
}
