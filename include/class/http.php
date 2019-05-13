<?php

abstract class HTTP {

    const UserAgent = 'FWHAgent/1.0';

    private static function set_opts($ch, $headers) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UserAgent);
        if($headers !== null && count($headers) !== 0) {
            $hs = array();
            foreach ($headers as $name => $value) {
                $hs[] = $name . ': '. $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hs);
        }
        // for debug
//        curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8118');
//        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    /**
     * Perform a HEAD request to target URL.
     * Return the response headers in array, all header names
     * will be converted into lower case.
     *
     * @param string $url: Target URL.
     * @param $headers: Request headers, can be array or null.
     * @return array
     */
    public static function head(string $url, ?array $headers): array {
        $out_headers = array();
        $ch = curl_init($url);
        try{
            self::set_opts($ch, $headers);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $raw_header) use (&$out_headers) {
                $len = strlen($raw_header);
                $fields = explode(':', $raw_header, 2);
                if(count($fields) == 2) {
                    $name = strtolower(trim($fields[0]));
                    $value = trim($fields[1]);
                    $out_headers[$name] = $value;
                }
                return $len;
            });
            curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $out_headers;
    }

    /**
     * Perform a GET request to target URL.
     *
     * @param string $url : target URL
     * @param $headers
     * @param $header_func
     * @return bool|string
     */
    public static function get(string $url, ?array $headers, $header_func) {
        $ch = curl_init($url);
        try{
            self::set_opts($ch, $headers);
            if($header_func !== null) {
                curl_setopt($ch, CURLOPT_HEADERFUNCTION, $header_func);
            }
            $resp = curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $resp;
    }

    /**
     * Perform a POST request to target URL.
     *
     * @param string $url
     * @param $params
     * @param $headers
     * @return bool|string
     */
    public static function post(string $url, ?array $params, ?array $headers) {
        $ch = curl_init($url);
        try {
            $data = http_build_query($params);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            if($headers === null) {
                $headers = array();
            }
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $headers['Content-Length'] = strlen($data);
            self::set_opts($ch, $headers);
            $resp = curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $resp;
    }

}
