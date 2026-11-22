<?php
namespace CodeCart\Core;

/**
 * Public checkout selection for OpenCart/ocStore 3.x and optional themes.
 *
 * A theme may override checkout/checkout using OCMOD without changing its
 * route. A checkout with its own controller must be explicitly configured,
 * unless a supported legacy theme provides its original settings.
 *
 * Never guess controllers by file-name similarity: payment callbacks, API and
 * AJAX actions must not become public checkout landing pages.
 */
final class CheckoutRouteResolver {
    private const DEFAULT_ROUTE = 'checkout/checkout';

    public static function isValidRoute(string $route): bool {
        return (bool)preg_match(
            '#^(?:checkout/[a-z][a-z0-9_]{0,63}|extension/(?!payment/|shipping/|total/|feed/|captcha/)[a-z][a-z0-9_]{0,63}/[a-z][a-z0-9_]{0,63})$#D',
            $route
        ) && !in_array($route, array('checkout/cart', 'checkout/success', 'checkout/failure'), true);
    }

    public static function controllerExists(string $route, string $applicationDir, string $storageDir = ''): bool {
        if (!self::isValidRoute($route)) {
            return false;
        }
        $relative = '/controller/' . $route . '.php';
        if (is_file(rtrim($applicationDir, "/\\") . $relative)) {
            return true;
        }
        return $storageDir !== '' && is_file(
            rtrim($storageDir, "/\\") . '/modification/catalog' . $relative
        );
    }

    private static function configuredRoute($config): string {
        // Explicit per-store route has priority. Empty means automatic fallback.
        foreach (array('config_codecart_checkout_route', 'config_checkout_route') as $key) {
            $route = strtolower(trim((string)$config->get($key)));
            if ($route !== '' && self::isValidRoute($route)) {
                return $route;
            }
        }

        // Optional theme-provided setting, never required on legacy installs.
        $theme = strtolower(trim((string)$config->get('config_theme')));
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $theme)) {
            $route = strtolower(trim((string)$config->get('theme_' . $theme . '_checkout_route')));
            if ($route !== '' && self::isValidRoute($route)) {
                return $route;
            }
        }
        return '';
    }

    public static function resolve($config, string $applicationDir, string $storageDir = '', bool $explicitUni = false): string {
        // Backward compatibility: old bookmarks to /checkout/uni_checkout/
        // still work, but only if the corresponding controller is installed.
        if ($explicitUni) {
            return self::controllerExists('checkout/uni_checkout', $applicationDir, $storageDir)
                ? 'checkout/uni_checkout' : self::DEFAULT_ROUTE;
        }

        $configured = self::configuredRoute($config);
        if ($configured === self::DEFAULT_ROUTE) {
            return self::DEFAULT_ROUTE;
        }
        if ($configured !== '' && self::controllerExists($configured, $applicationDir, $storageDir)) {
            return $configured;
        }

        // Preserve older UniShop2 installations that never saved an explicit
        // checkout route. Do not require the theme name to be set correctly.
        if (self::controllerExists('checkout/uni_checkout', $applicationDir, $storageDir)) {
            $theme = strtolower((string)$config->get('config_theme'));
            $legacyTheme = strtolower((string)$config->get('config_template'));
            $settings = $config->get('config_unishop2');
            $hasSettings = is_array($settings) && isset($settings['checkout']) && is_array($settings['checkout']);
            if ($hasSettings || strpos($theme, 'uni') !== false || strpos($legacyTheme, 'uni') !== false) {
                return 'checkout/uni_checkout';
            }
        }
        return self::DEFAULT_ROUTE;
    }
}
