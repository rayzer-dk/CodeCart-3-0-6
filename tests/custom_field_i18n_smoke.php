<?php
/** Localized custom-field default text and choice translation regression. */
$root = dirname(__DIR__) . '/upload/';
function assertCustomField($ok, $message) {
    if (!$ok) { throw new RuntimeException($message); }
}
$form = file_get_contents($root . 'admin/view/template/customer/custom_field_form.twig');
$adminModel = file_get_contents($root . 'admin/model/customer/custom_field.php');
$catalogModel = file_get_contents($root . 'catalog/model/account/custom_field.php');
$migration = file_get_contents($root . 'system/library/codecart/migration.php');
foreach (array('uk-ua','ru-ru','en-gb') as $locale) {
    $localeFile = $root . 'admin/language/' . $locale . '/customer/custom_field.php';
    assertCustomField(is_file($localeFile), 'Missing custom-field admin locale ' . $locale);
    $language = file_get_contents($localeFile);
    foreach (array('entry_localized_value','help_localized_default_value','text_legacy_value_fallback') as $key) {
        assertCustomField(strpos($language, chr(36) . "_['" . $key . "']") !== false, 'Missing localized key: ' . $locale . '/' . $key);
    }
}
assertCustomField(strpos($form, 'name="custom_field_description[{{ language.language_id }}][default_value]"') !== false, 'Per-language field missing');
assertCustomField(strpos($form, "default_value is not null ? custom_field_description[language.language_id].default_value : ''") !== false, 'Untranslated field must not silently copy old shared text into every language');
assertCustomField(strpos($form, 'name="custom_field_value[{{ custom_field_value_row }}][custom_field_value_id]"') !== false, 'Localized select/radio/checkbox choices missing');
assertCustomField(strpos($adminModel, "trim(\$value['default_value']) !== ''") !== false, 'Empty translations must fall back to legacy default');
assertCustomField(strpos($adminModel, 'ON DUPLICATE KEY UPDATE') !== false, 'Editing must preserve unsubmitted locale descriptions');
assertCustomField(strpos($catalogModel, "cfd.language_id = '") !== false, 'Storefront must select current locale');
assertCustomField(strpos($catalogModel, "'default_value'] !== null") !== false, 'Storefront must preserve legacy fallback');
assertCustomField(strpos($migration, 'ensureCustomFieldDefaultTranslations()') !== false, 'Missing idempotent default_value migration');
$header = file_get_contents($root . 'admin/view/template/common/header.twig');
$overflow = file_get_contents($root . 'admin/view/javascript/codecart-header-overflow.js');
assertCustomField(strpos($header, 'ccp-header-overflow-panel') !== false, 'Missing mobile overflow panel');
assertCustomField(strpos($overflow, 'MutationObserver') !== false && strpos($overflow, 'box.appendChild(node)') !== false, 'Third-party header icons must be moved intact');
echo "PASS: multilingual custom fields, legacy fallback, selective options, mobile header overflow.\n";
