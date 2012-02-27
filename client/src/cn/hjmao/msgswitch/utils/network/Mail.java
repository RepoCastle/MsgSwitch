package cn.hjmao.msgswitch.utils.network;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.URL;
import java.net.URLConnection;

import android.os.StrictMode;
import android.util.Log;

public class Mail implements Sender {
	private final static String TAG = "MsgSwitch.Utils.Mail";
	private final static String MSSRV_PREFIX = "http://143.89.191.30:9001/cgi-bin/msgswitch/msgswitch.php?";
	private final static int CTL_SEND = 100;
	@Override
	public int send(String sender, String recvVendor, String recvNumber, String content) {
		int retCode = 0;
		
		String url = MSSRV_PREFIX + "CTL=" + CTL_SEND + "&"
					 + "SENDER=" + sender + "&"
					 + "RECVVENDOR" + recvVendor + "&"
					 + "RECVNUMBER=" + recvNumber + "&"
					 + "CONTENT=" + content;
		Log.v(TAG, url);
		
		try {
			// FIXME:
			StrictMode.ThreadPolicy policy = new StrictMode.ThreadPolicy.Builder().permitAll().build();  
			StrictMode.setThreadPolicy(policy);
			
			URL msgSend = new URL(url);
			URLConnection msgc = msgSend.openConnection();
			BufferedReader in = new BufferedReader(new InputStreamReader(msgc.getInputStream()));
			String inputLine;
			String retCodeStr = "";
			while ((inputLine = in.readLine()) != null)
				retCodeStr += inputLine;
			in.close();
//			retCode = Integer.parseInt(retCodeStr);
		} catch (Exception e) {
			Log.v(TAG, "Error");
			e.printStackTrace();
		}
		return retCode;
	}
	
	public static void main(String[] args) {
		Mail mail = new Mail();
		mail.send("13487577466", "CM", "1234895", "hello world, are you ok");
	}
}