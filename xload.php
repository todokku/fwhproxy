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
    // check source
    $source = $_GET['source'];
    if (strcasecmp('pixiv', $source) === 0) {
        $opts = Pixiv\Options::parse($_GET);
        $upstream = new Pixiv\Upstream($dbsession);
    } elseif (strcasecmp('manhuagui', $source) === 0) {
        $opts = ManHuaGui\Options::parse($_GET);
        $upstream = new ManHuaGui\Upstream($dbsession);
    } else {
        throw new ProxyException('Unsupported Source', 400);
    }

    // check xload cache
    $cache = new DB\CacheIO($dbsession);
    $cache_key = 'telegraph_' . $opts->cacheKey();
    $src = $cache->get($cache_key);
    if ($src === null) {
        // download from upstream
        $metadata = new Metadata();
        $image = $upstream->download($opts, $metadata);
        if ($image === null) {
            throw new ProxyException('Download Failed', 404);
        }
        // upload to telegraph
        $src = Telegraph::uploadData($image, $metadata->mimetype);
        if($src === null) {
            throw new ProxyException("Upload failed", 503);
        }
        // store in cache
        $cache->put($cache_key, $src);
    }

    // set result
    $result['ok'] = true;
    $result['src'] = $src;
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
