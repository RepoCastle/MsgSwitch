package cn.hjmao.msgswitch.utils;

public class Constant {

	public enum Vendor {
		CM, // china mobile
		CU, // china unicom
		CT, // china telecom
	}
	
	public static class ERRCODE {
		public static int SENDER_NOT_CORRECT = -1;
		public static int RECEIVER_NOT_CORRECT = -2;
		public static int VENDOR_NOT_SUPPORT = -3;
		public static int CONTENT_IS_NULL = -4;
		
		public static int SEND_MAIL_FAIL = -21;
	}
}
