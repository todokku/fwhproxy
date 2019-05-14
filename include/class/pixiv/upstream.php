<?php

namespace Pixiv;

use \Exception;
use \HTTP;

class Upstream implements \Upstream {

    // API url
    private const TokenUrl = 'https://oauth.secure.pixiv.net/auth/token';
    private const RefererUrl = 'https://app-api.pixiv.net/';
    // OAuth settings
    private const ClientId = 'MOBrBDS8blbauoSck0ZfDbtuzpyT';
    private const ClientSecret = 'lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj';
    // Default access_token with limited scopes
    private const BuiltinAccessToken = '8mMXXWT9iuwdJvsVIvQsFYDwuZpRCMePeyagSh30ZdU';
    // Illust size
    private const SizeLarge = 'large';
    private const SizeMedium = 'medium';
    private const SizeAuto = 'auto';

    private const HeaderContentLength = 'content-length';
    private const MaxAutoSize = 5 * 1024 * 1024;

    private $need_oauth = false;
    private $dba;

    // The access_token using for call API
    private $access_token = self::BuiltinAccessToken;

    public function __construct(?DBA $dba, $need_oauth=false) {
        $this->dba = $dba;
        $this->need_oauth = $need_oauth;
    }

    public function setup(array $args) {
        if(!$this->need_oauth) {
            return;
        }
        if(!array_key_exists('username', $args) || !array_key_exists('password', $args)) {
            throw new Exception('You should provide username and password!');
        }
        $username = $args['username'];
        $password = $args['password'];
        // Login to grant access token
        $resp = HTTP::post(self::TokenUrl, array(
            'get_secure_url' => '1',
            'client_id'      => self::ClientId,
            'client_secret'  => self::ClientSecret,
            'grant_type'     => 'password',
            'username'       => $username,
            'password'       => $password,
        ), null);
        $result = json_decode($resp, true);
        if($result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        // Store config
        $config = array(
            'access_token' => $result['response']['access_token'],
            'refresh_token' => $result['response']['refresh_token'],
            'token_expiry' => $result['response']['expires_in'] + time()
        );
        $this->dba->saveConfig($config);
    }

    public function fetch(array $args) {
        // Get parameters
        $illust_id = intval($args['illust_id'], 10);
        $page = array_key_exists('page', $args) ? intval($args['page'], 10) : 1;
        // Prior to fetch large size
        $size = array_key_exists('size', $args) ? strtolower($args['size']) : self::SizeLarge;
        if($size != self::SizeMedium && $size != self::SizeLarge && $size != self::SizeAuto) {
            $size = self::SizeLarge;
        }

        // Init oauth
        $this->initOauth();

        // Get illust info
        $info = $this->fetchInfo($illust_id);
        $image_urls = $info['image_urls'];
        if($info['metadata'] !== null) {
            $page_index = ($page - 1) % $info['page_count'];
            $image_urls = $info['metadata']['pages'][$page_index]['image_urls'];
        }
        // Select image url
        if($size == self::SizeAuto) {
            $image_url = $image_urls[self::SizeLarge];
            $large_size = $this->getImageSize($image_url);
            if($large_size > self::MaxAutoSize) {
                $image_url = $image_urls[self::SizeMedium];
            }
        } else {
            $image_url = $image_urls[$size];
        }
        // return
        return $this->getStream($image_url);
    }

    private function initOauth() {
        if(!$this->need_oauth || $this->dba === null) {
            return;
        }
        // Load config from database
        $config = $this->dba->loadConfig();
        if($config === null) {
            return;
        }
        // refresh token if need
        if(empty($config['access_token']) || $config['token_expiry'] < time()) {
            $config = $this->refreshToken($config['refresh_token']);
            $this->dba->saveConfig($config);
        }
        $this->access_token = $config['access_token'];
    }

    private function refreshToken($refresh_token) {
        $data = array(
            'get_secure_url' => '1',
            'client_id'      => self::ClientId,
            'client_secret'  => self::ClientSecret,
            'grant_type'     => 'refresh_token',
            'refresh_token'  => $refresh_token
        );
        $resp = HTTP::post(self::TokenUrl, $data, null);
        $result = json_decode($resp, true);
        if($result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        return array(
            'access_token' => $result['response']['access_token'],
            'refresh_token' => $result['response']['refresh_token'],
            'token_expiry' => $result['response']['expires_in'] + time()
        );
    }

    private function fetchInfo($illust_id) {
        // fetch all available sizes
        $info_url = 'https://public-api.secure.pixiv.net/v1/works/'.$illust_id.'.json?image_sizes=medium%2Clarge';
        $resp = HTTP::get($info_url, array(
            'Authorization' => 'Bearer '.$this->access_token
        ), null);
        $result = json_decode($resp, true);
        if(array_key_exists('has_error', $result) && $result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        return $result['response'][0];
    }

    private function getImageSize($image_url) {
        $headers = HTTP::head($image_url, array(
            'Referer' => self::RefererUrl
        ));
        return array_key_exists(self::HeaderContentLength, $headers) ?
            intval($headers[self::HeaderContentLength]) : -1;
    }

    private function getStream($image_url) {
        // Open URL stream
        $context = stream_context_create(array(
            'http' => array(
                'protocol_version' => 1.1,
                'user_agent' => HTTP::UserAgent,
                'header' => 'Referer: '.self::RefererUrl
            )
        ));
        $stream = fopen($image_url, 'r', null, $context);

        // Get headers
        $headers = array();
        $meta = stream_get_meta_data($stream);
        foreach ($meta['wrapper_data'] as $header) {
            $fields = explode(':', $header, 2);
            if( count($fields) !== 2) {
                continue;
            }
            $name = strtolower(trim($fields[0]));
            $value = trim($fields[1]);
            if($name == 'content-type' ||
                $name == 'content-length' ||
                $name == 'last-modified' ||
                $name == 'cache-control' ||
                $name == 'expires') {
                $headers[$name] = $value;
            }
        }
        $filename = basename( parse_url($image_url, PHP_URL_PATH) );
        $headers['content-disposition'] = 'inline; filename="' . $filename . '"';

        return array(
            $headers, $stream
        );
    }

}
