<?php
error_reporting(E_ALL);
class Model {}
define('DB_PREFIX', 'oc_');
$root = sys_get_temp_dir() . '/sc-test-' . getmypid();
define('DIR_APPLICATION', $root . '/admin/');
define('DIR_CATALOG', $root . '/catalog/');
require dirname(__DIR__) . '/upload/admin/model/extension/module/settings_cleaner.php';
$model = new ModelExtensionModuleSettingsCleaner();
$method = new ReflectionMethod($model, 'classify');
$method->setAccessible(true);
$base = array('installed' => array(), 'instances' => array(), 'layouts' => array(), 'references' => array(), 'incomplete' => false);
$checks = array(
    array('config', 'config_name', $base, 'protected'),
    array('codecart_extension_install', 'installed_at_module_dummy', $base, 'unknown'),
    array('module_dead_name', 'module_dead_name_status', $base, 'candidate'),
    array('payment_good', 'payment_good_status', array_merge($base, array('installed' => array('payment/good' => true))), 'protected'),
    array('module_checkout', 'module_checkout_status', array_merge($base, array('instances' => array('checkout' => true))), 'review'),
    array('module_checkout', 'module_checkout_status', array_merge($base, array('layouts' => array('checkout' => true))), 'review'),
    array('shipping_unused', 'shipping_unused_status', array_merge($base, array('references' => array('extension/shipping/unused'))), 'review'),
    array('module_old', 'module_old_status', array_merge($base, array('references' => array('module_old_status'))), 'review'),
    array('module_old', 'module_old_status', array_merge($base, array('incomplete' => true)), 'review'),
    array('module_bad-name', 'module_bad-name_status', $base, 'unknown'),
    array('module_active_addon', 'module_active_addon_status', array_merge($base, array('installed' => array('module/active' => true))), 'review')
);
mkdir($root . '/admin/controller/extension/module', 0770, true);
file_put_contents($root . '/admin/controller/extension/module/fileonly.php', '<?php');
$checks[] = array('module_fileonly', 'module_fileonly_status', $base, 'protected');
$passed = 0;
foreach ($checks as $test) {
    $cache = array();
    $row = array('code' => $test[0], 'key' => $test[1]);
    $args = array($row, $test[2], &$cache);
    $actual = $method->invokeArgs($model, $args)['status'];
    if ($actual !== $test[3]) {
        fwrite(STDERR, 'FAIL ' . $test[0] . ': expected ' . $test[3] . ' got ' . $actual . "\n");
        exit(1);
    }
    $passed++;
}
unlink($root . '/admin/controller/extension/module/fileonly.php');
rmdir($root . '/admin/controller/extension/module');
rmdir($root . '/admin/controller/extension');
rmdir($root . '/admin/controller');
rmdir($root . '/admin');
if (is_dir($root . '/catalog')) { rmdir($root . '/catalog'); }
rmdir($root);
echo 'PASS: ' . $passed . " classifier cases\n";
