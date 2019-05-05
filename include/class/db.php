<?php

class DB {

    private $host;
    private $username;
    private $password;
    private $database;

    private $conn;

    public function __construct($host, $username, $password, $database) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    private function db_query($sql) {
        if($this->conn === null) {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        }
        $rs = $this->conn->query($sql);
        if($rs === true) {
            return true;
        } else {
            $result = [];
            while ($row = $rs->fetch_assoc()) {
                $result[] = $row;
            }
            $rs->free();
            return $result;
        }
    }


    public function get_config($name) {
        $sql = "SELECT config FROM upstream WHERE name='".$name."' LIMIT 1";
        $result = $this->db_query($sql);
        if(count($result) > 0) {
            return json_decode($result[0]['config'], true);
        } else {
            return null;
        }
    }

    public function set_config($name, $config) {
        $config = json_encode($config);
        $sql = "
            INSERT INTO upstream(`name`, `config`)
            VALUES('".$name."', '".$config."') 
            ON DUPLICATE KEY UPDATE 
                config='".$config."', 
                update_time=NOW()";
        $this->db_query($sql);
    }

    public function close() {
        if($this->conn !== null) {
            $this->conn->close();
        }
    }

}