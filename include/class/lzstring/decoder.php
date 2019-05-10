<?php

namespace LZString;

abstract class Decoder {

    protected abstract function getBaseValue(string $seed, int $index);

    protected abstract function getResetValue(): int;

    private function getBits(Data $data, string $seed, int $numBits) {
        $bits = 0;
        for($i = 0; $i < $numBits; $i ++) {
            $resb = $data->value & $data->position;
            $data->position >>= 1;
            if($data->position === 0) {
                $data->position = $this->getResetValue();
                $data->value = $this->getBaseValue($seed, $data->index++);
            }
            $bits |= ($resb > 0 ? 1 : 0) << $i;
        }
        return $bits;
    }

    public function decompress(string $input) {
        if($input === null || strlen($input) === 0) {
            return '';
        }

        // inner variables
        $dict = new Dictionary();
        $data = new Data($this->getBaseValue($input, 0), $this->getResetValue(), 1);

        // first character
        $flag = $this->getBits($data, $input, 2);
        if($flag !== 0 && $flag !== 1) {
            return '';
        }
        $numBits = 8 * ($flag + 1);
        $bits = $this->getBits($data, $input, $numBits);
        $first = Utils::chr($bits);
        $dict->addEntry($first);
        $word = $first;
        $result = [$first];

        // decompress
        $numBits = 3;
        $enlargeIn = 4;
        $entry = null;
        while (true) {
            $index = $this->getBits($data, $input, $numBits);
            if($index === 2) {
                return join('', $result);
            } else if($index === 0 || $index === 1) {
                $bits = $this->getBits($data, $input, 8 * ($index + 1));
                $index = $dict->size();
                $dict->addEntry( Utils::chr($bits) );
                $enlargeIn --;
            }
            if($enlargeIn === 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits ++;
            }

            if($dict->hasEntry($index)) {
                $entry = $dict->getEntry($index);
            } else {
                if( $index === $dict->size() ) {
                    $entry = $word . Utils::charAt($word, 0);
                } else {
                    return null;
                }
            }

            array_push($result, $entry);
            $dict->addEntry( $word . Utils::charAt($entry, 0) );
            $word = $entry;

            $enlargeIn --;
            if($enlargeIn === 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits ++;
            }
        }

    }

}