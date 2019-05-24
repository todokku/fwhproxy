<?php

namespace Pixiv;

use Exception;
use HTTP;
use Metadata;

class Upstream implements \Upstream {

    // API url
    private const TokenUrl = 'https://oauth.secure.pixiv.net/auth/token';
    private const RefererUrl = 'https://app-api.pixiv.net/';
    // OAuth settings
    private const ClientId = 'MOBrBDS8blbauoSck0ZfDbtuzpyT';
    private const ClientSecret = 'lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj';
    // Default access_token with limited scopes
    private const BuiltinAccessToken = '8mMXXWT9iuwdJvsVIvQsFYDwuZpRCMePeyagSh30ZdU';

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

    public function download(\Options $opts, Metadata &$metadata) {
        function cast($base):Options {
            return $base;
        }
        $opts = cast($opts);
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
        $resp = HTTP::post(self::TokenUrl, $data);
        $result = json_decode($resp, true);
        if(array_key_exists('has_error', $result) && $result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        return array(
            'access_token' => $result['response']['access_token'],
            'refresh_token' => $result['response']['refresh_token'],
            'token_expiry' => $result['response']['expires_in'] + time()
        );
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
