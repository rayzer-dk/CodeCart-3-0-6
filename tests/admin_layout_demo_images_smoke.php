<?php
/** Regression coverage for sidebar layout and opt-in demo category image repair. */
$root = dirname(__DIR__) . '/';
$errors = array();
$check = static function ($yes, $message) use (&$errors) {
    if (!$yes) { $errors[] = $message; }
};

$templates = array(
    'admin/view/template/catalog/category_list.twig',
    'admin/view/template/catalog/product_list.twig',
    'admin/view/template/catalog/manufacturer_list.twig',
    'admin/view/template/catalog/information_list.twig',
    'admin/view/template/blog/article_list.twig',
    'admin/view/template/blog/category_list.twig'
);
foreach ($templates as $relative) {
    $source = file_get_contents($root . 'upload/' . $relative);
    $check($source !== false, $relative . ': readable');
    $check((bool)preg_match('/\\{\\{\\s*column_left\\s*\\}\\}\\s*<div id="content">/', $source), $relative . ': sidebar/content adjacency');
    $check(strpos($source, 'catalog-quality.js') !== false, $relative . ': quality indicators stay enabled');
}

$category = file_get_contents($root . 'upload/admin/view/template/catalog/category_list.twig');
$check(substr_count($category, 'cc-quality-count-link') === 2, 'clickable category product counts in expanded and collapsed branches');
$check(strpos($category, 'href="{{ category.products_href }}"') !== false, 'category count navigation bound to backend URL');

$controller = file_get_contents($root . 'upload/admin/controller/catalog/category.php');
$check(strpos($controller, "'products_href'") !== false, 'backend exposes product-filter links');
$check(strpos($controller, "'&filter_category='") !== false, 'links use existing product category filter');

$css = file_get_contents($root . 'upload/admin/view/stylesheet/codecart-catalog-quality.css');
$js = file_get_contents($root . 'upload/admin/view/javascript/codecart/catalog-quality.js');
$check(strpos($css, 'conic-gradient(') !== false && strpos($css, 'radial-gradient(') !== false, 'quality donut uses real percent ring');
$check(strpos($js, "--cc-quality-percent") !== false, 'score drives gauge fill');
$check(strpos($js, ".cc-quality-count-number") !== false, 'count change preserves action arrow');
$check(strpos($css, '.cc-quality-count[hidden]') !== false, 'counter does not flash before async data loads');

$tool = file_get_contents($root . 'upload/system/cli/restore_ocstore_demo_category_photos.php');
$check(strpos($tool, '$backupConfirmed') !== false && strpos($tool, 'if (!$apply)') !== false, 'demo image repair is opt-in');
$check(strpos($tool, "image IS NULL OR image = ''") !== false, 'demo image repair does not overwrite merchant image paths');
$check(strpos($tool, 'array_keys($actual) !== array_keys($map)') !== false, 'only original demo structure may be repaired');

preg_match_all("/^\\s*(\\d+)\\s*=>\\s*array\\((\\d+),\\s*'([^']+\\.webp)'\\)/m", $tool, $rows, PREG_SET_ORDER);
$toolMap = array();
foreach ($rows as $row) {
    $toolMap[(int)$row[1]] = array((int)$row[2], $row[3]);
}
$check(count($toolMap) === 38, 'exactly 38 original demo categories');
$sql = file_get_contents($root . 'upload/install/opencart.sql');
preg_match('/INSERT INTO `oc_category`[^;]*;/s', $sql, $block);
$expected = array();
if (isset($block[0])) {
    preg_match_all("/\\((\\d+),\\s*'catalog\\/demo\\/([^']+\\.webp)',\\s*(\\d+),/", $block[0], $matches, PREG_SET_ORDER);
    foreach ($matches as $row) {
        $expected[(int)$row[1]] = array((int)$row[3], $row[2]);
    }
}
ksort($toolMap);
ksort($expected);
$check(count($expected) === 38 && $toolMap === $expected, 'demo repair map agrees with current bundled clean-install data');

if ($errors) {
    fwrite(STDERR, "FAILED: " . implode('; ', $errors) . "\n");
    exit(1);
}
echo "PASS: sidebar geometry, filtered category count, circular quality indicators and safe demo image map\n";
