package cn.hjmao.msgswitch.utils;

import java.util.HashSet;
import java.util.Set;

public class Constant {

	@SuppressWarnings("serial")
	public static class VendorNum {
		public static final Set<String> CMPREFIX = new HashSet<String>() {{
				add("134");
				add("135");
				add("136");
				add("137");
				add("138");
				add("139");
				add("150");
				add("151");
				add("152");
				add("157");
				add("158");
				add("159");
				add("188");
		}};
		public static final Set<String> CUPREFIX = new HashSet<String>() {{
			add("130");
			add("131");
			add("132");
			add("155");
			add("156");
			add("185");
			add("186");
		}};
		public static final Set<String> CTPREFIX = new HashSet<String>() {{
			add("133");
			add("153");
			add("180");
			add("189");
		}};
	}
	
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
