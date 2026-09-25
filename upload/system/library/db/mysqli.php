<?php
namespace DB;

class MySQLi {
	private $connection;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		if (!extension_loaded('mysqli') || !class_exists('\\mysqli', false)) {
			throw new \RuntimeException('Required PHP extension "mysqli" is not loaded.');
		}

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		try {
			$this->connection = new \mysqli((string)$hostname, (string)$username, (string)$password, (string)$database, (int)$port);
			$this->connection->set_charset('utf8mb4');
			$this->connection->query("SET collation_connection = 'utf8mb4_unicode_ci'");
		} catch (\mysqli_sql_exception $e) {
			throw new \RuntimeException('Database connection failed. Check database host, port, credentials and database name.', (int)$e->getCode(), $e);
		}
	}

	public function query($sql) {
		if (!is_string($sql) || $sql === '') {
			throw new \InvalidArgumentException('Database query must be a non-empty string.');
		}

		try {
			$query = $this->connection->query($sql);
		} catch (\mysqli_sql_exception $e) {
			// Do not append SQL text to the exception: it can contain customer data,
			// API tokens or other secrets and may be shown when error_display is on.
			throw new \RuntimeException('Database query failed (MySQL error ' . (int)$e->getCode() . '). See the protected error log for context.', (int)$e->getCode(), $e);
		}

		if ($query instanceof \mysqli_result) {
			$data = array();

			while ($row = $query->fetch_assoc()) {
				$data[] = $row;
			}

			$result = new \stdClass();
			$result->num_rows = $query->num_rows;
			$result->row = isset($data[0]) ? $data[0] : array();
			$result->rows = $data;
			$query->free();

			return $result;
		}

		return true;
	}

	public function escape($value) {
		if (is_array($value) || is_object($value)) {
			throw new \InvalidArgumentException('Database escape() accepts scalar values only.');
		}

		return $this->connection->real_escape_string($value === null ? '' : (string)$value);
	}

	public function countAffected() {
		return $this->connection->affected_rows;
	}

	public function getLastId() {
		return $this->connection->insert_id;
	}

	public function beginTransaction() {
		return $this->connection->begin_transaction();
	}

	public function commit() {
		return $this->connection->commit();
	}

	public function rollback() {
		return $this->connection->rollback();
	}

	public function isConnected() {
		return $this->connection instanceof \mysqli;
	}

	public function __destruct() {
		if ($this->connection instanceof \mysqli) {
			try {
				$this->connection->close();
			} catch (\Throwable $e) {
				// Connection can already be closed during shutdown.
			}
		}
	}
}
