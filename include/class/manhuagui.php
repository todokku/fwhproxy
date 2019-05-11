<?php

class ManHuaGui implements Upstream {

    private const JsPattern = '/}\(\'([^\']+)\',\d+,\d+,\'([^\']+)\'.*\)/';
    private const KeyBaseChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const ImageHost = "https://us.hamreus.com";

    public function setup(array $args) {}

    public function fetch(array $args) {
        $book_id = intval($args['book_id']);
        $chapter_id = intval($args['chapter_id']);
        $page = array_key_exists('page', $args) ? intval($args['page']) - 1 : 0;
        if($page < 0) {
            $page = 0;
        }

        // fetch page content
        $page_url = 'https://tw.manhuagui.com/comic/' . $book_id . '/' . $chapter_id . '.html';
        $body = HTTP::get($page_url, null, null, null);
        // search key data
        $matches = array();
        preg_match(self::JsPattern, $body, $matches);
        // decode comic data
        $data = self::decodeData($matches[1], $matches[2]);
        $data = json_decode($data, true);
        // download image
        return $this->download($data, $page, $page_url);
    }

    private static function decodeData($template, $data) {
        // decode data
        $decorder = new LZString\Base64Decoder();
        $data = explode('|', $decorder->decompress($data));
        // create dict
        $dict = array();
        foreach ($data as $index => $value) {
            $key = self::encodeKey($index);
            if(empty($value)) {
                $dict[$key] = $key;
            } else {
                $dict[$key] = $value;
            }
        }
        // trim the template
        $start_pos = strpos($template, '(');
        $end_pos = strrpos($template, ').');
        $template = substr($template, $start_pos + 1, $end_pos - $start_pos - 1);
        // render the template
        $result = preg_replace_callback('/\b\w+\b/', function ($matches) use ($dict) {
            return $dict[$matches[0]];
        }, $template);
        return $result;
    }

    private static function encodeKey($index) {
        if($index === 0) {
            return "0";
        }
        $result = '';
        while($index !== 0) {
            $digit = $index % 62;
            $result = self::KeyBaseChars[$digit] . $result;
            $index = ($index - $digit) / 62;
        }
        return $result;
    }

    private function download($data, $page, $referer) {
        $filename = $data['files'][ $page % $data['len'] ];
        $image_url = self::ImageHost . $data['path'] . $filename . '?' .
            http_build_query(array(
                'cid' => $data['cid'],
                'md5' => $data['sl']['md5']
            ));
        $headers = array();
        $body = HTTP::get($image_url, array(
            'Referer' => $referer,
        ), function ($ch, $raw_header) use (&$headers) {
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

        return array(
            $headers, $body
        );
    }

}
