namespace java inc.thrift
namespace py inc.thrift

exception FWException {
  1: string errCode,
  2: string additionalInformation,
}

service MsgSwitchService {
	void test() throws (1:FWException ex),
	i32 sendsms(1:string sender, 2:string recvVendor, 3:string recvNumber, 4:string content) throws (1:FWException ex),
}
