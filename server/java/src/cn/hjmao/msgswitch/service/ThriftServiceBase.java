package cn.hjmao.msgswitch.service;

import java.lang.reflect.Constructor;
import java.util.Properties;

import org.apache.thrift.TProcessor;
import org.apache.thrift.server.TServer;
import org.apache.thrift.server.TThreadPoolServer;
import org.apache.thrift.transport.TServerSocket;


public class ThriftServiceBase implements ServiceIface {

	private Properties serviceProperties = null;
	private TServer server = null;
	private ServiceManager serviceManager = null;

	@Override
	public void start() {
		try {
			if (null==server) {
				String thriftIdentifier = serviceProperties.getProperty("thriftIdentifier");
				String ifaceIdentifier = thriftIdentifier + "$Iface";
				String processorIdentifier = thriftIdentifier + "$Processor";
				int port = Integer.parseInt(serviceProperties.getProperty("port"));
				
				Class<?> clsProcessor = Class.forName(processorIdentifier);
				Class<?>[] clsParamTypes = new Class[1];
				clsParamTypes[0] = Class.forName(ifaceIdentifier);
				Constructor<?> constructor = clsProcessor.getConstructor(clsParamTypes);
				Object[] argList = new Object[1];
				argList[0] = this;
				TProcessor processor = (TProcessor) constructor.newInstance(argList);
				TServerSocket serverTransport = new TServerSocket(port, 30000);
				server = new TThreadPoolServer(new TThreadPoolServer.Args(serverTransport).processor(processor));
				System.out.println("Starting service " + thriftIdentifier + " on port " + port + " ...");
			}
			loadResource();
			server.serve();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
	@Override
	public void loadResource() {
	}
	
	@Override
	public void stop() {
		server.stop();
	}
	
	@Override
	public String getIdentifier() {
		return serviceProperties.getProperty("identifier");
	}

	@Override
	public Properties getProperties() {
		return serviceProperties;
	}

	@Override
	public void setProperties(Properties taskProperties) {
		this.serviceProperties = taskProperties;
	}


	public ServiceManager getServiceManager() {
		return serviceManager;
	}

	public void setServiceManager(ServiceManager avatarServiceManager) {
		this.serviceManager = avatarServiceManager;
	}
}
