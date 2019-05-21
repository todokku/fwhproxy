<?php

abstract class HTTP {

    const UserAgent = 'FWHAgent/1.0';

    /**
     * Set common options
     *
     * @param resource $ch
     * @param array|null $headers
     * @param array|null $out_headers
     */
    private static function setStandardOpts($ch, array $headers = null, array &$out_headers = null) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UserAgent);
        if($headers !== null && count($headers) !== 0) {
            $hs = array();
            foreach ($headers as $name => $value) {
                $hs[] = $name . ': '. $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hs);
        }
        if($out_headers !== null) {
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $raw_header) use (&$out_headers) {
                $len = strlen($raw_header);
                $fields = explode(':', $raw_header, 2);
                if(count($fields) == 2) {
                    $name = strtolower(trim($fields[0]));
                    $value = trim($fields[1]);
                    $out_headers[$name] = $value;
                }
                return $len;
            });
        }
    }

    /**
     * Perform a HEAD request to target URL.
     * Return the response headers in array, all header names
     * will be converted into lower case.
     *
     * @param string $url: Target URL.
     * @param array|null $headers: Request headers.
     * @return array
     */
    public static function head(string $url, array $headers = null): array {
        $out_headers = array();
        $ch = curl_init($url);
        try{
            self::setStandardOpts($ch, $headers, $out_headers);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $out_headers;
    }

    /**
     * Perform a GET request to target URL.
     *
     * @param string $url
     * @param array|null $headers
     * @param array|null $out_headers
     * @return bool|string
     */
    public static function get(string $url, array $headers = null, array &$out_headers = null) {
        $ch = curl_init($url);
        try{
            self::setStandardOpts($ch, $headers, $out_headers);
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
     * @param array $params
     * @param array|null $headers
     * @return bool|string
     */
    public static function post(string $url, array $params, array $headers = null) {
        $ch = curl_init($url);
        try {
            // encode parameters
            $data = http_build_query($params);
            if($headers === null) {
                $headers = array();
            }
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $headers['Content-Length'] = strlen($data);
            self::setStandardOpts($ch, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $resp = curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $resp;
    }

    /**
     * Perform a POST request for uploading.
     *
     * @param string $url
     * @param array $form
     * @param array|null $headers
     * @return bool|string
     */
    public static function upload(string $url, array $form, array $headers = null) {
        $ch = curl_init($url);
        try {
            self::setStandardOpts($ch, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
            $resp = curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $resp;
    }

}
