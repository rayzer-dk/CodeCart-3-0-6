<?php
/** PHP 7.4-8.5 regression: product condition stays consistent across integrations. */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/ProductCondition.php';
use CodeCart\Core\ProductCondition;
$root = dirname(__DIR__) . '/upload/';
$issues = array();
$check = function ($ok, $message) use (&$issues) {
    if (!$ok) { $issues[] = $message; }
};
$types = array('new' => 'NewCondition', 'refurbished' => 'RefurbishedCondition', 'used' => 'UsedCondition');
$check(ProductCondition::options() === array_keys($types), 'allowed dropdown values');
foreach ($types as $key => $schema) {
    $check(ProductCondition::normalize($key) === $key, 'normalize ' . $key);
    $check(ProductCondition::merchantValue($key) === $key, 'merchant ' . $key);
    $check(ProductCondition::schemaUrl($key) === 'https://schema.org/' . $schema, 'schema ' . $key);
}
foreach (array('', 'OTHER', 'bad', null, 1, array('used')) as $invalid) {
    $check(ProductCondition::normalize($invalid) === 'new', 'invalid condition defaults to new');
}
$sql = file_get_contents($root . 'install/opencart.sql');
$start = strpos($sql, 'CREATE TABLE ' . chr(96) . 'oc_product' . chr(96));
$end = strpos($sql, ') ENGINE=InnoDB', $start);
$productSql = substr($sql, $start, $end - $start);
$check(strpos($productSql, 'condition' . chr(96) . " varchar(12) NOT NULL DEFAULT 'new'") !== false, 'fresh install column');
$migration = file_get_contents($root . 'system/library/codecart/migration.php');
$hasSchemaVersion = preg_match("/const PRESENTATION_SCHEMA_VERSION = '([0-9]+)'/", $migration, $revision);
$check($hasSchemaVersion === 1 && (int)$revision[1] >= 41, 'migration revision (product condition introduced in 41)');
$check(strpos($migration, '$this->ensureProductCondition();') !== false, 'migration invoked');
$check(strpos($migration, "VARCHAR(12) NOT NULL DEFAULT 'new'") !== false, 'new condition default on upgrade');
$check(strpos($migration, "if (!isset(\$columns['condition']))") !== false, 'conditional migration');
$form = file_get_contents($root . 'admin/view/template/catalog/product_form.twig');
foreach (array_keys($types) as $value) {
    $check(strpos($form, '<option value="' . $value . '"') !== false, 'admin option ' . $value);
}
$check(strpos($form, 'name="condition"') !== false, 'condition field submitted');
$check(strpos($form, 'id="input-condition"') < strpos($form, 'id="input-price"'), 'field before price');
$adminController = file_get_contents($root . 'admin/controller/catalog/product.php');
$check(strpos($adminController, "ProductCondition::normalize(\$condition)") !== false, 'admin controller validates');
$adminModel = file_get_contents($root . 'admin/model/catalog/product.php');
$check(strpos($adminModel, 'ProductCondition::normalize') !== false, 'admin model normalizes');
$check(strpos($adminModel, '$condition_sql = isset($data[') !== false, 'edit preserves condition on omitted input');
$check(strpos($adminModel, chr(96) . "condition" . chr(96) . " = ") !== false, 'save condition SQL');
$catalog = file_get_contents($root . 'catalog/model/catalog/product.php');
$check(strpos($catalog, "'condition'        => ") !== false, 'storefront fetches condition');
$controller = file_get_contents($root . 'catalog/controller/product/product.php');
$check(strpos($controller, "ProductCondition::schemaUrl(\$data['condition'])") !== false, 'schema itemCondition');
$check(strpos($controller, 'https://schema.org/NewCondition') === false, 'no hard-coded new itemCondition');
$merchantModel = file_get_contents($root . 'catalog/model/extension/feed/google_base.php');
$check(strpos($merchantModel, 'AS ' . chr(96) . 'condition' . chr(96)) !== false, 'merchant model retrieves condition');
$merchant = file_get_contents($root . 'catalog/controller/extension/feed/google_base.php');
$check(strpos($merchant, 'ProductCondition::merchantValue') !== false, 'merchant outputs actual condition');
$check(strpos($merchant, '<g:condition>new</g:condition>') === false, 'hard-coded merchant new removed');
foreach (array('codecart', 'default') as $theme) {
    $twig = file_get_contents($root . 'catalog/view/theme/' . $theme . '/template/product/product.twig');
    $check(strpos($twig, '{{ condition_label }}') !== false, $theme . ' shows condition');
}
$translations = array(
    'uk-ua' => array('Новий', 'Відновлений', 'Б/в'),
    'ru-ru' => array('Новый', 'Восстановленный', 'Б/у'),
    'en-gb' => array('New', 'Refurbished', 'Used')
);
foreach ($translations as $lang => $values) {
    foreach (array('admin/language/' . $lang . '/catalog/product.php', 'catalog/language/' . $lang . '/product/product.php') as $path) {
        $body = file_get_contents($root . $path);
        foreach (array('entry_condition','text_condition_new','text_condition_refurbished','text_condition_used') as $key) {
            $check(strpos($body, "\$_['" . $key . "']") !== false, $path . ' missing ' . $key);
        }
        foreach ($values as $label) {
            $check(strpos($body, $label) !== false, $path . ' missing translation: ' . $label);
        }
    }
}
if ($issues) {
    fwrite(STDERR, "FAIL product condition:\n" . implode("\n", $issues) . "\n");
    exit(1);
}
echo "PASS product conditions: admin, storefront, feed, schema, migration, three languages\n";
