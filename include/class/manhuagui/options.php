<?php

namespace ManHuaGui;

class Options extends \Options {

    public const FormatJpeg = "jpeg";
    public const FormatJpg = "jpg";
    public const FormatGif = "gif";
    public const FormatPng = "png";

    private const SupportedFormats = array(
        self::FormatJpeg, self::FormatJpg,
        self::FormatGif, self::FormatPng
    );

    /**
     * @var int
     */
    public $book_id;
    /**
     * @var int
     */
    public $chapter_id;
    /**
     * @var int
     */
    public $page;
    /**
     * @var string
     */
    public $format;

    /**
     * Parse arguments
     *
     * @param array $args
     * @return Options
     */
    public static function parse(array $args): Options {
        $opts = new Options();
        // required parameters
        $opts->book_id = intval($args['book_id']);
        $opts->chapter_id = intval($args['chapter_id']);
        $opts->page = intval($args['page']);
        // optional parameter - format
        $format = array_key_exists('format', $args) ? $args['format'] : '';
        if (!in_array($format, self::SupportedFormats)) {
            $format = '';
        }
        $opts->format = $format;

        return $opts;
    }

    public function cacheKey(): string {
        return join('_', array(
            'mhg',
            $this->book_id, $this->chapter_id,
            $this->page, $this->format
        ));
    }

}