<?php
/** Administrator change audit: php tests/admin_audit_smoke.php */
$root = dirname(__DIR__) . '/upload/';

class Controller {
    public $registry;
    public function __construct($registry) { $this->registry = $registry; }
}

class FakeUser {
    public function isLogged() { return true; }
    public function getId() { return 7; }
    public function getUserName() { return 'admin'; }
}

class FakeRegistry {
    public $entries = array();
    public function get($key) { return isset($this->entries[$key]) ? $this->entries[$key] : null; }
}

require_once $root . 'system/library/codecart/src/SecurityAudit.php';
require_once $root . 'admin/controller/event/codecart_audit.php';

$failures = array();
$sql = (string)file_get_contents($root . 'install/opencart.sql');
$migration = (string)file_get_contents($root . 'system/library/codecart/migration.php');

if (strpos($sql, "'codecart_admin_audit', 'admin/model/*/after', 'event/codecart_audit/record'") === false) {
    $failures[] = 'The audit event is not installed with a fresh installation.';
}
if (strpos($migration, "ensureCoreEvent('codecart_admin_audit'") === false) {
    $failures[] = 'The audit event is not registered for updated stores.';
}

// The handler must reach the logged-in user through the registry: isset() on a magic
// controller property is always false and silently disabled the audit.
$source = (string)file_get_contents($root . 'admin/controller/event/codecart_audit.php');
if (strpos($source, 'isset($this->user)') !== false || strpos($source, "registry->get('user')") === false) {
    $failures[] = 'The handler must read the user from the registry.';
}

$method = new ReflectionMethod('ControllerEventCodecartAudit', 'isAuditedMethod');
$handler = new ControllerEventCodecartAudit(new FakeRegistry());

foreach (array('addProduct' => true, 'editProductStatus' => true, 'deleteCategory' => true, 'copyProduct' => true, 'getProducts' => false, 'getTotalOrders' => false, 'saveQuickProductField' => false) as $name => $expected) {
    if ($method->invoke($handler, $name) !== $expected) {
        $failures[] = 'isAuditedMethod(' . $name . ') should be ' . ($expected ? 'true' : 'false');
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS administrator change audit event is installed and selects only changing model calls\n";
