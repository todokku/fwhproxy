<?php

require_once "include/config.inc.php";
require_once "include/classloader.php";

// response render
$renderer = new Renderer();
try {
    $storage = new DB(_MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE);
    $upstream = new Pixiv($storage, _PIXIV_NEED_OAUTH);
    $upstream->setup($_REQUEST);

    $renderer->set_body('OK!');
} catch (Exception $e) {
    $renderer->set_status_code(500);
    $renderer->set_body($e->getMessage());
} finally {
    $storage->close();
}
$renderer->render();
