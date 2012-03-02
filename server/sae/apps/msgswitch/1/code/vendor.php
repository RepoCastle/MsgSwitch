<?php
class Vendor {
    static $CMPREFIX = array('134', '135', '136', '137', '138', '139', '150', '151', '152', '157', '158', '159', '188');
    static $CUPREFIX = array('130', '131', '132', '155', '156', '185', '186');
    static $CTPREFIX = array('133', '153', '180', '189');

    function parse($receiver) {
        $vendor = "VENDOR_NOT_SUPPORT";
        $prefix = substr($receiver, 0, 3);

	if (strlen($receiver)!=11) {
            return "VENDOR_NOT_SUPPORT";
        }

        if (in_array($prefix, self::$CMPREFIX)) {
            $vendor = "CM";
        } else if (in_array($prefix, self::$CUPREFIX)) {
            $vendor = "CU";
        } else if (in_array($prefix, self::$CTPREFIX)) {
            $vendor = "CT";
        }
        return $vendor; 
    }
}
?>
