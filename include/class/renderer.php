<?php

class Renderer {

    private $status_code;
    private $headers;
    private $body;

    public function __construct() {
        $this->status_code = 200;
        $this->headers = [];
    }

    public function set_status_code($code) {
        $this->status_code = $code;
    }

    public function add_header($name, $value) {
        $name = strtolower($name);
        if(array_key_exists($name, $this->headers)) {
            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = [$value];
        }
    }

    public function set_body($body) {
        $this->body = $body;
    }

    public function render() {
        // Output status code
        http_response_code($this->status_code);
        // Set response header
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header($name.": ".$value, false);
            }
        }
        // Write response body
        $out = fopen('php://output', 'w');
        switch (gettype($this->body)) {
            case 'string':
                fwrite($out, $this->body);
                break;
            case 'resource':
                self::copy_stream($this->body, $out, true);
                break;
        }
        fclose($out);

    }

    private static function copy_stream($src, $dest, bool $auto_close=false) {
        while(!feof($src)) {
            fwrite($dest, fread($src, 8192));
            fflush($dest);
        }
        if($auto_close) {
            fclose($src);
        }
    }

}
