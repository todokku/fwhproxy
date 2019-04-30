<?php

require_once 'db.php';

function get_upstream_config($host) {
    $sql = "SELECT config FROM upstream WHERE host='".$host."' LIMIT 1";
    $result = db_query_one($sql);
    return json_decode($result['config'], true);
}
