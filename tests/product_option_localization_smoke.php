<?php
/** Text/textarea option defaults must honor storefront language with legacy fallback. */
define('DB_PREFIX', 'oc_');
require_once dirname(__DIR__) . '/upload/system/engine/registry.php';
require_once dirname(__DIR__) . '/upload/system/engine/model.php';
require_once dirname(__DIR__) . '/upload/catalog/model/catalog/product.php';

class OptionLocaleConfig {
    public $locale = 1;
    public $schema = 46;
    public function get($key) {
        if ($key === 'config_language_id') { return $this->locale; }
        if ($key === 'codecart_presentation_schema_version') { return $this->schema; }
        return null;
    }
}
class OptionLocaleDb {
    public $queries = array();
    public $config;
    public function __construct($config) { $this->config = $config; }
    public function query($sql) {
        $this->queries[] = $sql;
        if (strpos($sql, 'FROM oc_product_option po ') !== false) {
            $rows = array(
                array('product_option_id' => 11, 'product_id' => 100, 'option_id' => 2, 'name' => 'Message', 'type' => 'text', 'value' => 'Legacy text', 'required' => 0),
                array('product_option_id' => 12, 'product_id' => 100, 'option_id' => 3, 'name' => 'Notes', 'type' => 'textarea', 'value' => 'Legacy notes', 'required' => 0),
                array('product_option_id' => 13, 'product_id' => 100, 'option_id' => 4, 'name' => 'Date', 'type' => 'date', 'value' => '2026-11-18', 'required' => 0)
            );
        } elseif (strpos($sql, 'FROM oc_product_option_value pov ') !== false) {
            $rows = array();
        } elseif (strpos($sql, 'FROM `oc_codecart_product_option_translation`') !== false) {
            $rows = array();
            if ($this->config->locale === 1) {
                $rows = array(
                    array('product_option_id' => 11, 'value' => 'Текст українською'),
                    array('product_option_id' => 12, 'value' => 'Примітки українською'),
                    array('product_option_id' => 13, 'value' => 'not a date')
                );
            } elseif ($this->config->locale === 3) {
                $rows = array(array('product_option_id' => 11, 'value' => 'Текст по-русски'));
            }
        } else {
            throw new RuntimeException('Unexpected query: ' . $sql);
        }
        return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows));
    }
}
function optionCheck($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$registry = new Registry();
$config = new OptionLocaleConfig();
$db = new OptionLocaleDb($config);
$registry->set('config', $config);
$registry->set('db', $db);
$model = new ModelCatalogProduct($registry);

$en = array(1 => 'Текст українською', 2 => 'Legacy text', 3 => 'Текст по-русски');
foreach ($en as $languageId => $expectedText) {
    $config->locale = $languageId;
    $options = $model->getProductOptions(100);
    optionCheck(count($options) === 3, 'Not all options loaded for language ' . $languageId);
    optionCheck($options[0]['value'] === $expectedText, 'Localized text default mismatch: ' . $languageId);
    optionCheck($options[1]['value'] === ($languageId === 1 ? 'Примітки українською' : 'Legacy notes'), 'Textarea fallback mismatch: ' . $languageId);
    optionCheck($options[2]['value'] === '2026-11-18', 'Dates must remain language-neutral');
}
$config->schema = 45;
$db->queries = array();
optionCheck($model->getProductOptions(100)[0]['value'] === 'Legacy text', 'Pre-upgrade fallback failed');
foreach ($db->queries as $sql) {
    optionCheck(strpos($sql, 'codecart_product_option_translation') === false, 'An unupgraded store queried a nonexistent table');
}
$root = dirname(__DIR__) . '/upload/';
$admin = file_get_contents($root . 'admin/model/catalog/product.php');
$form = file_get_contents($root . 'admin/view/template/catalog/product_form.twig');
$controller = file_get_contents($root . 'admin/controller/catalog/product.php');
$migration = file_get_contents($root . 'system/library/codecart/migration.php');
$sql = file_get_contents($root . 'install/opencart.sql');
optionCheck(strpos($migration, 'ensureProductOptionTranslations()') !== false, 'Missing additive upgrade path');
optionCheck(strpos($sql, 'oc_codecart_product_option_translation') !== false, 'Missing fresh-install table');
optionCheck(strpos($admin, 'saveOptionTranslations') !== false, 'Admin model cannot persist translations');
optionCheck(strpos($form, '[localized_value][{{ language.language_id }}]') !== false, 'Existing option values lack per-language inputs');
optionCheck(strpos($form, '[localized_value][{{ language.language_id }}]') === strrpos($form, '[localized_value][{{ language.language_id }}]') ? false : true, 'New option values lack per-language inputs');
optionCheck(strpos($controller, "'localized_value'") !== false, 'Admin form loses translated defaults');
echo "PASS: existing and new product option text/textarea defaults, locale fallback, date stability, and pre-upgrade safety\n";
