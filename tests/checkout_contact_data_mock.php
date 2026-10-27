<?php
/* Exercise the guest-contact writer without a production DB. */
define('DB_PREFIX', 'oc_');
class TestDB {
    public $queries = array();
    public function escape($value) { return addslashes((string)$value); }
    public function query($sql) {
        $this->queries[] = $sql;
        return (object)array('num_rows'=>strpos($sql, 'SHOW COLUMNS') === 0 ? 1 : 0, 'rows'=>array());
    }
}
class TestConfig {
    public $enabled = 1;
    public function get($key) {
        if ($key === 'config_store_id') return 2;
        if ($key === 'codecart_abandoned_cart_source') return 'standard';
        if ($key === 'codecart_abandoned_cart_status' || $key === 'codecart_abandoned_cart_guest_capture') return $this->enabled;
        return null;
    }
}
class TestRegistry {
    public $db, $config;
    public function __construct() { $this->db = new TestDB(); $this->config = new TestConfig(); }
    public function get($key) { return $this->{$key}; }
}
require dirname(__DIR__) . '/upload/system/library/codecart/src/GuestCartContact.php';
function check($condition, $message) { if (!$condition) { fwrite(STDERR, 'FAIL '.$message."\n"); exit(1); } }
$r = new TestRegistry(); $g = new \CodeCart\Core\GuestCartContact($r);
$g->saveFromCheckout('session-1',array('firstname'=>'Ірина', 'lastname'=>'Коваленко', 'telephone'=>'+380501234567', 'email'=>'test@example.org','ccp_cart_recovery_consent'=>'1'));
$sql = end($r->db->queries);
check(strpos($sql, 'INSERT INTO') === 0, 'Consent saving');
check(strpos($sql, "lastname='Коваленко'") !== false, 'Surname retained');
check(strpos($sql, "firstname='Ірина'") !== false, 'First name retained');
check(strpos($sql, "email='test@example.org'") !== false, 'Email retained');
check(strpos($sql, "telephone='+380501234567'") !== false, 'Phone retained');
check(strpos($sql, "store_id='2'") !== false, 'Store scoped');
$before=count($r->db->queries);
$g->saveFromCheckout('session-1',array('ccp_cart_recovery_consent'=>'0'));
check(count($r->db->queries)===$before+1 && strpos(end($r->db->queries), 'DELETE FROM') === 0, 'Consent withdrawal deletes contact');
$r->config->enabled = 0;
$before=count($r->db->queries);
$g->saveFromCheckout('session-1',array('ccp_cart_recovery_consent'=>'1','email'=>'test@example.org'));
check(count($r->db->queries)===$before, 'OFF means no queries');
echo "Checkout contact SQL mock: OK\n";
