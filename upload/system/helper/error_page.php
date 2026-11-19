<?php
/**
 * Dependency-free 500 response. No Registry, Twig, DB, JavaScript or CDN.
 * Keep public output generic; details belong only in the server error log.
 */

/**
 * The error experience starts in Ukrainian, independently of the shop's
 * config_language, admin locale, or the browser's Accept-Language.
 * Honor only an explicit choice by the visitor/administrator.
 * This code must remain safe before the database or session is available.
 */
function codecart_error_language_code($selected = '') {
    return is_string($selected) && in_array(strtolower($selected), array('uk-ua', 'ru-ru', 'en-gb'), true)
        ? strtolower($selected) : 'uk-ua';
}

function codecart_error_requested_language($area = 'catalog', $registry = null) {
    $selected = '';
    $session = null;
    if ($registry && method_exists($registry, 'has') && $registry->has('session')) {
        $session = $registry->get('session');
    }

    if ($area === 'admin') {
        // Only a saved per-administrator preference is explicit.
        if ($registry && method_exists($registry, 'get')) {
            $preference = $registry->get('codecart_admin_explicit_language');
            if (is_string($preference)) { $selected = $preference; }
        }
        if ($selected === '' && $session && isset($session->data['codecart_admin_error_language'])) {
            $selected = $session->data['codecart_admin_error_language'];
        }
    } else {
        // An actual /ru/ or /en/ language route is an explicit selection.
        $config = $registry && method_exists($registry, 'get') ? $registry->get('config') : null;
        if ($config && $config->get('codecart_language_prefix_route_matched') && $session && isset($session->data['language'])) {
            $selected = $session->data['language'];
        }
        if ($selected === '' && $session && isset($session->data['codecart_error_language_explicit'])) {
            $selected = $session->data['codecart_error_language_explicit'];
        }
        if ($selected === '' && isset($_COOKIE['codecart_error_language'])) {
            $selected = $_COOKIE['codecart_error_language'];
        }
        if ($selected === '' && isset($_GET['language'])) {
            $selected = $_GET['language'];
        }
    }
    return codecart_error_language_code($selected);
}

function codecart_error_page($language = 'uk-ua', $home = '/') {
    $language = in_array($language, array('en-gb', 'ru-ru', 'uk-ua'), true) ? $language : 'uk-ua';
    $strings = static function ($code) {
        $_ = array();
        $file = DIR_SYSTEM . '../catalog/language/' . $code . '/error/server_error.php';
        if (is_file($file)) { require $file; }
        return $_;
    };
    $text = array_merge($strings('en-gb'), $strings($language));

    // Only links to the current store's configured homepage are permitted.
    // A malformed value must never lead to script, credential or query injection.
    $parts = is_string($home) ? parse_url($home) : false;
    if (!$parts || !is_string($home) ||
        preg_match('/[\x00-\x20<>"\x27\\\\]/', $home) ||
        isset($parts['query']) || isset($parts['fragment']) ||
        isset($parts['user']) || isset($parts['pass']) ||
        (isset($parts['scheme']) ?
            !in_array(strtolower($parts['scheme']), array('http', 'https'), true) || empty($parts['host']) :
            substr($home, 0, 1) !== '/' || substr($home, 0, 2) === '//')) {
        $home = '/';
    }
    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $css_file = DIR_SYSTEM . '../catalog/view/theme/codecart/stylesheet/error-page.css';
    $css = is_file($css_file) ? file_get_contents($css_file) : false;
    if (!is_string($css) || $css === '') {
        $css = 'body{margin:0;background:#f3f6fb;font:16px/1.6 system-ui,sans-serif}' .
            '.ccp-error{max-width:950px;margin:6vh auto;padding:50px;background:#fff;border-radius:24px}' .
            '.ccp-error__visual{font-size:100px;color:#d76c45}' .
            '.ccp-error__button{display:inline-block;padding:12px 18px;color:#fff;background:#246be8}';
    }

    $title = $escape($text['heading_title'] ?? 'Service temporarily unavailable');
    $message = $escape($text['text_error'] ?? 'Please try again later.');
    $button = $escape($text['button_home'] ?? 'Home');
    $status_caption = $escape($text['text_visual_status'] ?? 'SERVER ERROR');
    $safe_home = $escape($home);

    // Use the identical bundled CodeCart PRO logo, not a recreated variant.
    // Embed locally so the 500 page needs no asset requests when routing fails.
    $logo_file = DIR_SYSTEM . 'library/codecart/assets/default-logo.webp';
    $logo_bytes = is_file($logo_file) ? @file_get_contents($logo_file) : false;
    $brand = is_string($logo_bytes) && $logo_bytes !== ''
        ? '<div class="ccp-error__brand"><img src="data:image/webp;base64,' . base64_encode($logo_bytes) . '" alt="CodeCart PRO" /></div>'
        : '<div class="ccp-error__brand">CodeCart PRO</div>';

    return '<!DOCTYPE html><html lang="' . $language . '"><head>' .
        '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">' .
        '<meta name="robots" content="noindex,nofollow,noarchive">' .
        '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:; base-uri \'none\'; form-action \'none\'">' .
        '<title>' . $title . '</title><style>' . $css . '</style></head>' .
        '<body class="ccp-error-standalone">' .
        '<main class="ccp-error ccp-error--500" aria-labelledby="ccp-error-title">' .
        '<div class="ccp-error__content">' .
        '<div class="ccp-error__eyebrow"><span class="ccp-error__dot" aria-hidden="true"></span>' .
        '<span>CodeCart PRO</span><span class="ccp-error__http">HTTP 500</span></div>' .
        '<h1 id="ccp-error-title" class="ccp-error__title">' . $title . '</h1>' .
        '<p class="ccp-error__description">' . $message . '</p>' .
        '<div class="ccp-error__actions"><a class="ccp-error__button" href="' . $safe_home . '">' . $button . '</a></div>' .
        '<div class="ccp-error__divider" aria-hidden="true"></div>' .
        '<div class="ccp-error__footnote">HTTP 500 · CodeCart PRO</div></div>' .
        '<div class="ccp-error__visual" aria-hidden="true">' .
        '<span class="ccp-error__visual-top">' . $status_caption . '</span>' .
        '<span class="ccp-error__code">500</span>' .
        '<span class="ccp-error__visual-bottom">HTTP · 05</span>' .
        '<span class="ccp-error__visual-marker"></span></div></main>' .
        '</body></html>';
}
