<?php

class ImageConvertor {

    private const DefaultJpegQuality = 80;

    private const FormatJpeg = "jpeg";

    /**
     * Convert image to jpeg format.
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
     * Convert image to jpeg format and save to file.
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

}
