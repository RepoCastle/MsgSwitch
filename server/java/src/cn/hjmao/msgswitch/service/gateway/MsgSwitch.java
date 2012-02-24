package cn.hjmao.msgswitch.service.gateway;

import inc.thrift.FWException;
import inc.thrift.MsgSwitchService;

import org.apache.thrift.TException;

import cn.hjmao.msgswitch.service.ThriftServiceBase;
import cn.hjmao.msgswitch.utils.Logging;

public class MsgSwitch extends ThriftServiceBase implements MsgSwitchService.Iface{

	@Override
	public void test() throws FWException, TException {
		Logging.info("MsgSwitch::test ...");
	}
	
}
