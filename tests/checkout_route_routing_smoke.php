<?php
declare(strict_types=1);

/* Checkout compatibility for old OpenCart/ocStore 3.x and optional UniShop2. */
require dirname(__DIR__) . '/upload/system/library/codecart/src/CheckoutRouteResolver.php';
final class CodeCartCheckoutTestConfig {
    private $values;
    public function __construct(array $values) { $this->values = $values; }
    public function get($key) { return $this->values[$key] ?? null; }
}
function assertCheckoutRoute(array $values, string $app, string $expected, bool $explicit = false): void {
    $actual = \CodeCart\Core\CheckoutRouteResolver::resolve(new CodeCartCheckoutTestConfig($values), $app, '', $explicit);
    if ($actual !== $expected) { throw new RuntimeException($actual . ' !== ' . $expected); }
}
$tmp = sys_get_temp_dir() . '/codecart_checkout_' . bin2hex(random_bytes(6));
$app = $tmp . '/catalog/';
$folder = $app . 'controller/checkout';
if (!mkdir($folder, 0700, true) && !is_dir($folder)) { throw new RuntimeException('mkdir failed'); }
try {
    assertCheckoutRoute(['config_theme' => 'unishop2'], $app, 'checkout/checkout');
    assertCheckoutRoute(['config_theme' => 'codecart'], $app, 'checkout/checkout', true);
    file_put_contents($folder . '/uni_checkout.php', '<?php');
    assertCheckoutRoute(['config_theme' => 'unishop2'], $app, 'checkout/uni_checkout');
    assertCheckoutRoute(['config_theme' => 'default', 'config_template' => 'unishop2'], $app, 'checkout/uni_checkout');
    assertCheckoutRoute(['config_theme' => 'default', 'config_unishop2' => ['checkout' => []]], $app, 'checkout/uni_checkout');
    assertCheckoutRoute(['config_theme' => 'codecart'], $app, 'checkout/checkout');
    assertCheckoutRoute(['config_theme' => 'codecart'], $app, 'checkout/uni_checkout', true);
    unlink($folder . '/uni_checkout.php');
    assertCheckoutRoute(['config_theme' => 'unishop2'], $app, 'checkout/checkout');
    $seo = file_get_contents(dirname(__DIR__) . '/upload/catalog/controller/startup/seo_url.php');
    $header = file_get_contents(dirname(__DIR__) . '/upload/catalog/controller/common/header.php');
    $cart = file_get_contents(dirname(__DIR__) . '/upload/catalog/controller/common/cart.php');
    foreach ([
      strpos($seo, "'checkout/uni_checkout' => 'checkout/uni_checkout'") !== false,
      strpos($seo, "$"."public_path === 'checkout/uni_checkout'") !== false,
      strpos($header, 'CheckoutRouteResolver::resolve(') !== false,
      strpos($cart, 'CheckoutRouteResolver::resolve(') !== false,
    ] as $pass) { if (!$pass) { throw new RuntimeException('Checkout SEO/link integration missing'); } }
} finally {
    @unlink($folder . '/uni_checkout.php');
    @rmdir($folder); @rmdir($app . 'controller'); @rmdir($app); @rmdir($tmp);
}
echo "PASS: native checkout, UniShop2 route, legacy settings, fallback and links\n";
