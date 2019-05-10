<?php

class ManHuaGui implements Upstream {

    private const JsPattern = '/}\(\'([^\']+)\',\d+,\d+,\'([^\']+)\'.*\)/';

    private const KeyBaseChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function setup(array $args) {}

    public function fetch(array $args) {
        $comicId = intval($args['comic_id']);
        $chapterId = intval($args['chapter_id']);
        $page = intval($args['page']);

        // fetch page content
        $page_url = 'https://tw.manhuagui.com/comic/'.$comicId.'/'.$chapterId.'.html';
        $body = HTTP::get($page_url, null);
        // search key data
        $matches = array();
        preg_match(self::JsPattern, $body, $matches);
        // decode comic data
        $data = self::decodeData($matches[1], $matches[2]);
        // FIXME: Currently just return the decode result
        return array(
            array(), $data
        );
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
        // render the template
        $result = preg_replace_callback('/\b\w+\b/', function ($matches) use ($dict) {
            return $dict[$matches[0]];
        }, $template);
        // TODO: extract JSON from the result
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


}
