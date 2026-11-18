<?php
/**
 * Real DB regression for administrator order searching.
 * GitHub Actions only: runs inside a transaction and rolls back its fixtures.
 */
if (getenv('CODECART_ORDER_DB_SMOKE') !== '1') {
    fwrite(STDERR, "Set CODECART_ORDER_DB_SMOKE=1 in the isolated CI database.\n");
    exit(2);
}
define('DB_PREFIX', 'oc_');
$root = dirname(__DIR__) . '/upload/';
require $root . 'system/engine/registry.php';
require $root . 'system/engine/model.php';
require $root . 'system/library/db/mysqli.php';
require $root . 'system/library/codecart/src/OrderAdminSearch.php';
require $root . 'admin/model/sale/order.php';

class OrderSmokeConfig {
    public function get($key) { return $key === 'config_language_id' ? 1 : null; }
}
class OrderSmokeCache {
    private $values = array();
    public function get($key) { return isset($this->values[$key]) ? $this->values[$key] : false; }
    public function set($key, $value) { $this->values[$key] = $value; }
}
function assertDb($ok, $message) {
    if (!$ok) { throw new RuntimeException($message); }
}
$db = new \DB\MySQLi('127.0.0.1', 'root', 'root', 'codecart', 3306);
$registry = new Registry();
$registry->set('db', $db);
$registry->set('config', new OrderSmokeConfig());
$registry->set('cache', new OrderSmokeCache());
$model = new ModelSaleOrder($registry);
$esc = function($value) use ($db) { return "'" . $db->escape((string)$value) . "'"; };
$db->beginTransaction();
try {
    $schema = $db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order`")->rows;
    $base = array(
        'invoice_prefix' => 'QA-', 'store_name' => 'Smoke Store', 'store_url' => 'http://127.0.0.1:8080/',
        'firstname' => 'QA', 'lastname' => 'Buyer', 'email' => 'qa-order@example.test',
        'telephone' => '+38 (095) 899-39-86', 'payment_firstname' => 'QA', 'payment_lastname' => 'Buyer',
        'shipping_firstname' => 'QA', 'shipping_lastname' => 'Buyer',
        'payment_method' => 'Bank transfer', 'payment_code' => 'bank_transfer',
        'shipping_method' => 'Nova Poshta', 'shipping_code' => 'nova_poshta',
        'total' => '29850.0000', 'order_status_id' => 1,
        'language_id' => 1, 'currency_id' => 1, 'currency_code' => 'UAH',
        'currency_value' => '1.00000000', 'date_added' => date('Y-m-d H:i:s'),
        'date_modified' => date('Y-m-d H:i:s')
    );
    $insertOrder = function(array $overrides) use ($schema, $base, $db, $esc) {
        $values = array_merge($base, $overrides);
        $fields = array(); $data = array();
        foreach ($schema as $row) {
            $name = $row['Field'];
            if (stripos((string)$row['Extra'], 'auto_increment') !== false) { continue; }
            if (array_key_exists($name, $values)) { $value = $values[$name]; }
            elseif ($row['Null'] === 'NO' && $row['Default'] === null) {
                $type = (string)$row['Type'];
                $value = preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double|bit)/i', $type)
                    ? '0' : (stripos($type, 'date') !== false || stripos($type, 'timestamp') !== false
                        ? date('Y-m-d H:i:s') : '');
            } else { continue; }
            $fields[] = '`' . $name . '`';
            $data[] = $esc($value);
        }
        $db->query("INSERT INTO `" . DB_PREFIX . "order` (" . implode(',', $fields) . ") VALUES (" . implode(',', $data) . ")");
        return (int)$db->getLastId();
    };
    $first = $insertOrder(array());
    $second = $insertOrder(array(
        'email'=>'partial-qa@example.test', 'telephone'=>"095\u{2011}899\u{00A0}39 86",
        'payment_method'=>'Card', 'payment_code'=>'card',
        'shipping_method'=>'Courier', 'shipping_code'=>'courier', 'total'=>'100.0000'
    ));
    foreach (array(
        array('order_id'=>$first,'product_id'=>40,'name'=>'QA Milk Separator','model'=>'FTH-105','sku'=>'QA-105'),
        array('order_id'=>$first,'product_id'=>999999,'name'=>'QA Removed Product','model'=>'FTH-106','sku'=>'QA-106')
    ) as $item) {
        $db->query("INSERT INTO `" . DB_PREFIX . "order_product` "
            . "(order_id, product_id, name, model, sku, quantity, price, total, tax, reward) VALUES ("
            . (int)$item['order_id'] . "," . (int)$item['product_id'] . ","
            . $esc($item['name']) . "," . $esc($item['model']) . "," . $esc($item['sku'])
            . ",1,29850,29850,0,0)");
    }
    $matches = $model->getOrders(array('filter_phone'=>'+38 (095) 899-39-86'));
    assertDb(count($matches) === 2 && (int)$matches[0]['order_id'] === $first, 'Full phone must outrank country-code variation');
    assertDb($model->getTotalOrders(array('filter_product'=>'separator')) === 1, 'Product filtering must return unique orders');
    assertDb($model->getTotalOrders(array('filter_product'=>'QA-106')) === 1, 'Deleted products must be searchable by order SKU snapshot');
    assertDb($model->getTotalOrders(array('filter_total_min'=>'29800', 'filter_total_max'=>'29900')) === 1, 'Amount range');
    assertDb($model->getTotalOrders(array('filter_shipping_method'=>'Nova Poshta')) === 1, 'Shipping filter');
    assertDb($model->getTotalOrders(array('filter_payment_method'=>'Bank transfer')) === 1, 'Payment filter');
    assertDb($model->getTotalOrders(array('filter_email'=>'qa-order@example.test')) === 1, 'Email filter');
    $methods = $model->getOrderMethodChoices('shipping_method');
    assertDb(in_array('Nova Poshta', $methods, true) && in_array('Courier', $methods, true), 'Shipping choices from database');
    $preview = $model->getOrderPreview($first);
    assertDb(count($preview['products']) === 2, 'Preview must load both product snapshots');
    assertDb($preview['products'][0]['product_image'] !== null, 'Existing catalog photo was not joined');
    assertDb($preview['products'][1]['product_image'] === null, 'Deleted product must use a placeholder');
    assertDb(count($model->getOrderProducts($first)) === 2, 'Order detail photos must not duplicate products');
    echo "PASS: real DB order search, ranking, ranges, choice list and photo joins.\n";
} finally {
    $db->rollback();
}
