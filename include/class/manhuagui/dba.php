<?php

namespace ManHuaGui;

class DBA extends \DBA {

    public function __construct($host, $username, $password, $database) {
        parent::__construct($host, $username, $password, $database);
    }

    public function loadData($book_id, $chapter_id) {
        $sql = "SELECT `data` FROM `manhuagui` 
            WHERE book_id=".$book_id." AND chapter_id=".$chapter_id." LIMIT 1";
        $result = $this->executeQuery($sql);
        if(count($result) === 0) {
            return null;
        } else {
            return $result[0]['data'];
        }
    }

    public function storeData($book_id, $chapter_id, $data) {
        $sql = "INSERT INTO `manhuagui` (`book_id`, `chapter_id`, `data`, `create_time`, `update_time`)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            `data` = VALUES(`data`), `update_time` = NOW()";
        return $this->executeUpdate($sql, $book_id, $chapter_id, $data);
    }

}