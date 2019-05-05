<?php

class Pixiv {

    const name = 'pixiv';
    const token_url = "https://oauth.secure.pixiv.net/auth/token";
    const referer_url = "https://app-api.pixiv.net/";

    const client_id = "MOBrBDS8blbauoSck0ZfDbtuzpyT";
    const client_secret = "lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj";
    const builtin_access_token = '8mMXXWT9iuwdJvsVIvQsFYDwuZpRCMePeyagSh30ZdU';

    private $access_token = self::builtin_access_token;
    private $headers = array();
    private $body = null;

    public function __construct($db, $login=false) {
        if($login) {
            // Query config from database
            $config = $db->get_config(self::name);
            if($config == null || empty($config['access_token']) || $config['token_expiry'] < time()) {
                $config = $this->refresh_access_token($config['refresh_token']);
                $db->set_config(self::name, $config);
            }
            $this->access_token = $config['access_token'];
        }
    }

    private function refresh_access_token($refresh_token) {
        $data = http_build_query(array(
            'get_secure_url' => '1',
            'client_id' => self::client_id,
            'client_secret' => self::client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        ));
        $ch = curl_init(self::token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '.strlen($data),
        ));
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        return array(
            'access_token' => $result['response']['access_token'],
            'refresh_token' => $result['response']['refresh_token'],
            'token_expiry' => $result['response']['expires_in'] + time()
        );
    }

    private function oauth_get($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '.$this->access_token,
        ));
        $result = curl_exec($ch);
        return $result;
    }

    private function download($url) {
        $that = $this;
        // download image and store headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, self::referer_url);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $raw_header) use (&$that) {
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
                    $that->headers[$name] = $value;
                }
            }
            return $len;
        });
        $this->body = curl_exec($ch);
    }

    public function fetch($args) {
        // Get parameters
        $illust_id = intval($args['illust_id'], 10);
        $page = array_key_exists('page', $args) ? intval($args['page'], 10) : 1;

        // Get image info
        $info_url = 'https://public-api.secure.pixiv.net/v1/works/'.$illust_id.'.json?image_sizes=large';
        $result = $this->oauth_get($info_url);
        // Parse result
        $info = json_decode($result, true);
        $info = $info['response'][0];
        // Select image
        $image_url = $info['image_urls']['large'];
        if($info['metadata'] !== null) {
            $page_index = ($page - 1) % $info['page_count'];
            $image_url = $info['metadata']['pages'][$page_index]['image_urls']['large'];
        }
        // Download image
        $this->download($image_url);

        // Set special headers
        $this->headers['X-Upstream-URL'] = $image_url;
        $file_name = basename(parse_url($image_url, PHP_URL_PATH));
        $this->headers['Content-Disposition'] = 'inline; filename="'.$file_name.'"';
    }

    public function get_headers() {
        return $this->headers;
    }

    public function get_body() {
        return $this->body;
    }

}
