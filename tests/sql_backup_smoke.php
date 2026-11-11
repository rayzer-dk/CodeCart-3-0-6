<?php
/** SQL backup verification and scheduler wiring: php tests/sql_backup_smoke.php */
$root = dirname(__DIR__) . '/upload/';
require_once $root . 'system/library/codecart/src/SqlBackup.php';

$failures = array();
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cc_sqlbackup_' . bin2hex(random_bytes(4)) . '.sql';
$marker = \CodeCart\Core\SqlBackup::COMPLETE_MARKER;
$backup = new \CodeCart\Core\SqlBackup(new stdClass());

$table = "-- CodeCart SQL backup: table definition and contents\nCREATE TABLE IF NOT EXISTS `oc_a` (\n  `id` int(11) NOT NULL\n) ENGINE=InnoDB;\nTRUNCATE TABLE `oc_a`;\nINSERT INTO `oc_a` (`id`) VALUES (CONVERT(0x31 USING utf8mb4));\n\n\n";

$cases = array(
    'complete file' => array($table . $marker . "1\n", true),
    'truncated file without marker' => array($table, false),
    'marker announces more tables than present' => array($table . $marker . "2\n", false),
    'empty file' => array('', false),
);

foreach ($cases as $name => $case) {
    file_put_contents($tmp, $case[0]);
    if ($backup->verify($tmp) !== $case[1]) {
        $failures[] = 'verify(): ' . $name;
    }
}
@unlink($tmp);

if ($backup->verify($tmp)) {
    $failures[] = 'verify(): missing file must not pass';
}

$sql = (string)file_get_contents($root . 'install/opencart.sql');
$migration = (string)file_get_contents($root . 'system/library/codecart/migration.php');
$heartbeat = (string)file_get_contents($root . 'system/library/codecart/src/Heartbeat.php');

if (strpos($sql, "('core.backup.database','cron/backup'") === false) {
    $failures[] = 'The backup task is not seeded by the installer.';
}
if (strpos($sql, "('core.backup.database','cron/backup','{\"keep\":7}',86400,0,") === false) {
    $failures[] = 'The backup task must be seeded disabled with keep=7.';
}
if (strpos($migration, "'code' => 'core.backup.database', 'route' => 'cron/backup'") === false) {
    $failures[] = 'The backup task is not created for updated stores.';
}
if (strpos($heartbeat, "'core.backup.database'") === false) {
    $failures[] = 'The visitor heartbeat must never run the backup task.';
}
if (!is_file($root . 'catalog/controller/cron/backup.php')) {
    $failures[] = 'catalog/controller/cron/backup.php is missing.';
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS SQL backup completeness check and scheduled backup wiring\n";
