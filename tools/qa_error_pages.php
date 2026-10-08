<?php
// Standalone failure renderer: must work without Registry, database or Twig.
$root = dirname(__DIR__);
define('DIR_SYSTEM', $root . '/upload/system/');
$renderer = DIR_SYSTEM . 'helper/error_page.php';
if (!is_file($renderer)) { fwrite(STDERR, "FAIL: standalone error renderer is missing\n"); exit(1); }
require $renderer;
$checks = 0;
foreach (array('en-gb' => 'temporarily unavailable', 'ru-ru' => 'временно недоступен', 'uk-ua' => 'тимчасово недоступний') as $language => $expected) {
    $html = codecart_error_page($language, 'https://shop.example.test/store/');
    if (strpos($html, $expected) === false || strpos($html, 'lang="' . $language . '"') === false || strpos($html, 'https://shop.example.test/store/') === false || strpos($html, 'noindex') === false) {
        fwrite(STDERR, 'FAIL: translated standalone error page ' . $language . PHP_EOL); exit(1);
    }
    $checks++;
}
foreach (array('javascript:alert(1)', '//attacker.example/', 'https://shop.test/" onmouseover="alert(1)', '/?token=secret') as $url) {
    $html = codecart_error_page('../../private', $url);
    if (strpos($html, 'lang="en-gb"') === false || strpos($html, 'href="/"') === false || strpos($html, 'secret') !== false || strpos($html, 'onmouseover') !== false || strpos($html, 'javascript:') !== false) {
        fwrite(STDERR, "FAIL: unsafe fallback navigation or language\n"); exit(1);
    }
    $checks++;
}
echo 'PASS: standalone error pages, translations and safe navigation (' . $checks . ' checks)' . PHP_EOL;
