<?php

namespace Telegraph;

use Utils\HTTP;
use Utils\UploadFile;

final class Uploader implements \Uploader  {

    private const UploadUrl = 'https://telegra.ph/upload';
    private const RefererUrl = 'https://telegra.ph/';

    public function upload($data, string $mimetype = null): ?string {
        $upload_file = new UploadFile($data, $mimetype);
        $form = array(
            'file' => $upload_file->toCURLFile()
        );
        $resp = HTTP::upload(self::UploadUrl, $form, array(
            'Referer' => self::RefererUrl
        ));
        $upload_file->clean();

        if($resp === false) {
            return null;
        } else {
            $result = json_decode($resp, true);
            return $result[0]['src'];
        }
    }
}