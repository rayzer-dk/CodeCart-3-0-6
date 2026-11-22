<?php
declare(strict_types=1);

// Build tools are build-time dependencies only; they must be pinned to exact,
// reviewed versions. No legacy storefront/admin libraries are changed here.
$root = dirname(__DIR__);
$script = (string)file_get_contents($root . '/tools/build_theme_css.sh');
$workflow = (string)file_get_contents($root . '/.github/workflows/codecart-full-production.yml');
$errors = array();
foreach (array(
    'Lightning CSS CLI 1.33.0' => 'npx --yes lightningcss-cli@1.33.0 --minify',
    'Terser 5.51.4' => 'npx --yes terser@5.51.4 '
) as $label => $command) {
    if (substr_count($script, $command) !== 1) {
        $errors[] = $label . ' must be pinned exactly once in build_theme_css.sh';
    }
}
foreach (array('catalog/view/theme/codecart/stylesheet', 'catalog/view/theme/default/stylesheet', 'admin/view/stylesheet') as $directory) {
    $source = $root . '/upload/' . $directory . '/stylesheet.css';
    $target = $root . '/upload/' . $directory . '/stylesheet.min.css';
    if (!is_file($source) || !is_file($target) || filesize($target) === 0) {
        $errors[] = 'Missing stylesheet asset in ' . $directory;
    }
}
foreach (array('admin', 'catalog') as $side) {
    $source = $root . '/upload/' . $side . '/view/javascript/common.js';
    $target = $root . '/upload/' . $side . '/view/javascript/common.min.js';
    if (!is_file($source) || !is_file($target) || filesize($target) === 0) {
        $errors[] = 'Missing JavaScript asset for ' . $side;
    }
}
if (!str_contains($workflow, 'node --check upload/admin/view/javascript/common.min.js')
    || !str_contains($workflow, 'node --check upload/catalog/view/javascript/common.min.js')) {
    $errors[] = 'Production build must check generated JavaScript syntax';
}
if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
echo 'PASS: pinned Lightning CSS CLI 1.33.0 and Terser 5.51.4, generated assets present.' . PHP_EOL;
