<?php
namespace Session;
class File {
	private $directory;
	private $log;
	private $maxlifetime;

	public function __construct($registry = null) {
		$this->log = (is_object($registry) && method_exists($registry, 'has') && $registry->has('log')) ? $registry->get('log') : null;
		$configured_lifetime = (is_object($registry) && method_exists($registry, 'has') && $registry->has('config')) ? $registry->get('config')->get('session_maxlifetime') : null;
		$this->maxlifetime = $configured_lifetime !== null ? max(60, (int)$configured_lifetime) : max(60, (int)ini_get('session.gc_maxlifetime'));
	}

	public function read($session_id) {
		$file = DIR_SESSION . 'sess_' . basename($session_id);

		if (!is_file($file)) {
			return array();
		}

		$handle = fopen($file, 'rb');
		if (!$handle) {
			return array();
		}

		$data = '';
		if (flock($handle, LOCK_SH)) {
			$data = stream_get_contents($handle);
			flock($handle, LOCK_UN);
		}
		fclose($handle);

		if ($data === '') {
			return array();
		}

		$decode_error = '';
		$previous_handler = set_error_handler(function($severity, $message) use (&$decode_error) {
			$decode_error = (string)$message;
			return true;
		});

		try {
			$value = unserialize($data, array('allowed_classes' => false));
		} finally {
			if ($previous_handler !== null) {
				set_error_handler($previous_handler);
			} else {
				restore_error_handler();
			}
		}

		if ($decode_error !== '' && $this->log) {
			$this->log->write('Session file decode failed for ' . basename($file) . ': ' . $decode_error);
		}

		return is_array($value) ? $value : array();
	}

	public function write($session_id, $data) {
		$file = DIR_SESSION . 'sess_' . basename($session_id);
		$handle = fopen($file, 'c+b');

		if (!$handle) {
			return false;
		}

		$success = false;
		if (flock($handle, LOCK_EX)) {
			ftruncate($handle, 0);
			rewind($handle);
			$payload = serialize($data);
			$success = fwrite($handle, $payload) === strlen($payload);
			fflush($handle);
			flock($handle, LOCK_UN);
		}

		fclose($handle);
		return $success;
	}

	public function destroy($session_id) {
		$file = DIR_SESSION . 'sess_' . basename($session_id);

		if (is_file($file)) {
			unlink($file);
		}

		return true;
	}

	public function __destruct() {
		$gc_divisor = max(1, (int)ini_get('session.gc_divisor'));
		$gc_probability = max(0, (int)ini_get('session.gc_probability'));

		if ($gc_probability > 0 && random_int(1, $gc_divisor) <= $gc_probability) {
			$expire = time() - $this->maxlifetime;
			$files = glob(DIR_SESSION . 'sess_*');

			if (is_array($files)) {
				foreach ($files as $file) {
					$mtime = filemtime($file);
					if ($mtime !== false && $mtime < $expire && is_file($file)) {
						unlink($file);
					}
				}
			}
		}
	}
}
