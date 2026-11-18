<?php
error_reporting(E_ALL);
class Model { public $db; }
define('DB_PREFIX', 'oc_');
$root = sys_get_temp_dir() . '/sc-payments-' . getmypid();
define('DIR_APPLICATION', $root . '/admin/');
define('DIR_CATALOG', $root . '/catalog/');
mkdir(DIR_APPLICATION . 'controller/extension/payment', 0770, true);
mkdir(DIR_CATALOG . 'controller/extension/payment', 0770, true);
require dirname(__DIR__) . '/upload/admin/model/extension/module/settings_cleaner_files.php';
class FakeDb {
    public $installed = array();
    public $settings = array();
    public $orders = array();
    public $events = array();
    public $mods = array();
    public $packages = array();
    public $fileBackups = array();
    public $fileRows = array();
    private $last = 0;
    public function escape($s) { return addslashes($s); }
    public function getLastId() { return $this->last; }
    public function query($sql) {
        $rows = array();
        if (strpos($sql, 'SELECT `type`, `code` FROM') === 0) $rows = $this->installed;
        elseif (strpos($sql, 'SELECT `code`, `key`, `value` FROM') === 0) $rows = $this->settings;
        elseif (strpos($sql, 'SELECT `payment_code`, COUNT(*)') === 0) $rows = $this->orders;
        elseif (strpos($sql, 'SELECT `trigger`, `action` FROM') === 0) $rows = $this->events;
        elseif (strpos($sql, 'SELECT COALESCE(SUM(OCTET_LENGTH') === 0) $rows = array(array('total' => 0));
        elseif (strpos($sql, 'SELECT `xml` FROM') === 0) $rows = $this->mods;
        elseif (strpos($sql, 'SELECT `path` FROM') === 0) $rows = $this->packages;
        elseif (strpos($sql, 'INSERT INTO `oc_settings_cleaner_file_backup`') === 0) {
            $this->last++; preg_match("/`code` = '([^']+)'/", $sql, $m);
            $this->fileBackups[$this->last] = array('file_backup_id' => $this->last, 'code' => $m[1]);
        } elseif (strpos($sql, 'INSERT INTO `oc_settings_cleaner_file_backup_row`') === 0) {
            $this->last++; $fields = array();
            foreach (array('file_backup_id', 'relative_path', 'sha256', 'content_base64') as $field) {
                preg_match('/`' . $field . '` = (?:\'([^\']*)\'|(\d+))/', $sql, $m);
                $fields[$field] = isset($m[1]) && $m[1] !== '' ? $m[1] : (isset($m[2]) ? $m[2] : '');
            }
            $fields['file_backup_row_id'] = $this->last; $fields['removed'] = 0; $fields['restored'] = 0;
            $this->fileRows[$this->last] = $fields;
        } elseif (strpos($sql, 'UPDATE `oc_settings_cleaner_file_backup_row`') === 0) {
            if (preg_match('/`file_backup_row_id` = (\d+)/', $sql, $m)) {
                $this->fileRows[(int)$m[1]]['restored'] = 1;
            } elseif (preg_match('/`file_backup_id` = (\d+) AND `relative_path` = \'([^\']+)\'/D', $sql, $m)) {
                foreach ($this->fileRows as &$row) if ((int)$row['file_backup_id'] === (int)$m[1] && $row['relative_path'] === $m[2]) $row['removed'] = 1;
                unset($row);
            }
        } elseif (strpos($sql, 'SELECT `code` FROM `oc_settings_cleaner_file_backup`') === 0) {
            preg_match('/`file_backup_id` = (\d+)/', $sql, $m); if (isset($this->fileBackups[(int)$m[1]])) $rows[] = $this->fileBackups[(int)$m[1]];
        } elseif (strpos($sql, 'SELECT * FROM `oc_settings_cleaner_file_backup_row`') === 0) {
            preg_match('/`file_backup_id` = (\d+)/', $sql, $m); foreach ($this->fileRows as $row) if ((int)$row['file_backup_id'] === (int)$m[1]) $rows[] = $row;
        }
        return (object)array('rows' => $rows, 'row' => isset($rows[0]) ? $rows[0] : array(), 'num_rows' => count($rows));
    }
}
function ok($bool, $message) { if (!$bool) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
$db = new FakeDb();
$model = new ModelExtensionModuleSettingsCleanerFiles(); $model->db = $db;
$admin = DIR_APPLICATION . 'controller/extension/payment/';
$catalog = DIR_CATALOG . 'controller/extension/payment/';
file_put_contents($admin . 'legacy_gateway.php', '<?php class A {}');
file_put_contents($catalog . 'legacy_gateway.php', '<?php class B {}');
$list = $model->scanPayments();
ok(count($list) === 1 && $list[0]['status'] === 'candidate' && count($list[0]['files']) === 2, 'orphan payment is candidate');
$db->installed = array(array('type'=>'payment','code'=>'legacy_gateway'));
ok($model->scanPayments()[0]['status'] === 'review', 'installed payment protected');
$db->installed = array(); $db->settings = array(array('code'=>'payment_legacy_gateway','key'=>'payment_legacy_gateway_status','value'=>'0'));
ok($model->scanPayments()[0]['status'] === 'review', 'configured payment protected even if disabled');
$db->settings = array(); $db->orders = array(array('payment_code'=>'legacy_gateway', 'total'=>2));
ok($model->scanPayments()[0]['status'] === 'review', 'historical order protected');
$db->orders = array(); $db->events = array(array('trigger'=>'catalog/controller/extension/payment/legacy_gateway/after','action'=>'x'));
ok($model->scanPayments()[0]['status'] === 'review', 'event protected');
$db->events = array(); $db->mods = array(array('xml'=>'<file path="catalog/controller/extension/payment/legacy_gateway.php"/>'));
ok($model->scanPayments()[0]['status'] === 'review', 'modification protected');
$db->mods = array(); $db->packages = array(array('path'=>'admin/controller/extension/payment/legacy_gateway.php'));
ok($model->scanPayments()[0]['status'] === 'review', 'installer file protected');
$db->packages = array();
$thrown = false; try { $model->quarantinePayment('../legacy_gateway', 1); } catch (RuntimeException $e) { $thrown = true; }
ok($thrown, 'traversal prohibited');
$result = $model->quarantinePayment('legacy_gateway', 1);
ok($result['removed'] === 2 && !file_exists($admin . 'legacy_gateway.php') && !file_exists($catalog . 'legacy_gateway.php'), 'files archived');
ok(count($db->fileRows) === 2 && array_sum(array_column($db->fileRows,'removed')) === 2, 'database backups recorded');
$restored = $model->restoreFileBackup($result['backup_id']);
ok($restored === 2 && file_get_contents($catalog . 'legacy_gateway.php') === '<?php class B {}', 'files restored');
file_put_contents($admin . 'legacy_gateway.php', '<?php changed');
$thrown = false; try { $model->restoreFileBackup($result['backup_id']); } catch (RuntimeException $e) { $thrown = true; }
ok($thrown, 'no overwrite when backup already restored');
unlink($admin . 'legacy_gateway.php');unlink($catalog . 'legacy_gateway.php');
foreach (array($admin,$catalog) as $path) {
    rmdir($path); rmdir(dirname($path)); rmdir(dirname(dirname($path)));
}
rmdir(DIR_APPLICATION);rmdir(DIR_CATALOG);rmdir($root);
echo "PASS: 12 payment scan / quarantine / restore cases\n";
