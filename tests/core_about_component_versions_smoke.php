<?php
// Verify the Core / Compatibility / About system table agrees with bundled versions.
declare(strict_types=1);

$root = dirname(__DIR__);
$controller = (string)file_get_contents($root . '/upload/admin/controller/tool/codecart_core.php');
$lock = json_decode((string)file_get_contents($root . '/composer.lock'), true);
$packages = [];
foreach (($lock['packages'] ?? []) as $package) {
    $packages[$package['name']] = ltrim((string)$package['version'], 'v');
}
$issues = [];
$locked = [
    'Guzzle' => ['guzzlehttp/guzzle', '8.5.5'],
    'Guzzle Promises' => ['guzzlehttp/promises', '3.2.0'],
    'Guzzle PSR-7' => ['guzzlehttp/psr7', '3.6.0'],
    'Symfony Filesystem' => ['symfony/filesystem', '6.4.50'],
    'Symfony Polyfill PHP 8.2' => ['symfony/polyfill-php82', '1.43.0'],
];
$rows = [
    'jQuery' => '3.8.0 (Bootstrap 3 compatible)',
    'jQuery Migrate' => '3.6.0',
    'Moment.js' => '2.31.0',
    'DateTimePicker' => 'CodeCart Hybrid DateTimePicker 1.8.7; Bootstrap DateTimePicker 4.17.49 (legacy)',
    'Flot' => 'Flot 4.2.6 (legacy); CodeCart SVG Chart 1.2.0 in Core',
    'jQVMap' => 'jQVMap 1.5.1 (legacy); CodeCart SVG World Map 1.1.0 in Core',
];
foreach ($locked as $component => [$package, $expected]) {
    if (($packages[$package] ?? '') !== $expected) {
        $issues[] = $package . ' differs from composer.lock expected version ' . $expected;
    }
    $rows[$component] = $expected;
}
foreach ($rows as $component => $expected) {
    $re = "~array\('component'=>'" . preg_quote($component, '~') . "',[^\r\n]*'codecart'=>'([^']+)'~";
    if (!preg_match($re, $controller, $m) || $m[1] !== $expected) {
        $issues[] = 'Stale Core About row: ' . $component;
    }
}
foreach (['admin','catalog'] as $side) {
    $base = $root . '/upload/' . $side . '/view/javascript/jquery/';
    $loader = (string)file_get_contents($base . 'jquery-2.1.1.min.js');
    foreach (['jquery-3.8.0.min.js','jquery-migrate-3.6.0.min.js'] as $file) {
        if (!is_file($base . $file) || !str_contains($loader, $file)) {
            $issues[] = $side . ' legacy shim must load ' . $file;
        }
    }
}
foreach (['uk-ua','ru-ru','en-gb'] as $locale) {
    $text = (string)file_get_contents($root . '/upload/admin/language/' . $locale . '/tool/codecart_core.php');
    foreach (['Guzzle 8.5.5','jQuery 3.8.0','Migrate 3.6.0','Moment.js 2.31.0','Flot 4.2.6','jQVMap 1.5.1','DateTimePicker 4.17.49'] as $value) {
        if (!str_contains($text, $value)) {
            $issues[] = $locale . ' missing updated ' . $value;
        }
    }
    foreach (['cmp_flot_legacy','cmp_jqvmap_legacy','cmp_datepicker_legacy','cmp_jquery_compat'] as $key) {
        if (!str_contains($text, "\x24" . "_['" . $key . "']")) {
            $issues[] = $locale . ' missing translation key ' . $key;
        }
    }
}
if ($issues) {
    fwrite(STDERR, implode(PHP_EOL, $issues) . PHP_EOL);
    exit(1);
}
echo "PASS: Core About versions, Composer lock, legacy loaders and locales are synchronized." . PHP_EOL;
