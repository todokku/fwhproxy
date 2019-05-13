<?php

abstract class DBA {

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

    private function getDB(): mysqli {
        if($this->conn === null) {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        }
        return $this->conn;
    }

    protected function executeUpdate($sql, ...$params): int {
        $stmt = $this->getDB()->prepare($sql);
        $types = '';
        foreach ($params as $param) {
            switch (gettype($param)) {
                case "integer":
                    $types .= 'i';
                    break;
                case "double":
                    $types .= 'd';
                    break;
                default:
                    $types .= 's';
                    break;
            }
        }
        $stmt->bind_param($types, ...$params);
        // execute
        $result = 0;
        if($stmt->execute()) {
            $result = $stmt->affected_rows;
        }
        $stmt->close();
        return $result;
    }

    protected function executeQuery($sql) {
        $rs = $this->getDB()->query($sql);
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

    public function close() {
        if($this->conn !== null) {
            $this->conn->close();
        }
    }

}