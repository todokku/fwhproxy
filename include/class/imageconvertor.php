<?php

class ImageConvertor {

    private const DefaultJpegQuality = 80;

    private const FormatJpeg = "jpeg";
    private const FormatGif = "gif";
    private const FormatPng = "png";

    /**
     * Convert image to JPEG format.
     *
     * @param string $image
     * @param int $quality
     * @return string
     * @throws ImagickException
     */
    public static function toJpeg(string $image, int $quality=self::DefaultJpegQuality): string {
        if($quality <= 0 || $quality > 100) {
            $quality = self::DefaultJpegQuality;
        }

        $im = new Imagick();
        $im->readImageBlob($image);
        $im->setImageFormat(self::FormatJpeg);
        $im->setImageCompressionQuality($quality);
        return $im->getImageBlob();
    }

    /**
     * Save image to file in JPEG format.
     *
     * @param string $filename
     * @param string $image
     * @param int $quality
     * @return bool
     * @throws ImagickException
     */
    public static function toJpegFile(string $filename, string $image, int $quality=self::DefaultJpegQuality): bool {
        if($quality <= 0 || $quality > 100) {
            $quality = self::DefaultJpegQuality;
        }

        $im = new Imagick();
        $im->readImageBlob($image);
        $im->setImageFormat(self::FormatJpeg);
        $im->setImageCompressionQuality($quality);
        return $im->writeImage($filename);
    }

    /**
     * Convert image to GIF format.
     *
     * @param string $image
     * @return string
     * @throws ImagickException
     */
    public static function toGif(string $image): string {
        $im = new Imagick();
        $im->readImageBlob($image);
        $im->setImageFormat(self::FormatGif);
        $im->setImageCompression(Imagick::COMPRESSION_LZW);
        return $im->getImageBlob();
    }

    /**
     * Save image to file in GIF format.
     *
     * @param string $filename
     * @param string $image
     * @return bool
     * @throws ImagickException
     */
    public static function toGifFile(string $filename, string $image): bool {
        $im = new Imagick();
        $im->readImageBlob($image);
        $im->setImageFormat(self::FormatGif);
        $im->setImageCompression(Imagick::COMPRESSION_LZW);
        return $im->writeImage($filename);
    }

    /**
     * Convert image to PNG format.
     *
     * @param string $image
     * @return string
     * @throws ImagickException
     */
    public static function toPng(string $image): string {
        $im = new Imagick();
        $im->readImageBlob($image);
        $im->setImageFormat(self::FormatPng);
        $im->setOption('png:compression-level', 9);
        return $im->getImageBlob();
    }

    /**
     * Save image to file in PNG format.
     *
     * @param string $filename
     * @param string $image
     * @return bool
     * @throws ImagickException
     */
    public static function toPngFile(string $filename, string $image): bool {
        $im = new Imagick();
        $im->readImageBlob($image);
        $im->setImageFormat(self::FormatPng);
        $im->setOption('png:compression-level', 9);
        return $im->writeImage($filename);
    }

}
