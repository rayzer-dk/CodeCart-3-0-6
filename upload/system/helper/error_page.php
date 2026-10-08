<?php
/** Dependency-free HTML fallback when the application cannot render its theme. */
function codecart_error_page($language, $home) {
    $language = in_array($language, array('en-gb', 'ru-ru', 'uk-ua'), true) ? $language : 'en-gb';
    $strings = static function($code) {
        $_ = array();
        $file = DIR_SYSTEM . '../catalog/language/' . $code . '/error/server_error.php';
        if (is_file($file)) { require $file; }
        return $_;
    };
    $text = array_merge($strings('en-gb'), $strings($language));
    $parts = is_string($home) ? parse_url($home) : false;
    if (!$parts || preg_match('/[\x00-\x20<>"\x27\\\\]/', $home) || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass']) ||
        (isset($parts['scheme']) ? !in_array(strtolower($parts['scheme']), array('http', 'https'), true) || empty($parts['host']) : substr($home, 0, 1) !== '/' || substr($home, 0, 2) === '//')) {
        $home = '/';
    }
    $escape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    $css_file = DIR_SYSTEM . '../catalog/view/theme/codecart/stylesheet/error-page.css';
    $css = is_file($css_file) ? (string)file_get_contents($css_file) : '.ccp-error{max-width:720px;margin:10vh auto;padding:32px;font-family:sans-serif}';
    $title = $escape($text['heading_title'] ?? 'Service temporarily unavailable');
    $message = $escape($text['text_error'] ?? 'Please try again later.');
    $button = $escape($text['button_home'] ?? 'Home');
    return '<!DOCTYPE html><html lang="' . $language . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . $title . '</title><style>' . $css . '</style></head><body class="ccp-error-standalone"><main class="ccp-error"><div class="ccp-error__visual" aria-hidden="true"><span class="ccp-error__code">500</span><span class="ccp-error__orbit"></span></div><div class="ccp-error__body"><span class="ccp-error__label">CodeCart PRO</span><h1>' . $title . '</h1><p>' . $message . '</p><div class="ccp-error__actions"><a class="ccp-error__button" href="' . $escape($home) . '">' . $button . '</a></div></div></main></body></html>';
}
