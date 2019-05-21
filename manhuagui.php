<?php

require_once "include/config.inc.php";
require_once "include/classloader.php";

// response render
$renderer = new Renderer();
$dba = new ManHuaGui\DBA(_MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE);
try {
    $upstream = new ManHuaGui\Upstream($dba);
    // fetch comic image
    $metadata = new Metadata();
    $image = $upstream->download($_GET, $metadata);
    // set header
    $renderer->add_header('Content-Type', $metadata->mimetype);
    $renderer->add_header('Content-Length', $metadata->size);
    $renderer->add_header('Content-Disposition', 'inline; filename="' . $metadata->filename . '"');
    // set body
    $renderer->set_body($image);
} catch (Exception $e) {
    $renderer->set_status_code(500);
    $renderer->set_body($e->getMessage());
} finally {
    $dba->close();
}
$renderer->render();
