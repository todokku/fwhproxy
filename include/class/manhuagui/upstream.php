<?php

namespace ManHuaGui;

use LZString\Base64Decoder;
use \HTTP;

class Upstream implements \Upstream
{

    private const JsPattern = '/}\(\'([^\']+)\',\d+,\d+,\'([^\']+)\'.*\)/';
    private const KeyBaseChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const ImageHost = "https://us.hamreus.com";

    private const FormatJpeg = 'jpeg';
    private const FormatJpg = 'jpg';
    private const FormatGif = 'gif';
    private const FormatPng = 'png';
    private const SupportedFormats = array(self::FormatJpg, self::FormatJpeg, self::FormatGif, self::FormatPng);
    private const JpegQuality = 80;

    /**
     * @var DBA
     */
    private $dba;

    public function __construct(DBA $dba) {
        $this->dba = $dba;
    }

    public function setup(array $args) {}

    public function fetch(array $args) {
        $book_id = intval($args['book_id']);
        $chapter_id = intval($args['chapter_id']);
        // optional parameter - page
        $page = array_key_exists('page', $args) ? intval($args['page']) - 1 : 0;
        if ($page < 0) {
            $page = 0;
        }
        // optional parameter - format
        $format = array_key_exists('format', $args) ? $args['format'] : '';
        if (!in_array($format, self::SupportedFormats)) {
            $format = '';
        } elseif ($format == self::FormatJpg) {
            $format = self::FormatJpeg;
        }

        // get comic data
        $data = $this->dba->loadData($book_id, $chapter_id);
        if ($data === null) {
            $data = $this->fetchData($book_id, $chapter_id);
            $this->dba->storeData($book_id, $chapter_id, $data);
        }
        $data = json_decode($data, true);
        // download orignal image
        list($headers, $body) = $this->download($data, $page);
        if ($format != '') {
            $out_file = tempnam(sys_get_temp_dir(), 'mhg_');
            $this->convertImage($body, $format, $out_file);
            // update content-type
            $headers['content-type'] = 'image/' . $format;
            $headers['x-temp-file'] = $out_file;
            $body = file_get_contents($out_file);
        }
        return array($headers, $body);
    }

    private function fetchData($book_id, $chapter_id) {
        // fetch page content
        $page_url = 'https://tw.manhuagui.com/comic/' . $book_id . '/' . $chapter_id . '.html';
        $body = HTTP::get($page_url, null, null);
        // search key data
        $matches = array();
        preg_match(self::JsPattern, $body, $matches);
        return self::decodeData($matches[1], $matches[2]);
    }

    private static function decodeData($template, $data) {
        // decode data
        $decorder = new Base64Decoder();
        $data = explode('|', $decorder->decompress($data));
        // create dict
        $dict = array();
        foreach ($data as $index => $value) {
            $key = self::encodeKey($index);
            if (empty($value)) {
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

    private function download($data, $page) {
        $filename = $data['files'][$page % $data['len']];
        $image_url = self::ImageHost . $data['path'] . $filename . '?' .
            http_build_query(array(
                'cid' => $data['cid'],
                'md5' => $data['sl']['md5']
            ));
        $referer_url = 'https://tw.manhuagui.com/comic/' . $data['bid'] . '/' . $data['cid'] . '.html';
        $headers = array();
        $body = HTTP::get($image_url, array(
            'Referer' => $referer_url,
        ), function ($ch, $raw_header) use (&$headers) {
            $len = strlen($raw_header);
            $fields = explode(':', $raw_header, 2);
            if (count($fields) == 2) {
                $name = strtolower(trim($fields[0]));
                $value = trim($fields[1]);
                // Store specified headers
                if (strcmp($name, 'content-type') === 0 ||
                    strcmp($name, 'content-length') === 0 ||
                    strcmp($name, 'last-modified') === 0 ||
                    strcmp($name, 'cache-control') === 0 ||
                    strcmp($name, 'expires') === 0) {
                    $headers[$name] = $value;
                }
            }
            return $len;
        });
        // set filename
        $headers['content-disposition'] = 'inline; filename="' . $filename . '"';
        return array(
            $headers, $body
        );
    }

    private function convertImage(string $data, string $format, string $out_file) {
        // load image
        $data = 'data://text/plain;base64,' . base64_encode($data);
        $image = imagecreatefromwebp($data);
        // convert to format
        switch ($format) {
            case self::FormatJpeg:
            case self::FormatJpg:
                return imagejpeg($image, $out_file, self::JpegQuality);
            case self::FormatGif:
                return imagegif($image, $out_file);
            case self::FormatPng:
                return imagepng($image, $out_file);
            default:
                return false;
        }
    }

}
