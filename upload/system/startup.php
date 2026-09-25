<?php
// Error Reporting
error_reporting(E_ALL);

// Check Version
if (version_compare(phpversion(), '8.1.0', '<') == true) {
	exit('PHP 8.1+ Required');
}

if (!ini_get('date.timezone')) {
	date_default_timezone_set('UTC');
}

// Windows IIS Compatibility
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
	if (isset($_SERVER['SCRIPT_FILENAME'])) {
		$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
	}
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
	if (isset($_SERVER['PATH_TRANSLATED'])) {
		$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
	}
}

if (!isset($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];

	if (isset($_SERVER['QUERY_STRING'])) {
		$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
}

if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
}

require_once(DIR_SYSTEM . 'helper/general.php');

if (!defined('CACHE_PREFIX')) {
	define('CACHE_PREFIX', codecart_cache_prefix());
}

// Check if SSL. Forwarded headers are accepted only from explicitly trusted proxies.
$_SERVER['HTTPS'] = codecart_is_https();

// Modification Override
function modification($filename) {
	if (defined('DIR_CATALOG')) {
		$file = DIR_MODIFICATION . 'admin/' .  substr($filename, strlen(DIR_APPLICATION));
	} elseif (defined('DIR_OPENCART')) {
		$file = DIR_MODIFICATION . 'install/' .  substr($filename, strlen(DIR_APPLICATION));
	} else {
		$file = DIR_MODIFICATION . 'catalog/' . substr($filename, strlen(DIR_APPLICATION));
	}

	if (substr($filename, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
		$file = DIR_MODIFICATION . 'system/' . substr($filename, strlen(DIR_SYSTEM));
	}

	if (is_file($file)) {
		$marker = rtrim(DIR_MODIFICATION, '/\\') . '/.codecart-build';
		$expected = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : (defined('CODECART_BUILD') ? (string)CODECART_BUILD : '');
		$actual = is_file($marker) ? trim((string)file_get_contents($marker)) : '';

		// Never execute a generated modification tree built against another Core build.
		// After an update the original Core runs until Modifications are refreshed.
		if ($expected === '' || hash_equals($expected, $actual)) {
			return $file;
		}
	}

	return $filename;
}

// Modern CodeCart PRO internal PSR-4 layer. Legacy OpenCart 3 autoload remains unchanged.
require_once(DIR_SYSTEM . 'library/codecart/psr4.php');
CodeCartPsr4::register('CodeCart\\Core\\', DIR_SYSTEM . 'library/codecart/src/');
// Register namespaces from the cached Modern Extension registry only.
// Discovery/manifest scanning remains an explicit admin/CLI operation.
try {\CodeCart\Core\ModernExtensionRegistry::bootstrap();} catch (\Throwable $e) { /* Legacy Core must remain available if the modern cache is invalid. */ }

// Autoloader
if (defined('DIR_STORAGE') && is_file(DIR_STORAGE . 'vendor/autoload.php') && version_compare(PHP_VERSION, '8.0.0', '>=')) {
	require_once(DIR_STORAGE . 'vendor/autoload.php');
}

require_once(DIR_SYSTEM . 'helper/codecart_legacy_vendor.php');
codecart_register_legacy_vendor_compatibility();

function library($class) {
	$file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';

	if (is_file($file)) {
		include_once(modification($file));

		return true;
	} else {
		return false;
	}
}

spl_autoload_register('library');
spl_autoload_extensions('.php');

// Engine
require_once(modification(DIR_SYSTEM . 'engine/action.php'));
require_once(modification(DIR_SYSTEM . 'engine/controller.php'));
require_once(modification(DIR_SYSTEM . 'engine/event.php'));
require_once(modification(DIR_SYSTEM . 'engine/router.php'));
require_once(modification(DIR_SYSTEM . 'engine/loader.php'));
require_once(modification(DIR_SYSTEM . 'engine/model.php'));
require_once(modification(DIR_SYSTEM . 'engine/registry.php'));
require_once(modification(DIR_SYSTEM . 'engine/proxy.php'));

// Helper
require_once(DIR_SYSTEM . 'helper/utf8.php');

function start($application_config) {
	require_once(DIR_SYSTEM . 'framework.php');	
}