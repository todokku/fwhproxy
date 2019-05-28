<?php

namespace Telegram;

use Exception;
use Utils\HTTP;
use Utils\UploadFile;

final class Uploader implements \Uploader {

    private const APISendPhoto = "https://api.telegram.org/bot%s/sendPhoto";
    private const APIDeleteMessage = "https://api.telegram.org/bot%s/deleteMessage";

    private $token;
    private $chat_id;

    public function __construct() {
        $this->token = _TELEGRAM_TOKEN;
        $this->chat_id = _TELEGRAM_TOKEN;
    }

    public function upload($data, string $mimetype = null): ?string {
        // upload photo file
        $message = $this->sendPhoto(new UploadFile($data, $mimetype));
        // search the highest quality photo file
        $best_file_id = null;
        $max_size = 0;
        foreach ($message['photo'] as $photo) {
            if($photo['file_size'] > $max_size) {
                $best_file_id = $photo['file_id'];
            }
        }
        // delete message
        $this->deleteMessage($message['message_id']);
        return $best_file_id;
    }

    private function sendPhoto(UploadFile $upload_file) {
        // upload
        $url = sprintf(self::APISendPhoto, $this->token);
        $form = array(
            'chat_id' => $this->chat_id,
            'photo' => $upload_file->toCURLFile()
        );
        $resp = HTTP::upload($url, $form);
        // delete temp file
        $upload_file->clean();

        // check result
        if($resp === false) {
            throw new Exception("Call telegram API failed!");
        }
        $result = json_decode($resp, true);
        if(!$result['ok']) {
            throw new Exception("sendPhoto error: " . $result['description']);
        }

        return $resp['result'];
    }

    private function deleteMessage($message_id) {
        $url = sprintf(self::APIDeleteMessage, $this->token);
        $params = array(
            'chat_id' => $this->chat_id,
            'message_id' => $message_id,
        );
        $resp = HTTP::post($url, $params);
        // check result
        if($resp === false) {
            throw new Exception("Call telegram API failed!");
        }
        $result = json_decode($resp, true);
        if(!$result['ok']) {
            throw new Exception("deleteMessage error: " . $result['description']);
        }
    }

}