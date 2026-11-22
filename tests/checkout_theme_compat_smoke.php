<?php
declare(strict_types=1);
/* Diverse storefront checkouts, OC/ocStore upgrade compatibility and SEO safety. */
$root = dirname(__DIR__);
require_once $root . '/upload/system/library/codecart/src/CheckoutRouteResolver.php';
class CodeCartTestConfig {
    private $settings;
    public function __construct(array $settings) { $this->settings = $settings; }
    public function get($name) { return $this->settings[$name] ?? null; }
}
class Controller {
    public $config;
    public function __construct($registry = null) {}
}
require_once $root . '/upload/catalog/controller/startup/seo_url.php';
$tmp = sys_get_temp_dir() . '/cc_checkout_theme_' . bin2hex(random_bytes(5));
$catalog = $tmp . '/catalog/';
$storage = $tmp . '/storage/';
@mkdir($catalog . 'controller/checkout', 0700, true);
@mkdir($catalog . 'controller/extension/module', 0700, true);
@mkdir($storage . 'modification/catalog/controller/checkout', 0700, true);
define('DIR_APPLICATION', $catalog);
define('DIR_STORAGE', $storage);
$files = [
    $catalog . 'controller/checkout/fast_checkout.php',
    $catalog . 'controller/checkout/uni_checkout.php',
    $catalog . 'controller/extension/module/onepage_checkout.php',
    $storage . 'modification/catalog/controller/checkout/ocmod_checkout.php'
];
foreach ($files as $path) { file_put_contents($path, '<?php'); }
$pass = 0;
function checkRoute(array $settings, string $expect, bool $explicitUni = false): void {
    global $catalog, $storage, $pass;
    $found = \CodeCart\Core\CheckoutRouteResolver::resolve(new CodeCartTestConfig($settings), $catalog, $storage, $explicitUni);
    if ($found !== $expect) {
        throw new RuntimeException("Expected $expect, got $found");
    }
    ++$pass;
}
function checkRewritten(array $settings, string $route, string $expected): void {
    global $pass;
    $instance = (new ReflectionClass(ControllerStartupSeoUrl::class))->newInstanceWithoutConstructor();
    $instance->config = new CodeCartTestConfig($settings + ['config_seo_url' => 1, 'config_seo_pro' => 0]);
    $url = 'https://shop.example/index.php?route=' . $route;
    $result = html_entity_decode($instance->rewrite($url), ENT_QUOTES, 'UTF-8');
    if ($result !== $expected) {
        throw new RuntimeException("SEO $route: expected $expected, got $result");
    }
    ++$pass;
}
try {
    // No setting in migrated OpenCart/ocStore 3: retain standard route.
    checkRoute(['config_theme' => 'default'], 'checkout/checkout');
    checkRoute(['config_theme' => 'codecart'], 'checkout/checkout');
    // Explicit controller routes, including a renamed third-party theme.
    checkRoute(['config_theme' => 'fastshop', 'config_codecart_checkout_route' => 'checkout/fast_checkout'], 'checkout/fast_checkout');
    checkRoute(['config_theme' => 'fastshop', 'theme_fastshop_checkout_route' => 'checkout/fast_checkout'], 'checkout/fast_checkout');
    checkRoute(['config_theme' => 'any_theme', 'config_checkout_route' => 'extension/module/onepage_checkout'], 'extension/module/onepage_checkout');
    // OCMOD controller overrides work without an unmodified physical file.
    checkRoute(['config_codecart_checkout_route' => 'checkout/ocmod_checkout'], 'checkout/ocmod_checkout');
    checkRoute(['config_codecart_checkout_route' => 'checkout/missing_checkout'], 'checkout/checkout');
    checkRoute(['config_codecart_checkout_route' => 'checkout/missing_checkout', 'config_checkout_route' => 'checkout/fast_checkout'], 'checkout/fast_checkout');
    // Legacy UniShop2 support, even after an OC config_theme=default migration.
    checkRoute(['config_theme' => 'default', 'config_unishop2' => ['checkout' => ['status' => 1]]], 'checkout/uni_checkout');
    checkRoute(['config_theme' => 'unishop2'], 'checkout/uni_checkout');
    // A different theme must not accidentally select a leftover UniShop2.
    checkRoute(['config_theme' => 'unicorn'], 'checkout/checkout');
    checkRoute(['config_theme' => 'codecart', 'config_unishop2' => ['checkout' => ['status' => 1]]], 'checkout/checkout');
    checkRoute(['config_theme' => 'codecart'], 'checkout/uni_checkout', true);
    foreach (['../checkout/evil', 'checkout/cart', 'checkout/success', 'extension/payment/bank_transfer', 'api/order', 'checkout/cart/add', 'checkout/../foo'] as $route) {
        if (\CodeCart\Core\CheckoutRouteResolver::isValidRoute($route)) { throw new RuntimeException('Accepted unsafe route: ' . $route); }
        ++$pass;
    }

    checkRewritten(['config_codecart_checkout_route' => 'checkout/fast_checkout'], 'checkout/fast_checkout', 'https://shop.example/checkout/fast_checkout/');
    checkRewritten(['config_codecart_checkout_route' => 'extension/module/onepage_checkout'], 'extension/module/onepage_checkout', 'https://shop.example/checkout/');
    checkRewritten(['config_codecart_checkout_route' => 'checkout/fast_checkout'], 'checkout/cart/add', 'https://shop.example/index.php?route=checkout/cart/add');
    checkRewritten(['config_codecart_checkout_route' => 'checkout/fast_checkout'], 'checkout/confirm', 'https://shop.example/index.php?route=checkout/confirm');
    checkRewritten(['config_codecart_checkout_route' => 'checkout/fast_checkout'], 'extension/payment/bank_transfer', 'https://shop.example/index.php?route=extension/payment/bank_transfer');
    checkRewritten(['config_theme' => 'default'], 'checkout/checkout', 'https://shop.example/checkout/');

    unlink($catalog . 'controller/checkout/fast_checkout.php');
    checkRoute(['config_codecart_checkout_route' => 'checkout/fast_checkout'], 'checkout/checkout');
    echo "PASS: $pass checkout/theme/SEO checks across stock, OCMOD, UniShop2 and third-party routes\n";
} finally {
    foreach ($files as $path) { @unlink($path); }
    @rmdir($catalog . 'controller/extension/module');
    @rmdir($catalog . 'controller/extension');
    @rmdir($catalog . 'controller/checkout');
    @rmdir($catalog . 'controller');
    @rmdir($catalog);
    @rmdir($storage . 'modification/catalog/controller/checkout');
    @rmdir($storage . 'modification/catalog/controller');
    @rmdir($storage . 'modification/catalog');
    @rmdir($storage . 'modification');
    @rmdir($storage);
    @rmdir($tmp);
}
