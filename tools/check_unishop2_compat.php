<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    'upload/admin/view/template/common/header.twig' => [
        'extension/module/uni_settings',
        'extension/theme/unishop2',
        'ccp-unishop-admin-compat',
    ],
    'upload/system/library/codecart/src/CompatibilityAdapterInterface.php' => [
        'interface CompatibilityAdapterInterface',
        'public function adapt(',
    ],
    'upload/system/library/codecart/src/CompatibilityFramework.php' => [
        'final class CompatibilityFramework',
        'public function apply(',
        'public function ocmodSatisfied(',
        'Unishop2Compatibility',
    ],
    'upload/system/library/codecart/src/Unishop2Compatibility.php' => [
        "return 'theme.unishop2';",
        "case 'catalog.menu.data':",
        "case 'catalog.category_module.full_tree':",
        "case 'catalog.category_page.subcategories':",
        "case 'catalog.category_page.banner_in_category':",
        "case 'catalog.product.option_image_size':",
        "case 'catalog.product.option_value':",
        "case 'catalog.banner.item':",
        'public function satisfiesOcmod(',
    ],
    'upload/catalog/controller/common/menu.php' => [
        "codecart_compatibility_framework",
        "apply('catalog.menu.data'",
    ],
    'upload/catalog/controller/extension/module/category.php' => [
        "codecart_compatibility_framework",
        "apply('catalog.category_module.full_tree'",
    ],
    'upload/catalog/controller/product/category.php' => [
        "apply('catalog.category_page.subcategories'",
        "apply('catalog.category_page.banner_in_category'",
    ],
    'upload/catalog/controller/product/product.php' => [
        "apply('catalog.product.option_image_size'",
        "apply('catalog.product.option_value'",
    ],
    'upload/catalog/controller/extension/module/banner.php' => [
        "apply('catalog.banner.item'",
    ],
];

$errors = [];
foreach ($checks as $relative => $needles) {
    $path = $root . '/' . $relative;
    if (!is_file($path)) {
        $errors[] = 'Missing compatibility file: ' . $relative;
        continue;
    }
    $source = (string)file_get_contents($path);
    foreach ($needles as $needle) {
        if (strpos($source, $needle) === false) {
            $errors[] = 'Missing compatibility contract in ' . $relative . ': ' . $needle;
        }
    }
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, '[FAIL] ' . $error . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, '[PASS] UniShop2 v3.6.6.0 Compatibility Framework contracts' . PHP_EOL);
