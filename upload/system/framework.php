<?php
// Registry
$registry = new Registry();

// Config
$config = new Config();
$config->load('default');
$config->load($application_config);
$registry->set('config', $config);

// Log
$log = new Log($config->get('error_filename'));
$registry->set('log', $log);

date_default_timezone_set($config->get('date_timezone'));

set_error_handler(function($code, $message, $file, $line) use($log, $config) {
	// error suppressed with @
	if (!(error_reporting() & $code)) {
		return false;
	}

	switch ($code) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$error = 'Deprecated';
			break;
		case E_RECOVERABLE_ERROR:
			$error = 'Recoverable Error';
			break;
		default:
			$error = 'Unknown';
			break;
	}

	if ($config->get('error_display')) {
		echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
	}

	if ($config->get('error_log')) {
		$log->write('PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line);
	}

	return true;
});

set_exception_handler(function($exception) use($log, $config, $registry) {
	$message = get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
	$previous = $exception->getPrevious();

	if ($previous) {
		$previous_message = trim(preg_replace('/\s+/', ' ', (string)$previous->getMessage()));
		if (strlen($previous_message) > 500) { $previous_message = substr($previous_message, 0, 500) . '...'; }
		$message .= ' | Cause: ' . get_class($previous) . ' #' . (int)$previous->getCode() . ': ' . $previous_message;
	}

	if ($config->get('error_log')) {
		$log->write('PHP Uncaught Exception: ' . $message);
	}

	if (!headers_sent()) {
		http_response_code(500);
		header('Content-Type: text/html; charset=utf-8');
	}

	if (PHP_SAPI === 'cli') {
		echo 'Internal Server Error';
		return;
	}
	// Keep API/AJAX failures machine-readable; never expose exception details.
	$accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
	$route = isset($_GET['route']) && is_string($_GET['route']) ? $_GET['route'] : '';
	if (stripos($accept, 'application/json') !== false || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest' || strpos($route, 'api/') === 0) {
		if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); }
		echo '{"error":"Internal Server Error"}';
		return;
	}
	// This renderer needs neither a working database nor the template engine.
	require_once(DIR_SYSTEM . 'helper/error_page.php');
	$language = (string)$config->get('config_language');
	if ($registry->has('session') && isset($registry->get('session')->data['language'])) {
		$language = (string)$registry->get('session')->data['language'];
	}
	if (!in_array($language, array('en-gb', 'ru-ru', 'uk-ua'), true)) {
		$preferred = strtolower(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), 0, 2));
		$language = array('ru' => 'ru-ru', 'uk' => 'uk-ua')[$preferred] ?? 'en-gb';
	}
	$home = defined('HTTP_SERVER') ? HTTP_SERVER : '/';
	if (defined('DIR_CATALOG') && defined('HTTP_CATALOG')) { $home = HTTP_CATALOG; }
	echo codecart_error_page($language, $home);
});

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Event Register
if ($config->has('action_event')) {
	foreach ($config->get('action_event') as $key => $value) {
		foreach ($value as $priority => $action) {
			$event->register($key, new Action($action), $priority);
		}
	}
}

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Request
$registry->set('request', new Request());

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT', true);
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
header('Pragma: no-cache', true);
$response->setCompression($config->get('config_compression'));
$registry->set('response', $response);

// Optional deployment overrides. Ordinary config.php remains the compatibility fallback.
// Empty environment values are ignored so shared-hosting installations behave exactly as before.
$env_db_map = array(
	'CODECART_DB_DRIVER' => 'db_engine',
	'CODECART_DB_HOSTNAME' => 'db_hostname',
	'CODECART_DB_USERNAME' => 'db_username',
	'CODECART_DB_PASSWORD' => 'db_password',
	'CODECART_DB_DATABASE' => 'db_database',
	'CODECART_DB_PORT' => 'db_port'
);
foreach ($env_db_map as $env_name => $config_key) {
	$env_value = getenv($env_name);
	if ($env_value !== false && $env_value !== '') {
		if ($config_key === 'db_port') {
			$port = (int)$env_value;
			if ($port > 0 && $port <= 65535) { $config->set($config_key, $port); }
		} elseif ($config_key === 'db_engine') {
			$driver = strtolower(trim((string)$env_value));
			if (in_array($driver, array('mysqli', 'pdo'), true)) { $config->set($config_key, $driver); }
		} else {
			$config->set($config_key, (string)$env_value);
		}
	}
}
unset($env_db_map, $env_name, $env_value, $config_key);

// Database
if ($config->get('db_autostart')) {
	$db_engine = strtolower((string)$config->get('db_engine'));
	$has_mysqli = extension_loaded('mysqli');
	$has_pdo_mysql = extension_loaded('pdo') && extension_loaded('pdo_mysql');

	// PHP profiles on hosting panels can expose different extension sets.
	// Prefer the configured driver, but safely fall back to the other supported
	// MySQL driver when it is available instead of treating the PHP version as incompatible.
	if ($db_engine === 'mysqli' && !$has_mysqli && $has_pdo_mysql) {
		$db_engine = 'pdo';
		$config->set('db_engine', 'pdo');
	} elseif ($db_engine === 'pdo' && !$has_pdo_mysql && $has_mysqli) {
		$db_engine = 'mysqli';
		$config->set('db_engine', 'mysqli');
	}

	if (($db_engine === 'mysqli' && !$has_mysqli) || ($db_engine === 'pdo' && !$has_pdo_mysql) || !in_array($db_engine, array('mysqli', 'pdo'), true)) {
		throw new RuntimeException('No supported MySQL PHP driver is available for PHP ' . PHP_VERSION . '. Enable mysqli or pdo_mysql in the active PHP profile.');
	}

	$db = new DB($db_engine, $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port'));
	$registry->set('db', $db);

	// Load the small set of bootstrap settings required before the Session layer.
	// Reuse the existing timezone query so UPDATE migration adds no SQL on steady-state requests.
	$query = $db->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` IN ('config_timezone', 'codecart_core_schema_version', 'codecart_presentation_schema_version', 'codecart_scheduler_key', 'codecart_cache_engine', 'codecart_db_strict_mode')");

	foreach ($query->rows as $setting) {
		$config->set($setting['key'], $setting['value']);
	}

	if ($config->get('config_timezone')) {
		date_default_timezone_set($config->get('config_timezone'));
	}

    $cache_engine_setting = strtolower(trim((string)$config->get('codecart_cache_engine')));
    if (in_array($cache_engine_setting, array('file','apcu','memcached','redis'), true)) {
        $config->set('cache_engine', $cache_engine_setting);
    }

	// Database/schema migrations are intentionally NOT executed from normal web requests.
	// Existing-store upgrades must run through Installer 2.0 after preflight and explicit
	// backup confirmation. This keeps storefront/admin traffic read-only with respect to
	// upgrade schema changes and prevents a file upload from silently mutating the database.
	$core_schema = (string)$config->get('codecart_core_schema_version');
	$presentation_schema = (string)$config->get('codecart_presentation_schema_version');
	$config->set('codecart_upgrade_required', $core_schema !== CodeCart\Migration::VERSION || $presentation_schema !== CodeCart\Migration::PRESENTATION_SCHEMA_VERSION);

	// OpenCart 3.x core, ocStore and the third-party extension ecosystem are written for
	// OpenCart's session SQL mode (see upstream system/library/db/mysqli.php). Under the
	// server default STRICT_TRANS_TABLES many legitimate INSERTs that omit NOT NULL columns
	// without defaults fail with MySQL 1364 after the preceding DELETE already ran, which
	// loses data (product options, manufacturer/article descriptions, geo zones...).
	// Strict mode stays available as an explicit opt-in: codecart_db_strict_mode = 1.
	if (!(int)$config->get('codecart_db_strict_mode')) {
		$db->query("SET SESSION sql_mode = 'NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION'");
	}

	// Sync PHP and DB time zones
	$db->query("SET time_zone = '" . $db->escape(date('P')) . "'");
}

// Session
$session = new Session($config->get('session_engine'), $registry);
$registry->set('session', $session);

if ($config->get('session_autostart')) {
	/*
	We are adding the session cookie outside of the session class as I believe
	PHP messed up in a big way handling sessions. Why in the hell is it so hard to
	have more than one concurrent session using cookies!

	Is it not better to have multiple cookies when accessing parts of the system
	that requires different cookie sessions for security reasons.

	Also cookies can be accessed via the URL parameters. So why force only one cookie
	for all sessions!
	*/

	if (isset($_COOKIE[$config->get('session_name')])) {
		$session_id = $_COOKIE[$config->get('session_name')];
	} else {
		$session_id = '';
	}

	$session->start($session_id);

	codecart_set_session_cookie($config->get('session_name'), $session->getId());
}

// Cache
$registry->set('cache', new Cache($config->get('cache_engine'), $config->get('cache_expire')));

// Url
if ($config->get('url_autostart')) {
	$registry->set('url', new Url($config->get('site_url'), $config->get('site_ssl')));
}

// Language
$language = new Language($config->get('language_directory'));
$registry->set('language', $language);

// Document
$registry->set('document', new Document());

// Additive Modern Core services. Legacy Registry/Loader/MVC-L remain unchanged.
$registry->set('codecart_service_container', new \CodeCart\Core\ServiceContainer($registry));
$registry->set('codecart_extension_points', new \CodeCart\Core\ExtensionPoints());
$registry->set('codecart_asset_manager', new \CodeCart\Core\AssetManager($registry));
$registry->set('codecart_compat', new \CodeCart\Core\CompatibilityLayer($registry));
$registry->set('codecart_compatibility_framework', new \CodeCart\Core\CompatibilityFramework($registry));
$registry->set('codecart_search', new \CodeCart\Core\SearchAdapter());
$registry->set('codecart_api_v1', new \CodeCart\Core\ApiV1($registry));

// Config Autoload
if ($config->has('config_autoload')) {
	foreach ($config->get('config_autoload') as $value) {
		$loader->config($value);
	}
}

// Language Autoload
if ($config->has('language_autoload')) {
	foreach ($config->get('language_autoload') as $value) {
		$loader->language($value);
	}
}

// Library Autoload
if ($config->has('library_autoload')) {
	foreach ($config->get('library_autoload') as $value) {
		$loader->library($value);
	}
}

// Model Autoload
if ($config->has('model_autoload')) {
	foreach ($config->get('model_autoload') as $value) {
		$loader->model($value);
	}
}

// Route
$route = new Router($registry);

// Pre Actions
if ($config->has('action_pre_action')) {
	foreach ($config->get('action_pre_action') as $value) {
		$route->addPreAction(new Action($value));
	}
}

// Dispatch
$route->dispatch(new Action($config->get('action_router')), new Action($config->get('action_error')));

// Output
// Optional aggregate content-404 report. OFF does not instantiate the service or query DB.
if (!defined('DIR_CATALOG') && !defined('DIR_OPENCART') && PHP_SAPI !== 'cli' && !defined('CODECART_CLI') && $config->get('codecart_lost_url_status') && $response->getStatusCode() === 404 && stripos((string)$response->getOutput(), '<html') !== false) {
    try { (new \CodeCart\Core\LostUrlMonitor($registry))->record((array)$_SERVER, (array)$registry->get('request')->get); }
    catch (\Throwable $e) { if ($log) { $log->write('CodeCart lost URL report: ' . get_class($e)); } }
}
$response->output();

// Lightweight traffic heartbeat. Register it for shutdown so normal page output
// is prepared first. On PHP-FPM, fastcgi_finish_request() flushes the response to
// the visitor before scheduled work starts. Exact no-traffic execution and heavy
// queue workers can still use cli.php/cron.php from the hosting scheduler.
if (isset($db) && PHP_SAPI !== 'cli' && !defined('CODECART_CLI')) {
    register_shutdown_function(function() use ($registry, $log) {
        try {
            \CodeCart\Core\Heartbeat::finishRequest();
            (new \CodeCart\Core\Heartbeat($registry))->run(300);
        } catch (\Throwable $e) {
            if ($log) { $log->write('CodeCart PRO heartbeat bootstrap failed: ' . $e->getMessage()); }
        }
    });
}

