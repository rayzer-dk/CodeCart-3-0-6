<?php
/** Admin order counts must sum quantities; photos remain only in detail/preview. */
define('DB_PREFIX', 'oc_');
require_once dirname(__DIR__) . '/upload/system/engine/registry.php';
require_once dirname(__DIR__) . '/upload/system/engine/model.php';
require_once dirname(__DIR__) . '/upload/admin/model/sale/order.php';

class OrderCountMockDb {
    public $calls = array();
    public function query($sql) {
        $this->calls[] = $sql;
        if (strpos($sql, 'SUM(quantity)') === false || strpos($sql, 'GROUP BY order_id') === false || strpos($sql, 'JOIN') !== false) {
            throw new RuntimeException('Expected independent grouped unit-count query');
        }
        return (object)array('rows' => array(
            array('order_id' => 12, 'units' => 5, 'positions' => 2),
            array('order_id' => 13, 'units' => 1, 'positions' => 1)
        ));
    }
}
function countCheck($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$registry = new Registry();
$db = new OrderCountMockDb();
$registry->set('db', $db);
$model = new ModelSaleOrder($registry);
countCheck($model->getProductCountsForOrderIds(array()) === array(), 'Empty order list should not query');
$counts = $model->getProductCountsForOrderIds(array(12, 13, 12, 0, -1));
countCheck(count($db->calls) === 1, 'Orders page must issue one count query');
countCheck($counts[12]['units'] === 5 && $counts[12]['positions'] === 2, 'Quantity and position totals differ');
$base = dirname(__DIR__) . '/upload/';
$list = file_get_contents($base . 'admin/view/template/sale/order_list.twig');
$controller = file_get_contents($base . 'admin/controller/sale/order.php');
$info = file_get_contents($base . 'admin/view/template/sale/order_info.twig');
countCheck(strpos($list, 'ccp-order-unit-count') !== false && strpos($list, 'order.product_units') !== false, 'Order count badge missing');
countCheck(strpos($list, 'order.product_thumb') === false && strpos($list, 'ccp-order-photo-cell') === false, 'Order list must not include thumbnail column');
countCheck(strpos($list, 'colspan="9"') !== false && strpos($list, 'colspan="8"') === false, 'Order table and expanded preview column counts differ');
countCheck(strpos($list, 'ccp-order-preview-row') !== false && strpos($list, 'item.image') !== false, 'Expanded preview photo is missing');
countCheck(strpos($info, 'product.image') !== false, 'Order details no longer render product images');
countCheck(strpos($controller, 'getProductCountsForOrderIds') !== false && strpos($controller, 'product_count_tooltip') !== false, 'Order controller does not bind batch count');
countCheck(strpos($controller, "['column_image']") !== false, 'Preview image header translation missing');
foreach (array('uk-ua','ru-ru','en-gb') as $locale) {
    $words = file_get_contents($base . 'admin/language/' . $locale . '/sale/order.php');
    countCheck(strpos($words, "text_order_product_count") !== false && strpos($words, "text_order_units_label") !== false, 'Count badge locale missing: ' . $locale);
}
echo "PASS: order units and positions, no image list column, preserved preview and 3 localizations\n";
