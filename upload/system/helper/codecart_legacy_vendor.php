<?php
/**
 * CodeCart PRO legacy vendor compatibility loader.
 *
 * Keeps selected regional extensions from an existing ocStore installation
 * operational after the modern Core Composer runtime has been refreshed.
 * Fresh installations do not ship these packages, so this loader is inert.
 */
function codecart_register_legacy_vendor_compatibility(): void {
	if (!defined('DIR_STORAGE')) { return; }
	$active_vendor = rtrim(DIR_STORAGE, '/\\') . '/vendor/';
	$previous_vendor = rtrim(DIR_STORAGE, '/\\') . '/codecart/vendor-previous/';
	$vendors = array();
	if (is_dir($active_vendor)) { $vendors[] = $active_vendor; }
	if (is_dir($previous_vendor)) { $vendors[] = $previous_vendor; }
	if (!$vendors) { return; }

	$prefixes = array();
	$candidates = array(
		'Cardinity\\' => 'cardinity/cardinity-sdk-php/src/',
		'GuzzleHttp\\Subscriber\\Oauth\\' => 'guzzlehttp/oauth-subscriber/src/',
		'Symfony\\Component\\Validator\\' => 'symfony/validator/',
		'Symfony\\Contracts\\Translation\\' => 'symfony/translation-contracts/',
		'Symfony\\Polyfill\\Php73\\' => 'symfony/polyfill-php73/',
		'Symfony\\Polyfill\\Php81\\' => 'symfony/polyfill-php81/',
		'Braintree\\' => 'braintree/braintree_php/lib/Braintree/',
		'Wechat\\' => 'zoujingli/wechat-php-sdk/Wechat/',
		'WePay\\' => 'zoujingli/wechat-developer/WePay/',
		'WePayV3\\' => 'zoujingli/wechat-developer/WePayV3/',
		'WeMini\\' => 'zoujingli/wechat-developer/WeMini/',
		'WeChat\\' => 'zoujingli/wechat-developer/WeChat/',
		'AliPay\\' => 'zoujingli/wechat-developer/AliPay/'
	);
	foreach ($candidates as $prefix => $relative) {
		foreach ($vendors as $vendor) {
			$directory = $vendor . $relative;
			if (is_dir($directory)) { $prefixes[$prefix] = $directory; break; }
		}
	}
	$special = array();
	foreach ($vendors as $vendor) {
		if (is_file($vendor . 'zoujingli/wechat-developer/We.php') || is_dir($vendor . 'divido/divido-php/lib/Divido') || is_file($vendor . 'divido/divido-php/lib/Divido.php') || is_dir($vendor . 'braintree/braintree_php/lib/Braintree')) { $special[] = $vendor; }
	}
	if (!$prefixes && !$special) { return; }
	foreach ($vendors as $vendor) {
		foreach (array('symfony/polyfill-php73/bootstrap.php','symfony/polyfill-php81/bootstrap.php') as $bootstrap) { $file=$vendor.$bootstrap; if (is_file($file)) { require_once $file; } }
		$file=$vendor.'zoujingli/wechat-developer/helper.php'; if (is_file($file)) { require_once $file; }
	}
	spl_autoload_register(static function (string $class) use ($prefixes, $special): void {
		if ($class === 'We') { foreach ($special as $vendor) { $file=$vendor.'zoujingli/wechat-developer/We.php'; if (is_file($file)) { require $file; return; } } return; }
		if (strncmp($class,'Braintree_',10)===0) { $relative=substr($class,10); foreach($special as $vendor){$file=$vendor.'braintree/braintree_php/lib/Braintree/'.str_replace('_','/',$relative).'.php'; if(is_file($file)){require $file; return;}} return; }
		if (strncmp($class,'Divido',6)===0) { $relative=str_replace(array('\\','_'),'/',$class); foreach($special as $vendor){$file=$vendor.'divido/divido-php/lib/'.$relative.'.php'; if(is_file($file)){require $file; return;}} return; }
		foreach ($prefixes as $prefix=>$directory) { if (strncmp($class,$prefix,strlen($prefix))!==0) { continue; } $relative=substr($class,strlen($prefix)); $file=$directory.str_replace('\\','/',$relative).'.php'; if(is_file($file)){require $file;} return; }
	}, true, false);
}
