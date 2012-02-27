package cn.hjmao.msgswitch;

import android.app.Activity;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.EditText;
import cn.hjmao.msgswitch.utils.network.Mail;
import cn.hjmao.msgswitch.utils.network.Sender;

public class SMSEditor extends Activity {
	private static final String senderNum = "13487577466";
	private static final String TAG = "SMSEditor";
	private static final Sender sender = new Mail();

	private EditText mReceiverText;
	private EditText mContentText;
	@Override
	protected void onCreate(Bundle savedInstanceState) {
		Log.v(TAG, "onCreate");
		super.onCreate(savedInstanceState);
		
		setContentView(R.layout.smseditor);

		mReceiverText = (EditText) findViewById(R.id.receiver);
		mContentText = (EditText) findViewById(R.id.content);
	}

	@Override
	protected void onResume() {
		Log.v(TAG, "onResume");
		super.onResume();
	}

	@Override
	protected void onPause() {
		Log.v(TAG, "onPause");
		super.onPause();
	}
	
	public void onClickOk(View v) {
		String recvNumber = mReceiverText.getText().toString();
		String content = mContentText.getText().toString();
		Log.v(TAG, "Sending sms ...");
		//FIXME: It should be parsed from the number;
		String recvVendor = "CM";
		sender.send(senderNum, recvVendor, recvNumber, content);
		Log.v(TAG, "Done sending sms ...");
		finish();
	}
}
