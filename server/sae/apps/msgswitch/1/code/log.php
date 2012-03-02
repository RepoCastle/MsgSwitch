<?php

class Log {
    function iplog($ip) {
        $retCode = 0;
        $mysql = new SaeMysql();
        $sql = "INSERT INTO `access` (`userip`, `accesstime`) VALUES ('" . $ip . "', NOW())";
        $mysql->runSql($sql);
        if ($mysql->errno() != 0) {
            echo "Error in insert log";
            $retCode = -1;
        }
        $mysql->closeDb();
        return $retCode;
    }
}

?>
