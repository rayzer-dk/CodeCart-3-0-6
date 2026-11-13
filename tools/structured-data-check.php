<?php
/**
 * Standalone regression check: php tools/structured-data-check.php
 * Does not need the database or a running OpenCart installation.
 */
define('DB_PREFIX', 'oc_');
define('DIR_SYSTEM', __DIR__ . '/../upload/system/');
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

require_once __DIR__ . '/../upload/system/library/codecart/merchant_schema.php';

$settings = CodeCartMerchantSchema::defaults();
$settings['config_store_id'] = 0;
$settings['config_codecart_schema_store_id'] = 0;
check(CodeCartMerchantSchema::shipping($settings, 'https://example.test/') === null, 'shipping disabled by default');
check(CodeCartMerchantSchema::returns($settings) === null, 'returns disabled by default');
check(CodeCartMerchantSchema::offerShipping($settings, 'UAH') === null, 'product shipping disabled by default');
check(CodeCartMerchantSchema::errors($settings) === array(), 'empty disabled settings accepted');

$settings['config_codecart_schema_shipping_status'] = 1;
check(CodeCartMerchantSchema::errors($settings) === array('shipping'), 'incomplete shipping is rejected');
$settings['config_codecart_schema_shipping_name'] = 'Standard';
$settings['config_codecart_schema_shipping_countries'] = 'UA, PL';
$settings['config_codecart_schema_shipping_currency'] = 'UAH';
$settings['config_codecart_schema_shipping_price'] = '0.00';
$settings['config_codecart_schema_shipping_handling_min'] = '0';
$settings['config_codecart_schema_shipping_handling_max'] = '1';
$settings['config_codecart_schema_shipping_transit_min'] = '1';
$settings['config_codecart_schema_shipping_transit_max'] = '3';
check(CodeCartMerchantSchema::validShipping($settings), 'valid shipping policy accepted');
$shipping = CodeCartMerchantSchema::shipping($settings, 'https://example.test/', 'https://example.test/shipping');
check($shipping['@type'] === 'ShippingService', 'organization-level ShippingService');
check($shipping['shippingConditions'][0]['shippingRate']['value'] === 0.0, 'free shipping is zero, not missing');
check(count($shipping['shippingConditions'][0]['shippingDestination']) === 2, 'multiple countries rendered');
check(CodeCartMerchantSchema::offerShipping($settings, 'UAH') === null, 'offer-level shipping remains OFF');
$settings['config_codecart_schema_shipping_offers'] = 1;
$offer = CodeCartMerchantSchema::offerShipping($settings, 'UAH');
check($offer['@type'] === 'OfferShippingDetails', 'explicit offer shipping enabled');
check($offer['deliveryTime']['handlingTime']['minValue'] === 0, 'handling day range');
check(CodeCartMerchantSchema::offerShipping($settings, 'EUR') === null, 'never claim rate in another currency');

$settings['config_codecart_schema_return_status'] = 1;
$settings['config_codecart_schema_return_countries'] = 'UA,PL';
$settings['config_codecart_schema_return_category'] = 'finite';
check(in_array('return', CodeCartMerchantSchema::errors($settings), true), 'missing return window rejected');
$settings['config_codecart_schema_return_days'] = '14';
$settings['config_codecart_schema_return_fees'] = 'ReturnFeesCustomerResponsibility';
$settings['config_codecart_schema_return_method'] = 'ReturnByMail';
$return = CodeCartMerchantSchema::returns($settings, 'https://example.test/returns');
check($return['@type'] === 'MerchantReturnPolicy', 'organization-level returns');
check($return['merchantReturnDays'] === 14, 'return window 14 days');
check($return['merchantReturnLink'] === 'https://example.test/returns', 'return policy link');
check(!isset($return['returnShippingFeesAmount']), 'customer-responsibility does not invent fees');

$settings['config_codecart_schema_return_fees'] = 'ReturnShippingFees';
check(in_array('return', CodeCartMerchantSchema::errors($settings), true), 'fixed return cost requires amount');
$settings['config_codecart_schema_return_cost'] = '5.50';
$settings['config_codecart_schema_return_currency'] = 'UAH';
$return = CodeCartMerchantSchema::returns($settings);
check($return['returnShippingFeesAmount']['value'] === 5.5, 'fixed return cost');
$settings['config_codecart_schema_return_category'] = 'not_permitted';
$return = CodeCartMerchantSchema::returns($settings);
check(!isset($return['merchantReturnDays']) && !isset($return['returnFees']), 'not permitted has no contradictory charges');

$settings['config_codecart_schema_legal_name'] = 'Example LLC';
$settings['config_codecart_schema_vat_id'] = 'UA0123456789';
$settings['config_codecart_schema_same_as'] = "https://example.org/company\nhttps://example.net/shop";
$identity = CodeCartMerchantSchema::identity($settings);
check($identity['legalName'] === 'Example LLC' && count($identity['sameAs']) === 2, 'verified merchant identity');

$secondary = $settings;
$secondary['config_store_id'] = 2;
check(CodeCartMerchantSchema::identity($secondary) === array(), 'main store identity cannot leak into another store');
check(CodeCartMerchantSchema::shipping($secondary, 'https://other.test/') === null, 'no cross-store shipping');
check(CodeCartMerchantSchema::returns($secondary) === null, 'no cross-store return policy');
check(CodeCartMerchantSchema::offerShipping($secondary, 'UAH') === null, 'no cross-store offer shipping');
$secondary['config_codecart_schema_store_id'] = 2;
check(CodeCartMerchantSchema::shipping($secondary, 'https://other.test/') !== null, 'secondary store policy supported');
$secondary['config_codecart_schema_shipping_handling_min'] = '4';
$secondary['config_codecart_schema_shipping_handling_max'] = '1';
check(!CodeCartMerchantSchema::validShipping($secondary), 'invalid day range suppressed');
$secondary['config_codecart_schema_shipping_handling_min'] = '0';
$secondary['config_codecart_schema_shipping_countries'] = 'not-a-country';
check(!CodeCartMerchantSchema::validShipping($secondary), 'invalid country list suppressed');

echo "Structured data checks passed\n";
