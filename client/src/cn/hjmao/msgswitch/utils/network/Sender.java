package cn.hjmao.msgswitch.utils.network;

public interface Sender {
	public int send(String sender, String recvNumber, String content);
}
