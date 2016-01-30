<?php

/**
 * by TeNNoX
 */
class ImgSteward
{

    const src_path = 'site/static/img/';
    const cache_path = 'cache/img/';

    const debug = false;

    /**
     * Serve the requested image in the requested size.
     * If it is cached, serve the cached version,
     * if a downscaled version is needed, generate it.
     *
     * @param string $img_path The image path relative to src_path
     * @param int $width The requested width, or null if original is wanted
     */
    public static function serve($img_path, $width)
    {
        $full_src_path = self::src_path . $img_path;
        $full_cache_path = self::cachePath($img_path, $width);

        if (!is_file($full_src_path)) {
            http_response_code(404);
            echo "File not found: $full_src_path<br>";
            exit;
        }
        if (self::debug)
            echo "memory limit: " . ini_get("memory_limit") . "<br>"; // TODO:? set memory limit up

        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            if (self::debug)
                echo "GD library not available :/<br>Serving original<br>";
            self::serve_file($full_src_path);
            return;
        }

        // get source image dimensions
        list($src_width, $src_height) = getimagesize($full_src_path);
        if (self::debug)
            echo "src={$src_width}x{$src_height}<br>";

        if ($src_width <= $width || $width == null) { // it's unnecessary to generate it, if the source is sufficient
            if (self::debug)
                echo "Serving original, it's sufficient for the requested width ($width)<br>";
            self::serve_file($full_src_path);
            return;
        } else {
            if (!is_file($full_cache_path)) { // no cached file? generate it!
                $height = ($src_height / $src_width) * $width;
                if (self::debug)
                    echo "new={$width}x{$height}<br>";

                self::generate($full_src_path, $full_cache_path, $src_width, $src_height, $width, $height);
            } else {
                //$cacheHash = hash_file('crc32', $full_src_path) TODO: image change detection
            }
        }

        self::serve_file($full_cache_path);
    }

    private static function generate($src, $cachePath, $src_width, $src_height, $width, $height)
    {
        $img = imagecreatefromjpeg($src);
        $newimg = imagecreatetruecolor($width, $height);

        imagecopyresampled($newimg, $img, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
        imagedestroy($img);

        /*$text_color = imagecolorallocate($newimg, 255, 0, 0);
        imagestring($newimg, 40, $width * 0.25, $height * 0.25, "$width", $text_color);
        imagestring($newimg, 40, $width * 0.75, $height * 0.25, "$width", $text_color);
        imagestring($newimg, 40, $width * 0.25, $height * 0.75, "$width", $text_color);
        imagestring($newimg, 40, $width * 0.75, $height * 0.75, "$width", $text_color);
        imagestring($newimg, 40, $width / 2, $height / 2, "$width", $text_color);*/

        self::mkdirs($cachePath);
        imagejpeg($newimg, $cachePath); //TODO: set quality?
        imagedestroy($newimg);
    }

    private static function serve_file($full_path, $src_info = null)
    {
        if (!$src_info) // allow passing a cached instance, if not, generate it.
            $src_info = getimagesize($full_path);

        if (self::debug)
            echo "serving '$full_path' as <b>" . $src_info['mime'] . "</b><br>";
        else {
            header("Content-type: " . $src_info['mime']);
            readfile($full_path);
        }
        exit;
    }


    private static function cachePath($img_path, $width)
    {
        $without_extension = implode('.', explode('.', $img_path, -1));
        return self::cache_path . $without_extension . '-' . $width . '.jpg';


    }

    public static function mkdirs($path, $mode = 0777)
    {
        // remove filename
        $path = implode('/', explode('/', $path, -1));

        if (!is_dir($path)) {
            if (!mkdir($path, $mode, true))
                throw new Exception("Couldn't create directory: " . $path);
        }
    }
}