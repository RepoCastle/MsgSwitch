package cn.hjmao.msgswitch.service;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.lang.reflect.Constructor;
import java.util.HashMap;
import java.util.Properties;

public class ServiceManager {

	private HashMap<String, ServiceIface> serviceMap = new HashMap<String, ServiceIface>();
	private Properties msgswitchServiceProperties = new Properties();

	public static class ServiceThread implements Runnable {
		private ServiceIface service = null;
		ServiceThread(ServiceIface service) {
			this.service = service;
		}
		public void run() {
			service.start();
		}
	}
	
	public void reloadPropertyFile(File propertyFilePath) throws IOException {
		msgswitchServiceProperties.clear();
		FileReader reader = new FileReader(propertyFilePath);
		msgswitchServiceProperties.load(reader);
		reader.close();
	}
	
	public void registerServices() throws Exception{
		String dirString = msgswitchServiceProperties.getProperty("msgswitchServicePropertiesDirectory");
		File dir = new File(dirString);
		assert dir.isDirectory();
		
		for (File configFile: dir.listFiles()) {
			Properties properties = new Properties();
			FileReader reader = new FileReader(configFile);
			properties.load(reader);
			reader.close();
			
			String identifier = properties.getProperty("identifier");
			
			Class<?> cls = Class.forName(identifier);
			Constructor<?> constructor = cls.getConstructor();

			ServiceIface service = (ServiceIface) constructor.newInstance();
			service.setProperties(properties);
			serviceMap.put(identifier, service);
		}
	}
	private void startEnabledServices() {
		for (String identifier: serviceMap.keySet()) {
			ServiceIface service = serviceMap.get(identifier);
			Properties serviceProperties = service.getProperties();
			String isEnable = serviceProperties.getProperty("isEnable");
			if ("true".equals(isEnable)) {
				Runnable runnable = new ServiceThread(service);
				Thread thread = new Thread(runnable, identifier);
				thread.start();
			}
		}
	}
	public ServiceIface getService(String id) {
		return this.serviceMap.get(id);
	}

	public static void main(String args[]) {
		try {
			ServiceManager msgswitchSrv = new ServiceManager();
			msgswitchSrv.reloadPropertyFile(new File(args[0]));
			msgswitchSrv.registerServices();
			msgswitchSrv.startEnabledServices();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
}
