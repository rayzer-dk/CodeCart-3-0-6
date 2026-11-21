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
        $mime = strtolower((string)$info['mime']);

        // Bump the cache key when the image preparation algorithm changes.
        $source_version = dechex((int)@filemtime($source)) . '-' . dechex((int)@filesize($source));
        $name_hash = substr(sha1($filename . '|' . $source_version . '|email-adaptive-v3'), 0, 18);
        $relative = 'cache/email/logo-' . $name_hash . '.png';
        $target = DIR_IMAGE . $relative;
        $dark_relative = 'cache/email/logo-' . $name_hash . '-dark.png';
        $dark_target = DIR_IMAGE . $dark_relative;

        if (!is_file($target) || !is_file($dark_target)) {
            if (!self::ensureDirectory(dirname($target))) {
                return self::fallback($filename, $base_url);
            }

            $image = self::load($source, $mime);
            if (!$image) {
                return self::fallback($filename, $base_url);
            }

            // Use the source aspect ratio rather than a fixed 600x160
            // white rectangle. Keep room for a small, email-safe border.
            $padding = 10;
            $scale = min(
                (self::OUTPUT_MAX_WIDTH - 2 * $padding) / $source_width,
                (self::OUTPUT_MAX_HEIGHT - 2 * $padding) / $source_height,
                1
            );
            $scaled_width = max(1, (int)round($source_width * $scale));
            $scaled_height = max(1, (int)round($source_height * $scale));
            $preview = imagecreatetruecolor($scaled_width, $scaled_height);
            if (!$preview) {
                imagedestroy($image);
                return self::fallback($filename, $base_url);
            }

            imagealphablending($preview, false);
            imagesavealpha($preview, true);
            imagefill($preview, 0, 0, imagecolorallocatealpha($preview, 255, 255, 255, 127));
            imagecopyresampled($preview, $image, 0, 0, 0, 0, $scaled_width, $scaled_height, $source_width, $source_height);
            imagedestroy($image);

            // Transparent margins and white image backgrounds are both common.
            // Trim only those margins, never opaque white parts of a transparent logo.
            $bounds = self::contentBounds($preview, $scaled_width, $scaled_height);
            $crop_width = $bounds['right'] - $bounds['left'] + 1;
            $crop_height = $bounds['bottom'] - $bounds['top'] + 1;
            $canvas_width = $crop_width + 2 * $padding;
            $canvas_height = $crop_height + 2 * $padding;
            $canvas = imagecreatetruecolor($canvas_width, $canvas_height);
            if (!$canvas) {
                imagedestroy($preview);
                return self::fallback($filename, $base_url);
            }

            // Preserve the source alpha channel: no forced white backing.
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 255, 255, 255, 127));
            imagecopy($canvas, $preview, $padding, $padding,
                $bounds['left'], $bounds['top'], $crop_width, $crop_height);
            imagedestroy($preview);

            // A separate image for dark-mode-aware mail clients retains
            // saturated brand colours and brightens nearly black lettering.
            $dark = self::darkVariant($canvas, $canvas_width, $canvas_height);
            if (!is_file($target) && !self::savePng($canvas, $target)) {
                imagedestroy($canvas);
                if ($dark) { imagedestroy($dark); }
                return self::fallback($filename, $base_url);
            }
            imagedestroy($canvas);
            if ($dark && !is_file($dark_target)) {
                self::savePng($dark, $dark_target);
            }
            if ($dark) { imagedestroy($dark); }
        }

        $dimensions = @getimagesize($target);
        if (!$dimensions || !$dimensions[0] || !$dimensions[1]) {
            return self::fallback($filename, $base_url);
        }
        // Fit the actual logo artwork into the existing 300x80 email slot.
        // Large images shrink; smaller source artwork is displayed at slot scale.
        $display_scale = min(
            self::DISPLAY_MAX_WIDTH / (int)$dimensions[0],
            self::DISPLAY_MAX_HEIGHT / (int)$dimensions[1]
        );
        return array(
            'url' => rtrim((string)$base_url, '/') . '/image/' . str_replace(' ', '%20', $relative),
            'dark_url' => is_file($dark_target)
                ? rtrim((string)$base_url, '/') . '/image/' . str_replace(' ', '%20', $dark_relative)
                : '',
            'width' => max(1, (int)round($dimensions[0] * $display_scale)),
            'height' => max(1, (int)round($dimensions[1] * $display_scale)),
            'format' => 'PNG'
        );
    }

    /** Preserve transparency and promote only grayscale dark strokes for dark-mode email. */
    private static function darkVariant($source, $width, $height) {
        $dark = imagecreatetruecolor($width, $height);
        if (!$dark) { return false; }
        imagealphablending($dark, false);
        imagesavealpha($dark, true);
        imagefill($dark, 0, 0, imagecolorallocatealpha($dark, 255, 255, 255, 127));

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixel = imagecolorat($source, $x, $y);
                $alpha = ($pixel >> 24) & 0x7f;
                if ($alpha === 127) { continue; }
                $red = ($pixel >> 16) & 0xff;
                $green = ($pixel >> 8) & 0xff;
                $blue = $pixel & 0xff;
                $low = min($red, $green, $blue);
                $high = max($red, $green, $blue);
                if (($high - $low) < 75 && ($red + $green + $blue) < 480) {
                    // Black/gray type becomes light; blue logo details are unchanged.
                    $red = 238; $green = 243; $blue = 250;
                }
                imagesetpixel($dark, $x, $y, imagecolorallocatealpha($dark, $red, $green, $blue, $alpha));
            }
        }
        return $dark;
    }

    private static function savePng($image, $target) {
        try {
            $tmp = $target . '.' . bin2hex(random_bytes(5)) . '.tmp';
        } catch (\Throwable $e) {
            return false;
        }
        if (!@imagepng($image, $tmp, 6)) {
            @unlink($tmp);
            return false;
        }
        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            return is_file($target);
        }
        return true;
    }

    /**
     * Find foreground pixels in a bounded preview (at most 600x160).
     * Respect alpha for transparent artwork; otherwise trim white padding.
     */
    private static function contentBounds($image, $width, $height) {
        $alpha_bounds = array('left' => $width, 'top' => $height, 'right' => -1, 'bottom' => -1);
        $ink_bounds = $alpha_bounds;
        $has_transparency = false;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixel = imagecolorat($image, $x, $y);
                $alpha = ($pixel >> 24) & 0x7f;
                if ($alpha >= 110) {
                    $has_transparency = true;
                    continue;
                }
                self::extendBounds($alpha_bounds, $x, $y);

                $red = ($pixel >> 16) & 0xff;
                $green = ($pixel >> 8) & 0xff;
                $blue = $pixel & 0xff;
                if ($red < 246 || $green < 246 || $blue < 246) {
                    self::extendBounds($ink_bounds, $x, $y);
                }
            }
        }
        if ($has_transparency && $alpha_bounds['right'] >= 0) {
            return $alpha_bounds;
        }
        if ($ink_bounds['right'] >= 0) {
            return $ink_bounds;
        }
        // A fully white image is not assumed to be empty artwork.
        return array('left' => 0, 'top' => 0, 'right' => $width - 1, 'bottom' => $height - 1);
    }

    private static function extendBounds(&$bounds, $x, $y) {
        $bounds['left'] = min($bounds['left'], $x);
        $bounds['top'] = min($bounds['top'], $y);
        $bounds['right'] = max($bounds['right'], $x);
        $bounds['bottom'] = max($bounds['bottom'], $y);
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

        $format = self::formatFromMime($info['mime']);
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
