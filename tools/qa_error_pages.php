<?php
// Standalone failure renderer: must work without Registry, database or Twig.
$root = dirname(__DIR__);
define('DIR_SYSTEM', $root . '/upload/system/');
$renderer = DIR_SYSTEM . 'helper/error_page.php';
if (!is_file($renderer)) { fwrite(STDERR, "FAIL: standalone error renderer is missing\n"); exit(1); }
require $renderer;
$checks = 0;

// An error page MUST start in Ukrainian, even when OpenCart, the browser,
// or an automatically initialized session still use the old Russian language.
$originalCookies = $_COOKIE;
$originalGet = $_GET;
$_COOKIE = array();
$_GET = array();
$fakeRegistry = new class {
    public $session;
    public $explicitAdmin = null;
    public function __construct() {
        $this->session = (object)array('data' => array('language' => 'ru-ru'));
    }
    public function has($key) { return $key === 'session'; }
    public function get($key) {
        if ($key === 'session') { return $this->session; }
        if ($key === 'codecart_admin_explicit_language') { return $this->explicitAdmin; }
        if ($key === 'config') {
            return new class { public function get($name) { return $name === 'config_language' ? 'ru-ru' : 0; } };
        }
        return null;
    }
};
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9,en;q=0.7';
if (codecart_error_language_code() !== 'uk-ua'
    || codecart_error_requested_language('catalog', $fakeRegistry) !== 'uk-ua'
    || codecart_error_requested_language('admin', $fakeRegistry) !== 'uk-ua'
    || strpos(codecart_error_page(), 'lang="uk-ua"') === false
    || strpos(codecart_error_page(), 'Сервіс тимчасово недоступний') === false) {
    fwrite(STDERR, "FAIL: Ukrainian must be the primary language, not a last fallback\n"); exit(1);
}
$checks += 5;
$fakeRegistry->session->data['codecart_error_language_explicit'] = 'ru-ru';
if (codecart_error_requested_language('catalog', $fakeRegistry) !== 'ru-ru') {
    fwrite(STDERR, "FAIL: explicit storefront choice was ignored\n"); exit(1);
}
unset($fakeRegistry->session->data['codecart_error_language_explicit']);
$_COOKIE['codecart_error_language'] = 'en-gb';
if (codecart_error_requested_language('catalog', $fakeRegistry) !== 'en-gb') {
    fwrite(STDERR, "FAIL: remembered storefront language was ignored\n"); exit(1);
}
$fakeRegistry->explicitAdmin = 'ru-ru';
if (codecart_error_requested_language('admin', $fakeRegistry) !== 'ru-ru') {
    fwrite(STDERR, "FAIL: explicit administrator language was ignored\n"); exit(1);
}
$checks += 3;
$_COOKIE = $originalCookies;
$_GET = $originalGet;

$frameworkSource = file_get_contents($root . '/upload/system/framework.php');
if (!$frameworkSource
    || strpos($frameworkSource, 'codecart_error_requested_language($area, $registry)') === false
    || strpos($frameworkSource, "HTTP_ACCEPT_LANGUAGE") !== false) {
    fwrite(STDERR, "FAIL: standalone 500 still inherits old store/browser language\n"); exit(1);
}
$checks++;
foreach (array('upload/catalog/controller/error/not_found.php',
               'upload/catalog/controller/common/maintenance.php',
               'upload/admin/controller/error/not_found.php',
               'upload/admin/controller/error/permission.php') as $route) {
    $controllerSource = file_get_contents($root . '/' . $route);
    if (!$controllerSource ||
        strpos($controllerSource, 'codecart_error_requested_language(') === false ||
        strpos($controllerSource, "new Language(\$error_language_code)") === false) {
        fwrite(STDERR, "FAIL: " . $route . " bypasses Ukrainian-first error language\n"); exit(1);
    }
    $checks++;
}
foreach (array('en-gb' => 'temporarily unavailable', 'ru-ru' => 'временно недоступен', 'uk-ua' => 'тимчасово недоступний') as $language => $expected) {
    $html = codecart_error_page($language, 'https://shop.example.test/store/');
    if (strpos($html, $expected) === false || strpos($html, 'lang="' . $language . '"') === false || strpos($html, 'https://shop.example.test/store/') === false || strpos($html, 'noindex') === false) {
        fwrite(STDERR, 'FAIL: translated standalone error page ' . $language . PHP_EOL); exit(1);
    }
    $checks++;
}
foreach (array('javascript:alert(1)', '//attacker.example/', 'https://shop.test/" onmouseover="alert(1)', '/?token=secret') as $url) {
    $html = codecart_error_page('../../private', $url);
    if (strpos($html, 'lang="uk-ua"') === false || strpos($html, 'href="/"') === false || strpos($html, 'secret') !== false || strpos($html, 'onmouseover') !== false || strpos($html, 'javascript:') !== false) {
        fwrite(STDERR, "FAIL: unsafe fallback navigation or language\n"); exit(1);
    }
    $checks++;
}
// Design-system contract: 404 / 403 / 500 / 503 share responsive layout.
$cssFile = $root . '/upload/catalog/view/theme/codecart/stylesheet/error-page.css';
$logoFile = $root . '/upload/catalog/view/theme/codecart/image/codecart-logo.webp';
$originalLogo = $root . '/upload/system/library/codecart/assets/default-logo.webp';
if (!is_file($logoFile) || !is_file($originalLogo) || hash_file('sha256', $logoFile) !== hash_file('sha256', $originalLogo)) {
    fwrite(STDERR, "FAIL: error pages must use the unchanged original CodeCart logo\n"); exit(1);
}
$checks++;
$css = file_get_contents($cssFile);
if ($css === false) { fwrite(STDERR, "FAIL: shared error stylesheet missing\n"); exit(1); }
foreach (array(
    '.ccp-error--404', '.ccp-error--403', '.ccp-error--500', '.ccp-error--503',
    '.ccp-error__visual', '.ccp-error__button', '.ccp-error-standalone',
    '@media(max-width:540px)', 'prefers-reduced-motion', 'prefers-color-scheme:dark',
    'ccp-theme-dark', 'ccp-admin-dark', ':focus-visible'
) as $token) {
    if (strpos($css, $token) === false) {
        fwrite(STDERR, "FAIL: responsive error design token missing: " . $token . "\n"); exit(1);
    }
    $checks++;
}
foreach (array('codecart', 'default') as $theme) {
    $notFound = file_get_contents($root . '/upload/catalog/view/theme/' . $theme . '/template/error/not_found.twig');
    $maintenance = file_get_contents($root . '/upload/catalog/view/theme/' . $theme . '/template/common/maintenance.twig');
    if (!$notFound || !$maintenance ||
        strpos($notFound, 'catalog/view/theme/codecart/image/codecart-logo.webp') === false ||
        strpos($maintenance, 'catalog/view/theme/codecart/image/codecart-logo.webp') === false ||
        strpos($notFound, 'ccp-error--404') === false || strpos($notFound, 'ccp-error__button--secondary') === false ||
        strpos($notFound, 'aria-labelledby="ccp-error-title"') === false ||
        strpos($notFound, '{{ text_visual_status }}') === false ||
        strpos($notFound, 'content_bottom') === false ||
        strpos($maintenance, 'ccp-error--503') === false ||
        strpos($maintenance, '{{ text_visual_status }}') === false ||
        strpos($maintenance, 'aria-labelledby="ccp-error-title"') === false) {
        fwrite(STDERR, "FAIL: " . $theme . " storefront error page design or accessible navigation\n"); exit(1);
    }
    $checks += 2;
}
$adminCss = file_get_contents($root . '/upload/admin/view/stylesheet/codecart-error-page.css');
if (!$adminCss || strpos($adminCss, '../../../catalog/view/theme/codecart/stylesheet/error-page.css') === false) {
    fwrite(STDERR, "FAIL: admin error design is not shared with storefront\n"); exit(1);
}
$checks++;
foreach (array('not_found' => '404', 'permission' => '403') as $route => $code) {
    $html = file_get_contents($root . '/upload/admin/view/template/error/' . $route . '.twig');
    $controller = file_get_contents($root . '/upload/admin/controller/error/' . $route . '.php');
    if (!$html || !$controller ||
        strpos($html, '../catalog/view/theme/codecart/image/codecart-logo.webp') === false ||
        strpos($html, '{{ header }}{{ column_left }}' . "\n" . '<div id="content">') === false ||
        strpos($html, 'ccp-error--' . $code) === false ||
        strpos($html, '{{ text_visual_status }}') === false ||
        strpos($html, 'href="{{ dashboard }}"') === false ||
        strpos($controller, 'setStatusCode(' . $code . ')') === false ||
        strpos($controller, "codecart-error-page.css") === false) {
        fwrite(STDERR, "FAIL: admin " . $route . " design, layout or HTTP status\n"); exit(1);
    }
    $checks++;
    foreach (array('en-gb', 'ru-ru', 'uk-ua') as $locale) {
        $lang = file_get_contents($root . '/upload/admin/language/' . $locale . '/error/' . $route . '.php');
        if (!$lang || strpos($lang, "'button_dashboard'") === false || strpos($lang, "'text_error_label'") === false || strpos($lang, "'text_visual_status'") === false) {
            fwrite(STDERR, "FAIL: admin " . $locale . " " . $route . " localization\n"); exit(1);
        }
        $checks++;
    }
}
foreach (array('en-gb', 'ru-ru', 'uk-ua') as $locale) {
    $html = codecart_error_page($locale, '/');
    if (strpos($html, 'ccp-error--500') === false ||
        strpos($html, 'STATUS / SERVER ERROR') !== false || strpos($html, 'aria-labelledby="ccp-error-title"') === false ||
        strpos($html, 'Content-Security-Policy') === false ||
        strpos($html, 'img-src data:') === false ||
        strpos($html, 'src="data:image/webp;base64,') === false ||
        strpos($html, '<script') !== false || strpos($html, '<link') !== false ||
        strpos($html, 'font.googleapis') !== false) {
        fwrite(STDERR, "FAIL: standalone 500 must be secure and dependency-free\n"); exit(1);
    }
    $checks++;
}
$store404 = file_get_contents($root . '/upload/catalog/controller/error/not_found.php');
$store503 = file_get_contents($root . '/upload/catalog/controller/common/maintenance.php');
if (!$store404 || !$store503 ||
    strpos($store404, "setStatusCode(404)") === false ||
    strpos($store503, "setStatusCode(503)") === false ||
    strpos($store503, 'Retry-After: 3600') === false ||
    strpos($store404, "['button_home']") === false ||
    strpos($store503, "['text_error_description']") === false) {
    fwrite(STDERR, "FAIL: storefront status, Retry-After or localized data\n"); exit(1);
}
$checks++;

echo 'PASS: standalone error pages, translations and safe navigation (' . $checks . ' checks)' . PHP_EOL;
