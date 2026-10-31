<?php
// Read-only source-level regression check; no connection to production DB.
$root = dirname(__DIR__) . '/upload/';
$guest = file_get_contents($root . 'catalog/controller/checkout/guest.php');
$contact = file_get_contents($root . 'system/library/codecart/src/GuestCartContact.php');
$startup = file_get_contents($root . 'catalog/controller/startup/startup.php');
$bridge = file_get_contents($root . 'catalog/controller/extension/module/codecart_guest_cart_contact.php');
$asset = file_get_contents($root . 'catalog/view/javascript/codecart/guest-cart-uni-consent.js');
foreach (array(
    'quick_optional_email' => strpos($guest, "\$checkout_field === 'email' && \$data['ccp_cart_recovery_enabled']") !== false,
    'quick_preserve_email' => strpos($guest, "if (!\$this->config->get('codecart_abandoned_cart_status')") !== false,
    'surname_database' => strpos($contact, '`lastname` VARCHAR(100)') !== false,
    'opt_in_required' => strpos($contact, "if (!\$consent || !filter_var") !== false,
    'checkout_route_gated' => strpos($startup, "=== 'checkout/uni_checkout'") !== false && strpos($startup, "=== 'custom'") !== false,
    'off_inert' => strpos($startup, "config->get('codecart_abandoned_cart_status')") !== false && strpos($startup, "config->get('codecart_abandoned_cart_guest_capture')") !== false,
    'nonce' => strpos($bridge, 'hash_equals($expected, $token)') !== false,
    'separate_checkout_form' => strpos($asset, 'form[name="unicheckout-customer-form"]') !== false,
    'async_forms_supported' => strpos($asset, 'MutationObserver') !== false,
    'explicit_consent' => strpos($asset, 'checkbox.checked = false') !== false,
    'native_checkout_optin_bound' => strpos($asset, 'var nativeCheckbox = select(box,') !== false && strpos($asset, 'checkbox = nativeCheckbox') !== false,
    'form_selector_mapping' => strpos($bridge, "'selectors'=>array(") !== false,
) as $test => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL $test\n"); exit(1); }
}
echo "Checkout contact contract: OK\n";
