package cn.hjmao.msgswitch.provider;

import java.util.HashMap;

import android.content.ContentProvider;
import android.content.ContentUris;
import android.content.ContentValues;
import android.content.Context;
import android.content.UriMatcher;
import android.database.Cursor;
import android.database.SQLException;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.database.sqlite.SQLiteQueryBuilder;
import android.net.Uri;
import android.provider.BaseColumns;
import android.text.TextUtils;
import android.util.Log;
import cn.hjmao.msgswitch.MsgSwitch;

public class MsgSwitchProvider  extends ContentProvider {

	private static final String TAG = "MsgSwitchProvider";
	private static final String DATABASE_NAME = "msgswitch.db";
	private static final int DATABASE_VERSION = 1;

	public static final class SMS implements BaseColumns {
		private SMS() {
		}
		
		public static final int DEFAULT_COLUMN_NAME_THREADID = 0;
		public static final String DEFAULT_COLUMN_NAME_ADDRESS = "10086";
		public static final int DEFAULT_COLUMN_NAME_PERSON = 0;
		public static final long DEFAULT_COLUMN_NAME_CREATE_DATE = 0;
		public static final long DEFAULT_COLUMN_NAME_SENT_DATE = 0;
		public static final int DEFAULT_COLUMN_NAME_PROTOCOL = 0;
		public static final int DEFAULT_COLUMN_NAME_READ = 0;
		public static final int DEFAULT_COLUMN_NAME_STATUS = -1;
		public static final int DEFAULT_COLUMN_NAME_TYPE = 0;
		public static final int DEFAULT_COLUMN_NAME_REPLY_PATH_PRESENT = 0;
		public static final String DEFAULT_COLUMN_NAME_SUBJECT = "subject";	
		public static final String DEFAULT_COLUMN_NAME_BODY = "body";	
		public static final String DEFAULT_COLUMN_NAME_SERVICE_CENTER = "service_center";	
		public static final int DEFAULT_COLUMN_NAME_LOCKED = 0;	
		public static final int DEFAULT_COLUMN_NAME_ERROR_CODE = 0;
		public static final int DEFAULT_COLUMN_NAME_SEEN = 0;
		
		public static final String COLUMN_NAME_THREADID = "thread_id";
		public static final String COLUMN_NAME_ADDRESS = "address";
		public static final String COLUMN_NAME_PERSON = "person";
		public static final String COLUMN_NAME_CREATE_DATE = "date";
		public static final String COLUMN_NAME_SENT_DATE = "date_sent";
		public static final String COLUMN_NAME_PROTOCOL = "protocol";
		public static final String COLUMN_NAME_READ = "read";
		public static final String COLUMN_NAME_STATUS = "status";
		public static final String COLUMN_NAME_TYPE = "type";
		public static final String COLUMN_NAME_REPLY_PATH_PRESENT = "reply_path_present";
		public static final String COLUMN_NAME_SUBJECT = "subject";	
		public static final String COLUMN_NAME_BODY = "body";	
		public static final String COLUMN_NAME_SERVICE_CENTER = "service_center";	
		public static final String COLUMN_NAME_LOCKED = "locked";	
		public static final String COLUMN_NAME_ERROR_CODE = "error_code";
		public static final String COLUMN_NAME_SEEN = "seen";
		
		
		public static final String COLUMN_NAME_MODIFICATION_DATE = "modified";
		public static final String DEFAULT_SORT_ORDER = COLUMN_NAME_SENT_DATE + " DESC";

		public static final String TABLE_NAME = "sms";
		private static final String SCHEME = "content://";
		private static final String PATH_SMS = "/sms";
		private static final String PATH_SMS_ID = "/sms/";
		public static final int SMS_ID_PATH_POSITION = 1;
		public static final Uri CONTENT_URI = Uri.parse(SCHEME + MsgSwitch.AUTHORITY + PATH_SMS);
		public static final Uri CONTENT_ID_URI_BASE = Uri.parse(SCHEME + MsgSwitch.AUTHORITY + PATH_SMS_ID);
		public static final Uri CONTENT_ID_URI_PATTERN = Uri.parse(SCHEME + MsgSwitch.AUTHORITY + PATH_SMS_ID + "/#");
		public static final String CONTENT_TYPE = "vnd.android.cursor.dir/vnd.msgswitch.sms";
		public static final String CONTENT_ITEM_TYPE = "vnd.android.cursor.item/vnd.msgswitch.sms";
	}

	private DatabaseHelper mOpenHelper;
	static class DatabaseHelper extends SQLiteOpenHelper {
		DatabaseHelper(Context context) {
			super(context, DATABASE_NAME, null, DATABASE_VERSION);
		}

		@Override
		public void onCreate(SQLiteDatabase db) {
			db.execSQL("CREATE TABLE " + SMS.TABLE_NAME + " ("
					+ SMS._ID + " INTEGER PRIMARY KEY,"
					+ SMS.COLUMN_NAME_THREADID + " INTEGER,"
					+ SMS.COLUMN_NAME_ADDRESS + " TEXT,"
					+ SMS.COLUMN_NAME_PERSON + " INTEGER,"
					+ SMS.COLUMN_NAME_CREATE_DATE + " INTEGER,"
					+ SMS.COLUMN_NAME_SENT_DATE + " INTEGER DEFAULT 0,"
					+ SMS.COLUMN_NAME_PROTOCOL + " INTEGER,"
					+ SMS.COLUMN_NAME_READ + " INTEGER DEFAULT 0,"
					+ SMS.COLUMN_NAME_STATUS + " INTEGER DEFAULT -1,"
					+ SMS.COLUMN_NAME_TYPE + " INTEGER,"
					+ SMS.COLUMN_NAME_REPLY_PATH_PRESENT + " INTEGER,"
					+ SMS.COLUMN_NAME_SUBJECT + " TEXT,"
					+ SMS.COLUMN_NAME_BODY + " TEXT,"
					+ SMS.COLUMN_NAME_SERVICE_CENTER + " TEXT,"
					+ SMS.COLUMN_NAME_LOCKED + " INTEGER DEFAULT 0,"
					+ SMS.COLUMN_NAME_ERROR_CODE + " INTEGER DEFAULT 0,"
					+ SMS.COLUMN_NAME_SEEN + " INTEGER DEFAULT 0);"
					);
		}
		
		@Override
		public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
			Log.w(TAG, "Upgrading database from version " + oldVersion + " to " + newVersion + ", which will destroy all old data");
			db.execSQL("DROP TABLE IF EXISTS " + SMS.TABLE_NAME);
			onCreate(db);
		}
	}


	private static final int SMSES = 1;
	private static final int SMS_ID = 2;
	private static final UriMatcher sUriMatcher;
	private static HashMap<String, String> sSMSProjectionMap;
	static {
		sUriMatcher = new UriMatcher(UriMatcher.NO_MATCH);
		sUriMatcher.addURI(MsgSwitch.AUTHORITY, "sms", SMSES);
		sUriMatcher.addURI(MsgSwitch.AUTHORITY, "sms/#", SMS_ID);
		sSMSProjectionMap = new HashMap<String, String>();
		sSMSProjectionMap.put(SMS._ID, SMS._ID);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_THREADID, SMS.COLUMN_NAME_THREADID);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_ADDRESS, SMS.COLUMN_NAME_ADDRESS);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_PERSON, SMS.COLUMN_NAME_PERSON);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_CREATE_DATE, SMS.COLUMN_NAME_CREATE_DATE);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_SENT_DATE, SMS.COLUMN_NAME_SENT_DATE);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_PROTOCOL, SMS.COLUMN_NAME_PROTOCOL);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_READ, SMS.COLUMN_NAME_READ);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_STATUS, SMS.COLUMN_NAME_STATUS);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_TYPE, SMS.COLUMN_NAME_TYPE);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_REPLY_PATH_PRESENT, SMS.COLUMN_NAME_REPLY_PATH_PRESENT);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_SUBJECT, SMS.COLUMN_NAME_SUBJECT);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_BODY, SMS.COLUMN_NAME_BODY);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_SERVICE_CENTER, SMS.COLUMN_NAME_SERVICE_CENTER);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_LOCKED, SMS.COLUMN_NAME_LOCKED);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_ERROR_CODE, SMS.COLUMN_NAME_ERROR_CODE);
		sSMSProjectionMap.put(SMS.COLUMN_NAME_SEEN, SMS.COLUMN_NAME_SEEN);
	}
	
	@Override
	public Uri insert(Uri uri, ContentValues initialValues) {
		if (sUriMatcher.match(uri) != SMSES) {
			throw new IllegalArgumentException("Unknown URI " + uri);
		}
		ContentValues values;
		if (initialValues != null) {
			values = new ContentValues(initialValues);
		} else {
			values = new ContentValues();
		}
		
		Long now = Long.valueOf(System.currentTimeMillis());
		if (values.containsKey(SMS.COLUMN_NAME_THREADID) == false) {
			values.put(SMS.COLUMN_NAME_THREADID, SMS.DEFAULT_COLUMN_NAME_THREADID);
		}
		if (values.containsKey(SMS.COLUMN_NAME_ADDRESS) == false) {
			values.put(SMS.COLUMN_NAME_ADDRESS, SMS.DEFAULT_COLUMN_NAME_ADDRESS);
		}
		if (values.containsKey(SMS.COLUMN_NAME_PERSON) == false) {
			values.put(SMS.COLUMN_NAME_PERSON, SMS.DEFAULT_COLUMN_NAME_PERSON);
		}
		if (values.containsKey(SMS.COLUMN_NAME_CREATE_DATE) == false) {
			values.put(SMS.COLUMN_NAME_CREATE_DATE, now);
		}
		if (values.containsKey(SMS.COLUMN_NAME_PROTOCOL) == false) {
			values.put(SMS.COLUMN_NAME_PROTOCOL, SMS.DEFAULT_COLUMN_NAME_PROTOCOL);
		}
		if (values.containsKey(SMS.COLUMN_NAME_TYPE) == false) {
			values.put(SMS.COLUMN_NAME_TYPE, SMS.DEFAULT_COLUMN_NAME_TYPE);
		}
		if (values.containsKey(SMS.COLUMN_NAME_REPLY_PATH_PRESENT) == false) {
			values.put(SMS.COLUMN_NAME_REPLY_PATH_PRESENT, SMS.DEFAULT_COLUMN_NAME_REPLY_PATH_PRESENT);
		}
		if (values.containsKey(SMS.COLUMN_NAME_SUBJECT) == false) {
			values.put(SMS.COLUMN_NAME_SUBJECT, SMS.DEFAULT_COLUMN_NAME_SUBJECT);
		}
		if (values.containsKey(SMS.COLUMN_NAME_BODY) == false) {
			values.put(SMS.COLUMN_NAME_BODY, SMS.DEFAULT_COLUMN_NAME_BODY);
		}
		if (values.containsKey(SMS.COLUMN_NAME_SERVICE_CENTER) == false) {
			values.put(SMS.COLUMN_NAME_SERVICE_CENTER, SMS.DEFAULT_COLUMN_NAME_SERVICE_CENTER);
		}
		if (values.containsKey(SMS.COLUMN_NAME_SENT_DATE) == false) {
			values.put(SMS.COLUMN_NAME_SENT_DATE, now);
		}
		if (values.containsKey(SMS.COLUMN_NAME_READ) == false) {
			values.put(SMS.COLUMN_NAME_READ, SMS.DEFAULT_COLUMN_NAME_READ);
		}
		if (values.containsKey(SMS.COLUMN_NAME_STATUS) == false) {
			values.put(SMS.COLUMN_NAME_STATUS, SMS.DEFAULT_COLUMN_NAME_STATUS);
		}
		if (values.containsKey(SMS.COLUMN_NAME_LOCKED) == false) {
			values.put(SMS.COLUMN_NAME_LOCKED, SMS.DEFAULT_COLUMN_NAME_LOCKED);
		}
		if (values.containsKey(SMS.COLUMN_NAME_ERROR_CODE) == false) {
			values.put(SMS.COLUMN_NAME_ERROR_CODE, SMS.DEFAULT_COLUMN_NAME_ERROR_CODE);
		}
		if (values.containsKey(SMS.COLUMN_NAME_SEEN) == false) {
			values.put(SMS.COLUMN_NAME_SEEN, SMS.DEFAULT_COLUMN_NAME_SEEN);
		}

		SQLiteDatabase db = mOpenHelper.getWritableDatabase();

		long rowId = db.insert(SMS.TABLE_NAME, null, values);

		if (rowId > 0) {
			Uri noteUri = ContentUris.withAppendedId(SMS.CONTENT_ID_URI_BASE, rowId);
			getContext().getContentResolver().notifyChange(noteUri, null);
			return noteUri;
		}

		throw new SQLException("Failed to insert row into " + uri);
	}

	@Override
	public int delete(Uri uri, String where, String[] whereArgs) {
		SQLiteDatabase db = mOpenHelper.getWritableDatabase();
		String finalWhere;

		int count;
		switch (sUriMatcher.match(uri)) {
		case SMSES:
			count = db.delete(SMS.TABLE_NAME, where, whereArgs);
			break;

		case SMS_ID:
			finalWhere = SMS._ID + " = " + uri.getPathSegments().get(SMS.SMS_ID_PATH_POSITION);
			if (where != null) {
				finalWhere = finalWhere + " AND " + where;
			}
			count = db.delete(SMS.TABLE_NAME, finalWhere, whereArgs);
			break;

		default:
			throw new IllegalArgumentException("Unknown URI " + uri);
		}

		getContext().getContentResolver().notifyChange(uri, null);

		return count;
	}
	
	@Override
	public int update(Uri uri, ContentValues values, String where,
			String[] whereArgs) {
		SQLiteDatabase db = mOpenHelper.getWritableDatabase();
		int count;
		String finalWhere;
		switch (sUriMatcher.match(uri)) {
		case SMSES:
			count = db.update(SMS.TABLE_NAME, values, where, whereArgs);
			break;
		case SMS_ID:
			String ruleId = uri.getPathSegments().get(SMS.SMS_ID_PATH_POSITION);
			finalWhere = SMS._ID + " = " + ruleId;
			if (where != null) {
				finalWhere = finalWhere + " AND " + where;
			}
			count = db.update(SMS.TABLE_NAME, values, finalWhere, whereArgs);
			break;
		default:
			throw new IllegalArgumentException("Unknown URI " + uri);
		}
		getContext().getContentResolver().notifyChange(uri, null);
		return count;
	}

	@Override
	public Cursor query(Uri uri, String[] projection, String selection,
			String[] selectionArgs, String sortOrder) {
		SQLiteQueryBuilder qb = new SQLiteQueryBuilder();
		qb.setTables(SMS.TABLE_NAME);
		switch (sUriMatcher.match(uri)) {
		case SMSES:
			qb.setProjectionMap(sSMSProjectionMap);
			break;
		case SMS_ID:
			qb.setProjectionMap(sSMSProjectionMap);
			qb.appendWhere(SMS._ID + "=" + uri.getPathSegments().get(SMS.SMS_ID_PATH_POSITION));
			break;
		default:
			throw new IllegalArgumentException("Unknown URI " + uri);
		}

		String orderBy;
		if (TextUtils.isEmpty(sortOrder)) {
			orderBy = SMS.DEFAULT_SORT_ORDER;
		} else {
			orderBy = sortOrder;
		}
		SQLiteDatabase db = mOpenHelper.getReadableDatabase();
		Cursor c = qb.query(db, projection, selection, selectionArgs, null, null, orderBy);
		c.setNotificationUri(getContext().getContentResolver(), uri);
		return c;
	}
	
	@Override
	public boolean onCreate() {
		mOpenHelper = new DatabaseHelper(getContext());
		return true;
	}

	@Override
	public String getType(Uri uri) {
		switch (sUriMatcher.match(uri)) {
		case SMSES:
			return SMS.CONTENT_TYPE;
		case SMS_ID:
			return SMS.CONTENT_ITEM_TYPE;
		default:
			throw new IllegalArgumentException("Unknown URI " + uri);
		}
	}


}
