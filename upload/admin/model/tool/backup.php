<?php
class ModelToolBackup extends Model {
	public function getTables() {
		$table_data = array();

		$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "`");

		foreach ($query->rows as $result) {
			$table = reset($result);
			if ($table && utf8_substr($table, 0, strlen(DB_PREFIX)) == DB_PREFIX) {
				$table_data[] = $table;
			}
		}

		return $table_data;
	}

	public function backup($tables) {
		$tmp = tempnam(DIR_UPLOAD, 'ccbackup_');
		if (!$tmp) { return ''; }
		try {
			$this->backupToFile($tables, $tmp);
			$data = file_get_contents($tmp);
			return $data === false ? '' : $data;
		} finally {
			@unlink($tmp);
		}
	}

	public function backupToFile($tables, $path, $batch_size = 500) {
		$available = array_flip($this->getTables());
		$tables = is_array($tables) ? $tables : array();
		$batch_size = max(50, min(2000, (int)$batch_size));
		$handle = @fopen($path, 'wb');
		if (!$handle) { throw new \RuntimeException('Unable to create database backup file.'); }

		try {
			foreach ($tables as $table) {
				$table = (string)$table;
				if (!isset($available[$table]) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) { continue; }
				fwrite($handle, 'TRUNCATE TABLE `' . $table . '`;' . "\n\n");
				$offset = 0;
				do {
					$query = $this->db->query("SELECT * FROM `" . $table . "` LIMIT " . (int)$offset . "," . (int)$batch_size);
					$count = count($query->rows);
					foreach ($query->rows as $result) {
						$fields = array(); $values = array();
						foreach ($result as $field => $value) {
							$fields[] = '`' . str_replace('`', '``', $field) . '`';
							if ($value === null) { $values[] = 'NULL'; } else {
								$binary = (string)$value;
								$values[] = $binary === '' ? "''" : '0x' . bin2hex($binary);
							}
						}
						fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ');' . "\n");
					}
					$offset += $count;
				} while ($count === $batch_size);
				fwrite($handle, "\n\n");
			}
		} finally { fclose($handle); }
		return $path;
	}

}