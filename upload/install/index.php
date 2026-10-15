<?php
// Version
define('VERSION', '3.0.6.0');
define('VERSION_CORE', 'CodeCart PRO');
define('VERSION_BUILD', '0013');
define('VERSION_LANGPACK', 'UK-EN');
define('CODECART_BUILD', '3.0.6.0');
define('CODECART_PACKAGE_BUILD', '2.0.8');
define('CODECART_CHANNEL', '');
define('CODECART_BASE', 'CodeCart PRO 3.0.6.0');
define('CODECART_UPSTREAM', 'OpenCart 3.0.5.1');

error_reporting(E_ALL);

// Resolve filesystem paths before URL detection so the same trusted-proxy
// helper is used by installer, catalog and admin.
define('DIR_OPENCART', str_replace('\\', '/', realpath(__DIR__ . '/../')) . '/');
define('DIR_APPLICATION', str_replace('\\', '/', realpath(__DIR__)) . '/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
require_once(DIR_SYSTEM . 'helper/general.php');

$protocol = codecart_is_https() ? 'https://' : 'http://';
$http_host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)$_SERVER['HTTP_HOST']) : '';
if ($http_host === '') {
	$http_host = 'localhost';
}
$script_name = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '/install/index.php';
$install_path = rtrim(str_replace('\\', '/', dirname($script_name)), '/');
$store_path = preg_replace('#/install$#i', '', $install_path);

define('HTTP_SERVER', $protocol . $http_host . ($install_path !== '' ? $install_path : '/install') . '/');
define('HTTP_OPENCART', $protocol . $http_host . ($store_path !== '' ? $store_path : '') . '/');

// Step 3 writes this local bootstrap pointer after moving storage outside the
// public web root. It contains only a server filesystem path, no credentials.
// During UPDATE prefer the existing store's literal DIR_STORAGE definition so
// Installer 2.0 validates the real runtime storage instead of system/storage.
$storage = DIR_SYSTEM . 'storage/';
$existing_config = DIR_OPENCART . 'config.php';
if (is_file($existing_config) && filesize($existing_config) > 0) {
	$contents = @file_get_contents($existing_config);
	if (is_string($contents) && preg_match('/define\(\s*([\'"])(DIR_STORAGE)\1\s*,\s*([\'"])((?:\\.|(?!\3).)*)\3\s*\)\s*;/s', $contents, $match)) {
		$candidate = stripcslashes($match[4]);
		$candidate = rtrim(str_replace('\\', '/', $candidate), '/') . '/';
		if (is_dir($candidate)) {
			$storage = $candidate;
		}
	}
}
$storage_pointer = DIR_APPLICATION . 'storage-path.php';
if (is_file($storage_pointer)) {
	$candidate = require($storage_pointer);
	if (is_string($candidate)) {
		$candidate = rtrim(str_replace('\\', '/', $candidate), '/') . '/';
		if (is_dir($candidate)) {
			$storage = $candidate;
		}
	}
}

define('DIR_STORAGE', $storage);
define('DIR_IMAGE', DIR_OPENCART . 'image/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_DATABASE', DIR_SYSTEM . 'database/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

require_once(DIR_SYSTEM . 'startup.php');

start('install');
