<?php

namespace LZString;

abstract class Utils {

    public static function charAt($text, $index) {
        return mb_substr($text, $index, 1);
    }

    public static function chr($code) {
        return mb_convert_encoding('&#' . $code . ';', 'UTF-8', 'HTML-ENTITIES');
    }

}
