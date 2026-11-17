<?php
declare(strict_types=1);
/** PWA route smoke: installation metadata and icons never read customer/checkout data. */
define('DIR_IMAGE', sys_get_temp_dir() . '/codecart-pwa-fixtures/');
function codecart_is_https($server = array()) { return !empty($server['HTTPS']); }
class Controller {
    public $config;
    public $request;
    public $response;
}
class PwaConfigStub {
    private $items;
    public function __construct(array $items) { $this->items = $items; }
    public function get($key) { return $this->items[$key] ?? null; }
}
class PwaResponseStub {
    public $headers = array();
    public $output = '';
    public function addHeader($value) { $this->headers[] = $value; }
    public function setOutput($value) { $this->output = $value; }
}
require dirname(__DIR__) . '/upload/catalog/controller/common/pwa.php';
function pwaAssert($result, $message) {
    if (!$result) { fwrite(STDERR, 'FAILED: ' . $message . PHP_EOL); exit(1); }
}
function pwaController($enabled, $store, $https = true) {
    $app = new ControllerCommonPwa();
    $app->config = new PwaConfigStub(array(
        'config_codecart_pwa_status' => $enabled,
        'config_name' => $store,
        'config_ssl' => 'https://example.test/shop/',
        'config_icon' => '',
    ));
    $app->request = (object)array('get' => array(), 'server' => array('REQUEST_METHOD' => 'GET', 'HTTPS' => $https));
    $app->response = new PwaResponseStub();
    return $app;
}
$app = pwaController(0, 'Fixture Store');
$app->manifest();
pwaAssert(in_array('HTTP/1.1 404 Not Found', $app->response->headers, true), 'Manifest must be absent when OFF');

$app = pwaController(1, 'Fixture Store');
$app->manifest();
$data = json_decode($app->response->output, true);
pwaAssert(is_array($data), 'Manifest should be valid JSON');
pwaAssert(($data['name'] ?? '') === 'Fixture Store', 'Use the real store name');
pwaAssert(($data['scope'] ?? '') === '/shop/', 'Respect installed subdirectory');
pwaAssert(($data['display'] ?? '') === 'standalone', 'Manifest must support standalone app');
pwaAssert(count($data['icons'] ?? array()) === 2, 'Manifest should contain two icons');
pwaAssert(($data['icons'][0]['sizes'] ?? '') === '192x192', 'Icon 192 declaration');
pwaAssert(($data['icons'][1]['sizes'] ?? '') === '512x512', 'Icon 512 declaration');

if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
    foreach (array(180, 192, 512) as $size) {
        $app = pwaController(1, 'Fixture Store');
        $app->request->get['size'] = (string)$size;
        $app->icon();
        $info = getimagesizefromstring($app->response->output);
        pwaAssert(is_array($info) && $info[0] === $size && $info[1] === $size, 'PNG icon dimensions ' . $size);
        pwaAssert(in_array('Content-Type: image/png', $app->response->headers, true), 'PNG response type');
    }
}

$app = pwaController(1, 'Fixture Store', false);
$app->manifest();
pwaAssert(in_array('HTTP/1.1 404 Not Found', $app->response->headers, true), 'Requires HTTPS');
echo "PWA manifest/icon smoke: PASS\n";
