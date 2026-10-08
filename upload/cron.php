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

if (!is_file(__DIR__ . '/config.php')) { exit("CodeCart PRO cron: config.php not found\n"); }
require_once(__DIR__ . '/config.php');
define('CODECART_CLI', PHP_SAPI === 'cli');
if (PHP_SAPI === 'cli') { $_GET['route'] = 'cron/codecart'; } elseif (!isset($_GET['route'])) { $_GET['route'] = 'cron/codecart'; }
require_once(DIR_SYSTEM . 'startup.php');
start('catalog');
