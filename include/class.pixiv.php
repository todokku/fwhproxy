<?php

class Pixiv implements Upstream {

    // Upstream name
    const name = 'pixiv';
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
    private $storage;

    // The access_token using for call API
    private $access_token = self::builtin_access_token;

    public function __construct(Storage $storage, $need_oauth=false) {
        $this->storage = $storage;
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
        $data = http_build_query(array(
            'get_secure_url' => '1',
            'client_id'      => self::client_id,
            'client_secret'  => self::client_secret,
            'grant_type'     => 'password',
            'username'       => $username,
            'password'       => $password,

        ));
        $ch = curl_init(self::token_url);
        curl_setopt($ch, CURLOPT_USERAGENT, self::user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '.strlen($data),
        ));
        $resp = curl_exec($ch);
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
        $this->storage->init();
        $this->storage->save_config(self::name, $config);
    }

    public function fetch(array $args) {
        // Get parameters
        $illust_id = intval($args['illust_id'], 10);
        $page = array_key_exists('page', $args) ? intval($args['page'], 10) : 1;
        $size = array_key_exists('size', $args) ? strtolower($args['size']) : 'large';
        if($size != 'medium' && $size != 'large') {
            $size = 'large';
        }

        // Init oauth
        $this->init_oauth();
        // Get illust info
        $info = $this->fetch_info($illust_id);
        // Select page
        $image_url = $info['image_urls'][$size];
        if($info['metadata'] !== null) {
            $page_index = ($page - 1) % $info['page_count'];
            $image_url = $info['metadata']['pages'][$page_index]['image_urls'][$size];
        }
        // Download illust image
        return $this->download($image_url);
    }

    private function init_oauth() {
        if(!$this->need_oauth || $this->storage === null) {
            return;
        }
        // load config
        $config = $this->storage->load_config(self::name);
        if($config === null) {
            return;
        }
        if(empty($config['access_token']) || $config['token_expiry'] < time()) {
            // refresh token
            $config = $this->refresh_token($config['refresh_token']);
            // store tokens
            $this->storage->save_config(self::name, $config);
        }
        $this->access_token = $config['access_token'];
    }

    private function refresh_token($refresh_token) {
        $data = http_build_query(array(
            'get_secure_url' => '1',
            'client_id'      => self::client_id,
            'client_secret'  => self::client_secret,
            'grant_type'     => 'refresh_token',
            'refresh_token'  => $refresh_token
        ));
        $ch = curl_init(self::token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '.strlen($data),
        ));
        $resp = curl_exec($ch);
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

    private function fetch_info($illust_id) {
        // fetch all available sizes
        $info_url = 'https://public-api.secure.pixiv.net/v1/works/'.$illust_id.'.json?image_sizes=medium%2Clarge';
        $ch = curl_init($info_url);
        curl_setopt($ch, CURLOPT_USERAGENT, self::user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '.$this->access_token
        ));
        $resp = curl_exec($ch);
        $result = json_decode($resp, true);
        if($result['has_error']) {
            throw new Exception($result['errors']['system']['message']);
        }
        return $result['response'][0];
    }

    private function download($image_url) {
        $headers = array();
        // download image and store headers
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, self::referer_url);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $raw_header) use (&$headers) {
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
        $body = curl_exec($ch);
        // set special headers
        $file_name = basename(parse_url($image_url, PHP_URL_PATH));
        $headers['Content-Disposition'] = 'inline; filename="'.$file_name.'"';

        return array($headers, $body);
    }

}
