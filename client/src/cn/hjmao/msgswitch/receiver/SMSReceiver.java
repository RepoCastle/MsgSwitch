package cn.hjmao.msgswitch.receiver;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.telephony.SmsMessage;
import android.util.Log;
import cn.hjmao.msgswitch.utils.SMSModifier;

public class SMSReceiver extends BroadcastReceiver {

	@Override
	public void onReceive(Context context, Intent intent) {
		Log.v("TAG", "onReceive");

		Object[] pdus = (Object[]) intent.getExtras().get("pdus");
		if (pdus != null && pdus.length > 0) {
			SmsMessage[] messages = new SmsMessage[pdus.length];
			for (int i = 0; i < pdus.length; i++) {
				byte[] pdu = (byte[]) pdus[i];
				messages[i] = SmsMessage.createFromPdu(pdu);
			}

			String content = "";
			for (SmsMessage message : messages) {
				content += message.getMessageBody();
			}

			if (messages.length > 0) {
				String sender = messages[0].getOriginatingAddress();
				if (sender.matches("^10658138.*")) {
					// FIXME: process it
					String origSender = SMSModifier.getSenderFromContent(content);
					SMSModifier.smsInsert(context.getContentResolver(), origSender, content);
//					this.abortBroadcast();
//					
//					Toast.makeText(context, content, Toast.LENGTH_LONG).show();
				}
			}
		}
	}
}
