<?php

class HttpResponse {

    private $status_code;
    private $headers;
    private $body;

    public function __construct() {
        $this->headers = [];
    }

    public function set_status_code($code) {
        $this->status_code = $code;
    }

    public function get_status_code() {
        return $this->status_code;
    }

    public function add_header($name, $value) {
        $name = strtolower(trim($name));
        $value = trim($value);
        if(array_key_exists($name, $this->headers)) {
            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = [$value];
        }
    }

    public function get_header($name) {
        $name = strtolower(trim($name));
        if(array_key_exists($name, $this->headers)) {
            return $this->headers[$name][0];
        } else {
            return "";
        }
    }

    public function get_headers() {
        return $this->headers;
    }

    public function set_body($body) {
        $this->body = $body;
    }

    public function get_body() {
        return $this->body;
    }

}

class HttpClient {

    private $cookie_str = "";

    public function __construct($cookie) {
        $buf = array();
        foreach ($cookie as $k => $v) {
            array_push($buf, $k . "=" . $v);
        }
        $this->cookie_str = join("; ", $buf);
    }

    public function get($url, $referer=null) {
        // Http response object
        $resp = new HttpResponse();
        // Setup curl
        $ch = curl_init($url);
        // for debugging
//        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
//        curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1');
//        curl_setopt($ch, CURLOPT_PROXYPORT, '8118');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie_str);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$resp) {
            $len = strlen($header);
            $fields = explode(':', $header, 2);
            if(count($fields) == 2) {
                $resp->add_header($fields[0], $fields[1]);
            }
            return $len;
        });
        if($referer !== null) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        // Perform request
        $body = curl_exec($ch);
        if ($body) {
            $info = curl_getinfo($ch);
            $resp->set_status_code($info["http_code"]);
            $resp->set_body($body);
        } else {
            $resp = null;
        }
        curl_close($ch);
        return $resp;
    }

}
