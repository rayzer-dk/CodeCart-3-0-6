<?php
class ModelToolImage extends Model {
	public function resize($filename, $width, $height) {
		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
			return;
		}

		$extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
		if ($extension === 'svg') {
			return $this->sanitizedSvgUrl($filename);
		}

		// Keep admin pages usable if GD is missing in the WEB/FPM profile.
		// Image editing/resizing remains unavailable until GD is enabled.
		if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
			$base = (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off') ? HTTPS_CATALOG : HTTP_CATALOG;
			return rtrim($base, '/') . '/image/' . ltrim(str_replace('\\', '/', $filename), '/');
		}

		$image_old = $filename;
		$source_version = dechex((int)@filemtime(DIR_IMAGE . $image_old)) . '-' . dechex((int)@filesize(DIR_IMAGE . $image_old));
		$image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . $width . 'x' . $height . '-v' . $source_version . '.' . $extension;

		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);
				 
			$supported_types = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
			if (defined('IMAGETYPE_WEBP') && function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
				$supported_types[] = IMAGETYPE_WEBP;
			}
			if (defined('IMAGETYPE_AVIF') && function_exists('imagecreatefromavif') && function_exists('imageavif')) {
				$supported_types[] = constant('IMAGETYPE_AVIF');
			}

			if (!in_array($image_type, $supported_types, true)) { 
				if ($this->request->server['HTTPS']) {
					return HTTPS_CATALOG . 'image/' . $image_old;
				} else {
					return HTTP_CATALOG . 'image/' . $image_old;
				}
			}
 
			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path) && !mkdir(DIR_IMAGE . $path, 0755) && !is_dir(DIR_IMAGE . $path)) {
					return $this->request->server['HTTPS'] ? HTTPS_CATALOG . 'image/' . $image_old : HTTP_CATALOG . 'image/' . $image_old;
				}
			}

			if ($width_orig != $width || $height_orig != $height) {
				$image = new Image(DIR_IMAGE . $image_old);
				$image->resize($width, $height);
				$image->save(DIR_IMAGE . $image_new);
			} else {
				copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
			}
		}

		if ($this->request->server['HTTPS']) {
			return HTTPS_CATALOG . 'image/' . $image_new;
		} else {
			return HTTP_CATALOG . 'image/' . $image_new;
		}
	}

	private function sanitizedSvgUrl($filename) {
		$source = DIR_IMAGE . $filename;
		$hash = @hash_file('sha256', $source);
		if (!is_string($hash) || $hash === '') {
			return;
		}

		$normalized = str_replace('\\', '/', (string)$filename);
		$stem = preg_replace('/\.svg$/i', '', $normalized);
		$image_new = 'cache/' . $stem . '-svg-' . substr($hash, 0, 16) . '.svg';
		$target = DIR_IMAGE . $image_new;
		if (!is_file($target)) {
			$error = '';
			if (!\CodeCart\Core\SvgSanitizer::sanitizeToFile($source, $target, $error)) {
				if ($this->config->get('error_log')) {
					$this->log->write('SVG preview rejected: ' . $normalized . ' [' . $error . ']');
				}
				return;
			}
		}

		$base = (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off') ? HTTPS_CATALOG : HTTP_CATALOG;
		return rtrim($base, '/') . '/image/' . ltrim(str_replace(' ', '%20', $image_new), '/');
	}
}
