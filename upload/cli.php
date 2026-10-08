<?php
// Version
define('VERSION', '3.0.6.0');
define('VERSION_CORE', 'CodeCart PRO');
define('VERSION_BUILD', '0013');
define('VERSION_LANGPACK', 'UK-EN');
define('CODECART_BUILD', '3.0.6.0');
define('CODECART_PACKAGE_BUILD', '2.0.4');
define('CODECART_CHANNEL', '');
define('CODECART_BASE', 'CodeCart PRO 3.0.6.0');
define('CODECART_UPSTREAM', 'OpenCart 3.0.5.1');

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!is_file(__DIR__ . '/config.php')) { fwrite(STDERR, "CodeCart PRO CLI: config.php not found\n"); exit(2); }

$command = $argv[1] ?? 'cron:run';
$modes = array(
    'cron:run'      => 'all',
    'scheduler:run' => 'scheduler',
    'queue:run'     => 'queue',
    'queue:work'    => 'queue',
    'queue:purge'   => 'queue_purge',
    'cron:list'     => 'scheduler_list',
    'cron:key'      => 'key',
    'db:preflight'  => 'db_preflight',
    'db:migrate'    => 'db_migrate',
    'styles:rebuild' => 'styles_rebuild'
);

if (!isset($modes[$command])) {
    fwrite(STDERR, "Usage: php cli.php cron:run|scheduler:run|queue:run|queue:work|queue:purge|cron:list|cron:key|db:preflight|db:migrate --backup-confirmed [--large]|styles:rebuild\n");
    exit(2);
}

require_once(__DIR__ . '/config.php');
define('CODECART_CLI', true);
$_GET['route'] = 'cron/codecart';
$_GET['mode'] = $modes[$command];

if ($command === 'queue:purge' && isset($argv[2])) {
    $_GET['hours'] = max(1, (int)$argv[2]);
}
if ($command === 'db:migrate') {
    if (!in_array('--backup-confirmed', $argv, true)) {
        fwrite(STDERR, "Refusing database modernization: create and verify a full database backup, then rerun with --backup-confirmed\n");
        exit(3);
    }
    $_GET['backup_confirmed'] = '1';
    if (in_array('--large', $argv, true)) {
        $_GET['large'] = '1';
    }
}

require_once(DIR_SYSTEM . 'startup.php');
start('catalog');
