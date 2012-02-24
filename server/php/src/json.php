<?php

class JSON {

        public function lst($lst, $handle) {
                $count = count($lst);
                $json = "[\n";
                for ($i=0; $i<$count; $i++) {
                        $obj = $lst[$i];
                        $json .= $this->$handle($obj);
                        if ($i<$count-1) {
                                $json .=  ",\n";
                        } else {
                                $json .= "\n";
                        }
                }

                $json .= "]";
                return $json;
        }

        public function map($map) {
                $json = "{\n";
                $count=count($map);
                $c=1;
                foreach ($map as $key => $val) {
                        $json .= '"' . json_encode($key) . '":' . json_encode($val);
                        if  ($c < $count) {
                                $json .= ",\n";
                        }
                        $c++;
                }
                $json .= "}";
                return $json;
        }

        public function mapOfSet($map) {
                $json = "{\n";
                $count=count($map);
                $c=1;
                foreach ($map as $key => $val) {
                        $ids = "";
                        $idcount = count($val);
                        $idc = 1;
                        foreach ($val as $id => $temp) {
                                if ($idc < $idcount) {
                                        $ids .= $id . ",";
                                } else {
                                        $ids .= $id;
                                }
                                $idc++;
                        }
                        $json .= '"' . json_encode($key) . '":' . '"' . $ids . '"';
                        if  ($c < $count) {
                                $json .= ",\n";
                        }
                        $c++;
                }
                $json .= "}";
                return $json;
        }

        public function valuesOfMap($map) {
                $valueLst = array();
                $i = 0;
                foreach ($map as $key => $val) {
                        $valueLst[$i] = $val;
                        $i++;
                }
                return $valueLst;
        }

        public function keysOfMap($map) {
                $keyLst = array();
                $i = 0;
                foreach ($map as $key => $val) {
                        $valueLst[$i] = $key;
                        $i++;
                }
                return $keyLst;
        }

        public function pair($key, $value) {
                $json = "{\n";
                $json .= json_encode($key) . ':' . json_encode($value);
                $json .= "}";
                return $json;
        }
}
?>

