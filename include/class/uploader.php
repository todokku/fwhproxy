<?php

interface Uploader {

    /**
     * Upload to remote server, return the upload result.
     *
     * @param string|resource $data
     * @param string|null $mimetype
     * @return string|null
     */
    public function upload($data, string $mimetype = null): ?string;

}
