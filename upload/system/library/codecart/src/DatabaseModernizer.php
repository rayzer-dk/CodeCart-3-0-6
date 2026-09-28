<?php
namespace CodeCart\Core;

class DatabaseModernizer {
    private $db;
    private $log;
    private $config;
    private $collation = 'utf8mb4_unicode_ci';

    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
        $this->config = $registry->get('config');
    }

    public function preflight() {
        $server = $this->serverInfo();
        $capabilities = $this->capabilities($server);
        $tables = $this->tables();
        $summary = array('total' => count($tables), 'engine' => 0, 'charset' => 0, 'skipped' => 0, 'large' => 0, 'fulltext' => 0, 'foreign_keys' => 0, 'blocked' => 0, 'bytes' => 0);
        $items = array();

        foreach ($tables as $row) {
            $engine = strtoupper((string)$row['ENGINE']);
            $collation = strtolower((string)$row['TABLE_COLLATION']);
            $bytes = (int)$row['DATA_LENGTH'] + (int)$row['INDEX_LENGTH'];
            $needsEngine = $engine !== 'INNODB';
            $needsCharset = $collation !== '' && strpos($collation, 'utf8mb4_') !== 0;
            $supported = in_array($engine, array('INNODB', 'MYISAM', 'ARIA'), true);
            $fulltext = $this->hasFulltext((string)$row['TABLE_NAME']);
            $foreignKeys = $this->foreignKeyCount((string)$row['TABLE_NAME']);
            $risks = $this->conversionRisks((string)$row['TABLE_NAME'], $fulltext, $foreignKeys, $capabilities);

            if ($needsEngine) $summary['engine']++;
            if ($needsCharset) $summary['charset']++;
            if (!$supported) $summary['skipped']++;
            if ($bytes >= 268435456) $summary['large']++;
            if ($fulltext) $summary['fulltext']++;
            if ($foreignKeys > 0) $summary['foreign_keys']++;
            if (!empty($risks)) $summary['blocked']++;
            $summary['bytes'] += $bytes;

            if ($needsEngine || $needsCharset || !$supported) {
                $items[] = array(
                    'table' => (string)$row['TABLE_NAME'],
                    'engine' => $engine,
                    'collation' => (string)$row['TABLE_COLLATION'],
                    'size_bytes' => $bytes,
                    'row_format' => isset($row['ROW_FORMAT']) ? (string)$row['ROW_FORMAT'] : '',
                    'fulltext' => $fulltext,
                    'foreign_keys' => $foreignKeys,
                    'needs_engine' => $needsEngine,
                    'needs_charset' => $needsCharset,
                    'supported' => $supported,
                    'risks' => $risks
                );
            }
        }

        return array(
            'server' => $server,
            'capabilities' => $capabilities,
            'target' => array('engine' => 'InnoDB', 'row_format' => 'DYNAMIC', 'charset' => 'utf8mb4', 'collation' => $this->collation),
            'summary' => $summary,
            'tables' => $items,
            'backup_required' => true
        );
    }

    /**
     * @param bool $includeLarge convert tables >= 256 MB (CLI only by policy)
     * @param int  $limit        0 = all tables; N = stop after N converted tables (web batches)
     */
    public function migrate($includeLarge = false, $limit = 0) {
        $limit = max(0, (int)$limit);
        $lock = 'codecart_db_modernizer_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX), 0, 32);
        $acquired = false;
        $result = array('changed' => array(), 'skipped' => array(), 'errors' => array());
        $server = $this->serverInfo();
        $capabilities = $this->capabilities($server);

        try {
            $q = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 0) AS acquired");
            $acquired = !empty($q->row['acquired']);
            if (!$acquired) {
                return array('changed' => array(), 'skipped' => array(), 'errors' => array(array('table' => '', 'error' => 'Database modernization is already running.')));
            }

            foreach ($this->tables() as $row) {
                $table = (string)$row['TABLE_NAME'];
                $engine = strtoupper((string)$row['ENGINE']);
                $collation = strtolower((string)$row['TABLE_COLLATION']);
                $bytes = (int)$row['DATA_LENGTH'] + (int)$row['INDEX_LENGTH'];
                $supported = in_array($engine, array('INNODB', 'MYISAM', 'ARIA'), true);
                $needsEngine = $engine !== 'INNODB';
                $needsCharset = $collation !== '' && strpos($collation, 'utf8mb4_') !== 0;

                if (!$needsEngine && !$needsCharset) {
                    continue;
                }
                if (!$supported) {
                    $result['skipped'][] = array('table' => $table, 'reason' => 'Unsupported storage engine ' . $engine);
                    continue;
                }
                if (!$includeLarge && $bytes >= 268435456) {
                    $result['skipped'][] = array('table' => $table, 'reason' => 'Large table requires --large', 'size_bytes' => $bytes);
                    continue;
                }

                $fulltext = $this->hasFulltext($table);
                $foreignKeys = $this->foreignKeyCount($table);
                $risks = $this->conversionRisks($table, $fulltext, $foreignKeys, $capabilities);
                if (!empty($risks)) {
                    $result['skipped'][] = array('table' => $table, 'reason' => 'Preflight blocker', 'risks' => $risks);
                    continue;
                }

                try {
                    $clauses = array();
                    if ($needsEngine) {
                        $clauses[] = 'ENGINE=InnoDB';
                    }
                    if ($needsEngine || $needsCharset) {
                        $clauses[] = 'ROW_FORMAT=DYNAMIC';
                    }
                    if ($needsCharset) {
                        $clauses[] = 'CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $this->collation;
                    }
                    if ($clauses) {
                        // Use one ALTER per table so engine/row-format/charset modernization is one DDL operation.
                        $this->db->query("ALTER TABLE `" . $this->identifier($table) . "` " . implode(', ', $clauses));
                    }
                    $result['changed'][] = $table;
                    if ($limit > 0 && count($result['changed']) >= $limit) {
                        break;
                    }
                } catch (\Throwable $e) {
                    $result['errors'][] = array('table' => $table, 'error' => $e->getMessage());
                    if ($this->log) {
                        $this->log->write('CodeCart PRO DB modernization failed for ' . $table . ': ' . $e->getMessage());
                    }
                }
            }
        } finally {
            if ($acquired) {
                try { $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')"); } catch (\Throwable $e) {}
            }
        }

        $result['preflight_after'] = $this->preflight()['summary'];
        $required = ((int)$result['preflight_after']['engine'] > 0 || (int)$result['preflight_after']['charset'] > 0) ? 1 : 0;
        $this->saveRequiredFlag($required);
        return $result;
    }

    private function tables() {
        $q = $this->db->query("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, ROW_FORMAT, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND LEFT(TABLE_NAME, " . (int)strlen(DB_PREFIX) . ") = '" . $this->db->escape(DB_PREFIX) . "' ORDER BY TABLE_NAME ASC");
        return $q->rows;
    }

    private function hasFulltext($table) {
        $q = $this->db->query("SHOW INDEX FROM `" . $this->identifier($table) . "`");
        foreach ($q->rows as $row) {
            if (strtoupper((string)$row['Index_type']) === 'FULLTEXT') return true;
        }
        return false;
    }


    private function foreignKeyCount($table) {
        $q = $this->db->query("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL AND (TABLE_NAME = '" . $this->db->escape($table) . "' OR REFERENCED_TABLE_NAME = '" . $this->db->escape($table) . "')");
        return isset($q->row['total']) ? (int)$q->row['total'] : 0;
    }

    private function capabilities(array $server) {
        $maxIndexBytes = 3072;
        $largePrefix = null;
        $dynamicRowFormat = true;

        try {
            $q = $this->db->query("SHOW VARIABLES LIKE 'innodb_large_prefix'");
            if ($q->num_rows) {
                $value = strtoupper((string)$q->row['Value']);
                $largePrefix = in_array($value, array('1', 'ON', 'TRUE'), true);
                if (!$largePrefix) {
                    $maxIndexBytes = 767;
                }
            }
        } catch (\Throwable $e) {
            // New MySQL/MariaDB versions removed this legacy variable and support large prefixes by default.
        }


        try {
            $q = $this->db->query("SHOW VARIABLES LIKE 'innodb_file_format'");
            if ($q->num_rows) {
                $format = strtoupper((string)$q->row['Value']);
                if ($format !== '' && $format !== 'BARRACUDA') {
                    $dynamicRowFormat = false;
                }
            }
        } catch (\Throwable $e) {
            // Modern MySQL/MariaDB no longer expose this legacy variable.
        }

        $vendor = stripos((string)$server['version'], 'mariadb') !== false || stripos((string)$server['comment'], 'mariadb') !== false ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/[^0-9.].*$/', '', (string)$server['version']);
        $innoDbFulltext = true;
        if ($vendor === 'MySQL' && $version !== '' && version_compare($version, '5.6.0', '<')) {
            $innoDbFulltext = false;
        }
        if ($vendor === 'MariaDB' && $version !== '' && version_compare($version, '10.0.5', '<')) {
            $innoDbFulltext = false;
        }

        return array(
            'vendor' => $vendor,
            'version' => $version,
            'innodb_large_prefix' => $largePrefix,
            'max_index_bytes' => $maxIndexBytes,
            'innodb_fulltext' => $innoDbFulltext,
            'dynamic_row_format' => $dynamicRowFormat
        );
    }

    private function conversionRisks($table, $hasFulltext, $foreignKeys, array $capabilities) {
        $risks = array();

        if ($hasFulltext && empty($capabilities['innodb_fulltext'])) {
            $risks[] = 'Server does not support FULLTEXT indexes on InnoDB.';
        }

        if (empty($capabilities['dynamic_row_format'])) {
            $risks[] = 'Server configuration does not safely support InnoDB ROW_FORMAT=DYNAMIC.';
        }

        if ((int)$foreignKeys > 0) {
            $risks[] = 'Table participates in foreign keys; coordinated charset/engine migration is required.';
        }

        $limit = isset($capabilities['max_index_bytes']) ? (int)$capabilities['max_index_bytes'] : 3072;
        $q = $this->db->query("SELECT s.INDEX_NAME, s.SEQ_IN_INDEX, s.COLUMN_NAME, s.SUB_PART, s.INDEX_TYPE, c.DATA_TYPE, c.CHARACTER_MAXIMUM_LENGTH, c.CHARACTER_OCTET_LENGTH FROM INFORMATION_SCHEMA.STATISTICS s LEFT JOIN INFORMATION_SCHEMA.COLUMNS c ON c.TABLE_SCHEMA = s.TABLE_SCHEMA AND c.TABLE_NAME = s.TABLE_NAME AND c.COLUMN_NAME = s.COLUMN_NAME WHERE s.TABLE_SCHEMA = DATABASE() AND s.TABLE_NAME = '" . $this->db->escape($table) . "' ORDER BY s.INDEX_NAME, s.SEQ_IN_INDEX");
        $indexes = array();

        foreach ($q->rows as $row) {
            $type = strtoupper((string)$row['INDEX_TYPE']);
            if ($type === 'FULLTEXT' || $type === 'SPATIAL') {
                continue;
            }
            $name = (string)$row['INDEX_NAME'];
            $dataType = strtolower((string)$row['DATA_TYPE']);
            $subPart = $row['SUB_PART'] !== null ? (int)$row['SUB_PART'] : 0;
            $chars = $subPart > 0 ? $subPart : (int)$row['CHARACTER_MAXIMUM_LENGTH'];
            $bytes = 0;

            if (in_array($dataType, array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'set'), true)) {
                $bytes = $chars > 0 ? $chars * 4 : 0; // Target is utf8mb4: up to four bytes per character.
            } elseif (in_array($dataType, array('binary', 'varbinary', 'tinyblob', 'blob', 'mediumblob', 'longblob'), true)) {
                $bytes = $subPart > 0 ? $subPart : (int)$row['CHARACTER_OCTET_LENGTH'];
            } else {
                $bytes = $this->numericIndexBytes($dataType);
            }

            if (!isset($indexes[$name])) {
                $indexes[$name] = 0;
            }
            $indexes[$name] += $bytes;
        }

        foreach ($indexes as $name => $bytes) {
            if ($bytes > $limit) {
                $risks[] = 'Index ' . $name . ' may require ' . $bytes . ' bytes after utf8mb4 conversion; server limit is ' . $limit . ' bytes.';
            }
        }

        return $risks;
    }

    private function numericIndexBytes($dataType) {
        $sizes = array(
            'tinyint' => 1, 'smallint' => 2, 'mediumint' => 3, 'int' => 4, 'integer' => 4,
            'bigint' => 8, 'float' => 4, 'double' => 8, 'real' => 8, 'date' => 3,
            'datetime' => 8, 'timestamp' => 4, 'time' => 3, 'year' => 1, 'decimal' => 16, 'numeric' => 16,
            'bit' => 8, 'bool' => 1, 'boolean' => 1
        );
        return isset($sizes[$dataType]) ? $sizes[$dataType] : 16;
    }

    private function serverInfo() {
        $q = $this->db->query("SELECT VERSION() AS version, @@version_comment AS comment");
        return array('version' => isset($q->row['version']) ? (string)$q->row['version'] : '', 'comment' => isset($q->row['comment']) ? (string)$q->row['comment'] : '');
    }

    private function identifier($name) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$name)) {
            throw new \RuntimeException('Unsafe table identifier.');
        }
        return (string)$name;
    }

    private function saveRequiredFlag($required) {
        $required = (int)$required ? 1 : 0;
        $q = $this->db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = 'codecart_db_modernization_required' LIMIT 1");
        if ($q->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "setting` SET code = 'codecart_core', value = '" . $required . "', serialized = '0' WHERE setting_id = '" . (int)$q->row['setting_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_db_modernization_required', value = '" . $required . "', serialized = '0'");
        }
        if ($this->config) {
            $this->config->set('codecart_db_modernization_required', $required);
        }
    }
}
