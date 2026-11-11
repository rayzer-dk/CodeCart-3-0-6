<?php
/** Product and category saves run as one transaction: php tests/transaction_smoke.php */
$root = dirname(__DIR__) . '/upload/';
require_once $root . 'system/library/codecart/src/Transaction.php';

class FakeDb {
    public $log = array();
    public function beginTransaction() { $this->log[] = 'begin'; }
    public function commit() { $this->log[] = 'commit'; }
    public function rollback() { $this->log[] = 'rollback'; }
}

$failures = array();

$db = new FakeDb();
$result = \CodeCart\Core\Transaction::run($db, function () use ($db) { $db->log[] = 'write'; return 42; });
if ($result !== 42 || $db->log !== array('begin', 'write', 'commit')) {
    $failures[] = 'A successful run must commit once and return the result: ' . implode(',', $db->log);
}

$db = new FakeDb();
$caught = null;
try {
    \CodeCart\Core\Transaction::run($db, function () use ($db) { $db->log[] = 'write'; throw new RuntimeException('sql failed'); });
} catch (RuntimeException $e) {
    $caught = $e;
}
if (!$caught || $caught->getMessage() !== 'sql failed') {
    $failures[] = 'The original exception must be re-thrown.';
}
if ($db->log !== array('begin', 'write', 'rollback')) {
    $failures[] = 'A failed run must roll back and never commit: ' . implode(',', $db->log);
}

// The four saves must go through it, keeping their public names for third-party modifications.
$product = (string)file_get_contents($root . 'admin/model/catalog/product.php');
$category = (string)file_get_contents($root . 'admin/model/catalog/category.php');
foreach (array('public function addProduct($data)', 'public function editProduct($product_id, $data)', 'Transaction::run($this->db, function () use ($data)', 'Transaction::run($this->db, function () use ($product_id, $data)') as $needle) {
    if (strpos($product, $needle) === false) { $failures[] = 'product model is missing: ' . $needle; }
}
foreach (array('public function addCategory($data)', 'public function editCategory($category_id, $data)', 'Transaction::run($this->db, function () use ($data)', 'Transaction::run($this->db, function () use ($category_id, $data)') as $needle) {
    if (strpos($category, $needle) === false) { $failures[] = 'category model is missing: ' . $needle; }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS product and category saves are transactional\n";
