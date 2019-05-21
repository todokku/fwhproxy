<?php

namespace Pixiv;

class Options {

    public const SizeLarge = 'large';
    public const SizeMedium = 'medium';
    public const SizeAuto = 'auto';
    private const AvailableSizes = array(self::SizeLarge, self::SizeMedium, self::SizeAuto);

    /**
     * @var int
     */
    public $illust_id;
    /**
     * @var int
     */
    public $page;
    /**
     * @var string
     */
    public $size;

    public static function parse(array $args): Options {
        $opts = new Options();
        // parse illust id
        $opts->illust_id = intval($args['illust_id'], 10);
        // parse page
        $opts->page = array_key_exists('page', $args) ? intval($args['page'], 10) : 1;
        // parse size
        $opts->size = array_key_exists('size', $args) ?
            strtolower($args['size']) : self::SizeLarge;
        if(!in_array($opts->size, self::AvailableSizes)) {
            $opts->size = self::SizeLarge;
        }

        return $opts;
    }

}