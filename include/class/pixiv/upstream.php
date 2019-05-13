<?php

namespace Pixiv;

use \Exception;
use \HTTP;

class Upstream implements \Upstream {

    // User-Agent for call API
    const user_agent = 'PixivAndroidApp/5.0.64 (Android 6.0)';
    // API url
    const token_url = 'https://oauth.secure.pixiv.net/auth/token';
    const referer_url = 'https://app-api.pixiv.net/';
    // OAuth settings
    const client_id = 'MOBrBDS8blbauoSck0ZfDbtuzpyT';
    const client_secret = 'lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj';
    // Default access_token with limited scopes
    const builtin_access_token = '8mMXXWT9iuwdJvsVIvQsFYDwuZpRCMePeyagSh30ZdU';

    private $need_oauth = false;
    private $dba;

    // The access_token using for call API
    private $access_token = self::builtin_access_token;

    public function __construct(DBA $dba, $need_oauth=false) {
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
        $resp = HTTP::post(self::token_url, array(
            'get_secure_url' => '1',
            'client_id'      => self::client_id,
            'client_secret'  => self::client_secret,
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
        $size = array_key_exists('size', $args) ? strtolower($args['size']) : 'large';
        if($size != 'medium' && $size != 'large' && $size != 'auto') {
            $size = 'large';
        }

        // Init oauth
        $this->initOauth();
        // Get illust info
        $info = $this->fetchInfo($illust_id);
        // Select page
        $image_url = $info['image_urls'][$size];
        if($info['metadata'] !== null) {
            $page_index = ($page - 1) % $info['page_count'];
            $image_url = $info['metadata']['pages'][$page_index]['image_urls'][$size];
        }
        // Download illust image
        return $this->download($image_url);
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
            'client_id'      => self::client_id,
            'client_secret'  => self::client_secret,
            'grant_type'     => 'refresh_token',
            'refresh_token'  => $refresh_token
        );
        $resp = HTTP::post(self::token_url, $data, null);
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
        if($result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        return $result['response'][0];
    }

    private function download($image_url) {
        // download image
        $headers = array();
        $body = HTTP::get($image_url, array(
            'Referer' => self::referer_url
        ), function($ch, $raw_header) use (&$headers) {
            $len = strlen($raw_header);
            $fields = explode(':', $raw_header, 2);
            if(count($fields) == 2) {
                $name = trim($fields[0]);
                $value = trim($fields[1]);
                // Store specified headers
                if(strcasecmp($name, 'content-type') === 0 ||
                    strcasecmp($name, 'content-length') === 0 ||
                    strcasecmp($name, 'last-modified') === 0 ||
                    strcasecmp($name, 'cache-control') === 0 ||
                    strcasecmp($name, 'expires') === 0) {
                    $headers[$name] = $value;
                }
            }
            return $len;
        });
        // set special headers
        $file_name = basename(parse_url($image_url, PHP_URL_PATH));
        $headers['Content-Disposition'] = 'inline; filename="'.$file_name.'"';

        return array($headers, $body);
    }

}
