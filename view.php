<?php

require_once "include/config.inc.php";
require_once "include/classloader.php";

// start database session
$dbsession = new DB\Session();

// prepare render
$renderer = new Utils\Renderer();
try {
    // check source
    $source = $_GET['source'];
    if(strcasecmp('pixiv', $source) === 0) {
        $opts = Pixiv\Options::parse($_GET);
        $upstream = new Pixiv\Upstream($dbsession);
    } elseif (strcasecmp('manhuagui', $source) === 0) {
        $opts = ManHuaGui\Options::parse($_GET);
        $upstream = new ManHuaGui\Upstream($dbsession);
    } else {
        throw new ProxyException('Unsupported Source', 400);
    }
    // download image
    $metadata = new Metadata();
    $image = $upstream->download($opts, $metadata);
    if($image === null) {
        throw new ProxyException('Image No Found', 404);
    }
    // set header
    $renderer->add_header('Content-Type', $metadata->mimetype);
    $renderer->add_header('Content-Length', $metadata->size);
    $renderer->add_header('Content-Disposition', 'inline; filename="' . $metadata->filename . '"');
    // set body
    $renderer->set_body($image);
} catch (Exception $e) {
    $renderer->set_body($e->getMessage());
} finally {
    $dbsession->close();
}
$renderer->render();
