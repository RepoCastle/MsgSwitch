package cn.hjmao.msgswitch.utils;

import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import cn.hjmao.msgswitch.R;

public class Notify {
	public static void statusBar(Context context, String content) {
		NotificationManager notifMng = (NotificationManager) context
				.getSystemService(Context.NOTIFICATION_SERVICE);
		Notification notif = new Notification();
		notif.icon = android.R.drawable.stat_notify_chat;
		notif.tickerText = context.getText(R.string.notif_tickertext);
		notif.defaults = Notification.DEFAULT_SOUND;
		notif.flags |= Notification.FLAG_AUTO_CANCEL;
		notif.flags |= Notification.DEFAULT_SOUND;
		Intent intent = new Intent(Intent.ACTION_VIEW);
		intent.setType("vnd.android-dir/mms-sms");
		PendingIntent pIntent = PendingIntent.getBroadcast(context, 0, intent,0);
		notif.setLatestEventInfo(context,
				context.getText(R.string.notif_contenttitle),
				context.getText(R.string.notif_contenttext), pIntent);
		notifMng.notify(0, notif);
	}
}
