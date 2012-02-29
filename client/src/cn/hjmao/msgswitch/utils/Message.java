package cn.hjmao.msgswitch.utils;

public class Message {
	private String sender;
	private String content;

	private static final String SENDER_MARK_S = "[msgs]";
	private static final String SENDER_MARK_E = "[/msgs]";
	private static final String CONTENT_MARK_S = "[msgc]";
	private static final String CONTENT_MARK_E = "[/msgc]";

	public Message(String sender, String content) {
		this.sender = sender;
		this.content = content;
	}

	public Message(String senderWithContent) {
		int sender_mark_s_index = senderWithContent.indexOf(SENDER_MARK_S);
		int sender_mark_e_index = senderWithContent.indexOf(SENDER_MARK_E);
		int content_mark_s_index = senderWithContent.indexOf(CONTENT_MARK_S);
		int content_mark_e_index = senderWithContent.indexOf(CONTENT_MARK_E);

		if (sender_mark_s_index != -1 && sender_mark_e_index != -1
				&& content_mark_s_index != -1 && content_mark_e_index != -1) {
			this.sender = senderWithContent.substring(sender_mark_s_index
					+ SENDER_MARK_S.length(), sender_mark_e_index);
			this.content = senderWithContent.substring(content_mark_s_index
					+ CONTENT_MARK_S.length(), content_mark_e_index);
		}
	}
	
	public String getSender() {
		return sender;
	}

	public String getContent() {
		return content;
	}

	public String toString() {
		return CONTENT_MARK_S + content + CONTENT_MARK_E;
	}

	public static boolean isMessage(String content) {
		boolean isMsg = false;

		int sender_mark_s_index = content.indexOf(SENDER_MARK_S);
		int sender_mark_e_index = content.indexOf(SENDER_MARK_E);
		int content_mark_s_index = content.indexOf(CONTENT_MARK_S);
		int content_mark_e_index = content.indexOf(CONTENT_MARK_E);
		if (sender_mark_s_index != -1 && sender_mark_e_index != -1
				&& content_mark_s_index != -1 && content_mark_e_index != -1) {
			isMsg = true;
		}
		return isMsg;
	}
}
