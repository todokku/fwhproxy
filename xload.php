<?php

require_once "include/config.inc.php";
require_once "include/classloader.php";

// start database session
$dbsession = new DB\Session();

// prepare render
$result = array(
    'ok' => false
);
try {
    // xload source
    $from = $_GET['from'];
    if (strcasecmp('pixiv', $from) === 0) {
        $opts = Pixiv\Options::parse($_GET);
        $upstream = new Pixiv\Upstream($dbsession);
    } elseif (strcasecmp('manhuagui', $from) === 0) {
        $opts = ManHuaGui\Options::parse($_GET);
        $upstream = new ManHuaGui\Upstream($dbsession);
    } else {
        throw new ProxyException('Unsupported Source', 400);
    }

    // xload destation
    $to = $_GET['to'];
    if (strcasecmp('telegram', $to) === 0) {
        $target = new Telegram\Uploader();
    } elseif (strcasecmp('telegraph', $to) === 0) {
        $target = new Telegraph\Uploader();
    }

    // check xload cache
    $cache_key = $to . $opts->cacheKey();
    $cache = new DB\CacheIO($dbsession);
    $result = $cache->get($cache_key);
    if ($result === null) {
        // download from upstream
        $metadata = new Metadata();
        $image = $upstream->download($opts, $metadata);
        if ($image === null) {
            throw new ProxyException('Download Failed', 404);
        }
        // upload to telegraph
        $result = $target->upload($image, $metadata->mimetype);
        if($result === null) {
            throw new ProxyException("Upload failed", 503);
        }
        // store in cache
        $cache->put($cache_key, $result);
    }

    // set result
    $result['ok'] = true;
    $result['result'] = $result;
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
} finally {
    $dbsession->close();
}

// convert result to JSON
$result = json_encode($result);

// write result
$renderer = new Utils\Renderer();
$renderer->add_header('Content-Type', 'application/json');
$renderer->add_header('Content-Length', strlen($result));
$renderer->set_body($result);
$renderer->render();
