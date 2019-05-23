<?php

abstract class StreamUtils {

    private const BufferSize = 4096;

    /**
     * Transfer data from source stream to destination stream.
     *
     * @param resource $dest
     * @param resource $src
     * @param bool $auto_close <p>Auto close the source stream after done.</p>
     */
    public static function transfer($dest, $src, bool $auto_close=true) {
        while(!feof($src)) {
            fwrite($dest, fread($src, self::BufferSize));
            fflush($dest);
        }
        if($auto_close) {
            fclose($src);
        }
    }
}