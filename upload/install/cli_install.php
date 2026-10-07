<?php

//
// Command line tool for installing opencart
// Author: Vineet Naik <vineet.naik@kodeplay.com> <naikvin@gmail.com>
//
// (Currently tested on linux only)
//
// Usage:
//
//   cd install
//   php cli_install.php install --db_hostname localhost \
//                               --db_username root \
//                               --db_password pass \
//                               --db_database opencart \
//                               --db_driver mysqli \
//								 --db_port 3306 \
//                               --username admin \
//                               --password admin \
//                               --email youremail@example.com \
//                               --http_server http://localhost/opencart/
//

ini_set('display_errors', 1);

error_reporting(E_ALL);

// DIR
define('DIR_APPLICATION', str_replace('\\', '/', realpath(dirname(__FILE__))) . '/');
define('DIR_SYSTEM', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/system/');
define('DIR_OPENCART', str_replace('\\', '/', realpath(DIR_APPLICATION . '../')) . '/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');
define('DIR_DATABASE', DIR_SYSTEM . 'database/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);


function handleError($errno, $errstr, $errfile, $errline) {
	// error was suppressed with the @-operator
	if (!(error_reporting() & $errno)) {
		return false;
	}
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler('handleError');


function usage() {
	echo "Usage:\n";
	echo "======\n";
	echo "\n";
	$options = implode(" ", array(
		'--db_hostname', 'localhost',
		'--db_username', 'root',
		'--db_password', 'pass',
		'--db_database', 'opencart',
		'--db_driver', 'mysqli',
		'--db_port', '3306',
		'--username', 'admin',
		'--password', 'admin',
		'--email', 'youremail@example.com',
		'--http_server', 'http://localhost/opencart/'
	));
	echo 'php cli_install.php install ' . $options . "\n\n";
}


function get_options($argv) {
	$defaults = array(
		'db_hostname' => 'localhost',
		'db_database' => 'opencart',
		'db_prefix' => 'oc_',
		'db_driver' => 'mysqli',
		'db_port' => '3306',
		'username' => 'admin',
	);

	$allowed = array(
		'db_hostname', 'db_username', 'db_password', 'db_database', 'db_prefix',
		'db_driver', 'db_port', 'username', 'password', 'email', 'http_server'
	);

	if (count($argv) % 2 !== 0) {
		throw new InvalidArgumentException('Every command-line option must have a value.');
	}

	$options = array();
	$total = count($argv);

	for ($i = 0; $i < $total; $i += 2) {
		if (!preg_match('/^--([a-z0-9_]+)$/', $argv[$i], $match)) {
			throw new InvalidArgumentException($argv[$i] . ' is not a valid option name.');
		}

		if (!in_array($match[1], $allowed, true)) {
			throw new InvalidArgumentException('Unknown option: --' . $match[1]);
		}

		$options[$match[1]] = $argv[$i + 1];
	}

	return array_merge($defaults, $options);
}

function valid($options) {
	$required = array(
		'db_hostname', 'db_username', 'db_database', 'db_prefix',
		'db_port', 'username', 'password', 'email', 'http_server'
	);
	$errors = array();

	foreach ($required as $name) {
		if (!array_key_exists($name, $options) || $options[$name] === '') {
			$errors[] = $name;
		}
	}


	if (!array_key_exists('db_password', $options)) {
		$errors[] = 'db_password';
	}

	if ($options['db_driver'] === 'mysqli' && !extension_loaded('mysqli') && extension_loaded('pdo_mysql')) {
		$options['db_driver'] = 'pdo';
	}

	if (!in_array($options['db_driver'], array('mysqli', 'pdo'), true)) {
		$errors[] = 'db_driver';
	} elseif ($options['db_driver'] === 'mysqli' && !extension_loaded('mysqli')) {
		$errors[] = 'db_driver(mysqli extension unavailable)';
	} elseif ($options['db_driver'] === 'pdo' && !extension_loaded('pdo_mysql')) {
		$errors[] = 'db_driver(pdo_mysql extension unavailable)';
	}

	if (!preg_match('/^[A-Za-z0-9_]*$/', $options['db_prefix']) || strlen($options['db_prefix']) > 32) {
		$errors[] = 'db_prefix';
	}

	if (!ctype_digit((string)$options['db_port']) || (int)$options['db_port'] < 1 || (int)$options['db_port'] > 65535) {
		$errors[] = 'db_port';
	}

	if (!filter_var($options['email'], FILTER_VALIDATE_EMAIL) || strlen($options['email']) > 96) {
		$errors[] = 'email';
	}

	$url = filter_var($options['http_server'], FILTER_VALIDATE_URL);
	$scheme = $url ? strtolower((string)parse_url($url, PHP_URL_SCHEME)) : '';
	$host = $url ? parse_url($url, PHP_URL_HOST) : '';

	if (!$url || !in_array($scheme, array('http', 'https'), true) || !$host || preg_match('/[\r\n]/', $options['http_server'])) {
		$errors[] = 'http_server';
	}

	if ($url && substr($options['http_server'], -1) !== '/') {
		$options['http_server'] .= '/';
	}

	return array(count($errors) === 0, array_values(array_unique($errors)), $options);
}

function install($options) {
	ensure_cli_config_files();
	$check = check_requirements();
	if (!$check[0]) {
		throw new RuntimeException('Pre-installation check failed: ' . $check[1]);
	}

	// A CLI installation follows the same security invariant as the web installer:
	// writable runtime/vendor storage must be outside the document root.
	$storage = prepare_cli_storage();
	$GLOBALS['codecart_cli_db_started'] = false;

	try {
		setup_db($options);
		write_config_files($options, $storage);
		update_cli_robots_sitemap($options['http_server']);
		dir_permissions($storage);
		finalize_cli_public_storage($storage);
	} catch (Throwable $e) {
		if (!empty($GLOBALS['codecart_cli_db_started'])) {
			rollback_cli_database($options);
		}
		throw $e;
	}
}


function check_requirements() {
	$errors = array();

	if (version_compare(PHP_VERSION, '8.1.0', '<') || version_compare(PHP_VERSION, '8.6.0', '>=')) {
		$errors[] = 'Use one supported PHP version: 8.1, 8.2, 8.3, 8.4 or 8.5.';
	}

	if (!ini_get('file_uploads')) {
		$errors[] = 'file_uploads needs to be enabled.';
	}

	if (ini_get('session.auto_start')) {
		$errors[] = 'session.auto_start must be disabled.';
	}

	if (!extension_loaded('mysqli') && !extension_loaded('pdo_mysql')) {
		$errors[] = 'MySQLi or PDO MySQL extension is required.';
	}

	$required_extensions = array('gd', 'curl', 'zlib', 'zip', 'mbstring', 'dom', 'xmlwriter', 'fileinfo');

	foreach ($required_extensions as $extension) {
		if (!extension_loaded($extension)) {
			$errors[] = $extension . ' extension is required.';
		}
	}

	if (!function_exists('openssl_encrypt')) {
		$errors[] = 'OpenSSL extension is required.';
	}

	$required_paths = array(
		DIR_OPENCART . 'config.php',
		DIR_OPENCART . 'admin/config.php',
		DIR_OPENCART . 'image/',
		DIR_OPENCART . 'image/cache/',
		DIR_OPENCART . 'image/catalog/',
		DIR_SYSTEM . 'storage/cache/',
		DIR_SYSTEM . 'storage/logs/',
		DIR_SYSTEM . 'storage/download/',
		DIR_SYSTEM . 'storage/upload/',
		DIR_SYSTEM . 'storage/modification/',
		DIR_SYSTEM . 'storage/session/'
	);

	foreach ($required_paths as $path) {
		if (!file_exists($path)) {
			$errors[] = $path . ' does not exist.';
		} elseif (!is_writable($path)) {
			$errors[] = $path . ' is not writable.';
		}
	}

	return array(!$errors, implode(' ', $errors));
}


function has_tables_with_prefix($db, $prefix) {
	$query = $db->query('SHOW TABLES');

	foreach ($query->rows as $row) {
		$table = (string)reset($row);

		if ($table !== '' && strpos($table, $prefix) === 0) {
			return true;
		}
	}

	return false;
}

function setup_db($data) {
	$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);

	if (has_tables_with_prefix($db, $data['db_prefix'])) {
		throw new RuntimeException('Refusing to install: the selected database already contains tables using prefix ' . $data['db_prefix'] . '.');
	}

	$GLOBALS['codecart_cli_db_started'] = true;

	$file = DIR_APPLICATION . 'opencart.sql';

	if (!file_exists($file)) {
		exit('Could not load sql file: ' . $file);
	}

	$script = file_get_contents($file);

	if ($script !== false && $script !== '') {
		foreach (\CodeCart\Core\SqlScript::statements($script) as $statement) {
			$db->query(\CodeCart\Core\SqlScript::applyPrefix($statement, (string)$data['db_prefix']));
		}

		$db->query("SET CHARACTER SET utf8mb4");

		$db->query("SET collation_connection = 'utf8mb4_unicode_ci'");

		$db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE user_id = '1'");

		$db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '1', user_group_id = '1', username = '" . $db->escape($data['username']) . "', salt = '', password = '" . $db->escape(codecart_password_hash(codecart_password_input($data['password']))) . "', firstname = 'CodeCart', lastname = 'Pro', email = '" . $db->escape($data['email']) . "', status = '1', image = 'catalog/profile-pic.webp', code = '', ip = '', date_added = NOW()");

		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $db->escape($data['email']) . "', serialized = '0'");

		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $db->escape(token(1024)) . "', serialized = '0'");

		$db->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");

		$db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET username = 'Default', `key` = '" . $db->escape(token(256)) . "', status = 1, date_added = NOW(), date_modified = NOW()");

		$api_id = $db->getLastId();

		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', value = '" . (int)$api_id . "', serialized = '0'");

		$scheduler_key = bin2hex(random_bytes(32));
		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'codecart_scheduler_key'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_scheduler_key', value = '" . $db->escape($scheduler_key) . "', serialized = '0'");

		// robots.txt advertises /sitemap.xml on a clean install, so the sitemap feed must be active.
		$db->query("DELETE FROM `" . $data['db_prefix'] . "extension` WHERE `type` = 'feed' AND `code` = 'google_sitemap'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "extension` SET `type` = 'feed', `code` = 'google_sitemap'");
		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'feed_google_sitemap_status'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET store_id = '0', code = 'feed_google_sitemap', `key` = 'feed_google_sitemap_status', value = '1', serialized = '0'");

		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'codecart_db_modernization_required'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_db_modernization_required', value = '0', serialized = '0'");

		// Grant the Administrator group access to every controller shipped with this build.
		// CLI installs must have the same permissions as the web installer.
		$admin_root = DIR_OPENCART . 'admin/controller/';
		$admin_routes = array();
		if (is_dir($admin_root)) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($admin_root, FilesystemIterator::SKIP_DOTS));
			foreach ($iterator as $file) {
				if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') { continue; }
				$relative = substr(str_replace('\\', '/', $file->getPathname()), strlen(str_replace('\\', '/', $admin_root)));
				$route = preg_replace('/\.php$/i', '', $relative);
				if ($route !== '') { $admin_routes[] = $route; }
			}
		}
		$admin_routes = array_values(array_unique($admin_routes));
		sort($admin_routes, SORT_STRING);
		$permission = json_encode(array('access' => $admin_routes, 'modify' => $admin_routes), JSON_UNESCAPED_SLASHES);
		if ($permission === false) {
			throw new RuntimeException('Could not encode Administrator permissions.');
		}
		$db->query("UPDATE `" . $data['db_prefix'] . "user_group` SET permission = '" . $db->escape($permission) . "' WHERE user_group_id = '1'");
	}
}


function rollback_cli_database($data) {
	if (!isset($data['db_prefix']) || !preg_match('/^[A-Za-z0-9_]*$/', (string)$data['db_prefix'])) { return; }
	try {
		$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);
		$query = $db->query('SHOW TABLES');
		$tables = array();
		foreach ($query->rows as $row) {
			$table = (string)reset($row);
			if ($table !== '' && strpos($table, $data['db_prefix']) === 0) { $tables[] = $table; }
		}
		if ($tables) {
			$db->query('SET FOREIGN_KEY_CHECKS = 0');
			foreach ($tables as $table) {
				$db->query('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
			}
			$db->query('SET FOREIGN_KEY_CHECKS = 1');
		}
	} catch (Throwable $ignored) {
		// Preserve the original installer exception; rollback is best-effort.
	}
}



function atomic_write_file($path, $content) {
	$directory = dirname($path);
	$temp = tempnam($directory, '.codecart-config-');

	if ($temp === false) {
		throw new RuntimeException('Could not create temporary file in ' . $directory . '.');
	}

	$written = file_put_contents($temp, $content, LOCK_EX);

	if ($written === false || $written !== strlen($content)) {
		if (file_exists($temp)) {
			unlink($temp);
		}

		throw new RuntimeException('Could not write ' . $path . '.');
	}

	if (!rename($temp, $path)) {
		if (file_exists($temp)) {
			unlink($temp);
		}

		throw new RuntimeException('Could not replace ' . $path . '.');
	}

	@chmod($path, 0640);
}

function ensure_cli_config_files() {
	foreach (array(DIR_OPENCART . 'config.php', DIR_OPENCART . 'admin/config.php') as $path) {
		if (is_file($path)) { continue; }
		$dir = dirname($path);
		if (!is_dir($dir) || !is_writable($dir)) { continue; }
		atomic_write_file($path, '');
	}
}

function prepare_cli_storage() {
	$source = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
	$root = rtrim(str_replace('\\', '/', DIR_OPENCART), '/');
	$parent = dirname($root);

	if (!is_dir($source)) {
		throw new RuntimeException('Installer storage source is missing.');
	}
	if (!is_dir($parent) || !is_writable($parent)) {
		throw new RuntimeException('The directory above the public web root is not writable; protected storage cannot be created.');
	}

	$container = $parent . DIRECTORY_SEPARATOR . 'storage';
	if (!is_dir($container) && !@mkdir($container, 0750, true) && !is_dir($container)) {
		throw new RuntimeException('Cannot create protected storage container outside the public web root.');
	}

	$name = preg_replace('/[^A-Za-z0-9._-]/', '-', basename($root));
	$target = str_replace('\\', '/', $container . DIRECTORY_SEPARATOR . $name . '-' . substr(hash('sha256', $root), 0, 8)) . '/';
	if (!is_dir($target) && !@mkdir($target, 0750, true) && !is_dir($target)) {
		throw new RuntimeException('Cannot create protected storage directory.');
	}

	$sourcePrefix = rtrim($source, '/\\') . DIRECTORY_SEPARATOR;
	$targetPrefix = rtrim($target, '/\\') . DIRECTORY_SEPARATOR;
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
	foreach ($iterator as $item) {
		if ($item->isLink()) { continue; }
		$relative = substr($item->getPathname(), strlen($sourcePrefix));
		$destination = $targetPrefix . $relative;
		if ($item->isDir()) {
			if (!is_dir($destination) && !@mkdir($destination, 0750, true) && !is_dir($destination)) {
				throw new RuntimeException('Cannot create protected storage subdirectory.');
			}
		} else {
			$dir = dirname($destination);
			if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
				throw new RuntimeException('Cannot create protected storage subdirectory.');
			}
			if (!@copy($item->getPathname(), $destination)) {
				throw new RuntimeException('Cannot copy protected storage file: ' . $relative);
			}
		}
	}

	if (!is_file($target . 'vendor/autoload.php')) {
		throw new RuntimeException('Protected storage copy is incomplete: Composer autoloader is missing.');
	}

	return $target;
}

function finalize_cli_public_storage($storage) {
	$public = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
	$active = rtrim(str_replace('\\', '/', (string)$storage), '/') . '/';
	if ($active === $public || !is_file($active . 'vendor/autoload.php')) { return; }
	if (is_dir($public)) {
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($public, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $item) { if ($item->isLink() || $item->isFile()) { @unlink($item->getPathname()); } elseif ($item->isDir()) { @rmdir($item->getPathname()); } }
		@rmdir($public);
	}
	@mkdir($public, 0750, true);
	@file_put_contents($public . '.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n", LOCK_EX);
	@file_put_contents($public . 'web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs=""/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>', LOCK_EX);
	@file_put_contents($public . 'index.html', '', LOCK_EX);
}

function write_config_files($options, $storage) {
	$storage = rtrim(str_replace('\\', '/', (string)$storage), '/') . '/';
	$output  = '<?php' . "\n";
	$output .= '// HTTP' . "\n";
	$output .= 'define(\'HTTP_SERVER\', \'' . addslashes($options['http_server']) . '\');' . "\n";

	$output .= '// HTTPS' . "\n";
	$output .= 'define(\'HTTPS_SERVER\', \'' . addslashes($options['http_server']) . '\');' . "\n";

	$output .= '// DIR' . "\n";
	$output .= 'define(\'DIR_APPLICATION\', \'' . addslashes(DIR_OPENCART) . 'catalog/\');' . "\n";
	$output .= 'define(\'DIR_SYSTEM\', \'' . addslashes(DIR_OPENCART) . 'system/\');' . "\n";
	$output .= 'define(\'DIR_IMAGE\', \'' . addslashes(DIR_OPENCART) . 'image/\');' . "\n";
	$output .= 'define(\'DIR_STORAGE\', \'' . addslashes($storage) . '\');' . "\n";			
	$output .= 'define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');' . "\n";
	$output .= 'define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/theme/\');' . "\n";
	$output .= 'define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');' . "\n";
	$output .= 'define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');' . "\n";
	$output .= 'define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');' . "\n";
	$output .= 'define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');' . "\n";
	$output .= 'define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');' . "\n";
	$output .= 'define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');' . "\n";
	$output .= 'define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');' . "\n\n";

	$output .= '// DB' . "\n";
	$output .= 'define(\'DB_DRIVER\', \'' . addslashes($options['db_driver']) . '\');' . "\n";
	$output .= 'define(\'DB_HOSTNAME\', \'' . addslashes($options['db_hostname']) . '\');' . "\n";
	$output .= 'define(\'DB_USERNAME\', \'' . addslashes($options['db_username']) . '\');' . "\n";
	$output .= 'define(\'DB_PASSWORD\', \'' . addslashes($options['db_password']) . '\');' . "\n";
	$output .= 'define(\'DB_DATABASE\', \'' . addslashes($options['db_database']) . '\');' . "\n";
	$output .= 'define(\'DB_PREFIX\', \'' . addslashes($options['db_prefix']) . '\');' . "\n";
	$output .= 'define(\'DB_PORT\', \'' . addslashes($options['db_port']) . '\');' . "\n";


	atomic_write_file(DIR_OPENCART . 'config.php', $output);

	$output  = '<?php' . "\n";
	$output .= '// HTTP' . "\n";
	$output .= 'define(\'HTTP_SERVER\', \'' . addslashes($options['http_server']) . 'admin/\');' . "\n";
	$output .= 'define(\'HTTP_CATALOG\', \'' . addslashes($options['http_server']) . '\');' . "\n";

	$output .= '// HTTPS' . "\n";
	$output .= 'define(\'HTTPS_SERVER\', \'' . addslashes($options['http_server']) . 'admin/\');' . "\n";
	$output .= 'define(\'HTTPS_CATALOG\', \'' . addslashes($options['http_server']) . '\');' . "\n";

	$output .= '// DIR' . "\n";
	$output .= 'define(\'DIR_APPLICATION\', \'' . addslashes(DIR_OPENCART) . 'admin/\');' . "\n";
	$output .= 'define(\'DIR_SYSTEM\', \'' . addslashes(DIR_OPENCART) . 'system/\');' . "\n";
	$output .= 'define(\'DIR_IMAGE\', \'' . addslashes(DIR_OPENCART) . 'image/\');' . "\n";	
	$output .= 'define(\'DIR_STORAGE\', \'' . addslashes($storage) . '\');' . "\n";
	$output .= 'define(\'DIR_CATALOG\', \'' . addslashes(DIR_OPENCART) . 'catalog/\');' . "\n";
	$output .= 'define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');' . "\n";
	$output .= 'define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/template/\');' . "\n";
	$output .= 'define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');' . "\n";
	$output .= 'define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');' . "\n";
	$output .= 'define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');' . "\n";
	$output .= 'define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');' . "\n";
	$output .= 'define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');' . "\n";
	$output .= 'define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');' . "\n";
	$output .= 'define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');' . "\n\n";

	$output .= '// DB' . "\n";
	$output .= 'define(\'DB_DRIVER\', \'' . addslashes($options['db_driver']) . '\');' . "\n";
	$output .= 'define(\'DB_HOSTNAME\', \'' . addslashes($options['db_hostname']) . '\');' . "\n";
	$output .= 'define(\'DB_USERNAME\', \'' . addslashes($options['db_username']) . '\');' . "\n";
	$output .= 'define(\'DB_PASSWORD\', \'' . addslashes($options['db_password']) . '\');' . "\n";
	$output .= 'define(\'DB_DATABASE\', \'' . addslashes($options['db_database']) . '\');' . "\n";
	$output .= 'define(\'DB_PREFIX\', \'' . addslashes($options['db_prefix']) . '\');' . "\n";
	$output .= 'define(\'DB_PORT\', \'' . addslashes($options['db_port']) . '\');' . "\n";

	$output .= '// OpenCart API' . "\n";
	$output .= 'define(\'OPENCART_SERVER\', \'https://www.opencart.com/\');' . "\n";
	$output .= 'define(\'OPENCARTFORUM_SERVER\', \'https://opencartforum.com/\');' . "\n";


	atomic_write_file(DIR_OPENCART . 'admin/config.php', $output);
	return $storage;
}


function update_cli_robots_sitemap($baseUrl) {
	$path = DIR_OPENCART . 'robots.txt';
	if (!is_file($path) || !is_readable($path)) { return; }
	$content = @file_get_contents($path);
	if (!is_string($content)) { return; }
	$baseUrl = rtrim(trim((string)$baseUrl), '/');
	if (!preg_match('#^https?://#i', $baseUrl)) { return; }
	$sitemap = 'Sitemap: ' . $baseUrl . '/sitemap.xml';
	$content = preg_replace('/^\s*Sitemap:\s*https?:\/\/.*$/mi', '', $content);
	$content = preg_replace('/^\s*#\s*Sitemap:\s*https?:\/\/example\.com\/sitemap\.xml\s*$/mi', '', $content);
	$content = rtrim((string)$content) . "\n\n" . $sitemap . "\n";
	if (@file_put_contents($path, $content, LOCK_EX) === false) {
		fwrite(STDERR, "Warning: could not write Sitemap directive to robots.txt\n");
	}
}

function dir_permissions($storage = null) {
	$storage = rtrim((string)($storage ?: DIR_STORAGE), '/\\') . '/';

	// Public image assets must be web-readable; protected storage should not be
	// world-readable. Writability still depends on correct ownership, which is
	// safer than granting 0777/0666 permissions.
	$policies = array(
		array('root' => DIR_OPENCART . 'image/', 'dir' => 0755, 'file' => 0644),
		array('root' => $storage, 'dir' => 0750, 'file' => 0640)
	);

	foreach ($policies as $policy) {
		$root = $policy['root'];
		if (!is_dir($root)) { continue; }
		if (!@chmod($root, $policy['dir'])) {
			fwrite(STDERR, 'Warning: could not change permissions for ' . $root . PHP_EOL);
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ($iterator as $item) {
			$mode = $item->isDir() ? $policy['dir'] : $policy['file'];
			if (!@chmod($item->getPathname(), $mode)) {
				fwrite(STDERR, 'Warning: could not change permissions for ' . $item->getPathname() . PHP_EOL);
			}
		}
	}
}



$argv = $_SERVER['argv'];
$script = array_shift($argv);
$subcommand = array_shift($argv);


switch ($subcommand) {

case "install":
	try {
		$options = get_options($argv);
		$valid = valid($options);
		if (!$valid[0]) {
			echo "FAILED! Following inputs were missing or invalid: ";
			echo implode(', ', $valid[1]) . "\n\n";
			exit(1);
		}
		$options = $valid[2];
		if (!defined('HTTP_OPENCART')) {
			define('HTTP_OPENCART', $options['http_server']);
		}
		install($options);
		echo "SUCCESS! Opencart successfully installed on your server\n";
		echo "Store link: " . $options['http_server'] . "\n";
		echo "Admin link: " . $options['http_server'] . "admin/\n\n";
	} catch (Throwable $e) {
		echo 'FAILED!: ' . $e->getMessage() . "\n";
		exit(1);
	}
	break;
case "usage":
default:
	echo usage();
}
