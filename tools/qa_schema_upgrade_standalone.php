<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$temp = str_replace('\\', '/', sys_get_temp_dir()) . '/codecart-schema-' . bin2hex(random_bytes(8));
mkdir($temp . '/config', 0750, true);
register_shutdown_function(static function () use ($temp): void {
    if (is_file($temp . '/codecart/core-migration.failed')) { unlink($temp . '/codecart/core-migration.failed'); }
    if (is_dir($temp . '/codecart')) { rmdir($temp . '/codecart'); }
    unlink($temp . '/config/codecart_schema.json');
    rmdir($temp . '/config');
    rmdir($temp);
});
define('DIR_SYSTEM', $temp . '/');
define('DIR_STORAGE', $temp . '/');
define('DB_DATABASE', 'schema_contract_fixture');
define('DB_PREFIX', 'qa_');
require $root . '/upload/system/engine/registry.php';
require $root . '/upload/system/library/config.php';
require $root . '/upload/system/library/codecart/migration.php';
require $root . '/upload/system/library/codecart/src/SchemaRegistry.php';

$checks = 0;
function schemaCheck(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
final class SchemaFixtureDb {
    public array $types;
    public array $queries = array();
    public function __construct(array $types) { $this->types = $types; }
    public function escape(string $value): string { return addslashes($value); }
    public function query(string $sql): object {
        $this->queries[] = $sql;
        $rows = array();
        if (strpos($sql, 'GET_LOCK(') !== false) {
            $rows[] = array('acquired' => 0);
        } elseif (strpos($sql, 'information_schema.TABLES') !== false) {
            foreach ($this->types as $table => $columns) { $rows[] = array('TABLE_NAME' => DB_PREFIX . $table, 'ENGINE' => 'InnoDB', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'); }
        } elseif (strpos($sql, 'information_schema.COLUMNS') !== false) {
            foreach ($this->types as $table => $columns) {
                foreach ($columns as $name => $type) { $rows[] = array('TABLE_NAME' => DB_PREFIX . $table, 'COLUMN_NAME' => $name, 'COLUMN_TYPE' => $type, 'IS_NULLABLE' => 'NO'); }
            }
        } elseif (strpos($sql, 'information_schema.STATISTICS') === false) {
            throw new RuntimeException('Unexpected fixture query: ' . $sql);
        }
        return (object)array('rows' => $rows, 'row' => $rows[0] ?? array(), 'num_rows' => count($rows));
    }
}
$ipFields = array('customer' => array('ip'), 'customer_activity' => array('ip'), 'customer_ip' => array('ip'), 'customer_login' => array('ip'), 'customer_online' => array('ip'), 'customer_search' => array('ip'), 'order' => array('ip', 'forwarded_ip'));
$types = array();
foreach ($ipFields as $table => $fields) { foreach ($fields as $field) { $types[$table][$field] = 'varchar(45)'; } }
$types['order']['shipping_company'] = 'varchar(60)';
$types['voucher']['code'] = 'varchar(10)';
$types['order_voucher']['code'] = 'varchar(10)';
$types['content'] = array('description' => 'text', 'body' => 'mediumtext');
$contract = array('version' => '3.0.6.0', 'tables' => array());
foreach ($types as $table => $columns) {
    $spec = array('engine' => 'InnoDB', 'charset' => 'utf8mb4', 'columns' => array(), 'indexes' => array());
    foreach ($columns as $name => $type) { $spec['columns'][$name] = array('type' => $type); }
    $contract['tables'][$table] = $spec;
}
file_put_contents($temp . '/config/codecart_schema.json', json_encode($contract));
$db = new SchemaFixtureDb($types);
$config = new Config();
$config->set('codecart_core_schema_version', '3.0.6.0');
$config->set('codecart_presentation_schema_version', '33');
$registry = new Registry();
$registry->set('db', $db);
$registry->set('config', $config);
$registry->set('log', null);
schemaCheck(\CodeCart\Migration::VERSION === '3.0.6.0', 'Platform version must remain unchanged.');
schemaCheck(\CodeCart\Migration::PRESENTATION_SCHEMA_VERSION === '33', 'Repair must advance the stored revision.');
(new \CodeCart\Migration($registry))->run();
schemaCheck($db->queries === array(), 'Current schema must perform zero SQL.');
foreach (array(array('3.0.6.0', '32'), array('3.0.5.1', '33')) as $markers) {
    $config->set('codecart_core_schema_version', $markers[0]);
    $config->set('codecart_presentation_schema_version', $markers[1]);
    $db->queries = array();
    (new \CodeCart\Migration($registry))->run();
    schemaCheck(count($db->queries) === 1 && strpos($db->queries[0], 'GET_LOCK(') !== false, 'Outdated schema must attempt the serialized upgrade.');
}
$schema = new \CodeCart\Core\SchemaRegistry($registry);
schemaCheck($schema->diff()['issues'] === array(), 'Exact types must satisfy the schema contract.');
foreach ($ipFields as $table => $fields) { foreach ($fields as $field) { $db->types[$table][$field] = 'varchar(80)'; } }
$db->types['order']['shipping_company'] = 'varchar(120)';
$db->types['voucher']['code'] = $db->types['order_voucher']['code'] = 'varchar(32)';
$db->types['content'] = array('description' => 'mediumtext', 'body' => 'longtext');
schemaCheck($schema->diff()['issues'] === array(), 'Safe varchar, voucher and text widening must remain compatible.');
foreach ($types as $table => $columns) {
    foreach ($columns as $field => $type) {
        $db->types = $types;
        $db->types[$table][$field] = $table === 'content' ? ($field === 'body' ? 'text' : 'tinytext') : 'varchar(' . ($field === 'shipping_company' || $type === 'varchar(45)' ? '40' : '9') . ')';
        $issues = $schema->diff()['issues'];
        schemaCheck(count($issues) === 1 && $issues[0]['type'] === 'type', 'Narrow type must remain visible: ' . $table . '.' . $field);
        schemaCheck($issues[0]['table'] === $table && $issues[0]['column'] === $field, 'Narrow type must identify the affected field.');
    }
}
final class MigrationPublicationDb {
    public bool $failJournal = true;
    public int $journalAttempts = 0;
    public int $releases = 0;
    public int $locks = 0;
    public array $markers = array('codecart_core_schema_version' => '3.0.6.0', 'codecart_presentation_schema_version' => '32');
    public function escape(string $value): string { return addslashes($value); }
    public function getLastId(): int { return 1; }
    public function query(string $sql): object {
        $rows = array();
        if (strpos($sql, 'GET_LOCK(') !== false) {
            $this->locks++;
            $rows[] = array('acquired' => 1);
        } elseif (strpos($sql, 'RELEASE_LOCK(') !== false) {
            $this->releases++;
        } elseif (strpos($sql, 'INSERT INTO `qa_codecart_migration`') === 0) {
            $this->journalAttempts++;
            if ($this->failJournal) { throw new RuntimeException('Injected late journal failure.'); }
        } elseif (strpos($sql, 'INFORMATION_SCHEMA.TABLES') !== false && preg_match("/TABLE_NAME = 'qa_(setting|codecart_migration)'/", $sql, $match)) {
            $rows[] = array('TABLE_NAME' => 'qa_' . $match[1]);
        } elseif (strpos($sql, 'SELECT') === 0 && strpos($sql, '`qa_setting`') !== false && preg_match("/`key`\\s*=\\s*'([^']+)'/", $sql, $match)) {
            $key = $match[1];
            if (isset($this->markers[$key])) {
                $rows[] = array('setting_id' => $key === 'codecart_core_schema_version' ? 1 : 2, 'value' => $this->markers[$key]);
            } elseif ($key === 'config_name') {
                $rows[] = array('setting_id' => 3, 'value' => 'Merchant fixture');
            }
        } elseif (strpos($sql, 'UPDATE `qa_setting` SET code=\'codecart_core\'') === 0 && preg_match("/value='([^']+)'.*setting_id='2'/", $sql, $match)) {
            $this->markers['codecart_presentation_schema_version'] = $match[1];
        }
        return (object)array('rows' => $rows, 'row' => $rows[0] ?? array(), 'num_rows' => count($rows));
    }
}
$publicationDb = new MigrationPublicationDb();
$publicationConfig = new Config();
foreach ($publicationDb->markers as $key => $value) { $publicationConfig->set($key, $value); }
$publicationConfig->set('codecart_setting_serialized_default', '1');
$publicationRegistry = new Registry();
$publicationRegistry->set('db', $publicationDb);
$publicationRegistry->set('config', $publicationConfig);
$publicationRegistry->set('log', null);
(new \CodeCart\Migration($publicationRegistry))->run();
schemaCheck($publicationDb->journalAttempts === 1, 'Injected failure must reach the late migration journal.');
schemaCheck($publicationDb->markers['codecart_presentation_schema_version'] === '32', 'A later failure must preserve the pending stored revision.');
schemaCheck($publicationConfig->get('codecart_presentation_schema_version') === '32', 'A later failure must preserve the pending in-memory revision.');
schemaCheck($publicationDb->releases === 1, 'Failed migration must release its advisory lock.');
$publicationDb->failJournal = false;
(new \CodeCart\Migration($publicationRegistry))->run();
schemaCheck($publicationDb->journalAttempts === 2, 'Pending revision must retry the full migration.');
schemaCheck($publicationDb->markers['codecart_presentation_schema_version'] === '33' && $publicationConfig->get('codecart_presentation_schema_version') === '33', 'Successful retry must publish the stored and in-memory revision.');
schemaCheck($publicationDb->releases === 2, 'Successful retry must release its advisory lock.');
(new \CodeCart\Migration($publicationRegistry))->run();
schemaCheck($publicationDb->locks === 2, 'Completed retry must restore zero-work steady state.');
echo 'Schema upgrade standalone: ' . $checks . " assertions PASS\n";
