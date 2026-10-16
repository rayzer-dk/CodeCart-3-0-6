<?php
/**
 * Regression checks for the CodeCart extensions sortable table.
 * Run from project root: php tools/extension-sort-check.php
 */
$root = dirname(__DIR__);
$types = array('advertise', 'analytics', 'captcha', 'currency', 'dashboard', 'feed', 'fraud', 'menu', 'module', 'payment', 'report', 'shipping', 'theme', 'total');
$errors = array();
require_once $root . '/upload/system/storage/vendor/autoload.php';
require_once __DIR__ . '/lib/extension-layout.php';
require_once __DIR__ . '/lib/php-source.php';
$renderer = new \Twig\Environment(new \Twig\Loader\ArrayLoader(), array('autoescape'=>false, 'cache'=>false));

foreach ($types as $type) {
    $controller = $root . '/upload/admin/controller/extension/extension/' . $type . '.php';
    $view = $root . '/upload/admin/view/template/extension/extension/' . $type . '.twig';
    $php = file_get_contents($controller);
    $twig = file_get_contents($view);

    if (!ccpExtensionControllerHasMetadata($php, $type)) {
        $errors[] = $type . ': no installation date metadata in controller';
    }
    try {
        foreach (ccpCheckExtensionTable($renderer, $twig) as $error) { $errors[] = $type . ': ' . $error; }
    } catch (\Throwable $e) {
        $errors[] = $type . ': template check failed: ' . $e->getMessage();
    }
}

foreach (array('uk-ua', 'ru-ru', 'en-gb') as $locale) {
    $path = $root . '/upload/admin/language/' . $locale . '/marketplace/extension.php';
    $content = file_get_contents($path);
    $keys = ccpLanguageAssignmentKeys($content);
    foreach (array('column_installed','column_install_date','column_upload_date','text_installed','text_not_installed','text_date_unknown','text_upload_date_unknown') as $key) {
        if (!in_array($key, $keys, true)) {
            $errors[] = $locale . ': missing ' . $key;
        }
    }
}

$sortScript = file_get_contents($root . '/upload/admin/view/javascript/codecart-extension-sort.js');
if (strpos($sortScript, "$(document).on('click.ccpExtensionSort'") === false || strpos($sortScript, 'appendChild(fragment)') === false) {
    $errors[] = 'sorting JavaScript source markers changed; run the browser sorting regression';
}
$parentView = file_get_contents($root . '/upload/admin/view/template/marketplace/extension.twig');
try {
    foreach (ccpCheckExtensionParent($renderer, $parentView) as $error) { $errors[] = $error; }
} catch (\Throwable $e) {
    $errors[] = 'parent layout check failed: ' . $e->getMessage();
}

$model = file_get_contents($root . '/upload/admin/model/setting/extension.php');
foreach (array('getInstallDates', 'getUploadDates', 'extension_path', 'extension_install', 'codecart_extension_install', 'installed_at_') as $needle) {
    if (strpos($model, $needle) === false) {
        $errors[] = 'installation tracking: missing ' . $needle;
    }
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

// Run date-mapping regression tests without requiring a live database.
if (!defined('DB_PREFIX')) { define('DB_PREFIX', 'oc_'); }
if (!class_exists('Model')) { class Model { public $db; } }
require_once $root . '/upload/admin/model/setting/extension.php';
class DateSortMockDatabase {
    public $rows;
    public $sql = '';
    public function __construct($rows) { $this->rows = $rows; }
    public function escape($value) { return addslashes($value); }
    public function query($sql) {
        $this->sql = $sql;
        return (object) array('rows' => $this->rows);
    }
}
$dates_db = new DateSortMockDatabase(array(
    array('path' => 'admin/controller/extension/module/sample.php', 'date_added' => '2026-10-12 12:00:00'),
    array('path' => 'admin/controller/extension/module/sample.php', 'date_added' => '2026-09-12 12:00:00'),
    array('path' => 'admin/controller/module/legacy.php', 'date_added' => '2025-02-03 01:02:03'),
    array('path' => 'admin/controller/extension/module/helpers/invalid.php', 'date_added' => '2026-10-12 11:00:00'),
    array('path' => 'admin/controller/extension/module/invalid.twig', 'date_added' => '2026-10-12 11:00:00'),
));
$dates_model = new ModelSettingExtension();
$dates_model->db = $dates_db;
$dates = $dates_model->getUploadDates('module');
if ($dates !== array('sample' => '2026-10-12 12:00:00', 'legacy' => '2025-02-03 01:02:03')) {
    fwrite(STDERR, 'Upload date mapping failed.\n');
    exit(1);
}
if (strpos($dates_db->sql, 'extension_path') === false || strpos($dates_db->sql, 'extension_install') === false) {
    fwrite(STDERR, 'Upload date query does not use installer records.\n');
    exit(1);
}
if ($dates_model->getUploadDates('unexpected_type') !== array()) {
    fwrite(STDERR, 'Unrecognized extension type was accepted.\n');
    exit(1);
}

print 'Extension sorting checks passed: ' . count($types) . " types, three languages, rendered DOM/CSS contracts and date mapping.\n";
print "Browser layout and JavaScript sorting behavior are not exercised by this offline check.\n";
