<?php
declare(strict_types=1);

// Native SQL regression helper for disposable upgrade fixtures only.
if (PHP_SAPI === 'cli' && count($argv) === 1) {
    require __DIR__ . '/qa_schema_upgrade_standalone.php';
    exit;
}
if (PHP_SAPI !== 'cli' || count($argv) !== 3 || !in_array($argv[1], array('upgrade', 'parity', 'probe'), true)) {
    fwrite(STDERR, "Usage: php qa_schema_upgrade.php upgrade|parity|probe fixture/config.php\n");
    exit(2);
}
require $argv[2];
if (DB_HOSTNAME !== '127.0.0.1' || (string)DB_PORT !== '33117' || !preg_match('/^cc211_(schema_|up_)[a-z0-9_]+$/', DB_DATABASE)) {
    throw new RuntimeException('Expected an isolated loopback upgrade fixture.');
}
if ($argv[1] !== 'parity' && strpos(DB_DATABASE, 'cc211_schema_') !== 0) {
    throw new RuntimeException('Writes require a dedicated schema fixture.');
}
require DIR_SYSTEM . 'startup.php';
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->query("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
$registry = new Registry();
$registry->set('db', $db);
$config = new Config();
foreach ($db->query("SELECT `key`, value FROM `" . DB_PREFIX . "setting` WHERE store_id=0")->rows as $row) {
    $config->set($row['key'], $row['value']);
}
$registry->set('config', $config);
$registry->set('log', new Log('schema-upgrade-qa.log'));
$failures = array();
if ($argv[1] === 'upgrade') {
    (new \CodeCart\Migration($registry))->run();
    $result = array('core' => $config->get('codecart_core_schema_version'), 'revision' => $config->get('codecart_presentation_schema_version'));
    if ($result['core'] !== \CodeCart\Migration::VERSION || $result['revision'] !== \CodeCart\Migration::PRESENTATION_SCHEMA_VERSION) {
        fwrite(STDERR, "Upgrade did not publish current schema markers.\n");
        exit(1);
    }
} elseif ($argv[1] === 'parity') {
    $diff = (new \CodeCart\Core\SchemaRegistry($registry))->diff(1000);
    $result = array_values(array_filter($diff['issues'], static function (array $issue): bool {
        return in_array($issue['type'], array('missing_table', 'missing_column', 'type'), true);
    }));
} else {
    $ip = 'ffff:ffff:ffff:ffff:ffff:ffff:255.255.255.255';
    if (strlen($ip) !== 45 || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        throw new RuntimeException('Invalid IPv6 regression fixture.');
    }
    $fields = array('customer' => array('ip'), 'customer_activity' => array('ip'), 'customer_ip' => array('ip'), 'customer_login' => array('ip'), 'customer_online' => array('ip'), 'customer_search' => array('ip'), 'order' => array('ip', 'forwarded_ip', 'shipping_company'));
    $result = array();
    foreach ($fields as $table => $columns) {
        foreach ($columns as $column) {
            $value = $column === 'shipping_company' ? str_repeat('C', 60) : $ip;
            $engine = $db->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $db->escape(DB_PREFIX . $table) . "'")->row['ENGINE'];
            if (strtoupper($engine) !== 'INNODB') { throw new RuntimeException('Probe requires transactional fixture tables.'); }
            $db->query("START TRANSACTION");
            try {
                if (!$db->query("SELECT 1 FROM `" . DB_PREFIX . $table . "` LIMIT 1")->num_rows) { throw new RuntimeException('Missing native SQL probe row.'); }
                $db->query("UPDATE `" . DB_PREFIX . $table . "` SET `" . $column . "`='" . $db->escape($value) . "' LIMIT 1");
                $actual = $db->query("SELECT `" . $column . "` AS value FROM `" . DB_PREFIX . $table . "` WHERE `" . $column . "`='" . $db->escape($value) . "' LIMIT 1")->row['value'];
                if ($actual !== $value) { throw new RuntimeException('Persisted value was truncated.'); }
                $result[] = $table . '.' . $column;
            } catch (Throwable $e) {
                $failures[] = array('field' => $table . '.' . $column, 'error' => $e->getMessage());
            } finally {
                $db->query("ROLLBACK");
            }
        }
    }
    $result = array('persisted' => $result, 'failures' => $failures);
}
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
