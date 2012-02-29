package cn.hjmao.msgswitch.utils;

import android.content.ContentResolver;
import android.content.ContentValues;
import android.net.Uri;

public class SMSModifier {
	public static Uri SMSINBOX_URI = Uri.parse("content://sms/inbox");
	

	public static Uri smsInsert(ContentResolver contentResolver,
			String newSender, String content) {
		Uri uri = null;
		try {
			ContentValues values = SMSModifier.smsContentConstructor(content,
					newSender);
			uri = contentResolver.insert(SMSModifier.SMSINBOX_URI, values);
		} catch (Exception e) {
			e.printStackTrace();
		}
		return uri;
	}

	private static ContentValues smsContentConstructor(String content,
			String sender) {
		ContentValues values = new ContentValues();
		try {
			values.put("address", sender);
			values.put("read", 0);
			values.put("status", -1);
			values.put("type", 1);
			values.put("body", content);
		} catch (Exception e) {
			e.printStackTrace();
		}
		return values;
	}

	public static int smsDelete(ContentResolver contentResolver, long threadID, long smsID) {
		Uri uri = Uri.parse("content://sms/conversations/" + threadID);
		int number = contentResolver.delete(uri, "_id = " + smsID, null);
		return number;
	}
}
