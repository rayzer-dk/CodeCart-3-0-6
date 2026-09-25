<?php
namespace CodeCart\Core;

final class SchemaRegistry {
    private $db;
    private $expected;
    private $mariaDb = null;

    public function __construct($registry) {
        $this->db = $registry->get('db');
        $file = DIR_SYSTEM . 'config/codecart_schema.json';
        $json = is_file($file) ? json_decode((string)file_get_contents($file), true) : array();
        $this->expected = is_array($json) ? $json : array();
    }

    public function expectedVersion(): string {
        return isset($this->expected['version']) ? (string)$this->expected['version'] : '';
    }

    public function diff(int $limit = 300): array {
        $limit = max(1, min(1000, $limit));
        $issues = array();
        $summary = array('tables_expected'=>0,'tables_present'=>0,'missing_tables'=>0,'missing_columns'=>0,'type_mismatches'=>0,'engine_mismatches'=>0,'charset_mismatches'=>0,'missing_indexes'=>0,'external_tables'=>0,'external_columns'=>0,'external_indexes'=>0,'blocking_issues'=>0,'warnings'=>0,'informational'=>0);
        $tables = isset($this->expected['tables']) && is_array($this->expected['tables']) ? $this->expected['tables'] : array();
        $summary['tables_expected'] = count($tables);
        $dbName = defined('DB_DATABASE') ? DB_DATABASE : '';

        $actualTables = array();
        $q = $this->db->query("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $this->db->escape($dbName) . "'");
        foreach ($q->rows as $row) { $actualTables[(string)$row['TABLE_NAME']] = $row; }
        $expectedPhysical=array(); foreach(array_keys($tables) as $base){$expectedPhysical[DB_PREFIX.$base]=true;}
        foreach($actualTables as $physical=>$row){if(!isset($expectedPhysical[$physical])){$summary['external_tables']++;$this->issue($issues,$limit,'external_table',$physical,'','Additional table (third-party/custom); informational only','info','external');}}

        $columns = array();
        $q = $this->db->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $this->db->escape($dbName) . "'");
        foreach ($q->rows as $row) { $columns[(string)$row['TABLE_NAME']][(string)$row['COLUMN_NAME']] = $row; }

        $indexes = array();
        $q = $this->db->query("SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '" . $this->db->escape($dbName) . "' ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX");
        foreach ($q->rows as $row) {
            $t=(string)$row['TABLE_NAME']; $n=(string)$row['INDEX_NAME'];
            if (!isset($indexes[$t][$n])) { $indexes[$t][$n]=array('unique'=>(int)$row['NON_UNIQUE']===0,'columns'=>array()); }
            $indexes[$t][$n]['columns'][]=(string)$row['COLUMN_NAME'];
        }

        foreach ($tables as $base => $spec) {
            $table = DB_PREFIX . $base;
            if (!isset($actualTables[$table])) {
                $summary['missing_tables']++;
                $this->issue($issues,$limit,'missing_table',$base,'','Missing table','error','core');
                continue;
            }
            $summary['tables_present']++;
            $actual = $actualTables[$table];
            if (!empty($spec['engine']) && strcasecmp((string)$actual['ENGINE'], (string)$spec['engine']) !== 0) {
                $summary['engine_mismatches']++;
                $this->issue($issues,$limit,'engine',$base,'',(string)$actual['ENGINE'].' != '.$spec['engine'],'warning','core',true);
            }
            $collation = strtolower((string)$actual['TABLE_COLLATION']);
            if (!empty($spec['charset']) && strpos($collation, strtolower((string)$spec['charset']).'_') !== 0) {
                $summary['charset_mismatches']++;
                $this->issue($issues,$limit,'charset',$base,'',$collation.' != '.$spec['charset'],'warning','core',true);
            }
            foreach (($spec['columns'] ?? array()) as $name => $columnSpec) {
                if (!isset($columns[$table][$name])) {
                    $summary['missing_columns']++;
                    $this->issue($issues,$limit,'missing_column',$base,$name,'Missing column','error','core');
                    continue;
                }
                $actualType=(string)$columns[$table][$name]['COLUMN_TYPE'];
                $expectedType=(string)$columnSpec['type'];
                if (!$this->compatibleType($expectedType,$actualType)) {
                    $summary['type_mismatches']++;
                    $safeType = $this->isSafeTypeWidening($expectedType,$actualType);
                    $this->issue($issues,$limit,'type',$base,$name,$actualType.' != '.$expectedType,$safeType ? 'warning' : 'error','core',$safeType);
                }
            }
            foreach (($spec['indexes'] ?? array()) as $indexSpec) {
                if (!$this->hasCompatibleIndex($indexes[$table] ?? array(), $indexSpec)) {
                    $summary['missing_indexes']++;
                    $this->issue($issues,$limit,'missing_index',$base,implode(',', $indexSpec['columns']),$indexSpec['unique'] ? 'Missing unique index' : 'Missing index','warning','core',empty($indexSpec['unique']));
                }
            }
        }
        foreach ($tables as $base => $spec) {
            $table = DB_PREFIX . $base;
            if (!isset($actualTables[$table])) { continue; }
            $expectedColumns = isset($spec['columns']) && is_array($spec['columns']) ? $spec['columns'] : array();
            foreach (array_keys($columns[$table] ?? array()) as $columnName) {
                if (!array_key_exists($columnName, $expectedColumns)) {
                    $summary['external_columns']++;
                    $this->issue($issues,$limit,'external_column',$base,$columnName,'Additional column (third-party/custom); informational only','info','external');
                }
            }
            $expectedIndexSignatures = array();
            foreach (($spec['indexes'] ?? array()) as $indexSpec) {
                $expectedIndexSignatures[] = (!empty($indexSpec['unique']) ? 'U:' : 'N:') . implode(',', array_values($indexSpec['columns'] ?? array()));
            }
            foreach (($indexes[$table] ?? array()) as $indexName => $indexData) {
                if ($indexName === 'PRIMARY') { continue; }
                $signature = (!empty($indexData['unique']) ? 'U:' : 'N:') . implode(',', array_values($indexData['columns'] ?? array()));
                if (!in_array($signature, $expectedIndexSignatures, true)) {
                    $summary['external_indexes']++;
                    $this->issue($issues,$limit,'external_index',$base,implode(',', $indexData['columns'] ?? array()),'Additional index ' . $indexName . ' (third-party/custom); informational only','info','external');
                }
            }
        }

        foreach ($issues as $issue) {
            if (($issue['severity'] ?? '') === 'error') { $summary['blocking_issues']++; }
            elseif (($issue['severity'] ?? '') === 'warning') { $summary['warnings']++; }
            else { $summary['informational']++; }
        }
        return array('version'=>$this->expectedVersion(),'summary'=>$summary,'issues'=>$issues,'truncated'=>count($issues)>=$limit);
    }

    public function repairSafe(int $limit = 300): array {
        $limit = max(1, min(1000, $limit));
        $result = array('fixed'=>0, 'skipped'=>0, 'errors'=>0, 'messages'=>array());
        $diff = $this->diff($limit);
        $tables = isset($this->expected['tables']) && is_array($this->expected['tables']) ? $this->expected['tables'] : array();

        foreach (($diff['issues'] ?? array()) as $issue) {
            if (empty($issue['fixable']) || ($issue['source'] ?? '') !== 'core') {
                $result['skipped']++;
                continue;
            }

            $base = isset($issue['table']) ? (string)$issue['table'] : '';
            if (!$this->safeIdentifier($base) || !isset($tables[$base])) {
                $result['errors']++;
                $result['messages'][] = 'Unsafe or unknown table: ' . $base;
                continue;
            }

            $table = DB_PREFIX . $base;
            $spec = $tables[$base];
            try {
                if ($issue['type'] === 'engine') {
                    $engine = isset($spec['engine']) ? (string)$spec['engine'] : '';
                    if (!$this->safeIdentifier($engine)) { throw new \RuntimeException('Invalid expected engine.'); }
                    $this->db->query("ALTER TABLE `" . $table . "` ENGINE=" . $engine);
                    $result['fixed']++;
                    continue;
                }

                if ($issue['type'] === 'charset') {
                    $charset = isset($spec['charset']) ? (string)$spec['charset'] : '';
                    $collation = isset($spec['collation']) ? (string)$spec['collation'] : ($charset !== '' ? $charset . '_unicode_ci' : '');
                    if (!$this->safeIdentifier($charset) || !$this->safeIdentifier($collation)) { throw new \RuntimeException('Invalid expected charset/collation.'); }
                    $this->db->query("ALTER TABLE `" . $table . "` CONVERT TO CHARACTER SET " . $charset . " COLLATE " . $collation);
                    $result['fixed']++;
                    continue;
                }

                if ($issue['type'] === 'type') {
                    $column = isset($issue['column']) ? (string)$issue['column'] : '';
                    $expectedType = isset($spec['columns'][$column]['type']) ? (string)$spec['columns'][$column]['type'] : '';
                    $actualType = $this->getColumnType($table, $column);
                    if ($expectedType === '' || $actualType === '' || !$this->isSafeTypeWidening($expectedType, $actualType)) {
                        $result['skipped']++;
                        continue;
                    }
                    $definition = $this->safeTypeDefinition($expectedType);
                    $meta = $this->getColumnMeta($table, $column);
                    if ($definition === '' || !$meta) { $result['skipped']++; continue; }
                    $nullSql = strtoupper((string)$meta['IS_NULLABLE']) === 'NO' ? ' NOT NULL' : ' NULL';
                    $defaultSql = $this->buildColumnDefaultSql($meta);
                    if ($defaultSql === null) { $result['skipped']++; continue; }
                    $extraSql = trim((string)$meta['EXTRA']) !== '' ? ' ' . trim((string)$meta['EXTRA']) : '';
                    $this->db->query("ALTER TABLE `" . $table . "` MODIFY `" . $column . "` " . $definition . $nullSql . $defaultSql . $extraSql);
                    $result['fixed']++;
                    continue;
                }

                if ($issue['type'] === 'missing_index') {
                    $columns = array_filter(array_map('trim', explode(',', (string)($issue['column'] ?? ''))), 'strlen');
                    if (!$columns) { throw new \RuntimeException('Index columns are missing.'); }
                    $matched = null;
                    foreach (($spec['indexes'] ?? array()) as $indexSpec) {
                        if (!empty($indexSpec['unique'])) { continue; }
                        if (array_values($indexSpec['columns'] ?? array()) === array_values($columns)) { $matched = $indexSpec; break; }
                    }
                    if (!$matched) { $result['skipped']++; continue; }
                    foreach ($columns as $column) {
                        if (!$this->safeIdentifier($column) || !isset($spec['columns'][$column])) { throw new \RuntimeException('Invalid index column.'); }
                    }
                    $baseName = preg_replace('/[^A-Za-z0-9_]+/', '_', implode('_', $columns));
                    $indexName = 'ccp_' . substr($baseName, 0, 38) . '_' . substr(sha1($base . ':' . implode(',', $columns)), 0, 8);
                    $quoted = array(); foreach ($columns as $column) { $quoted[] = '`' . $column . '`'; }
                    $this->db->query("ALTER TABLE `" . $table . "` ADD INDEX `" . $indexName . "` (" . implode(',', $quoted) . ")");
                    $result['fixed']++;
                    continue;
                }

                $result['skipped']++;
            } catch (\Throwable $e) {
                $result['errors']++;
                $result['messages'][] = $base . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    private function issue(array &$issues,int $limit,string $type,string $table,string $column,string $message,string $severity='warning',string $source='core',bool $fixable=false): void {
        if (count($issues) < $limit) { $issues[]=array('type'=>$type,'table'=>$table,'column'=>$column,'message'=>$message,'severity'=>$severity,'source'=>$source,'fixable'=>$fixable); }
    }

    private function safeIdentifier(string $identifier): bool {
        return $identifier !== '' && (bool)preg_match('/^[A-Za-z0-9_]+$/', $identifier);
    }

    private function compatibleType(string $expected,string $actual): bool {
        $e=strtolower(preg_replace('/\s+/', '', $expected));
        $a=strtolower(preg_replace('/\s+/', '', $actual));
        if ($e===$a) return true;
        $e=preg_replace('/^(tinyint|smallint|mediumint|int|bigint)\(\d+\)/','$1',$e);
        $a=preg_replace('/^(tinyint|smallint|mediumint|int|bigint)\(\d+\)/','$1',$a);
        if ($e===$a) return true;
        if (preg_match('/^varchar\((\d+)\)(.*)$/',$e,$em) && preg_match('/^varchar\((\d+)\)(.*)$/',$a,$am)) {
            return (int)$am[1] >= (int)$em[1] && $am[2] === $em[2];
        }
        if (preg_match('/^decimal\((\d+),(\d+)\)(.*)$/',$e,$em) && preg_match('/^decimal\((\d+),(\d+)\)(.*)$/',$a,$am)) {
            return (int)$am[1] >= (int)$em[1] && (int)$am[2] >= (int)$em[2] && $am[3] === $em[3];
        }
        $textRank=array('tinytext'=>1,'text'=>2,'mediumtext'=>3,'longtext'=>4);
        return isset($textRank[$e],$textRank[$a]) && $textRank[$a] >= $textRank[$e];
    }

    private function isSafeTypeWidening(string $expected,string $actual): bool {
        $e=strtolower(preg_replace('/\s+/', '', $expected));
        $a=strtolower(preg_replace('/\s+/', '', $actual));
        if (preg_match('/^varchar\((\d+)\)(.*)$/',$e,$em) && preg_match('/^varchar\((\d+)\)(.*)$/',$a,$am)) {
            return $am[2] === $em[2] && (int)$em[1] > (int)$am[1];
        }
        $textRank=array('tinytext'=>1,'text'=>2,'mediumtext'=>3,'longtext'=>4);
        return isset($textRank[$e],$textRank[$a]) && $textRank[$e] > $textRank[$a];
    }

    private function safeTypeDefinition(string $expected): string {
        $normalized=strtolower(preg_replace('/\s+/', '', $expected));
        if (preg_match('/^(varchar\(\d+\)|tinytext|text|mediumtext|longtext)(.*)$/',$normalized,$m)) return strtoupper($m[1]).$m[2];
        return '';
    }

    private function getColumnType(string $table,string $column): string {
        if (!$this->safeIdentifier($table) || !$this->safeIdentifier($column)) return '';
        $dbName=defined('DB_DATABASE') ? DB_DATABASE : '';
        $q=$this->db->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$this->db->escape($dbName)."' AND TABLE_NAME='".$this->db->escape($table)."' AND COLUMN_NAME='".$this->db->escape($column)."' LIMIT 1");
        return $q->num_rows ? (string)$q->row['COLUMN_TYPE'] : '';
    }

    private function getColumnMeta(string $table,string $column): array {
        if (!$this->safeIdentifier($table) || !$this->safeIdentifier($column)) return array();
        $dbName=defined('DB_DATABASE') ? DB_DATABASE : '';
        $q=$this->db->query("SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_DEFAULT IS NULL AS DEFAULT_IS_NULL, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$this->db->escape($dbName)."' AND TABLE_NAME='".$this->db->escape($table)."' AND COLUMN_NAME='".$this->db->escape($column)."' LIMIT 1");
        return $q->num_rows ? $q->row : array();
    }

    private function buildColumnDefaultSql(array $meta): ?string {
        if (!array_key_exists('COLUMN_DEFAULT', $meta)) return '';
        if (!empty($meta['DEFAULT_IS_NULL'])) return '';

        $raw = (string)$meta['COLUMN_DEFAULT'];
        if ($raw === '') {
            return $this->isMariaDb() ? null : " DEFAULT ''";
        }

        if ($this->isMariaDb()) {
            if (strcasecmp($raw, 'NULL') === 0) return ' DEFAULT NULL';

            $length = strlen($raw);
            if ($length >= 2 && $raw[0] === "'" && $raw[$length - 1] === "'") {
                $value = substr($raw, 1, -1);
                $value = str_replace("''", "'", $value);
                return " DEFAULT '" . $this->db->escape($value) . "'";
            }

            // Expressions/default functions are intentionally not reconstructed by the safe fixer.
            return null;
        }

        return " DEFAULT '" . $this->db->escape($raw) . "'";
    }

    private function isMariaDb(): bool {
        if ($this->mariaDb !== null) return $this->mariaDb;
        try {
            $q = $this->db->query("SELECT VERSION() AS version");
            $version = $q->num_rows ? (string)$q->row['version'] : '';
            $this->mariaDb = stripos($version, 'mariadb') !== false;
        } catch (\Throwable $e) {
            $this->mariaDb = false;
        }
        return $this->mariaDb;
    }

    private function hasCompatibleIndex(array $actualIndexes,array $expected): bool {
        $cols=array_values($expected['columns'] ?? array());
        $unique=!empty($expected['unique']);
        foreach ($actualIndexes as $actual) {
            if ($unique && empty($actual['unique'])) continue;
            if (array_values($actual['columns']) === $cols) return true;
        }
        return false;
    }
}
