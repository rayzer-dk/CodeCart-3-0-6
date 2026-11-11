<?php
/** Regression checks for the code-audit fixes: php tests/audit_fixes_smoke.php */
$root = dirname(__DIR__) . '/upload/';
require_once $root . 'system/library/codecart/src/Money.php';
require_once $root . 'system/helper/general.php';

$failures = array();
$read = function ($path) use ($root) { return (string)file_get_contents($root . $path); };

// Money: scaling by hand before round() loses a unit on PHP 8.4.
foreach (array('79364.02835' => '79364.0284', '1.00005' => '1.0001', '-1.00005' => '-1.0001', '0.00004' => '0.0000', '12.5' => '12.5000') as $in => $out) {
    if (\CodeCart\Core\Money::normalize($in) !== $out) {
        $failures[] = 'Money::normalize(' . $in . ') returned ' . \CodeCart\Core\Money::normalize($in) . ', expected ' . $out;
    }
}

// Password reset codes expire and fit the varchar(40) column.
$code = codecart_reset_code();
if (strlen($code) !== 36 || !codecart_reset_code_valid($code)) {
    $failures[] = 'A freshly issued reset code must be valid and 36 characters long.';
}
$expired = token(28) . dechex(time() - 1);
if (codecart_reset_code_valid($expired)) {
    $failures[] = 'An expired reset code must be rejected.';
}
if (codecart_reset_code_valid(token(40)) || codecart_reset_code_valid('')) {
    $failures[] = 'A code without an expiry must be rejected.';
}
foreach (array('catalog/model/account/customer.php', 'admin/model/user/user.php') as $model) {
    if (strpos($read($model), 'codecart_reset_code_valid($code)') === false) { $failures[] = $model . ' must check the reset code expiry.'; }
}

// The PayPal migration drops tables and must not be a public route.
if (strpos($read('catalog/controller/extension/payment/paypal.php'), 'public function update()') !== false) {
    $failures[] = 'PayPal update() must not be publicly routable.';
}

// Only API calls may pick a store through the store_id parameter.
$startup = $read('catalog/controller/startup/startup.php');
if (strpos($startup, "get['store_id']) && strpos(") === false) {
    $failures[] = 'The store_id override must be limited to API routes.';
}

// Products that do not subtract stock are never reported as out of stock.
if (strpos($read('system/library/cart/cart.php'), "(int)\$product['subtract'] && (int)\$product['quantity'] < (int)\$cart['quantity']") === false) {
    $failures[] = 'Cart stock check must respect the product subtract flag.';
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS audit fixes: money rounding, reset code expiry, PayPal route, store_id, stock flag\n";
