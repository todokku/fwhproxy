<?php

namespace LZString;

class Dictionary {

    /**
     * @var array
     */
    private $entries;

    public function __construct() {
        $this->entries = array(0, 1, 2);
    }

    public function hasEntry($index) {
        return array_key_exists($index, $this->entries);
    }

    public function getEntry($index) {
        return $this->entries[$index];
    }

    public function addEntry($entry) {
        array_push($this->entries, $entry);
    }

    public function size() {
        return count($this->entries);
    }

}
