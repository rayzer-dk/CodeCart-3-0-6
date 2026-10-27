<?php
/** Read-only external cart schema validation: php tests/cart_external_schema_smoke.php */
define('DB_PREFIX', 'oc_');
class Model {}
class ExternalFakeResult {
    public $rows;
    public function __construct($rows) { $this->rows = $rows; }
}
class ExternalFakeDB {
    public $queries = array();
    public function query($query) {
        $this->queries[] = $query;
        if ($query !== 'SHOW COLUMNS FROM `oc_theme_cart`') { throw new RuntimeException('Unexpected query'); }
        return new ExternalFakeResult(array_map(function ($name) { return array('Field' => $name); },
            array('session_id','product_id','qty','date_modified','customer_id','customer_email')));
    }
}
require_once dirname(__DIR__) . '/upload/admin/model/sale/abandoned_cart.php';
$model = new ModelSaleAbandonedCart();
$model->db = new ExternalFakeDB();
$schema = $model->inspectExternalTable('theme_cart');
if (!$schema['valid'] || $schema['missing'] || $schema['fields']['col_quantity'] !== 'qty'
    || $schema['fields']['col_date'] !== 'date_modified'
    || $schema['fields']['col_email'] !== 'customer_email'
    || $schema['fields']['col_phone'] !== '') {
    throw new RuntimeException('External column inference failed.');
}
$queries = count($model->db->queries);
$bad = $model->inspectExternalTable('cart`;DROP_TABLE');
if ($bad['valid'] || count($model->db->queries) !== $queries) {
    throw new RuntimeException('Unsafe table name passed to SQL.');
}
$missing = $model->inspectExternalTable('oc_missing');
if ($missing['valid']) { throw new RuntimeException('Unknown table validated.'); }
echo "PASS external schema read-only detection, required/optional columns, unsafe identifier rejection.\n";
