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

// Index flag is inverted in CodeCart: 1 means the category is indexable.
class TreeIndexFakeDb {
    public $value = 0;
    public function query($sql) {
        if (strpos($sql, "UPDATE `oc_category` SET noindex = '1'") !== false &&
            strpos($sql, "AND noindex = '0'") !== false && $this->value === 0) {
            $this->value = 1;
            return (object)array('num_rows' => 0, 'row' => array());
        }
        if (strpos($sql, "SELECT noindex FROM `oc_category`") !== false) {
            return (object)array('num_rows' => 1, 'row' => array('noindex' => $this->value));
        }
        throw new RuntimeException('Unexpected indexing query.');
    }
}
$indexDb = new TreeIndexFakeDb();
$indexRegistry = new Registry();
$indexRegistry->set('db', $indexDb);
$indexRegistry->set('cache', new TreeFakeCache());
treeCheck(AdminCategoryTreeEditor::quickIndex($indexRegistry, 'catalog', 7, 1, 0) === 1,
    'Indexable toggle did not persist');
try {
    AdminCategoryTreeEditor::quickIndex($indexRegistry, 'catalog', 7, 0, 0);
    throw new RuntimeException('Stale index toggle accepted');
} catch (RuntimeException $expected) {
    treeCheck(strpos($expected->getMessage(), 'changed in another session') !== false,
        'Index toggle must reject concurrent updates');
}

$root = dirname(__DIR__) . '/upload/';
$service = file_get_contents($root . 'system/library/codecart/src/AdminCategoryTreeEditor.php');
$script = file_get_contents($root . 'admin/view/javascript/codecart/category-tree.js');
treeCheck(strpos($service, 'Transaction::run') !== false, 'Moving branches is not atomic');
treeCheck(strpos($service, 'GET_LOCK') !== false && strpos($service, 'RELEASE_LOCK') !== false, 'Concurrent moves are not serialized');
treeCheck(strpos($service, 'array_search($id, $old, true)') !== false, 'Subtree descendant paths were not preserved');
treeCheck(strpos($script, "row.draggable = false") !== false && strpos($script, "handle.setPointerCapture") !== false,
    'Pointer drag must work for mouse, pen and touch');
treeCheck(strpos($script, "config.dataset.ccTreeKind || 'catalog'") !== false,
    'Tree setup must not depend on an exact route query string');
treeCheck(strpos($script, 'data-cc-tree-target') !== false, 'Drop-to-root zone missing');
treeCheck(strpos($script, 'cc-tree-move') !== false, 'Touch fallback is missing');
treeCheck(strpos($script, 'function syncExpandAll()') !== false && strpos($script, 'if (expandAll) {\n      function syncExpandAll()') === false, 'Expand-all helper must be callable when initialization begins');
treeCheck(strpos($script, "action:'index'") !== false, 'Index toggle does not persist asynchronously');
foreach (array('catalog','blog') as $type) {
    $controller = file_get_contents($root . 'admin/controller/' . $type . '/category.php');
    $template = file_get_contents($root . 'admin/view/template/' . $type . '/category_list.twig');
    treeCheck(strpos($controller, 'function treeEdit()') !== false, $type . ' lacks secure mutation endpoint');
    treeCheck(strpos($controller, "hasPermission('modify', '" . $type . "/category')") !== false, $type . ' route does not verify write permission');
    treeCheck(strpos($controller, "\$data['category_tree_edit_url']") !== false, $type . ' controller does not provide drag-and-drop POST endpoint');
    treeCheck(strpos($controller, "\$data['category_tree_autocomplete']") !== false, $type . ' controller does not provide move-target autocomplete');
    treeCheck(strpos($template, 'data-cc-tree-url="{{ category_tree_edit_url }}"') !== false, $type . ' template is not connected to tree editor');
    treeCheck(strpos($template, 'cc-tree-root') !== false && strpos($template, 'cc-tree-row') !== false, $type . ' lacks tree UI');
    treeCheck(strpos($template, 'cc-category-sort') !== false, $type . ' lacks inline sorting');
    treeCheck(strpos($template, 'cc-category-index-toggle') !== false && strpos($controller, "quickIndex") !== false, $type . ' lacks inline indexing');
}

treeCheck(strpos($script, "icon.classList.add(isCollapsed ? 'fa-angle-right' : 'fa-angle-down')") !== false, 'Category disclosure arrow does not follow collapse state');
treeCheck(strpos($script, "icon.classList.add(anyCollapsed ? 'fa-angles-down' : 'fa-angles-up')") !== false, 'Expand-all arrow does not change with tree state');
treeCheck(strpos($script, "data-collapse-label") !== false && strpos($script, ".cc-tree-expand-label") !== false, 'Expand-all caption does not follow tree state');

foreach (array('catalog', 'blog') as $type) {
    $template = file_get_contents($root . 'admin/view/template/' . $type . '/category_list.twig');
    treeCheck(strpos($template, 'fa-solid fa-angles-down') !== false, $type . ' expand-all button missing arrows');
    treeCheck(strpos($template, 'btn btn-primary pull-right cc-tree-expand-all') !== false, $type . ' expand-all button must be prominent');
    treeCheck(strpos($template, 'tree_collapse_all') !== false, $type . ' collapse button label must be translated');
    treeCheck(strpos($template, 'fa-solid fa-angle-right') !== false, $type . ' category disclosure missing up arrow');
}
$orderList = file_get_contents($root . 'admin/view/template/sale/order_list.twig');
$orderModel = file_get_contents($root . 'admin/model/sale/order.php');
treeCheck(strpos($orderList, 'order.product_units') !== false && strpos($orderList, 'ccp-order-unit-count') !== false, 'Order list lacks the item-count indicator');
treeCheck(strpos($orderList, 'order.product_thumb') === false && strpos($orderList, 'ccp-order-photo-cell') === false, 'Photos must not be in the compact order list');
treeCheck(strpos($orderList, 'colspan="9"') !== false, 'Expanded row must match the nine list columns');
treeCheck(strpos($orderModel, 'getProductCountsForOrderIds') !== false, 'Order counts are not loaded in batches');
treeCheck(strpos($orderList, 'ccp-order-preview-row') !== false && strpos($orderList, 'item.image') !== false, 'Expandable order preview with photos was removed');
echo "PASS: category hierarchy safeguards, inline sorting, sidebar-compatible tree view and order count badge\n";
