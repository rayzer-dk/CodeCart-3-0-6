<?php
class ModelToolImage extends Model {
    public function display($filename, $width, $height) {
        $filename = $this->resolveWithFallback((string)$filename);
        $source = DIR_IMAGE . $filename;
        $real = is_file($source) ? realpath($source) : false;
        $image_root = str_replace('\\', '/', rtrim((string)DIR_IMAGE, '/\\') . '/');
        $real_normalized = $real ? str_replace('\\', '/', $real) : '';

        if (!$real || strpos($real_normalized, $image_root) !== 0) {
            return $this->resize($filename, $width, $height);
        }

        $info = @getimagesize($source);
        if (!$info) {
            return $this->resize($filename, $width, $height);
        }

        $width = max(1, (int)$width);
        $height = max(1, (int)$height);
        $source_width = (int)$info[0];
        $source_height = (int)$info[1];

        // Never enlarge a small original for the storefront. When automatic WebP
        // is enabled, still create a same-size WebP derivative instead of bypassing
        // the conversion by returning the original JPEG/PNG URL.
        if ($source_width <= $width && $source_height <= $height) {
            if ($this->preferredOutputFormat(isset($info[2]) ? (int)$info[2] : 0) !== '') {
                return $this->resize($filename, max(1, $source_width), max(1, $source_height));
            }

            return $this->sourceUrl(str_replace('\\', '/', $filename));
        }

        return $this->resize($filename, $width, $height);
    }


    public function getDisplaySize($filename, $width, $height) {
        $filename = $this->resolveWithFallback((string)$filename);
        $source = DIR_IMAGE . $filename;
        $info = is_file($source) ? @getimagesize($source) : false;
        $width = max(1, (int)$width);
        $height = max(1, (int)$height);

        if (!is_array($info) || empty($info[0]) || empty($info[1])) {
            return array('width' => $width, 'height' => $height, 'filename' => $filename);
        }

        $source_width = max(1, (int)$info[0]);
        $source_height = max(1, (int)$info[1]);
        if ($source_width <= $width && $source_height <= $height) {
            return array('width' => $source_width, 'height' => $source_height, 'filename' => $filename);
        }

        // Image::resize() creates an exact target canvas while preserving the
        // source inside it, so these are the intrinsic dimensions of the URL returned by display().
        return array('width' => $width, 'height' => $height, 'filename' => $filename);
    }

    public function resolveFilename($filename) {
        $filename = str_replace('\\', '/', trim((string)$filename));
        $filename = ltrim($filename, '/');
        if ($filename === '' || strpos($filename, "\0") !== false || preg_match('#(^|/)\.\.(/|$)#', $filename)) {
            return '';
        }
        if ($this->isSafeImageFile($filename)) {
            return $filename;
        }

        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $stem = $extension !== '' ? substr($filename, 0, -strlen($extension) - 1) : $filename;
        if (in_array($extension, array('jpg','jpeg','png','gif','webp','avif'), true)) {
            // WebP is the canonical bundled format. Other formats remain fallbacks for merchant files.
            foreach (array('webp','avif','png','jpg','jpeg','gif') as $candidateExtension) {
                if ($candidateExtension === $extension) { continue; }
                $candidate = $stem . '.' . $candidateExtension;
                if ($this->isSafeImageFile($candidate)) {
                    return $candidate;
                }
            }
        }
        return $filename;
    }

    private function resolveWithFallback($filename) {
        $resolved = $this->resolveFilename((string)$filename);
        if ($resolved !== '' && $this->isSafeImageFile($resolved)) {
            return $resolved;
        }

        $fallback = trim((string)$this->config->get('config_catalog_fallback_image'));
        if ($fallback !== '') {
            $resolvedFallback = $this->resolveFilename($fallback);
            if ($resolvedFallback !== '' && $this->isSafeImageFile($resolvedFallback)) {
                return $resolvedFallback;
            }
        }

        foreach (array('no_image.webp', 'placeholder.webp') as $bundledFallback) {
            $resolvedFallback = $this->resolveFilename($bundledFallback);
            if ($resolvedFallback !== '' && $this->isSafeImageFile($resolvedFallback)) {
                return $resolvedFallback;
            }
        }

        return $resolved;
    }

    private function isSafeImageFile($filename) {
        $root = realpath(DIR_IMAGE);
        $path = realpath(DIR_IMAGE . (string)$filename);
        if ($root === false || $path === false || !is_file($path)) { return false; }
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        return strncmp($path, $root, strlen($root)) === 0;
    }

    public function resize($filename, $width, $height) {
        $filename = $this->resolveWithFallback((string)$filename);
        $source = DIR_IMAGE . $filename;

        $real = is_file($source) ? realpath($source) : false;
        $image_root = str_replace('\\', '/', rtrim((string)DIR_IMAGE, '/\\') . '/');
        $real_normalized = $real ? str_replace('\\', '/', $real) : '';

        if (!$real || strpos($real_normalized, $image_root) !== 0) {
            return;
        }

        $width = max(1, (int)$width);
        $height = max(1, (int)$height);
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $image_old = str_replace('\\', '/', $filename);

        if ($extension === 'svg') {
            return $this->sanitizedSvgUrl($image_old, $source);
        }

        // GD is a production requirement, but a temporary FPM profile mismatch must not take the catalog down.
        // Serve the original raster image until GD is restored; no resize/WebP/AVIF conversion is attempted.
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            return $this->sourceUrl($image_old);
        }

        $info = @getimagesize($source);
        if (!$info) {
            return $this->sourceUrl($image_old);
        }

        $image_type = isset($info[2]) ? (int)$info[2] : 0;
        $supported_types = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
        if (defined('IMAGETYPE_WEBP') && function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
            $supported_types[] = IMAGETYPE_WEBP;
        }
        if (defined('IMAGETYPE_AVIF') && function_exists('imagecreatefromavif') && function_exists('imageavif')) {
            $supported_types[] = constant('IMAGETYPE_AVIF');
        }

        if (!in_array($image_type, $supported_types, true)) {
            return $this->sourceUrl($image_old);
        }

        $output_format = $this->preferredOutputFormat($image_type);
        if ($output_format === 'avif') {
            $quality = (int)$this->config->get('config_image_avif_quality');
            if ($quality < 45 || $quality > 90) { $quality = 72; }
        } else {
            $quality = (int)$this->config->get('config_image_webp_quality');
            if ($quality < 60 || $quality > 95) { $quality = 82; }
        }

        $stem = utf8_substr($image_old, 0, utf8_strrpos($image_old, '.'));
        if ($output_format !== '') {
            // Include format and quality in the cache name so settings changes never reuse stale derivatives.
            $source_version = dechex((int)@filemtime($source)) . '-' . dechex((int)@filesize($source));
            $image_new = 'cache/' . $stem . '-' . $width . 'x' . $height . '-v' . $source_version . '-q' . $quality . '.' . $output_format;
        } else {
            $source_version = dechex((int)@filemtime($source)) . '-' . dechex((int)@filesize($source));
            $image_new = 'cache/' . $stem . '-' . $width . 'x' . $height . '-v' . $source_version . '.' . $extension;
        }

        $target = DIR_IMAGE . $image_new;
        if (!is_file($target) || filemtime($source) > filemtime($target)) {
            if (!$this->ensureDirectory(dirname($target))) {
                return $this->sourceUrl($image_old);
            }

            $width_orig = (int)$info[0];
            $height_orig = (int)$info[1];

            if ($output_format !== '') {
                // Modern-format derivatives are generated atomically; the source is never modified.
                $tmp = dirname($target) . DIRECTORY_SEPARATOR . '.codecart-' . $output_format . '-' . bin2hex(random_bytes(8)) . '.' . $output_format;
                try {
                    $image = new Image($source);
                    if ($width_orig !== $width || $height_orig !== $height) {
                        $image->resize($width, $height);
                    }
                    $image->save($tmp, $quality);
                    if (!is_file($tmp) || filesize($tmp) < 1) {
                        @unlink($tmp);
                        return $this->fallbackResize($image_old, $extension, $width, $height, $width_orig, $height_orig);
                    }
                    if (is_file($target)) {
                        @unlink($target);
                    }
                    if (!@rename($tmp, $target)) {
                        @unlink($tmp);
                        return $this->fallbackResize($image_old, $extension, $width, $height, $width_orig, $height_orig);
                    }
                    @chmod($target, 0644);
                } catch (\Throwable $e) {
                    if (isset($tmp) && is_file($tmp)) {
                        @unlink($tmp);
                    }
                    return $this->fallbackResize($image_old, $extension, $width, $height, $width_orig, $height_orig);
                }
            } else {
                if ($width_orig !== $width || $height_orig !== $height) {
                    $image = new Image($source);
                    $image->resize($width, $height);
                    $image->save($target);
                } else {
                    if (!@copy($source, $target)) {
                        return $this->sourceUrl($image_old);
                    }
                }
            }
        }

        return $this->imageUrl($image_new);
    }

    private function preferredOutputFormat($image_type) {
        if (!in_array($image_type, array(IMAGETYPE_JPEG, IMAGETYPE_PNG), true)) {
            return '';
        }
        // Keep CLI, cron and feeds deterministic. Browser HTML varies by Accept.
        $accept = isset($this->request->server['HTTP_ACCEPT']) ? strtolower((string)$this->request->server['HTTP_ACCEPT']) : '';
        if ($accept === '') { return ''; }

        if ((int)$this->config->get('config_image_avif')
            && strpos($accept, 'image/avif') !== false
            && extension_loaded('gd') && function_exists('imageavif') && function_exists('imagecreatefromavif')) {
            return 'avif';
        }
        if ((int)$this->config->get('config_image_webp')
            && strpos($accept, 'image/webp') !== false
            && extension_loaded('gd') && function_exists('imagewebp')) {
            return 'webp';
        }
        return '';
    }


    private function ensureDirectory($directory) {
        if (is_dir($directory)) {
            return is_writable($directory);
        }
        return @mkdir($directory, 0755, true) || is_dir($directory);
    }

    private function fallbackResize($image_old, $extension, $width, $height, $width_orig, $height_orig) {
        $source = DIR_IMAGE . $image_old;
        $stem = utf8_substr($image_old, 0, utf8_strrpos($image_old, '.'));
        $source_version = dechex((int)@filemtime($source)) . '-' . dechex((int)@filesize($source));
        $image_new = 'cache/' . $stem . '-' . (int)$width . 'x' . (int)$height . '-v' . $source_version . '.' . $extension;
        $target = DIR_IMAGE . $image_new;

        if (!$this->ensureDirectory(dirname($target))) {
            return $this->sourceUrl($image_old);
        }
        if (!is_file($target) || filemtime($source) > filemtime($target)) {
            if ((int)$width_orig !== (int)$width || (int)$height_orig !== (int)$height) {
                $image = new Image($source);
                $image->resize((int)$width, (int)$height);
                $image->save($target);
            } elseif (!@copy($source, $target)) {
                return $this->sourceUrl($image_old);
            }
        }
        return $this->imageUrl($image_new);
    }

    private function sanitizedSvgUrl($image_old, $source) {
        $hash = @hash_file('sha256', $source);
        if (!is_string($hash) || $hash === '') {
            return;
        }

        $stem = preg_replace('/\.svg$/i', '', $image_old);
        $image_new = 'cache/' . $stem . '-svg-' . substr($hash, 0, 16) . '.svg';
        $target = DIR_IMAGE . $image_new;
        if (!is_file($target)) {
            $error = '';
            if (!\CodeCart\Core\SvgSanitizer::sanitizeToFile($source, $target, $error)) {
                if ($this->config->get('error_log')) {
                    $this->log->write('SVG image rejected: ' . $image_old . ' [' . $error . ']');
                }
                return;
            }
        }

        return $this->imageUrl($image_new);
    }

    private function sourceUrl($filename) {
        return $this->imageUrl($filename);
    }

    private function imageUrl($filename) {
        $filename = str_replace(' ', '%20', str_replace('\\', '/', (string)$filename));
        $base = function_exists('codecart_is_https') && codecart_is_https()
            ? (string)$this->config->get('config_ssl')
            : (string)$this->config->get('config_url');
        return rtrim($base, '/') . '/image/' . ltrim($filename, '/');
    }
}
