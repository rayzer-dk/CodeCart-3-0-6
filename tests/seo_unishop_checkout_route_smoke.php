<?php
/** Regression: /checkout/ must use UniShop2 when its checkout is installed,
 * including legacy stores that still report config_theme=default.
 * Run: php tests/seo_unishop_checkout_route_smoke.php
 */
declare(strict_types=1);
class Controller {
    public $config;
    public function __construct($registry = null) {}
}
class SeoPro {}
class CheckoutConfigStub {
    private $values;
    public function __construct(array $values) { $this->values = $values; }
    public function get($key) { return $this->values[$key] ?? null; }
}
$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/codecart-seo-unishop-' . getmypid();
@mkdir($tmp . '/catalog/controller/checkout', 0777, true);
define('DIR_APPLICATION', $tmp . '/catalog/');
define('DIR_STORAGE', $tmp . '/storage/');
require $root . '/upload/catalog/controller/startup/seo_url.php';
$instance = (new ReflectionClass(ControllerStartupSeoUrl::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(ControllerStartupSeoUrl::class, 'resolvePublicCheckoutRoute');
$method->setAccessible(true);
function checkCheckout($instance, ReflectionMethod $method, array $config, string $expected): void {
    $instance->config = new CheckoutConfigStub($config);
    $actual = $method->invoke($instance);
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAIL checkout route: expected ' . $expected . ', got ' . $actual . PHP_EOL);
        exit(1);
    }
}
$uni = array('checkout' => array('status' => 1));
try {
    checkCheckout($instance, $method, array('config_theme' => 'default', 'config_unishop2' => $uni), 'checkout/checkout');
    file_put_contents($tmp . '/catalog/controller/checkout/uni_checkout.php', "<?php\\n");
    checkCheckout($instance, $method, array('config_theme' => 'default', 'config_unishop2' => $uni), 'checkout/uni_checkout');
    checkCheckout($instance, $method, array('config_theme' => 'unishop2'), 'checkout/uni_checkout');
    checkCheckout($instance, $method, array('config_theme' => 'default', 'config_template' => 'unishop2'), 'checkout/uni_checkout');
    checkCheckout($instance, $method, array('config_theme' => 'default'), 'checkout/checkout');
    unlink($tmp . '/catalog/controller/checkout/uni_checkout.php');
    checkCheckout($instance, $method, array('config_theme' => 'unishop2'), 'checkout/checkout');
    echo "PASS UniShop2 /checkout/ resolves with legacy theme settings and safe fallback (6 checks)\\n";
} finally {
    @unlink($tmp . '/catalog/controller/checkout/uni_checkout.php');
    @rmdir($tmp . '/catalog/controller/checkout');
    @rmdir($tmp . '/catalog/controller');
    @rmdir($tmp . '/catalog');
    @rmdir($tmp);
}
