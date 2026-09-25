<?php
namespace CodeCart\Core;

/**
 * Produces a bounded, email-safe raster derivative of the configured store logo.
 * WebP/other web assets stay untouched; only the email representation is normalized.
 */
final class EmailLogo {
    const OUTPUT_MAX_WIDTH = 600;
    const OUTPUT_MAX_HEIGHT = 160;
    const DISPLAY_MAX_WIDTH = 300;
    const DISPLAY_MAX_HEIGHT = 80;

    public static function prepare($filename, $base_url) {
        $filename = self::normalizePath($filename);
        if ($filename === '' || !self::isSafeFile($filename)) {
            return self::fallback('', $base_url);
        }

        $source = DIR_IMAGE . $filename;
        $info = @getimagesize($source);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return self::fallback($filename, $base_url);
        }

        $source_width = (int)$info[0];
        $source_height = (int)$info[1];
        $mime = isset($info['mime']) ? strtolower((string)$info['mime']) : '';

        $source_version = dechex((int)@filemtime($source)) . '-' . dechex((int)@filesize($source));
        $name_hash = substr(sha1($filename . '|' . $source_version . '|email'), 0, 18);
        $relative = 'cache/email/logo-' . $name_hash . '.png';
        $target = DIR_IMAGE . $relative;

        if (!is_file($target)) {
            if (!self::ensureDirectory(dirname($target))) {
                return self::fallback($filename, $base_url);
            }

            $image = self::load($source, $mime);
            if (!$image) {
                return self::fallback($filename, $base_url);
            }

            $scale = min(
                self::OUTPUT_MAX_WIDTH / $source_width,
                self::OUTPUT_MAX_HEIGHT / $source_height,
                1
            );
            $out_width = max(1, (int)round($source_width * $scale));
            $out_height = max(1, (int)round($source_height * $scale));

            $canvas = imagecreatetruecolor(self::OUTPUT_MAX_WIDTH, self::OUTPUT_MAX_HEIGHT);
            if (!$canvas) {
                imagedestroy($image);
                return self::fallback($filename, $base_url);
            }

            // Email-safe white canvas: prevents transparent WebP logos from turning
            // into dark/black blocks when an email client/proxy converts the image.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagealphablending($canvas, true);
            imagesavealpha($canvas, false);

            $dst_x = (int)floor((self::OUTPUT_MAX_WIDTH - $out_width) / 2);
            $dst_y = (int)floor((self::OUTPUT_MAX_HEIGHT - $out_height) / 2);

            imagecopyresampled(
                $canvas,
                $image,
                $dst_x,
                $dst_y,
                0,
                0,
                $out_width,
                $out_height,
                $source_width,
                $source_height
            );

            $tmp = $target . '.tmp';
            $saved = @imagepng($canvas, $tmp, 6);
            imagedestroy($canvas);
            imagedestroy($image);

            if (!$saved) {
                @unlink($tmp);
                return self::fallback($filename, $base_url);
            }

            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                if (!is_file($target)) {
                    return self::fallback($filename, $base_url);
                }
            }
        }

        return array(
            'url' => rtrim((string)$base_url, '/') . '/image/' . str_replace(' ', '%20', $relative),
            'width' => self::DISPLAY_MAX_WIDTH,
            'height' => self::DISPLAY_MAX_HEIGHT,
            'format' => 'PNG'
        );
    }

    public static function inspect($filename) {
        $filename = self::normalizePath($filename);
        if ($filename === '' || !self::isSafeFile($filename)) {
            return null;
        }

        $source = DIR_IMAGE . $filename;
        $info = @getimagesize($source);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return null;
        }

        $format = self::formatFromMime(isset($info['mime']) ? $info['mime'] : '');
        $size = is_file($source) ? (int)@filesize($source) : 0;

        return array(
            'filename' => $filename,
            'format' => $format,
            'width' => (int)$info[0],
            'height' => (int)$info[1],
            'size' => $size,
            'size_kb' => round($size / 1024, 1),
            'supported' => in_array(strtolower($format), array('png', 'jpeg', 'jpg', 'webp'), true)
        );
    }

    private static function fallback($filename, $base_url) {
        $relative = $filename !== '' ? $filename : 'no_image.webp';
        $inspect = self::inspect($relative);
        $width = $inspect && $inspect['width'] > 0 ? $inspect['width'] : 300;
        $height = $inspect && $inspect['height'] > 0 ? $inspect['height'] : 80;
        $scale = min(self::DISPLAY_MAX_WIDTH / $width, self::DISPLAY_MAX_HEIGHT / $height, 1);

        return array(
            'url' => rtrim((string)$base_url, '/') . '/image/' . str_replace(' ', '%20', $relative),
            'width' => max(1, (int)round($width * $scale)),
            'height' => max(1, (int)round($height * $scale)),
            'format' => $inspect ? $inspect['format'] : ''
        );
    }

    private static function load($source, $mime) {
        switch ($mime) {
            case 'image/jpeg':
                return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source) : false;
            case 'image/png':
                return function_exists('imagecreatefrompng') ? @imagecreatefrompng($source) : false;
            case 'image/gif':
                return function_exists('imagecreatefromgif') ? @imagecreatefromgif($source) : false;
            case 'image/webp':
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false;
            default:
                return false;
        }
    }

    private static function formatFromMime($mime) {
        $mime = strtolower((string)$mime);
        $map = array(
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/gif' => 'GIF',
            'image/webp' => 'WebP',
            'image/avif' => 'AVIF'
        );
        return isset($map[$mime]) ? $map[$mime] : 'Unknown';
    }

    private static function normalizePath($filename) {
        $filename = trim(str_replace('\\', '/', (string)$filename));
        return ltrim($filename, '/');
    }

    private static function isSafeFile($filename) {
        $root = realpath(DIR_IMAGE);
        $path = realpath(DIR_IMAGE . $filename);
        if ($root === false || $path === false || !is_file($path)) {
            return false;
        }
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        return strpos($path, $root) === 0;
    }

    private static function ensureDirectory($directory) {
        if (is_dir($directory)) {
            return is_writable($directory);
        }
        return @mkdir($directory, 0755, true) || is_dir($directory);
    }
}
