<?php

class Metadata {

    private const DefaultMimetype = "application/octet-stream";

    /**
     * @var string
     */
    public $filename;

    /**
     * @var string
     */
    public $mimetype = self::DefaultMimetype;

    /**
     * @var int
     */
    public $size;

}