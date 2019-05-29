<?php

namespace Utils;

use CURLFile;

final class UploadFile {

    private $filename;
    private $mimetype;

    /**
     * UploadFile constructor.
     *
     * @param string|resource $data
     * @param string|null $mimetype
     */
    public function __construct($data, string $mimetype = null) {
        // store data to temp file
        $tmp_file = tempnam(sys_get_temp_dir(), "telegram_");
        file_put_contents($tmp_file, $data);
        if(gettype($data) == 'resource') {
            fclose($data);
        }
        // fill fields
        $this->filename = $tmp_file;
        if($mimetype === null) {
            $this->mimetype = mime_content_type($tmp_file);
        } else {
            $this->mimetype = $mimetype;
        }
    }

    public function toCURLFile(string $fieldname = null): CURLFile {
        return new CURLFile($this->filename, $this->mimetype, $fieldname);
    }

    public function clean() {
        unlink($this->filename);
    }

}