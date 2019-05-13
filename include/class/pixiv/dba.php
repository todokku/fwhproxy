<?php

namespace Pixiv;

class DBA extends \DBA {

    public function __construct($host, $username, $password, $database) {
        parent::__construct($host, $username, $password, $database);
    }

    public function loadConfig() {
        $sql = "SELECT config FROM upstream WHERE name='pixiv' LIMIT 1";
        $result = $this->executeQuery($sql);
        if(count($result) > 0) {
            return json_decode($result[0]['config'], true);
        } else {
            return null;
        }
    }

    public function saveConfig($config) {
        $config = json_encode($config);
        $sql = "INSERT INTO `upstream` (`name`, `config`, `update_time`) 
                VALUES(?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                config=VALUES(`config`), `update_time`=NOW()";
        return $this->executeUpdate($sql, 'pixiv', $config);
    }

}
