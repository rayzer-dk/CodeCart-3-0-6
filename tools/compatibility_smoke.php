<?php
$root = dirname(__DIR__) . '/upload/';
define('DIR_SYSTEM', $root . 'system/');
define('DIR_STORAGE', sys_get_temp_dir() . '/codecart_compat_smoke_' . getmypid() . '/');
@mkdir(DIR_STORAGE, 0750, true);

require DIR_SYSTEM . 'library/codecart/src/CompatibilityAdapterInterface.php';
require DIR_SYSTEM . 'library/codecart/src/ModernExtensionRegistry.php';
require DIR_SYSTEM . 'library/codecart/src/Unishop2Compatibility.php';
require DIR_SYSTEM . 'library/codecart/src/CompatibilityFramework.php';

final class SmokeConfig {
    private $data;
    public function __construct(array $data) { $this->data = $data; }
    public function get($key) { return $this->data[$key] ?? null; }
    public function has($key) { return array_key_exists($key, $this->data); }
}
final class SmokeLog { public $rows = array(); public function write($message) { $this->rows[] = $message; } }
final class SmokeRegistry {
    private $data;
    public function __construct(array $data) { $this->data = $data; }
    public function get($key) { return $this->data[$key] ?? null; }
}

$defaultRegistry = new SmokeRegistry(array('config' => new SmokeConfig(array('config_theme' => 'default')), 'log' => new SmokeLog()));
$default = new \CodeCart\Core\CompatibilityFramework($defaultRegistry);
$out = $default->apply('catalog.banner.item', array('item' => array('title' => 'x'), 'width' => 100, 'height' => 50));
if (isset($out['item']['width'])) { fwrite(STDERR, "Inactive adapter modified payload\n"); exit(1); }

$uniConfig = new SmokeConfig(array(
    'config_theme' => 'unishop2',
    'config_unishop2' => array('catalog' => array('subcategory' => array('image' => 1))),
    'theme_unishop2_status' => 1,
    'theme_unishop2_image_thumb_width' => 200,
    'theme_unishop2_image_thumb_height' => 160,
    'module_uni_banner_in_category_status' => 1
));
$uniRegistry = new SmokeRegistry(array('config' => $uniConfig, 'log' => new SmokeLog()));
$uni = new \CodeCart\Core\CompatibilityFramework($uniRegistry);
$out = $uni->apply('catalog.banner.item', array('item' => array('title' => 'x'), 'width' => 100, 'height' => 50));
if (($out['item']['width'] ?? 0) !== 100 || ($out['item']['height'] ?? 0) !== 50) { fwrite(STDERR, "UniShop banner contract failed\n"); exit(2); }
$sub = $uni->apply('catalog.category_page.subcategories', array('enabled' => true, 'images' => false, 'category_id' => 1));
if (empty($sub['enabled']) || empty($sub['images'])) { fwrite(STDERR, "UniShop category contract failed\n"); exit(3); }
$opt = $uni->apply('catalog.product.option_value', array('value' => array('quantity' => 0, 'subtract' => 1), 'raw_price' => 9.5));
if (empty($opt['value']['ended']) || ($opt['value']['maximum'] ?? -1) !== 0) { fwrite(STDERR, "UniShop option contract failed\n"); exit(4); }

@unlink(DIR_STORAGE . 'codecart/modern_extensions.json');
@rmdir(DIR_STORAGE . 'codecart');
@rmdir(DIR_STORAGE);
echo "Compatibility framework smoke: PASS\n";
