<?php
/** Custom field language and default-value regression. No live DB needed. */
define('DB_PREFIX', 'oc_');
require dirname(__DIR__) . '/upload/system/engine/registry.php';
require dirname(__DIR__) . '/upload/system/engine/model.php';
require dirname(__DIR__) . '/upload/catalog/model/account/custom_field.php';

class CodeCartFieldTestConfig {
    public $languageId = 1;
    public function get($key) { return $key === 'config_language_id' ? $this->languageId : null; }
}
class CodeCartFieldTestDB {
    public $type = 'textarea';
    public $translations = array(1 => 'Ваші реквізити…', 2 => 'Your details…', 3 => 'Ваши реквизиты…');
    public function query($sql) {
        if (strpos($sql, 'FROM `oc_custom_field` cf') === false && strpos($sql, 'FROM `oc_custom_field_customer_group` cfcg') === false) {
            throw new RuntimeException('Unexpected query: ' . $sql);
        }
        if (!preg_match("/cfd.language_id = '([0-9]+)'/", $sql, $m)) {
            throw new RuntimeException('Missing language constraint');
        }
        $id = (int)$m[1];
        $row = array(
            'custom_field_id' => 1, 'name' => 'Field', 'type' => $this->type,
            'value' => 'Legacy shared value', 'default_value' => $this->translations[$id] ?? null,
            'location' => 'account', 'validation' => '', 'sort_order' => 0, 'required' => 1
        );
        return (object)array('num_rows' => 1, 'row' => $row, 'rows' => array($row));
    }
}
function fieldCheck($ok, $message) {
    if (!$ok) { throw new RuntimeException($message); }
}
$registry = new Registry();
$config = new CodeCartFieldTestConfig();
$db = new CodeCartFieldTestDB();
$registry->set('config', $config);
$registry->set('db', $db);
$model = new ModelAccountCustomField($registry);
foreach ($db->translations as $id => $text) {
    $config->languageId = $id;
    $fields = $model->getCustomFields(2);
    fieldCheck(count($fields) === 1 && $fields[0]['value'] === $text, 'Wrong storefront language: ' . $id);
    fieldCheck($fields[0]['required'] === true, 'Customer group requirement lost: ' . $id);
    fieldCheck($model->getCustomField(1)['value'] === $text, 'Single field translation: ' . $id);
}
$db->translations[3] = null;
$config->languageId = 3;
fieldCheck($model->getCustomFields(2)[0]['value'] === 'Legacy shared value', 'Null translation must use legacy value');
$db->translations[3] = '';
fieldCheck($model->getCustomFields(2)[0]['value'] === '', 'Explicit empty translated default must stay empty');
$db->type = 'date';
$db->translations[3] = 'Translated but unused';
fieldCheck($model->getCustomFields(2)[0]['value'] === 'Legacy shared value', 'Dates are not translatable text');
$base = dirname(__DIR__) . '/upload/';
$form = file_get_contents($base . 'admin/view/template/customer/custom_field_form.twig');
$admin = file_get_contents($base . 'admin/model/customer/custom_field.php');
$migration = file_get_contents($base . 'system/library/codecart/migration.php');
$sql = file_get_contents($base . 'install/opencart.sql');
fieldCheck(strpos($form, 'custom_field_description[{{ language.language_id }}][default_value]') !== false, 'Admin multilingual input missing');
fieldCheck(strpos($admin, 'default_value = ') !== false, 'Admin model does not persist default language text');
fieldCheck(strpos($admin, 'COALESCE(VALUES(default_value), default_value)') !== false, 'Unsubmitted language defaults must survive updates');
fieldCheck(strpos($migration, "const PRESENTATION_SCHEMA_VERSION = '45'") !== false, 'Migration revision missing');
fieldCheck(strpos($migration, "ensureCustomFieldDefaultTranslations();") !== false, 'Migration not called');
fieldCheck(strpos($sql, '`default_value` text NULL DEFAULT NULL') !== false, 'Fresh install schema missing');
foreach (array('uk-ua','ru-ru','en-gb') as $code) {
    $lang = file_get_contents($base . 'admin/language/' . $code . '/customer/custom_field.php');
    fieldCheck(strpos($lang, "['entry_value']") !== false && strpos($lang, "['help_localized_default_value']") !== false, 'Missing translation: ' . $code);
}
echo "PASS: three locales, fallback, empty text, non-text types, admin inputs and migration.\n";
