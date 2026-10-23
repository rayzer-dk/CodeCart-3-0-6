<?php
declare(strict_types=1);

$root = dirname(__DIR__);
define('DIR_APPLICATION', $root . '/upload/catalog/');
foreach (array('registry', 'controller', 'model', 'proxy', 'loader', 'action', 'event') as $engine) {
    require $root . '/upload/system/engine/' . $engine . '.php';
}
require $root . '/upload/system/library/config.php';
require $root . '/upload/system/library/document.php';
require $root . '/upload/system/helper/utf8.php';
require $root . '/upload/system/library/image.php';
require $root . '/upload/system/library/codecart/src/TaxDisplay.php';
require $root . '/upload/system/library/codecart/src/CardText.php';
require DIR_APPLICATION . 'controller/extension/module/bestseller.php';

function imageEventAction(Closure $callback): Action {
    return new class($callback) extends Action {
        private $callback;
        public function __construct(Closure $callback) { $this->callback = $callback; }
        public function execute($registry, array $args = array()) { return ($this->callback)($args); }
    };
}

$checks = 0;
function imageEventCheck(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}

$fixture = realpath(sys_get_temp_dir()) . '/codecart-image-events-' . bin2hex(random_bytes(6));
mkdir($fixture, 0755, true);
define('DIR_IMAGE', $fixture . '/');
$failure = null;
try {
    $source = imagecreatetruecolor(1086, 355);
    imagefill($source, 0, 0, imagecolorallocate($source, 25, 80, 130));
    imagejpeg($source, DIR_IMAGE . 'wide.jpg');
    foreach (array('default', 'unishop2', 'codecart') as $theme) {
        $registry = new Registry();
        $config = new Config();
        $config->set('config_theme', $theme);
        $config->set('config_url', 'http://localhost/');
        $config->set('config_customer_price', 1);
        $config->set('theme_default_product_card_content', 'none');
        $registry->set('config', $config);
        $registry->set('request', (object)array('server' => array()));
        $registry->set('document', new Document());
        $registry->set('language', new class {
            public function load($route, $key = '') { return array(); }
            public function get($key) { return $key; }
        });
        $registry->set('customer', new class { public function isLogged() { return false; } });
        $registry->set('url', new class {
            public function link($route, $args = '') { return 'http://localhost/index.php?route=' . $route . '&' . $args; }
        });
        $registry->set('model_catalog_product', new class {
            public function getBestSellerProductCards($limit) {
                return array(array('product_id' => 1, 'image' => 'wide.jpg', 'price' => 10, 'special' => null,
                    'tax_class_id' => 0, 'quantity' => 1, 'stock_status' => 'Available', 'name' => 'Fixture', 'description' => ''));
            }
        });
        $event = new Event($registry);
        $registry->set('event', $event);
        $loader = new Loader($registry);
        $registry->set('load', $loader);
        $before = $after = $policy = 0;
        $arguments = array();
        $rendered = array();
        $event->register('model/tool/image/resize/before', imageEventAction(function (array $args) use (&$before, &$arguments) {
            $before++;
            $arguments = $args[1];
        }));
        $event->register('model/tool/image/resize/after', imageEventAction(function (array $args) use (&$after) {
            $after++;
            $args[2] .= '#legacy-image-listener';
        }));
        $event->register('model/tool/image/productImage/before', imageEventAction(function () use (&$policy) { $policy++; }));
        $event->register('view/extension/module/bestseller/before', imageEventAction(function (array $args) use (&$rendered) {
            $rendered = $args[1];
            return 'Captured actual controller output';
        }));
        // Execute the production controller and its real Loader/Proxy/Event boundary.
        (new ControllerExtensionModuleBestSeller($registry))->index(array('width' => 200, 'height' => 200));
        $card = $rendered['products'][0];
        if ($theme === 'codecart') {
            imageEventCheck($before === 0 && $after === 0 && $policy === 1, 'CodeCart explicitly selects the new image API');
            imageEventCheck($card['image_size']['width'] === 200 && $card['image_size']['height'] === 65, 'CodeCart card reports actual proportional dimensions');
        } else {
            imageEventCheck($before === 1 && $after === 1 && $policy === 0, $theme . ' preserves existing resize events');
            imageEventCheck($arguments === array('wide.jpg', 200, 200), $theme . ' preserves all three event arguments');
            imageEventCheck(str_ends_with($card['thumb'], '#legacy-image-listener'), $theme . ' preserves the image listener result');
            imageEventCheck($card['image_size'] === array(), $theme . ' receives no CodeCart image policy metadata');
        }
        $relative = substr((string)parse_url($card['thumb'], PHP_URL_PATH), strlen('/image/'));
        $info = getimagesize(DIR_IMAGE . $relative);
        imageEventCheck(array($info[0], $info[1]) === ($theme === 'codecart' ? array(200, 65) : array(200, 200)), $theme . ' actual derivative matches its image contract');
    }
    fwrite(STDOUT, '[PASS] Product image events: ' . $checks . " assertions; actual bestseller controller, Loader, Proxy and Event\n");
} catch (Throwable $error) {
    $failure = $error;
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($fixture);
}
if ($failure) {
    fwrite(STDERR, '[FAIL] ' . $failure->getMessage() . "\n");
    exit(1);
}
