<?php

namespace LZString;

class Data {

    public $value;
    public $position;
    public $index;

    public function __construct($value, $position, $index) {
        $this->value = $value;
        $this->position = $position;
        $this->index = $index;
    }

}