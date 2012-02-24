
package cn.hjmao.msgswitch.utils;

import java.text.SimpleDateFormat;
import java.util.Calendar;

public class Date {
	private static String dateFmtStr = "yyyy-MM-dd HH:mm:ss";
	public static SimpleDateFormat dateFmt = new SimpleDateFormat(dateFmtStr);

	public static String getTime() {
		Calendar cal = Calendar.getInstance();
		return Date.dateFmt.format(cal.getTime());
	}

	public static long getTimeInMills() {
		Calendar cal = Calendar.getInstance();
		return cal.getTimeInMillis();
	}

	public static String mills2str(long mills) {
		String str = Date.dateFmt.format(new java.util.Date(mills));
		return str;
	}

	public static long str2mills(String str) throws Exception {
		long mills = Date.dateFmt.parse(str).getTime();
		return mills;
	}

	public static String mills2date(long mills) {
		String date = "";
		date = mills2str(mills).substring(0, dateFmtStr.indexOf("dd") + 2);
		return date;
	}

	public static String mills2time(long mills) {
		String time = "";
		time = mills2str(mills).substring(dateFmtStr.indexOf("HH"));
		return time;
	}
}
