<?php
/** Run: php -d zend.assertions=1 tests/cart_source_discovery_smoke.php */
define('DB_PREFIX', 'oc_');
class Model { public $db; }
class FakeCartSchemaDb {
    public $queries = 0;
    public function query($sql) {
        $this->queries++;
        $response = new stdClass();
        $response->rows = array();
        if ($sql === 'SHOW TABLES') {
            $response->rows = array(
                array('Tables_in_test'=>'oc_cart'),
                array('Tables_in_test'=>'oc_uni_checkout_cart'),
                array('Tables_in_test'=>'oc_customer_cart_audit'),
                array('Tables_in_test'=>'oc_product')
            );
        } elseif ($sql === 'SHOW COLUMNS FROM `oc_uni_checkout_cart`') {
            foreach (array('session_id','product_id','quantity','date_added','customer_id','email','telephone') as $c) {
                $response->rows[] = array('Field'=>$c);
            }
        }
        return $response;
    }
}
require dirname(__DIR__) . '/upload/admin/model/sale/abandoned_cart.php';
function check_cart($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
$model = new ModelSaleAbandonedCart();
$model->db = new FakeCartSchemaDb();
$tables = $model->findExternalCartTables();
check_cart(count($tables) === 1, 'Only tables with the complete cart schema must be shown');
check_cart($tables[0]['table'] === 'oc_uni_checkout_cart', 'Expected valid extra cart source');
$mapping = $model->inspectExternalTable('uni_checkout_cart');
check_cart($mapping['valid'] && !$mapping['missing'], 'All required columns must be mapped');
check_cart($mapping['fields']['col_phone'] === 'telephone', 'Optional customer phone mapping');
check_cart(!$model->inspectExternalTable('checkout/uni_checkout')['valid'], 'Checkout URL must be rejected as SQL table');
check_cart(!$model->inspectExternalTable('abc`; DROP TABLE oc_order;--')['valid'], 'Unsafe SQL name must be rejected');
check_cart($model->db->queries < 20, 'Bounded manual schema scan');
echo "PASS: valid cart table discovery, column mapping, SQL name validation\n";
