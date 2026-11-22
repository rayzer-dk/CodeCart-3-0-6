<?php
namespace CodeCart\Core;

/**
 * Select a public checkout without assuming third-party theme files exist.
 * Native OpenCart remains the fallback after an upgrade or UniShop2 uninstall.
 */
final class CheckoutRouteResolver {
    public static function resolve($config, string $applicationDir, string $storageDir = '', bool $explicitUni = false): string {
        $fallback = 'checkout/checkout';
        $controller = rtrim($applicationDir, "/\\") . '/controller/checkout/uni_checkout.php';
        $modified = $storageDir !== ''
            ? rtrim($storageDir, "/\\") . '/modification/catalog/controller/checkout/uni_checkout.php' : '';
        if (!is_file($controller) && ($modified === '' || !is_file($modified))) {
            return $fallback;
        }
        if ($explicitUni) {
            return 'checkout/uni_checkout';
        }
        $theme = strtolower((string)$config->get('config_theme'));
        $legacyTheme = strtolower((string)$config->get('config_template'));
        $unishop = $config->get('config_unishop2');
        $hasCheckoutSettings = is_array($unishop) && isset($unishop['checkout']) && is_array($unishop['checkout']);
        $isUniTheme = strpos($theme, 'uni') !== false || strpos($legacyTheme, 'uni') !== false;
        return ($hasCheckoutSettings || $isUniTheme) ? 'checkout/uni_checkout' : $fallback;
    }
}
