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
        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8118');
        //curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    public static function get(string $url, $headers, $header_func) {
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

    public static function post(string $url, $params, $headers) {
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
