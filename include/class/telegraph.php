<?php

class Telegraph {

    private const UploadUrl = 'https://telegra.ph/upload';

    /**
     * Upload file to telegraph
     *
     * @param string $filename
     * @param string|null $mimetype
     * @return string|null
     */
    public static function uploadFile(string $filename, ?string $mimetype = null): ?string {
        if($mimetype === null || empty($mimetype)) {
            $mimetype = mime_content_type($filename);
        }
        $curlfile = new CURLFile($filename, $mimetype, 'blob');
        $form = array(
            'file' => $curlfile
        );
        $resp = HTTP::post(self::UploadUrl, $form, null);
        if($resp === false) {
            return null;
        } else {
            $result = json_decode($resp, true);
            return $result[0]['src'];
        }
    }

    /**
     * Upload stream as a file to telegraph
     *
     * @param resource $stream
     * @return string|null
     */
    public static function uploadStream(resource $stream): ?string {
        // write stream to tempfile
        $tmpfile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmpfile, $stream);
        fclose($stream);
        try {
            // upload file
            $result = self::uploadFile($tmpfile);
        } finally {
            // remove tempfile
            unlink($tmpfile);
        }
        return $result;
    }
}
