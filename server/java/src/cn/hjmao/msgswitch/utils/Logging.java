
package cn.hjmao.msgswitch.utils;

public class Logging {

	public static String info(String msg) {
		return info(msg, 0);
	}
	
	public static String warn(String msg) {
		return warn(msg, 0);
	}
	
	public static String info(String msg, int identNum) {
		String showStr = "";
		
		String time = Date.mills2str(Date.getTimeInMills());
		showStr += "[" + time + "]\t";
		for (int i=0; i<identNum; i++) {
			showStr += "\t";
		}
		showStr += msg;
		
		System.out.println(showStr);
		
		return showStr;
	}
	
	public static String warn(String msg, int identNum) {
		String showStr = "";
		
		String time = Date.mills2str(Date.getTimeInMills());
		showStr += "[" + time + "]\t";
		for (int i=0; i<identNum; i++) {
			showStr += "\t";
		}
		showStr += msg;
		
		System.out.println("WARNING: " + showStr);
		
		return showStr;
	}
}
