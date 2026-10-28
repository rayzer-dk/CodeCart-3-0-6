<?php
/** Lightweight regression checks; does not connect to a production database or SMTP. */
const DB_PREFIX = 'oc_';
class FakeCartConfig {
    private $values;
    public function __construct($values) { $this->values=$values; }
    public function get($key) { return $this->values[$key] ?? null; }
}
class FakeCartDb {
    public $sql = array();
    public $affected=1;
    public function escape($value) { return addslashes((string)$value); }
    public function countAffected() { return $this->affected; }
    public function query($sql) {
        $this->sql[]=$sql;
        return (object)array('rows'=>array(),'row'=>array('total'=>0),'num_rows'=>0);
    }
}
class FakeCartRegistry {
    private $resources;
    public function __construct($db,$config) { $this->resources=array('db'=>$db,'config'=>$config); }
    public function get($key) { return $this->resources[$key] ?? null; }
}
class Model {
    public $db;
    public $config;
    public function __construct($db,$config) { $this->db=$db;$this->config=$config; }
}
function check($condition,$message) { if (!$condition) { throw new RuntimeException($message); } }
require_once __DIR__ . '/../upload/system/library/codecart/src/GuestCartContact.php';
require_once __DIR__ . '/../upload/admin/model/sale/abandoned_cart.php';
$db = new FakeCartDb();
$config = new FakeCartConfig(array('codecart_abandoned_cart_status'=>0,'codecart_abandoned_cart_guest_capture'=>1,'codecart_abandoned_cart_source'=>'standard','config_store_id'=>0));
$contact = new \CodeCart\Core\GuestCartContact(new FakeCartRegistry($db,$config));
$contact->saveFromCheckout('session',array('email'=>'example@example.com','ccp_cart_recovery_consent'=>'1'));
check(count($db->sql)===0,'OFF must not contact database');
check($contact->issueToken('session')==='','OFF must not issue token');
$config = new FakeCartConfig(array('codecart_abandoned_cart_status'=>1,'codecart_abandoned_cart_guest_capture'=>1,'codecart_abandoned_cart_source'=>'standard','config_store_id'=>0,'config_language_id'=>1,'codecart_abandoned_cart_days'=>7,'codecart_abandoned_cart_idle_minutes'=>60));
$db = new FakeCartDb();
$contact = new \CodeCart\Core\GuestCartContact(new FakeCartRegistry($db,$config));
$contact->saveFromCheckout('sid',array('email'=>'foo@example.com'));
check(strpos(end($db->sql),'DELETE FROM')!==false,'no guest contact without explicit consent');
$contact->saveFromCheckout('sid',array('email'=>'foo@example.com','ccp_cart_recovery_consent'=>'1'));
check(strpos(end($db->sql),'INSERT INTO')!==false,'explicit consent should store contact');
$token = $contact->issueToken('sid');
check(strlen($token)===64 && ctype_xdigit($token),'recovery token must contain 32 random bytes');
check(strpos(end($db->sql),$token)===false,'raw bearer token must never appear in SQL');
$model = new ModelSaleAbandonedCart($db,$config);
$model->getAbandonedCarts(0,25,'last_activity','DESC',array('search'=>"a' OR 1=1 --",'contact'=>'yes'));
$sql=end($db->sql);
check(strpos($sql,"a\\' OR 1=1 --")!==false,'search must be escaped');
check(strpos($sql,'GROUP BY c.customer_id,c.session_id HAVING')!==false,'aggregation must include unfiltered cart rows');
check(strpos($sql,'LIMIT 0,25')!==false,'pagination limit must be bounded');
check(strpos($sql,'codecart_cart_guest_contact` WHERE consent=1')!==false,'guest contacts must require consent');
check(strpos($sql,'`oc_codecart_cart_guest_contact`')!==false,'guest contact table expected');
echo "PASS cart workflow smoke (OFF, consent, hashed tokens, filter, paging)\n";
