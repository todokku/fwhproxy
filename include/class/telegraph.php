<?php

abstract class Telegraph {

    private const UploadUrl = 'https://telegra.ph/upload';
    private const RefererUrl = 'https://telegra.ph/';

    /**
     * Upload a file to telegraph
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
        $headers = array(
            'Referer' => self::RefererUrl
        );
        $resp = HTTP::upload(self::UploadUrl, $form, $headers);
        if($resp === false) {
            return null;
        } else {
            $result = json_decode($resp, true);
            return $result[0]['src'];
        }
    }

    /**
     * Upload data to telegraph
     *
     * @param string|resource $data <p>
     * Data to upload, can be string or stream. When pass a stream, it will be closed after using.
     * </p>
     * @param string|null $mimetype <p>
     * The mimetype of the data. If pass null, will be detected by mime_content_type() function.
     * </p>
     * @return string|null
     */
    public static function uploadData($data, ?string $mimetype = null): ?string {
        // write blob to tempfile
        $tmpfile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmpfile, $data);
        if(gettype($data) === 'resource') {
            fclose($data);
        }
        try {
            // upload file
            $result = self::uploadFile($tmpfile, $mimetype);
        } finally {
            // remove tempfile
            unlink($tmpfile);
        }
        return $result;
    }
}
