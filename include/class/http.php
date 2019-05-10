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
    }

    public static function get(string $url, $headers) {
        $ch = curl_init($url);
        self::set_opts($ch, $headers);
        $res = curl_exec($ch);
        return $res;
    }

    public static function post(string $url, $params, $headers) {
        $ch = curl_init($url);
        $data = http_build_query($params);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if($headers === null) {
            $headers = array();
        }
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['Content-Length'] = strlen($data);
        self::set_opts($ch, $headers);
        $res = curl_exec($ch);
        return $res;
    }

}
