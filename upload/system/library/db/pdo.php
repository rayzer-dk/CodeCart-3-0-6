<?php
namespace DB;

final class PDO {
	private $connection = null;
	private $statement = null;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		if (!extension_loaded('pdo_mysql')) {
			throw new \RuntimeException('Required PHP extension "pdo_mysql" is not loaded.');
		}

		$dsn = 'mysql:host=' . (string)$hostname . ';port=' . (int)$port . ';dbname=' . (string)$database . ';charset=utf8mb4';

		try {
			$this->connection = new \PDO($dsn, (string)$username, (string)$password, array(
				\PDO::ATTR_PERSISTENT => false,
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES => false
			));
			$this->connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
		} catch (\PDOException $e) {
			throw new \RuntimeException('Database connection failed. Check database host, port, credentials and database name.', (int)$e->getCode(), $e);
		}
	}

	public function prepare($sql) {
		if (!is_string($sql) || $sql === '') {
			throw new \InvalidArgumentException('Database query must be a non-empty string.');
		}

		try {
			$this->statement = $this->connection->prepare($sql);
			return $this->statement !== false;
		} catch (\PDOException $e) {
			throw $this->queryException($e);
		}
	}

	public function bindParam($parameter, &$variable, $data_type = \PDO::PARAM_STR, $length = 0) {
		if (!$this->statement) {
			return false;
		}

		return $length ? $this->statement->bindParam($parameter, $variable, $data_type, $length) : $this->statement->bindParam($parameter, $variable, $data_type);
	}

	public function execute($params = array()) {
		if (!$this->statement) {
			return false;
		}

		try {
			$this->statement->execute(is_array($params) ? $params : array());
			return $this->buildResult($this->statement);
		} catch (\PDOException $e) {
			throw $this->queryException($e);
		}
	}

	public function query($sql, $params = array()) {
		if (!is_string($sql) || $sql === '') {
			throw new \InvalidArgumentException('Database query must be a non-empty string.');
		}

		try {
			$this->statement = $this->connection->prepare($sql);
			$this->statement->execute(is_array($params) ? $params : array());
			return $this->buildResult($this->statement);
		} catch (\PDOException $e) {
			throw $this->queryException($e);
		}
	}

	private function queryException(\PDOException $e) {
		$code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
		return new \RuntimeException('Database query failed' . ($code ? ' (PDO error ' . $code . ')' : '') . '. See the protected error log for context.', $code, $e);
	}

	private function buildResult($statement) {
		$result = new \stdClass();
		$result->row = array();
		$result->rows = array();
		$result->num_rows = 0;

		if ($statement->columnCount() > 0) {
			$data = $statement->fetchAll(\PDO::FETCH_ASSOC);
			$result->rows = $data;
			$result->row = isset($data[0]) ? $data[0] : array();
			$result->num_rows = count($data);
		}

		return $result;
	}

	public function escape($value) {
		if (is_array($value) || is_object($value)) {
			throw new \InvalidArgumentException('Database escape() accepts scalar values only.');
		}

		$quoted = $this->connection->quote($value === null ? '' : (string)$value);
		return $quoted === false ? '' : substr($quoted, 1, -1);
	}

	public function countAffected() {
		return $this->statement ? $this->statement->rowCount() : 0;
	}

	public function getLastId() {
		return (int)$this->connection->lastInsertId();
	}

	public function beginTransaction() {
		return $this->connection->beginTransaction();
	}

	public function commit() {
		return $this->connection->inTransaction() ? $this->connection->commit() : true;
	}

	public function rollback() {
		return $this->connection->inTransaction() ? $this->connection->rollBack() : true;
	}

	public function isConnected() {
		return $this->connection instanceof \PDO;
	}

	public function __destruct() {
		$this->statement = null;
		$this->connection = null;
	}
}
