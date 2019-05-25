<?php

final class ProxyException extends Exception {

    private $statusCode;

    public function __construct($message = "", $status_code = 0, Throwable $cause = null) {
        parent::__construct($message, 0, $cause);
        $this->statusCode = $status_code;
    }

    public function getStatusCode():int {
        return $this->statusCode;
    }

}
