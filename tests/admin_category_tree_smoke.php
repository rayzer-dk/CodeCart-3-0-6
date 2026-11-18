<?php
/** Admin category-tree validation and optimistic inline sorting. */
define('DB_PREFIX', 'oc_');
define('DB_DATABASE', 'test_db');
require_once dirname(__DIR__) . '/upload/system/engine/registry.php';
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/Transaction.php';
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/AdminCategoryTreeEditor.php';
use CodeCart\Core\AdminCategoryTreeEditor;

class TreeFakeDb {
    public $value = 3;
    public $queries = array();
    public function query($sql) {
        $this->queries[] = $sql;
        if (preg_match("/UPDATE `oc_category` SET sort_order = '(-?\\d+)'[^;]*WHERE `category_id` = '7' AND sort_order = '(-?\\d+)'/", $sql, $match)) {
            if ($this->value === (int)$match[2]) { $this->value = (int)$match[1]; }
            return (object)array('num_rows' => 0, 'row' => array(), 'rows' => array());
        }
        if (strpos($sql, 'SELECT sort_order FROM `oc_category` WHERE `category_id` = \'7\'') !== false) {
            return (object)array('num_rows' => 1, 'row' => array('sort_order' => $this->value), 'rows' => array());
        }
        throw new RuntimeException('Unexpected query: ' . $sql);
    }
}
class TreeFakeCache {
    public $purged = array();
    public function delete($key) { $this->purged[] = $key; }
    public function deleteTag($a, $b) { $this->purged[] = $a . ':' . $b; }
}
function treeCheck($ok, $message) {
    if (!$ok) { throw new RuntimeException($message); }
}
$registry = new Registry();
$db = new TreeFakeDb();
$cache = new TreeFakeCache();
$registry->set('db', $db);
$registry->set('cache', $cache);
treeCheck(AdminCategoryTreeEditor::quickSort($registry, 'catalog', 7, 9, 3) === 9, 'Sort update failed');
treeCheck($db->value === 9, 'Sort did not change');
try {
    AdminCategoryTreeEditor::quickSort($registry, 'catalog', 7, 15, 3);
    throw new RuntimeException('Stale write incorrectly accepted');
} catch (RuntimeException $expected) {
    treeCheck(strpos($expected->getMessage(), 'changed in another session') !== false, 'Expected concurrent-write rejection');
}
treeCheck($db->value === 9, 'Conflict changed an existing value');
treeCheck(count($cache->purged) > 0, 'Sort did not invalidate caches');
foreach (array(array('catalog', 0, 4), array('catalog', 4, 4), array('blog', 0, 0), array('article', 4, 1)) as $args) {
    try {
        AdminCategoryTreeEditor::move($registry, $args[0], $args[1], $args[2]);
        throw new RuntimeException('Invalid move was accepted');
    } catch (InvalidArgumentException $expected) { }
}

$root = dirname(__DIR__) . '/upload/';
$service = file_get_contents($root . 'system/library/codecart/src/AdminCategoryTreeEditor.php');
$script = file_get_contents($root . 'admin/view/javascript/codecart/category-tree.js');
treeCheck(strpos($service, 'Transaction::run') !== false, 'Moving branches is not atomic');
treeCheck(strpos($service, 'GET_LOCK') !== false && strpos($service, 'RELEASE_LOCK') !== false, 'Concurrent moves are not serialized');
treeCheck(strpos($service, 'array_search($id, $old, true)') !== false, 'Subtree descendant paths were not preserved');
treeCheck(strpos($script, 'data-cc-tree-target') !== false, 'Drop-to-root zone missing');
treeCheck(strpos($script, 'cc-tree-move') !== false, 'Touch fallback is missing');
foreach (array('catalog','blog') as $type) {
    $controller = file_get_contents($root . 'admin/controller/' . $type . '/category.php');
    $template = file_get_contents($root . 'admin/view/template/' . $type . '/category_list.twig');
    treeCheck(strpos($controller, 'function treeEdit()') !== false, $type . ' lacks secure mutation endpoint');
    treeCheck(strpos($controller, "hasPermission('modify', '" . $type . "/category')") !== false, $type . ' route does not verify write permission');
    treeCheck(strpos($template, 'cc-tree-root') !== false && strpos($template, 'cc-tree-row') !== false, $type . ' lacks tree UI');
    treeCheck(strpos($template, 'cc-category-sort') !== false, $type . ' lacks inline sorting');
}

$orderList = file_get_contents($root . 'admin/view/template/sale/order_list.twig');
$orderModel = file_get_contents($root . 'admin/model/sale/order.php');
treeCheck(strpos($orderList, 'order.product_units') !== false, 'Order list lacks the item-count indicator');
treeCheck(strpos($orderList, 'order.product_thumb') === false, 'Order list must not render product photos');
treeCheck(strpos($orderModel, 'getProductCountsForOrderIds') !== false, 'Order counts are not loaded in batches');
treeCheck(strpos($orderList, 'ccp-order-preview-row') !== false, 'Expandable order preview was removed');
echo "PASS: category hierarchy safeguards, inline sorting, sidebar-compatible tree view and order count badge\n";
