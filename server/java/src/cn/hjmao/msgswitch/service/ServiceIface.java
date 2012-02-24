package cn.hjmao.msgswitch.service;
import java.util.Properties;


public interface ServiceIface {
	public String getIdentifier();
	public void setProperties(Properties taskProperties);
	public Properties getProperties();
	public void loadResource();
	
	public void start();
	public void stop();
}
