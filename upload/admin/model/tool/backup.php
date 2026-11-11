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
		(new \CodeCart\Core\SqlBackup($this->db))->dump(is_array($tables) ? $tables : array(), $path, (int)$batch_size);

		return $path;
	}

}
