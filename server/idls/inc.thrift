namespace java inc.thrift
namespace py inc.thrift

exception FWException {
  1: string errCode,
  2: string additionalInformation,
}

service MsgSwitchService {
	void test() throws (1:FWException ex),
}
