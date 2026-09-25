<?php
// Site
$_['site_url']           = HTTP_SERVER;
$_['site_ssl']           = HTTPS_SERVER;

// Url
$_['url_autostart']      = false;

// Database
$_['db_autostart']       = true;
$_['db_engine']          = DB_DRIVER; // mysqli or pdo (PDO MySQL)
$_['db_hostname']        = DB_HOSTNAME;
$_['db_username']        = DB_USERNAME;
$_['db_password']        = DB_PASSWORD;
$_['db_database']        = DB_DATABASE;
$_['db_port']            = DB_PORT;

// Session
$_['session_autostart']  = false;
$_['session_engine']     = 'db';
$_['session_name']       = 'OCSESSID';
$_['session_cookie_lifetime'] = 604800; // Keep storefront cookie for 7 days.
$_['session_maxlifetime']     = 604800; // Keep storefront session state and cart linkage for 7 days.

// Template
$_['template_engine']    = 'twig';
$_['template_directory'] = '';
$_['template_cache']     = true;

// Autoload Libraries
$_['library_autoload']   = array();

// Actions
$_['action_pre_action']  = array(
	'startup/session',
	'startup/startup',
	'startup/security_headers',
	'startup/device_authorization',
	'startup/error',
	'startup/event',
	'startup/maintenance',
	'startup/seo_url'
);

// Action Events
$_['action_event'] = array(
	'controller/*/before' => array(
		'event/language/before'
	),
	'controller/*/after' => array(
		'event/language/after'
	),	
	'view/*/before' => array(
		500  => 'event/theme',
		998  => 'event/language',
	),
	'language/*/after' => array(
		'event/translation'
	),
	//'view/*/before' => array(
	//	1000  => 'event/debug/before'
	//),
	//'controller/*/after'  => array(
	//	'event/debug/after'
//	)
);
// CodeCart PRO API v1 is opt-in to preserve legacy behaviour and attack surface.
$_['codecart_api_v1_status'] = 0;
