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
    // upload to telegraph
    $src = Telegraph::uploadData($image, $metadata->mimetype);
    // make result
    $result = array(
        'ok' => false
    );
    if($src !== null) {
        $result['ok'] = true;
        $result['src'] = $src;
    }
    $result = json_encode($result);
    // set header
    $renderer->add_header('Content-Type', 'application/json');
    $renderer->add_header('Content-Length', strlen($result));
    // set body
    $renderer->set_body($result);
} catch (Exception $e) {
    $renderer->set_status_code(500);
    $renderer->set_body($e->getMessage());
} finally {
    if($dba) {
        $dba->close();
    }
}
$renderer->render();
