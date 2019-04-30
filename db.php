<?php

require_once "db.inc.php";

function db_query($sql) {
    $conn = new mysqli(
        _MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE
    );
    $result = [];
    $rs = $conn->query($sql);
    if($rs) {
        while($row = $rs->fetch_assoc()) {
            $result[] = $row;
        }
        $rs->free();
    }
    $conn->close();
    return $result;
}

function db_query_one($sql) {
    $conn = new mysqli(
        _MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE
    );
    $result = [];
    $rs = $conn->query($sql);
    if($rs) {
        $result = $rs->fetch_assoc();
        $rs->free();
    }
    $conn->close();
    return $result;
}
