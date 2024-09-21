<?php

class mysqliClass{

    public function ConnectDatabase()
    {
        global $MySQL_Host, $MySQL_User, $MySQL_Password, $MySQL_Database;
        $mysqli = new mysqli($MySQL_Host, $MySQL_User, $MySQL_Password, $MySQL_Database);
        if ($mysqli->connect_error) {
            die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
        }
        return $mysqli;
    }

}


?>