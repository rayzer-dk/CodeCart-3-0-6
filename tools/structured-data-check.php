<?php
/**
 * Standalone regression check: php tools/structured-data-check.php
 * Does not need the database or a running OpenCart installation.
 */
define('DB_PREFIX', 'oc_');
define('DIR_IMAGE', sys_get_temp_dir() . '/');
require_once __DIR__ . '/../upload/system/library/codecart/schema_types.php';

function check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

check(CodeCartSchemaTypes::normalize('information', 'AboutPage') === 'AboutPage', 'AboutPage permitted');
check(CodeCartSchemaTypes::normalize('information', 'BadType') === 'auto', 'unknown type rejected');
check(CodeCartSchemaTypes::normalize('article', 'NewsArticle') === 'NewsArticle', 'news type permitted');
check(CodeCartSchemaTypes::normalize('article', 'AboutPage') === 'auto', 'cross-type rejected');

function codecart_is_https(array $server) {
    return !empty($server['HTTPS']) && strtolower((string)$server['HTTPS']) !== 'off';
}

class Controller {
    public $config;
    public $document;
    public $language;
    public $request;
}
class SchemaTestConfig {
    public $on = true;
    public function get($key) {
        $data = array(
            'config_codecart_structured_data_status' => $this->on,
            'config_ssl' => 'https://example.test/',
            'config_url' => 'https://example.test/',
            'config_name' => 'Example Shop',
            'config_logo' => '',
            'config_telephone' => '+4512345678',
            'config_email' => 'shop@example.test',
            'config_address' => 'Example Street 1'
        );
        return isset($data[$key]) ? $data[$key] : '';
    }
}
class SchemaTestLanguage {
    public function get($key) { return 'en-gb'; }
}
class SchemaTestDocument {
    public $nodes = array();
    public function getStructuredData() { return $this->nodes; }
}
require_once __DIR__ . '/../upload/catalog/controller/event/codecart.php';

$handler = new ControllerEventCodeCart();
$handler->config = new SchemaTestConfig();
$handler->document = new SchemaTestDocument();
$handler->language = new SchemaTestLanguage();
$handler->request = (object)array('server' => array('HTTPS' => 'on'));
$route = 'common/header';
$args = array();
$html = '<html><head><title>Test</title></head><body></body></html>';
$handler->injectStructuredData($route, $args, $html);
check(preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches) === 1, 'JSON-LD injected');
$parsed = json_decode($matches[1], true);
check(is_array($parsed) && $parsed['@context'] === 'https://schema.org', 'valid JSON-LD');
check($parsed['@graph'][0]['@type'] === 'OnlineStore', 'online shop type');
check($parsed['@graph'][0]['email'] === 'shop@example.test', 'contacts populated');
check($parsed['@graph'][1]['@type'] === 'WebSite', 'website type');

$handler->document->nodes = array(
    array('@type' => 'Product', '@id' => 'https://example.test/test-product#product',
        'url' => 'https://example.test/test-product', 'name' => 'Product')
);
$external = '<script type="application/ld+json">{"@context":"https://schema.org","@graph":['
    . '{"@type":"Organization","@id":"https://example.test/#organization","url":"https://example.test/"},'
    . '{"@type":"Product","url":"https://example.test/test-product","name":"Product"}]}</script>';
$html = '<html><head>' . $external . '</head><body></body></html>';
$handler->injectStructuredData($route, $args, $html);
preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);
check(count($matches[1]) === 2, 'foreign JSON-LD preserved');
$extra = json_decode($matches[1][1], true);
check(count($extra['@graph']) === 1 && $extra['@graph'][0]['@type'] === 'WebSite', 'equivalent nodes skipped');

$handler->config->on = false;
$html = '<html><head></head><body></body></html>';
$handler->injectStructuredData($route, $args, $html);
check(strpos($html, 'application/ld+json') === false, 'global OFF works');

echo "Structured data checks passed\n";
