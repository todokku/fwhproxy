<?php

abstract class HTTP {

    const UserAgent = 'FWHAgent/1.0';

    private static function setCommonOpts($ch, $headers) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UserAgent);
        if($headers !== null && count($headers) !== 0) {
            $hs = array();
            foreach ($headers as $name => $value) {
                $hs[] = $name . ': '. $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hs);
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
            self::setCommonOpts($ch, $headers);
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
     * @param array|null $headers
     * @param callable|null $header_func
     * @return bool|string
     */
    public static function get(string $url, array $headers = null, $header_func = null) {
        $ch = curl_init($url);
        try{
            self::setCommonOpts($ch, $headers);
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
     * @param array $params
     * @param array|null $headers
     * @return bool|string
     */
    public static function post(string $url, array $params, array $headers = null) {
        $ch = curl_init($url);
        try {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            self::setCommonOpts($ch, $headers);
            $resp = curl_exec($ch);
        } finally {
            curl_close($ch);
        }
        return $resp;
    }

    /**
     * Make a GET request and return the response as a stream.
     * You should close the stream after using.
     *
     * @param string $url
     * @param array|null $headers
     * @return bool|resource
     */
    public static function getStream(string $url, array $headers = null) {
        $http_opts = array(
            'protocol_version' => 1.1,
            'user_agent' => self::UserAgent,
        );
        if($headers !== null) {
            $http_opts['header'] = array();
            foreach ($headers as $name => $value) {
                $http_opts['header'][] = $name . ': ' . $value;
            }
        }
        $context = stream_context_create( array('http' => $http_opts) );
        return fopen($url, 'r', null, $context);
    }

}
