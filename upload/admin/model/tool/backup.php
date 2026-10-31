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
            // Read all InnoDB rows from one consistent snapshot, rather than
            // mixing records from different instants during a long backup.
            $this->db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
            try {
			foreach ($tables as $table) {
				$table = (string)$table;
				if (!isset($available[$table]) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) { continue; }
                // Include table structure so a fresh compatible database can
                // be prepared by this same restore tool. Never DROP a table.
                $schema = $this->db->query('SHOW CREATE TABLE `' . $table . '`');
                $create = isset($schema->row['Create Table']) ? $schema->row['Create Table'] : '';
                if (!is_string($create) || strpos($create, 'CREATE TABLE ') !== 0) {
                    throw new \RuntimeException('Cannot export table structure: ' . $table);
                }
                $create = preg_replace('/^CREATE TABLE\\s+/i', 'CREATE TABLE IF NOT EXISTS ', $create, 1);
                $types = array();
                $columns = $this->db->query('SHOW FULL COLUMNS FROM `' . $table . '`');
                foreach ($columns->rows as $column) {
                    if (isset($column['Field'],$column['Type'])) {
                        $types[(string)$column['Field']] = strtolower((string)$column['Type']);
                    }
                }
                fwrite($handle, '-- CodeCart SQL backup: table definition and contents' . "\n");
                fwrite($handle, $create . ';' . "\n");
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
								if ($binary === '') {
                                    $values[] = "''";
                                } else {
                                    $hex = '0x' . bin2hex($binary);
                                    $type = isset($types[$field]) ? $types[$field] : '';
                                    $binaryColumn = preg_match('/^(?:tinyblob|blob|mediumblob|longblob|binary|varbinary|bit|geometry|point|linestring|polygon|multipoint|multilinestring|multipolygon|geometrycollection)\\b/i', $type) === 1;
                                    // Bare MySQL 0x... is a NUMBER in numeric-column
                                    // context. e.g. a product_id of "15" (0x3135)
                                    // would be restored as 12597 instead of 15.
                                    // CONVERT forces a text value before MySQL applies
                                    // the target column's declared numeric/date type.
                                    $values[] = $binaryColumn ? $hex : 'CONVERT(' . $hex . ' USING utf8mb4)';
                                }
							}
						}
						fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ');' . "\n");
					}
					$offset += $count;
				} while ($count === $batch_size);
				fwrite($handle, "\n\n");
			}
            } finally {
                // Closing the snapshot is essential even if a table read fails.
                $this->db->query('ROLLBACK');
            }
		} finally { fclose($handle); }
		return $path;
	}

}