package cn.hjmao.msgswitch;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;

public class SMSEditor extends Activity {
	private static final String TAG = "SMSEditor";

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		
		final Intent intent = getIntent();
		final String action = intent.getAction();
		
		
		setContentView(R.layout.smseditor);
	}
}
