<?php

namespace ManHuaGui;

use DB\CacheIO;
use DB\Session;
use Utils\HTTP;
use ImageConvertor;
use LZString\Base64Decoder;
use Metadata;
use ProxyException;

class Upstream implements \Upstream {

    private const JsPattern = '/}\(\'([^\']+)\',\d+,\d+,\'([^\']+)\'.*\)/';
    private const KeyBaseChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const ImageHost = "https://us.hamreus.com";

    /**
     * @var CacheIO
     */
    private $cache = null;

    public function __construct(Session $session = null) {
        $this->cache = new CacheIO($session);
    }

    public function download(\Options $opts, Metadata &$metadata) {
        if(!($opts instanceof Options)) {
            throw new ProxyException("Bad Arguments", 400);
        }

        // get chapter data
        $data = $this->getData($opts->book_id, $opts->chapter_id);
        if($data === null){
            return null;
        }
        // download image
        $image = $this->getImage($data, $opts->page, $metadata);
        // fix content size
        if($metadata !== null && $metadata->size === 0) {
            $metadata->size = strlen($image);
        }
        // convert format
        if($opts->format != '') {
            $image = $this->convert($image, $opts->format, $metadata);
            $metadata->filename = $opts->book_id . '_' . $opts->chapter_id . '_' . $opts->page . '.' . $opts->format;
        }

        return $image;
    }

    private function getData($book_id, $chapter_id) {
        $cache_key = join('_', array('mhg', 'data', $book_id, $chapter_id));

        $data = null;
        // get data from cache
        $data = $this->cache->get($cache_key);
        // parse from upstream
        if($data === null) {
            $data = $this->fetchData($book_id, $chapter_id);
            if($data !== null) {
                $this->cache->put($cache_key, $data);
            }
        }
        if($data === null) {
            throw new ProxyException("Bad Request", 400);
        }
        return json_decode($data, true);
    }

    private function fetchData($book_id, $chapter_id) {
        // fetch page content
        $page_url = 'https://tw.manhuagui.com/comic/' . $book_id . '/' . $chapter_id . '.html';
        $body = HTTP::get($page_url);
        // search key data
        $matches = array();
        preg_match(self::JsPattern, $body, $matches);
        if(count($matches) === 0) {
            return null;
        }
        // decode data & fill dict
        $decorder = new Base64Decoder();
        $data = explode('|', $decorder->decompress($matches[2]));
        $dict = array();
        foreach ($data as $index => $value) {
            $key = self::encodeKey($index);
            if (empty($value)) {
                $dict[$key] = $key;
            } else {
                $dict[$key] = $value;
            }
        }
        // trim template
        $template = $matches[1];
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
        if ($index === 0) {
            return "0";
        }
        $result = '';
        while ($index !== 0) {
            $digit = $index % 62;
            $result = self::KeyBaseChars[$digit] . $result;
            $index = ($index - $digit) / 62;
        }
        return $result;
    }

    private function getImage($data, $page, Metadata &$metadata) {
        // image url and referer url
        $filename = $data['files'][($page - 1) % $data['len']];
        $image_url = self::ImageHost . $data['path'] . $filename . '?' .
            http_build_query(array(
                'cid' => $data['cid'],
                'md5' => $data['sl']['md5']
            ));
        $referer_url = 'https://tw.manhuagui.com/comic/' . $data['bid'] . '/' . $data['cid'] . '.html';
        // get image content
        $headers = array();
        $image = HTTP::get($image_url, array(
            'Referer' => $referer_url,
        ), $headers);
        if($image === false) {
            return null;
        }
        // fill metadata
        $metadata->filename = $filename;
        $metadata->mimetype = $headers['content-type'];
        $metadata->size = array_key_exists('content-length', $headers) ?
            intval($headers['content-length']) : strlen($image);
        return $image;
    }

    private function convert(string $image, string $format, Metadata &$metadata): string {
        switch ($format) {
            case Options::FormatJpeg:
            case Options::FormatJpg:
                $metadata->mimetype = 'image/jpeg';
                $image = ImageConvertor::toJpeg($image);
                break;
            case Options::FormatGif:
                $metadata->mimetype = 'image/gif';
                $image = ImageConvertor::toGif($image);
                break;
            case Options::FormatPng:
                $metadata->mimetype = 'image/png';
                $image = ImageConvertor::toPng($image);
                break;
        }
        $metadata->size = strlen($image);
        return $image;
    }

}
