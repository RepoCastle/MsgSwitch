<?php
echo "--1--";
$GLOBALS['THRIFT_ROOT'] = '../thrift';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/THttpClient.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
echo "--2--";

$GLOBALS['GEN_DIR'] = $GLOBALS['THRIFT_ROOT'].'/packages';
echo "--3--";
require_once $GLOBALS['GEN_DIR'].'/inc/inc_types.php';
echo "--4--";

require_once 'json.php';
echo "--5--";
$GLOBALS['JSON'] = new JSON();
$json = $GLOBALS['JSON'];
echo "--6--";

error_reporting(E_ALL);
class ServiceHandle {
        private $portArray = array();
        private $addrArray = array();
        private $transport;
        private $client;
        /*
         * Construnct and start open the transport
         */
        public function __construct($type) {
                $this->init();
                $socket = new TSocket($this->addrArray[$type], $this->portArray[$type]);
                $this->transport = new TBufferedTransport($socket, 1024, 1024);
                $protocol = new TBinaryProtocol($this->transport);
                switch($type) {
                        case 'MsgSwitchServiceClient':
                                require_once $GLOBALS['GEN_DIR'].'/inc/MsgSwitchService.php';
                                $this->client = new MsgSwitchServiceClient($protocol);
                                break;
                        default:
                                throw new Exception('Unkownn type:'.$type);
                }
                $this->transport->open();
        }

        private function init() {
                $this->addrArray['MsgSwitchServiceClient'] = 'localhost';
                $this->portArray['MsgSwitchServiceClient'] = 11000;
        }

        public function close() {
                $this->transport->close();
        }
        public function getClient(){
                if (empty($this->client)) {
                        echo "It is an empty client";
                } else {
                        echo $this->client->tempNum;
                }
                return $this->client;
        }
}
?>

