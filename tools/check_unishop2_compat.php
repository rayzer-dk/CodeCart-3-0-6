<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    'upload/admin/view/template/common/header.twig' => [
        "extension/module/uni_settings",
        "extension/theme/unishop2",
        "ccp-unishop-admin-compat",
    ],
    'upload/catalog/controller/common/menu.php' => [
        "config_theme') === 'unishop2'",
        "$children_data = array();",
        "$children = $this->model_catalog_category->getCategories($category['category_id']);",
        "// Level 1",
    ],
    'upload/catalog/controller/extension/module/category.php' => [
        "config_theme') === 'unishop2'",
        "if (isset($this->request->get['path'])) {",
        "if ($category['category_id'] == $data['category_id']) {",
        "$children = $this->model_catalog_category->getCategories($category['category_id']);",
    ],
    'upload/catalog/controller/extension/module/banner.php' => [
        "$setting['width'] = $width;",
        "$setting['height'] = $height;",
        "$this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])",
    ],
    'upload/catalog/controller/product/category.php' => [
        "$data['categories'] = array();",
        "$results = $this->model_catalog_category->getCategories($category_id);",
        "$data['categories'][] = array(",
    ],
    'upload/catalog/controller/product/product.php' => [
        "show_ended_option_value",
        "option_img_small_w",
        "option_img_small_h",
    ],
];

$errors = [];

foreach ($checks as $relative => $needles) {
    $path = $root . '/' . $relative;

    if (!is_file($path)) {
        $errors[] = 'Missing compatibility file: ' . $relative;
        continue;
    }

    $content = (string)file_get_contents($path);

    foreach ($needles as $needle) {
        if (strpos($content, $needle) === false) {
            $errors[] = 'Missing UniShop2 compatibility contract in ' . $relative . ': ' . $needle;
        }
    }
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] " . $error . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "[PASS] UniShop2 v3.6.5.2 compatibility contracts" . PHP_EOL);
