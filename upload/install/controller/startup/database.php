<?php
class ControllerStartupDatabase extends Controller {
	public function index() {
		$config_file = DIR_OPENCART . 'config.php';

		if (!is_file($config_file) || filesize($config_file) <= 0) {
			return;
		}

		$config = $this->readDefines($config_file, array(
			'DB_DRIVER',
			'DB_HOSTNAME',
			'DB_USERNAME',
			'DB_PASSWORD',
			'DB_DATABASE',
			'DB_PORT',
			'DB_PREFIX'
		));

		$required = array('DB_DRIVER', 'DB_HOSTNAME', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE', 'DB_PREFIX');

		foreach ($required as $key) {
			if (!array_key_exists($key, $config)) {
				return;
			}
		}

		$prefix = (string)$config['DB_PREFIX'];
		if (!preg_match('/^[A-Za-z0-9_]*$/', $prefix) || strlen($prefix) > 32) {
			throw new \RuntimeException('Existing config.php contains an invalid DB_PREFIX; upgrade cannot continue safely.');
		}

		// The normal catalog/admin bootstrap defines these constants before loading
		// the DB layer. Installer 2.0 must expose the same contract to migrations.
		foreach (array('DB_DRIVER','DB_HOSTNAME','DB_USERNAME','DB_PASSWORD','DB_DATABASE','DB_PREFIX') as $constant) {
			if (!defined($constant)) { define($constant, (string)$config[$constant]); }
		}

		$default_port = (int)ini_get('mysqli.default_port');
		if ($default_port <= 0) { $default_port = 3306; }
		$port = isset($config['DB_PORT']) && $config['DB_PORT'] !== '' ? (int)$config['DB_PORT'] : $default_port;
		if ($port <= 0 || $port > 65535) {
			throw new \RuntimeException('Existing config.php contains an invalid DB_PORT; upgrade cannot continue safely.');
		}
		if (!defined('DB_PORT')) { define('DB_PORT', (string)$port); }

		$this->registry->set('db', new DB(
			$config['DB_DRIVER'],
			$config['DB_HOSTNAME'],
			$config['DB_USERNAME'],
			$config['DB_PASSWORD'],
			$config['DB_DATABASE'],
			$port
		));
	}

	private function readDefines($file, array $allowed) {
		$result = array();
		$contents = file_get_contents($file);

		if ($contents === false) {
			return $result;
		}

		$pattern = '/define\\(\\s*([\\\'\"])([A-Z0-9_]+)\\1\\s*,\\s*([\\\'\"])((?:\\\\.|(?!\\3).)*)\\3\\s*\\)\\s*;/s';

		if (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$key = $match[2];

				if (in_array($key, $allowed, true)) {
					$result[$key] = $this->decodePhpStringLiteral($match[3], $match[4]);
				}
			}
		}

		return $result;
	}
	private function decodePhpStringLiteral($quote, $value) {
		$value = (string)$value;

		if ($quote === "'") {
			// In PHP single-quoted strings only \\ and \' are escape sequences.
			return str_replace(array('\\\\', "\\'"), array('\\', "'"), $value);
		}

		// Double-quoted config literals use the normal PHP backslash escapes.
		return stripcslashes($value);
	}

}
