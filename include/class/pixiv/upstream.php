<?php

namespace Pixiv;

use DB\CacheIO;
use DB\Session;
use Exception;
use HTTP;
use Metadata;
use ProxyException;

class Upstream implements \Upstream {

    // OAuth settings
    private const ClientId = 'MOBrBDS8blbauoSck0ZfDbtuzpyT';
    private const ClientSecret = 'lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj';
    // Default access_token with limited scopes
    private const BuiltinAccessToken = '8mMXXWT9iuwdJvsVIvQsFYDwuZpRCMePeyagSh30ZdU';

    private const OAuthCacheKey = 'pixiv_token';

    // API url
    private const TokenUrl = 'https://oauth.secure.pixiv.net/auth/token';
    private const RefererUrl = 'https://app-api.pixiv.net/';

    private const HeaderContentLength = 'content-length';
    private const MaxAutoSize = 5 * 1024 * 1024;

    private $cache = null;
    private $access_token = self::BuiltinAccessToken;

    public function __construct(Session $session) {
        $this->cache = new CacheIO($session);
    }

    public function download(\Options $opts, Metadata &$metadata) {
        if(!($opts instanceof Options)) {
            throw new ProxyException("Bad Request", 400);
        }

        // get illust data
        $data = $this->fetchData($opts->illust_id);
        // get image urls
        $image_urls = $data['image_urls'];
        if($data['metadata'] !== null) {
            $page_index = ($opts->page - 1) % $data['page_count'];
            $image_urls = $data['metadata']['pages'][$page_index]['image_urls'];
        }
        // select image url
        if($opts->size == Options::SizeAuto) {
            $opts->size = $this->getAutoImageSize($image_urls);
        }
        $image_url = $image_urls[$opts->size];
        return $this->getImageStream($image_url, $metadata);
    }

    private function fetchData(int $illust_id) {
        // init oauth
        $this->initOauth();

        // fetch all available sizes
        $info_url = 'https://public-api.secure.pixiv.net/v1/works/'.$illust_id.'.json?image_sizes=medium%2Clarge';
        $resp = HTTP::get($info_url, array(
            'Authorization' => 'Bearer '.$this->access_token
        ));
        $result = json_decode($resp, true);
        if(array_key_exists('has_error', $result) && $result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        return $result['response'][0];
    }
    private function initOauth() {
        if(  (!_PIXIV_NEED_OAUTH) ||
            _PIXIV_REFRESH_TOKEN === null || empty(_PIXIV_REFRESH_TOKEN)) {
            return;
        }
        // load oauth data from cache
        $data = $this->cache->get(self::OAuthCacheKey);
        if($data !== null) {
            $data = json_decode($data, true);
        }
        // check token
        if($data === null || $data['expiry_time'] < time()) {
            $data = $this->refreshToken();
            if($data !== null) {
                $this->cache->put(self::OAuthCacheKey, json_encode($data));
                $this->access_token = $data['access_token'];
            }
        } else {
            $this->access_token = $data['access_token'];
        }
    }
    private function refreshToken() {
        $data = array(
            'get_secure_url' => '1',
            'client_id'      => self::ClientId,
            'client_secret'  => self::ClientSecret,
            'grant_type'     => 'refresh_token',
            'refresh_token'  => _PIXIV_REFRESH_TOKEN
        );
        $resp = HTTP::post(self::TokenUrl, $data);
        $result = json_decode($resp, true);
        if(array_key_exists('has_error', $result) && $result['has_error']) {
            return null;
        } else {
            return array(
                'access_token' => $result['response']['access_token'],
                'expiry_time' => $result['response']['expires_in'] + time()
            );
        }
    }

    private function getAutoImageSize(array $image_urls): string {
        // check the large file size
        $headers = HTTP::head($image_urls[Options::SizeLarge], array(
            'Referer' => self::RefererUrl
        ));
        $filesize = intval($headers[self::HeaderContentLength], 10);
        // determind size
        return $filesize < self::MaxAutoSize ? Options::SizeLarge : Options::SizeMedium;
    }

    private function getImageStream(string $image_url, Metadata &$metadata) {
        // open stream
        $out_headers = array();
        $stream = HTTP::stream($image_url, array(
            'Referer' => self::RefererUrl
        ), $out_headers);
        // fill metadata
        $metadata->filename = basename( parse_url($image_url, PHP_URL_PATH) );
        $meta = stream_get_meta_data($stream);
        foreach ($meta['wrapper_data'] as $header) {
            $fields = explode(':', $header, 2);
            if(count($fields) < 2) {
                continue;
            }
            $name = strtolower(trim($fields[0]));
            $value = trim($fields[1]);

            switch ($name) {
                case 'content-type':
                    $metadata->mimetype = $value;
                    break;
                case 'content-length':
                    $metadata->size = intval($value, 10);
                    break;
            }
        }
        return $stream;
    }

}
