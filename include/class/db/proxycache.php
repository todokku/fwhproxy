<?php

namespace DB;

class ProxyCache {

    private const QuerySQL = "SELECT `data` FROM `cache` WHERE `name`=? LIMIT 1";
    private const InsertSQL = "INSERT INTO `cache`(`name`, `data`, `create_time`) 
VALUES (?, ?, NOW()) 
ON DUPLICATE KEY UPDATE `data`=VALUES(`data`), `update_time`=NOW()";

    /**
     * @var Session
     */
    private $session;

    public function __construct(Session $session) {
        $this->session = $session;
    }

    /**
     * Get data from cache
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string {
        $results = $this->session->query(self::QuerySQL, $key);
        if (count($results) === 0) {
            return null;
        } else {
            return $results[0]['data'];
        }
    }

    /**
     * Put data to cache
     *
     * @param string $key
     * @param string $data
     * @return bool
     */
    public function put(string $key, string $data): bool {
        return $this->session->update(self::InsertSQL, $key, $data) > 0;
    }

}