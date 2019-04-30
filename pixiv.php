<?php

require_once 'upstream.php';
require_once "http.php";
require_once "web.php";

# Proxy to pixiv
$rr = new ResponseRenderer();
try {
    # Get and process parameters
    $illust_id = intval($_REQUEST["illust_id"], 10);
    $page = intval($_REQUEST["page"], 10);
    if($page > 0) {
        $page -= 1;
    }
    if ($illust_id === 0) {
        throw new Exception('Invalid "illust_id" value!', 400);
    }

    # Get upstream config
    $config = get_upstream_config('pixiv');

    # Initialize http client
    $client = new HttpClient($config['cookie']);
    # Get image pages
    $resp = $client->get("https://www.pixiv.net/ajax/illust/".$illust_id."/pages");
    if($resp === null) {
        throw new Exception("Fail to get data!", 502);
    }
    $illust_data = json_decode($resp->get_body(), true);
    if($illust_data['error']) {
        throw new Exception("Upstream server error!", 502);
    }
    # Select illust
    $page = $page % count($illust_data['body']);
    $illust = $illust_data['body'][$page];
    # Fetch illust image
    $original = $illust['urls']['original'];
    $referer = "https://www.pixiv.net/member_illust.php?mode=medium&illust_id=".$illust_id;
    $resp = $client->get($original, $referer);
    if($resp == null) {
        throw new Exception("Illust not found!", 404);
    }

    # Content-Type header
    $content_type = $resp->get_header('content-type');
    if ($content_type == "") {
        $content_type = "application/octet-stream";
    }
    $rr->add_header('Content-Type', $content_type);
    # Filename header
    $pos = strrpos($original, '/');
    $file_name = substr($original, $pos + 1);
    $pos = strrpos($file_name, '?');
    if($pos !== false) {
        $file_name = substr($file_name, 0, $pos);
    }
    $rr->add_header('Content-Disposition', 'inline; filename="'.$file_name.'"');
    // Cache header
    $rr->add_header('Last-Modified', $resp->get_header('last-modified'));
    $rr->add_header('Cache-Control', $resp->get_header('cache-control'));
    $rr->add_header('Expires', $resp->get_header('expires'));
    # Custom headers
    $rr->add_header('X-Upstream-URL', $original);
    // Set response body
    $rr->set_body($resp->get_body());

} catch (Exception $e) {
    if($e->getCode() > 0) {
        $rr->set_status_code( $e->getCode() );
    } else {
        $rr->set_status_code(500);
    }
    $rr->set_body($e->getMessage());
}

# Render the response at the end
$rr->render();

