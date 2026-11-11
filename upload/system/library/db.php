<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* DB class
*/
class DB {
	private $adaptor;
	private $profile = false;
	private $profile_threshold = 50.0;
	private $profile_count = 0;
	private $profile_time = 0.0;

	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 * @param	string	$hostname
	 * @param	string	$username
     * @param	string	$password
	 * @param	string	$database
	 * @param	int		$port
	 *
 	*/
	public function __construct($adaptor, $hostname, $username, $password, $database, $port = NULL) {
		if (strtolower((string)$adaptor) === 'mpdo') {
			$adaptor = 'pdo';
		}

		$class = 'DB\\' . $adaptor;

		if (class_exists($class)) {
			$this->adaptor = new $class($hostname, $username, $password, $database, $port);
		} else {
			throw new \Exception('Error: Could not load database adaptor ' . $adaptor . '!');
		}

		// Development aid: define('CODECART_SQL_PROFILE', true) in config.php to log slow queries
		// (CODECART_SQL_PROFILE_MS, default 50) and a per-request summary to system/storage/logs/sql_profile.log.
		if (defined('CODECART_SQL_PROFILE') && CODECART_SQL_PROFILE && defined('DIR_LOGS')) {
			$this->profile = true;

			if (defined('CODECART_SQL_PROFILE_MS') && (float)CODECART_SQL_PROFILE_MS > 0) {
				$this->profile_threshold = (float)CODECART_SQL_PROFILE_MS;
			}

			register_shutdown_function(array($this, 'writeProfileSummary'));
		}
	}

	public function writeProfileSummary() {
		if ($this->profile_count) {
			$this->writeProfile(sprintf('SUMMARY %s queries=%d time=%.1fms', isset($_SERVER['REQUEST_URI']) ? substr((string)$_SERVER['REQUEST_URI'], 0, 200) : PHP_SAPI, $this->profile_count, $this->profile_time));
		}
	}

	private function writeProfile($line) {
		$file = DIR_LOGS . 'sql_profile.log';

		// Keep the file bounded; stop writing instead of growing without limit.
		if (is_file($file) && filesize($file) > 5242880) {
			return;
		}

		@file_put_contents($file, date('Y-m-d H:i:s') . ' ' . $line . "\n", FILE_APPEND | LOCK_EX);
	}

	/**
     * 
     *
     * @param	string	$sql
	 * 
	 * @return	\stdClass|true
     */
	public function query($sql) {
		if (!$this->profile) {
			return $this->adaptor->query($sql);
		}

		$start = microtime(true);

		try {
			return $this->adaptor->query($sql);
		} finally {
			$elapsed = (microtime(true) - $start) * 1000;
			$this->profile_count++;
			$this->profile_time += $elapsed;

			if ($elapsed >= $this->profile_threshold) {
				$this->writeProfile(sprintf('SLOW %.1fms %s', $elapsed, substr(preg_replace('/\s+/', ' ', (string)$sql), 0, 600)));
			}
		}
	}

	/**
     * 
     *
     * @param	string	$value
	 * 
	 * @return	string
     */
	public function escape($value) {
		return $this->adaptor->escape($value);
	}

	/**
     * 
	 * 
	 * @return	int
     */
	public function countAffected() {
		return $this->adaptor->countAffected();
	}

	/**
     * 
	 * 
	 * @return	int
     */
	public function getLastId() {
		return $this->adaptor->getLastId();
	}
	
	/**
     * 
	 * 
	 * @return	bool
     */	
	public function beginTransaction() {
		return $this->adaptor->beginTransaction();
	}

	public function commit() {
		return $this->adaptor->commit();
	}

	public function rollback() {
		return $this->adaptor->rollback();
	}

	public function connected() {
		if (method_exists($this->adaptor, 'isConnected')) {
			return $this->adaptor->isConnected();
		}

		if (method_exists($this->adaptor, 'connected')) {
			return $this->adaptor->connected();
		}

		return false;
	}
}