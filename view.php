<?php

require_once "include/config.inc.php";
require_once "include/classloader.php";

// prepare render
$renderer = new Renderer();
try {
    // check source
    $source = $_GET['source'];
    if(strcasecmp('pixiv', $source) === 0) {
        $dba = new Pixiv\DBA(_MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE);
        $upstream = new Pixiv\Upstream($dba, _PIXIV_NEED_OAUTH);
    } elseif (strcasecmp('manhuagui', $source) === 0) {
        $dba = new ManHuaGui\DBA(_MYSQL_HOST, _MYSQL_USERNAME, _MYSQL_PASSWORD, _MYSQL_DATABASE);
        $upstream = new ManHuaGui\Upstream($dba);
    } else {
        throw new Exception('Unsupported Source!');
    }
    // download image
    $metadata = new Metadata();
    $image = $upstream->download($_GET, $metadata);
    if($image === null) {
        throw new Exception('No Such Image!');
    }
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
    if($dba) {
        $dba->close();
    }
}
$renderer->render();
