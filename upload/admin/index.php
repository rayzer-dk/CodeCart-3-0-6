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

// Configuration
if (is_file('config.php')) {
	/** @phpstan-ignore-next-line requireOnce.fileNotFound */
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: ../install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

start('admin');
