<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$errors = array();
function requireFragment(string $path, string $fragment, array &$errors): void {
    global $root;
    $source = (string)file_get_contents($root . '/' . $path);
    if (!str_contains($source, $fragment)) $errors[] = $path . ': ' . $fragment;
}
foreach (array('catalog', 'blog') as $kind) {
    requireFragment('upload/admin/view/template/' . $kind . '/category_list.twig',
        "document.documentElement.classList.add('cc-tree-js')", $errors);
    requireFragment('upload/admin/view/template/' . $kind . '/category_list.twig',
        'codecart-category-tree.css?v=3.0.6.1.11', $errors);
}
requireFragment('upload/admin/view/stylesheet/codecart-category-tree.css',
    '.cc-category-list-drag:not(.cc-tree-ready)', $errors);
requireFragment('upload/admin/view/javascript/codecart/category-tree.js',
    "treeTable.classList.add('cc-tree-ready')", $errors);
requireFragment('upload/admin/view/javascript/codecart-header-overflow.js',
    'if (item.parentNode !== nav)', $errors);
requireFragment('upload/admin/view/javascript/codecart-header-overflow.js',
    'firstCore.compareDocumentPosition(item)', $errors);
requireFragment('upload/admin/view/template/catalog/product_list.twig',
    'ccp-product-name-cell ccp-table-ellipsis-cell', $errors);
requireFragment('upload/admin/view/javascript/codecart-table-ellipsis.js',
    "table.classList.contains('cc-category-list-drag')", $errors);
requireFragment('upload/admin/view/stylesheet/codecart-catalog-quality.css',
    '.cc-quality-count-link[hidden]', $errors);
if ($errors) { fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL); exit(1); }
echo "PASS: pre-rendered category tree, product cells, badge positions and header DOM stability.\n";
