<?php
// Independent contract smoke test for the native per-admin language preference.
define('DB_PREFIX', 'oc_');
define('DIR_LANGUAGE', dirname(__DIR__) . '/upload/admin/language/');
require dirname(__DIR__) . '/upload/system/library/codecart/src/AdminLanguagePreference.php';

class AdminLanguageFakeDB {
    public $sql = array();
    public $rows = array();
    public $saved = false;
    public function __construct() {
        $this->rows = array(
            array('language_id' => 1, 'name' => 'Українська', 'code' => 'uk-ua', 'status' => 1),
            array('language_id' => 2, 'name' => 'English', 'code' => 'en-gb', 'status' => 1),
            array('language_id' => 3, 'name' => 'Русский', 'code' => 'ru-ru', 'status' => 1),
            array('language_id' => 4, 'name' => 'Disabled', 'code' => 'de-de', 'status' => 0),
            array('language_id' => 5, 'name' => 'Unsafe', 'code' => '../../x', 'status' => 1)
        );
    }
    public function escape($s) { return addslashes((string)$s); }
    public function query($sql) {
        $this->sql[] = $sql;
        if (strpos($sql, 'SELECT language_id, name, code') === 0) {
            return (object)array('rows' => array_values(array_filter($this->rows, function ($row) { return (int)$row['status'] === 1; })));
        }
        if (strpos($sql, 'SELECT language_code') === 0) {
            return (object)array('row' => $this->saved ? array('language_code' => 'ru-ru') : array());
        }
        if (strpos($sql, 'INSERT INTO') === 0) {
            $this->saved = true;
            return (object)array();
        }
        throw new RuntimeException('Unexpected query');
    }
}
function verify($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$db = new AdminLanguageFakeDB();
$available = \CodeCart\Core\AdminLanguagePreference::available($db);
verify(count($available) === 3, 'Bundled languages are available independently of the storefront');
verify(isset($available['uk-ua'], $available['ru-ru'], $available['en-gb']), 'Missing bundled language');
verify(!isset($available['../../x']) && !isset($available['de-de']), 'Invalid or disabled language was available');
verify(\CodeCart\Core\AdminLanguagePreference::read($db, 7) === '', 'Missing preference must be empty');
\CodeCart\Core\AdminLanguagePreference::save($db, 7, 'ru-ru');
verify(\CodeCart\Core\AdminLanguagePreference::read($db, 7) === 'ru-ru', 'Preference did not persist');
try { \CodeCart\Core\AdminLanguagePreference::save($db, 7, '../en-gb'); throw new RuntimeException('Path traversal accepted'); }
catch (InvalidArgumentException $e) { /* expected */ }
$permission = file_get_contents(dirname(__DIR__) . '/upload/admin/controller/startup/permission.php');
verify(strpos($permission, "'common/language'") !== false, 'Authenticated permission whitelist missing');
$handler = file_get_contents(dirname(__DIR__) . '/upload/admin/controller/common/language.php');
verify(strpos($handler, "hash_equals(") !== false && strpos($handler, "codecart_is_safe_redirect(") !== false, 'POST guard or redirect validation missing');
echo "PASS: available languages, user preference, validation and route guards.\n";
