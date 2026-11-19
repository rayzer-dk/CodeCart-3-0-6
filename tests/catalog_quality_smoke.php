<?php
/** Bounded, read-only catalog quality checks. php tests/catalog_quality_smoke.php */
define('DB_PREFIX', 'oc_');
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/CatalogQuality.php';

use CodeCart\Core\CatalogQuality;

class QualityMockDb {
    public $queries = array();
    public function escape($text) { return addslashes((string)$text); }
    public function query($sql) {
        $this->queries[] = $sql;
        $rows = array();
        if (strpos($sql, 'FROM oc_language ') !== false) {
            $rows = array(array('language_id' => 1, 'name' => 'Українська'), array('language_id' => 2, 'name' => 'Русский'));
        } elseif (strpos($sql, 'FROM oc_product WHERE') !== false) {
            $rows = array(array('product_id' => 1, 'image' => 'no_image.webp', 'model' => 'A1', 'price' => 500));
        } elseif (strpos($sql, 'FROM oc_category WHERE') !== false) {
            $rows = array(array('category_id' => 1, 'image' => 'catalog/photo.webp'));
        } elseif (strpos($sql, 'FROM oc_manufacturer WHERE') !== false) {
            $rows = array(array('manufacturer_id' => 1, 'name' => 'Test', 'image' => 'catalog/logo.webp'));
        } elseif (strpos($sql, 'FROM oc_information WHERE') !== false) {
            $rows = array(array('information_id' => 1));
        } elseif (strpos($sql, 'FROM oc_article WHERE') !== false) {
            $rows = array(array('article_id' => 1, 'image' => 'catalog/photo.webp'));
        } elseif (strpos($sql, 'FROM oc_blog_category WHERE') !== false) {
            $rows = array(array('blog_category_id' => 1, 'image' => 'catalog/photo.webp'));
        } elseif (preg_match('/FROM oc_(product|category|manufacturer|information|article|blog_category)_description d /', $sql)) {
            $type = preg_replace('/.*FROM oc_(product|category|manufacturer|information|article|blog_category)_description d .*/s', '$1', $sql);
            $id = $type === 'blog_category' ? 'blog_category_id' : ($type === 'information' ? 'information_id' : ($type === 'manufacturer' ? 'manufacturer_id' : ($type === 'category' ? 'category_id' : ($type === 'article' ? 'article_id' : 'product_id'))));
            $rows = array(array($id => 1, 'language_id' => 1, 'name' => 1, 'title' => 1, 'description' => 1, 'meta_title' => 1, 'meta_description' => 0));
        } elseif (preg_match('/FROM oc_(product|category|manufacturer|information|article|blog_category)_to_store /', $sql)) {
            $type = preg_replace('/.*FROM oc_(product|category|manufacturer|information|article|blog_category)_to_store .*/s', '$1', $sql);
            $id = $type === 'blog_category' ? 'blog_category_id' : ($type === 'information' ? 'information_id' : ($type === 'manufacturer' ? 'manufacturer_id' : ($type === 'category' ? 'category_id' : ($type === 'article' ? 'article_id' : 'product_id'))));
            $rows = array(array($id => 1, 'store_id' => 0));
        } elseif (strpos($sql, 'FROM oc_seo_url ') !== false) {
            if (preg_match_all("/'((?:product|category|manufacturer|information|article|blog_category)_id=1)'/", $sql, $matches)) {
                foreach ($matches[1] as $query) {
                    $rows[] = array('query' => $query, 'store_id' => 0, 'language_id' => 1);
                }
            }
        } elseif (strpos($sql, 'COUNT(*) AS total') !== false) {
            if (strpos($sql, 'oc_product_to_category') !== false) {
                $rows = array(array('category_id' => 1, 'product_id' => 1, 'total' => 7));
            } elseif (strpos($sql, 'oc_article_to_blog_category') !== false) {
                $rows = array(array('blog_category_id' => 1, 'article_id' => 1, 'total' => 3));
            }
        }
        return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows));
    }
}

$errors = array();
$check = function ($condition, $name) use (&$errors) {
    if (!$condition) { $errors[] = $name; }
};

$check(CatalogQuality::normalizeIds('5,2,5,9') === array(5,2,9), 'valid unique ID list');
foreach (array('', '1,', '1 OR 1=1', '-2', '1,2,3;', implode(',', range(1, 41))) as $input) {
    $check(CatalogQuality::normalizeIds($input) === array(), 'reject unbounded/unsafe IDs');
}
$check(count(CatalogQuality::types()) === 6, 'six supported content kinds');
$totals = array();
foreach (CatalogQuality::types() as $type) {
    $db = new QualityMockDb();
    $rows = CatalogQuality::inspect($db, $type, array(1, 2));
    $check(isset($rows[1]) && !isset($rows[2]), $type . ' only existing IDs');
    $check($rows[1]['percent'] < 100 && $rows[1]['percent'] > 0, $type . ' missing translations are scored');
    $check(in_array('Русский: description', $rows[1]['missing'], true), $type . ' reports missing language');
    $check(in_array('Українська: meta_description', $rows[1]['missing'], true), $type . ' reports missing metadata');
    $check(in_array('Українська / #0: seo_url', $rows[1]['missing'], true) === false, $type . ' recognizes exact SEO URL');
    $check(in_array('Русский / #0: seo_url', $rows[1]['missing'], true), $type . ' detects missing localized URL');
    $sql = implode("\n", $db->queries);
    $check(strpos($sql, 'SELECT *') === false, $type . ' no full-row transfer');
    $check(count($db->queries) <= 7, $type . ' bounded number of SQL queries');
    if ($type === 'category') {
        $check($rows[1]['count'] === 7, 'direct category product count');
        $check(strpos($sql, 'WHERE category_id IN (1,2)') !== false, 'category count uses bounded IDs');
    }
    if ($type === 'blog_category') {
        $check($rows[1]['count'] === 3, 'direct blog category article count');
    }
    if ($type === 'product') {
        $check(in_array('image', $rows[1]['missing'], true), 'placeholder image is not complete');
        $check(!in_array('price', $rows[1]['missing'], true), 'positive price is valid');
    }
    $totals[$type] = $rows[1]['percent'];
}

// The original table columns and sort links must stay untouched: quality is inline only.
$routes = array(
    'catalog/product' => 'product.product_id',
    'catalog/category' => 'category.category_id',
    'catalog/manufacturer' => 'manufacturer.manufacturer_id',
    'catalog/information' => 'information.information_id',
    'blog/article' => 'article.article_id',
    'blog/category' => 'category.blog_category_id'
);
foreach ($routes as $route => $rowId) {
    $templatePath = dirname(__DIR__) . '/upload/admin/view/template/' . $route . '_list.twig';
    $controllerPath = dirname(__DIR__) . '/upload/admin/controller/' . $route . '.php';
    $template = file_get_contents($templatePath);
    $controller = file_get_contents($controllerPath);
    $check(strpos($template, 'cc-quality-table') !== false, $route . ' has quality table endpoint');
    $check(strpos($template, 'cc-quality-toggle') !== false, $route . ' has a compact preference control');
    $check(strpos($template, 'catalog-quality.js') !== false, $route . ' loads shared browser logic');
    $check(strpos($template, '{{ ' . $rowId . ' }}') !== false, $route . ' uses existing entity IDs');
    $check(strpos($controller, 'public function quality()') !== false, $route . ' shares permission-scoped route');
    $check(strpos($controller, 'quality_endpoint') !== false, $route . ' wires endpoint without an extra admin route');
    $check((bool)preg_match('/\\{\\{\\s*column_left\\s*\\}\\}.*?<div id="content"\\s+class=/s', $template), $route . ' keeps the content inside the same layout row after the sidebar and responsive class selection');
}

$base = dirname(__DIR__) . '/upload/';
$categoryTemplate = file_get_contents($base . 'admin/view/template/catalog/category_list.twig');
$categoryController = file_get_contents($base . 'admin/controller/catalog/category.php');
$productController = file_get_contents($base . 'admin/controller/catalog/product.php');
$productModel = file_get_contents($base . 'admin/model/catalog/product.php');
$qualityCss = file_get_contents($base . 'admin/view/stylesheet/codecart-catalog-quality.css');
$qualityJs = file_get_contents($base . 'admin/view/javascript/codecart/catalog-quality.js');

$check(strpos($categoryController, "'products_href'") !== false, 'category exposes an authorized product-filter link');
$check(strpos($categoryController, "'&filter_category='") !== false, 'category link uses the existing product category filter parameter');
$check(strpos($categoryTemplate, 'href="{{ category.products_href }}"') !== false, 'category count arrow opens its filtered product list');
$check(strpos($categoryTemplate, 'cc-quality-count-number') !== false, 'category count keeps its arrow icon when the asynchronous count loads');
$check(strpos($productController, "'filter_category'") !== false && strpos($productModel, "filter_category") !== false, 'product list recognizes category filters');
$check(strpos($qualityCss, 'conic-gradient(') !== false, 'quality percentage uses a circular gauge');
$check(strpos($qualityJs, "style.setProperty('--cc-quality-percent'") !== false, 'quality response drives circular percentage dynamically');
$check(strpos($qualityJs, "querySelector('.cc-quality-count-number')") !== false, 'count arrow is preserved after rendering');

if ($errors) {
    fwrite(STDERR, 'FAIL: ' . implode('; ', $errors) . "\n");
    exit(1);
}
echo 'PASS bounded catalog quality for ' . count($totals) . " entity types\n";
