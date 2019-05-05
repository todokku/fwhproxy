<?php

define("_PROXY", 1);

require_once "include/config.inc.php";

spl_autoload_register(function($class_name) {
    $class_name = strtolower($class_name);
    include_once "include/class/" . $class_name . ".php";
});

// Init DB
$db = new DB(_MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE);

// Prepare render
$renderer = new Renderer();
try {
    // Initial upstream fetcher
    $pixiv = new Pixiv($db, _PIXIV_LOGIN);
    $pixiv->fetch($_GET);
    // Setup output
    foreach ($pixiv->get_headers() as $name => $value) {
        $renderer->add_header($name, $value);
    }
    $renderer->set_body($pixiv->get_body());
} catch (Exception $e) {
    $renderer->set_status_code(500);
    $renderer->set_body($e->getMessage());
} finally {
    $db->close();
}
$renderer->render();
