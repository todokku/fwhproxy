<?php

namespace LZString;

class Base64Decoder extends Decoder {

    private const BaseChars =  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    /**
     * @var array
     */
    private $baseDict = array();

    public function __construct() {
        // init base dictionary
        for($i = 0; $i < strlen(self::BaseChars); $i++) {
            $this->baseDict[self::BaseChars[$i]] = $i;
        }
    }

    protected function getBaseValue(string $seed, int $index) {
        $ch = $seed[$index];
        return $this->baseDict[$ch];
    }

    protected function getResetValue(): int {
        return 32;
    }

}
