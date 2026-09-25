<?php
namespace Cache;
class File {
	private $expire;
	private $prefix;

	public function __construct($expire = 3600) {
		$this->expire = (int)$expire;
		$this->prefix = defined('CACHE_PREFIX') ? (string)CACHE_PREFIX : '';
		$this->ensureDirectory();
	}

	private function ensureDirectory() {
		if (is_dir(DIR_CACHE)) {
			return is_writable(DIR_CACHE);
		}

		$error = '';
		set_error_handler(function($severity, $message) use (&$error) {
			$error = $message;
			return true;
		});
		try {
			$result = mkdir(DIR_CACHE, 0755, true);
		} finally {
			restore_error_handler();
		}

		if (!$result && !is_dir(DIR_CACHE) && $error) {
			error_log('CodeCart PRO Cache: cannot create cache directory: ' . $error);
		}

		return is_dir(DIR_CACHE) && is_writable(DIR_CACHE);
	}

	private function key($key) {
		return preg_replace('/[^A-Z0-9\._-]/i', '', $this->prefix . (string)$key);
	}

	public function get($key) {
		if (!is_dir(DIR_CACHE)) {
			return false;
		}

		$files = glob(DIR_CACHE . 'cache.' . $this->key($key) . '.*');

		if (!$files) {
			return false;
		}

		$file = false;
		$now = time();

		foreach ($files as $candidate) {
			$expires = (int)substr(strrchr($candidate, '.'), 1);

			if ($expires > $now) {
				$file = $candidate;
				break;
			}

			if (is_file($candidate)) {
				@unlink($candidate);
			}
		}

		if ($file === false) {
			return false;
		}

		$handle = (is_file($file) && is_readable($file)) ? @fopen($file, 'rb') : false;
		if (!$handle) {
			return false;
		}

		$data = '';
		if (flock($handle, LOCK_SH)) {
			$data = stream_get_contents($handle);
			flock($handle, LOCK_UN);
		}
		fclose($handle);

		if ($data === '') {
			return false;
		}

		$value = json_decode($data, true);
		return json_last_error() === JSON_ERROR_NONE ? $value : false;
	}

	public function set($key, $value) {
		if (!$this->ensureDirectory()) {
			return false;
		}

		$this->delete($key);

		$file = DIR_CACHE . 'cache.' . $this->key($key) . '.' . (time() + $this->expire);
		$temp = tempnam(DIR_CACHE, 'cache.tmp.');

		if ($temp === false) {
			return false;
		}

		$data = json_encode($value);
		if ($data === false || file_put_contents($temp, $data, LOCK_EX) === false) {
			if (is_file($temp)) {
				unlink($temp);
			}
			return false;
		}

		if (!rename($temp, $file)) {
			if (is_file($temp)) {
				unlink($temp);
			}
			return false;
		}

		return true;
	}

	public function delete($key) {
		if (!is_dir(DIR_CACHE)) {
			return;
		}

		if ($key == '*') {
			$files = glob(DIR_CACHE . 'cache.' . $this->key('') . '*.*');
		} else {
			$files = glob(DIR_CACHE . 'cache.' . $this->key($key) . '.*');
		}

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}
		}
	}
}
