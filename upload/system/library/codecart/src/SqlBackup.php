<?php
namespace CodeCart\Core;

/**
 * Streams database tables into a restorable SQL file and verifies the result.
 * The format is the one the administration restore tool accepts: table definition,
 * TRUNCATE and batched INSERT statements with hex-encoded values.
 */
final class SqlBackup {
    public const COMPLETE_MARKER = '-- CodeCart SQL backup complete: tables=';

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function tables(): array {
        $tables = array();
        $query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "`");

        foreach ($query->rows as $row) {
            $table = (string)reset($row);
            if ($table !== '' && strncmp($table, DB_PREFIX, strlen(DB_PREFIX)) === 0) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * @return int number of tables written
     */
    public function dump(array $tables, string $path, int $batchSize = 500): int {
        $available = array_flip($this->tables());
        $batchSize = max(50, min(2000, $batchSize));
        $handle = @fopen($path, 'wb');
        if (!$handle) {
            throw new \RuntimeException('Unable to create database backup file.');
        }

        $written = 0;

        try {
            // Read all InnoDB rows from one consistent snapshot instead of mixing instants.
            $this->db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
            try {
                foreach ($tables as $table) {
                    $table = (string)$table;
                    if (!isset($available[$table]) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                        continue;
                    }

                    $schema = $this->db->query('SHOW CREATE TABLE `' . $table . '`');
                    $create = isset($schema->row['Create Table']) ? $schema->row['Create Table'] : '';
                    if (!is_string($create) || strpos($create, 'CREATE TABLE ') !== 0) {
                        throw new \RuntimeException('Cannot export table structure: ' . $table);
                    }
                    // Never DROP a table: the dump can prepare a fresh database and refill an existing one.
                    $create = preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $create, 1);

                    $types = array();
                    foreach ($this->db->query('SHOW FULL COLUMNS FROM `' . $table . '`')->rows as $column) {
                        if (isset($column['Field'], $column['Type'])) {
                            $types[(string)$column['Field']] = strtolower((string)$column['Type']);
                        }
                    }

                    fwrite($handle, '-- CodeCart SQL backup: table definition and contents' . "\n");
                    fwrite($handle, $create . ';' . "\n");
                    fwrite($handle, 'TRUNCATE TABLE `' . $table . '`;' . "\n\n");

                    $offset = 0;
                    do {
                        $query = $this->db->query('SELECT * FROM `' . $table . '` LIMIT ' . (int)$offset . ',' . (int)$batchSize);
                        $count = count($query->rows);

                        foreach ($query->rows as $result) {
                            $fields = array();
                            $values = array();

                            foreach ($result as $field => $value) {
                                $fields[] = '`' . str_replace('`', '``', $field) . '`';

                                if ($value === null) {
                                    $values[] = 'NULL';
                                } elseif ((string)$value === '') {
                                    $values[] = "''";
                                } else {
                                    $hex = '0x' . bin2hex((string)$value);
                                    $type = isset($types[$field]) ? $types[$field] : '';
                                    $binary = preg_match('/^(?:tinyblob|blob|mediumblob|longblob|binary|varbinary|bit|geometry|point|linestring|polygon|multipoint|multilinestring|multipolygon|geometrycollection)\b/i', $type) === 1;
                                    // A bare 0x literal is a number in numeric-column context
                                    // (product_id 15 would come back as 12597), so force text first.
                                    $values[] = $binary ? $hex : 'CONVERT(' . $hex . ' USING utf8mb4)';
                                }
                            }

                            fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ');' . "\n");
                        }

                        $offset += $count;
                    } while ($count === $batchSize);

                    fwrite($handle, "\n\n");
                    $written++;
                }
            } finally {
                $this->db->query('ROLLBACK');
            }

            fwrite($handle, self::COMPLETE_MARKER . $written . "\n");
        } finally {
            fclose($handle);
        }

        return $written;
    }

    /**
     * Confirms the file was written completely: the completion marker is last and
     * every announced table definition is present.
     */
    public function verify(string $path): bool {
        if (!is_file($path) || filesize($path) === 0) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if (!$handle) {
            return false;
        }

        $definitions = 0;
        $last = '';

        while (($line = fgets($handle, 1000000)) !== false) {
            if (strncmp($line, 'CREATE TABLE IF NOT EXISTS ', 27) === 0) {
                $definitions++;
            }
            if (trim($line) !== '') {
                $last = trim($line);
            }
        }

        fclose($handle);

        return strncmp($last, self::COMPLETE_MARKER, strlen(self::COMPLETE_MARKER)) === 0
            && (int)substr($last, strlen(self::COMPLETE_MARKER)) === $definitions;
    }
}
