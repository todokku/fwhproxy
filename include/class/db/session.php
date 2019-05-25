<?php

namespace DB;

use mysqli;
use mysqli_stmt;

class Session {

    /**
     * @var mysqli
     */
    private $conn = null;

    /**
     * Try to get database connection.
     *
     * @return mysqli
     */
    private function getConn(): mysqli {
        if($this->conn === null) {
            $this->conn = new mysqli(
                _MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE
            );
        }
        return $this->conn;
    }

    /**
     * Parepare SQL statement
     *
     * @param string $sql
     * @param mixed ...$params
     * @return mysqli_stmt
     */
    private function prepare(string $sql, ...$params): mysqli_stmt {
        $stmt = $this->getConn()->prepare($sql);
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
        return $stmt;
    }

    public function query(string $sql, ...$params): array {
        $stmt = $this->prepare($sql, ...$params);
        $result = array();
        if($stmt->execute() && $rs = $stmt->get_result()) {
            while($row = $rs->fetch_assoc()) {
                $result[] = $row;
            }
            $rs->free();
        }
        $stmt->close();
        return $result;
    }

    public function update(string $sql, ...$params): int {
        $stmt = $this->prepare($sql, ...$params);

        $affected_rows = -1;
        if($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
        }
        $stmt->close();
        return $affected_rows;
    }

    /**
     * Close session
     */
    public function close() {
        if($this->conn !== null) {
            $this->conn->close();
        }
    }

}
