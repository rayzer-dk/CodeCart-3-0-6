<?php
/** Contract and query safety regression without a live database/mail provider. */
const DB_PREFIX = 'oc_';
class Model { public $db; public $config; public function __construct($db=null,$config=null){$this->db=$db;$this->config=$config;} }
class FakeRegistry {
    private $services;
    public function __construct($db,$config) { $this->services=['db'=>$db,'config'=>$config]; }
    public function get($key) { return $this->services[$key] ?? null; }
}
class FakeConfig {
    private $values;
    public function __construct($values){$this->values=$values;}
    public function get($key){return $this->values[$key] ?? null;}
}
class FakeDb {
    public $sql=[];
    public $response=[];
    public function escape($s) { return addslashes((string)$s); }
    public function query($sql) {
        $this->sql[]=$sql;
        $rows=[];
        if (strpos($sql,'SHOW TABLES') !== false) { $rows=[['Tables_in_db'=>'oc_codecart_cart_registered_consent']]; }
        if (strpos($sql,'SELECT consent FROM') !== false) { $rows=[['consent'=>1]]; }
        return (object)['rows'=>$rows,'row'=>($rows[0]??['total'=>0]),'num_rows'=>count($rows)];
    }
}
function ck($condition,$message){ if (!$condition) throw new RuntimeException($message); }
require __DIR__ . '/../upload/system/library/codecart/src/RegisteredCartConsent.php';
require __DIR__ . '/../upload/system/library/codecart/src/AbandonedCartReminder.php';
require __DIR__ . '/../upload/admin/model/sale/abandoned_cart.php';
$db=new FakeDb();
$off=new FakeConfig(['codecart_abandoned_cart_status'=>0,'codecart_abandoned_cart_source'=>'standard','config_store_id'=>5]);
(new \CodeCart\Core\RegisteredCartConsent(new FakeRegistry($db,$off)))->save(12,'session',true);
ck(count($db->sql)===0,'OFF must not write consent');
$on=new FakeConfig(['codecart_abandoned_cart_status'=>1,'codecart_abandoned_cart_source'=>'standard','config_store_id'=>5,'config_language_id'=>1,'codecart_abandoned_cart_idle_minutes'=>60,'codecart_abandoned_cart_days'=>7]);
$consent=new \CodeCart\Core\RegisteredCartConsent(new FakeRegistry($db,$on));
$consent->save(12,'session',true);
$sql=end($db->sql);
ck(strpos($sql,"store_id='5'")!==false && strpos($sql,"customer_id='12'")!==false,'Store and customer must be scoped');
ck(strpos($sql,' ON DUPLICATE KEY UPDATE ')!==false,'Consent must upsert, not duplicate');
$consent->save(12,'session',false);
ck(strpos(end($db->sql),'DELETE FROM')!==false,'Revocation must remove consent');
ck($consent->hasConsent(12,'session'),'Session consent lookup must be supported');
$model=new ModelSaleAbandonedCart($db,$on);
$model->getAbandonedCarts(0,25);
$sql=end($db->sql);
ck(strpos($sql,'codecart_cart_registered_consent')!==false,'Registered explicit consent must be joined');
ck(strpos($sql,'cu.newsletter')===false,'Newsletter must not authorize mail');
ck(strpos($sql,'HAVING COUNT(DISTINCT store_id)=1')!==false,'Ambiguous origin must be suppressed');
$reminder=new \CodeCart\Core\AbandonedCartReminder(new FakeRegistry($db,$on));
$first=$reminder->fingerprint(['customer_id'=>12,'store_id'=>1,'session_id'=>'sid','last_activity'=>'2026-10-28','quantity'=>1,'max_cart_id'=>9]);
$second=$reminder->fingerprint(['customer_id'=>12,'store_id'=>2,'session_id'=>'sid','last_activity'=>'2026-10-28','quantity'=>1,'max_cart_id'=>9]);
ck($first!==$second,'Idempotency key must include origin store');
// Admin display cutoff and automatic reminder cutoff are deliberately independent.
$bounds=new FakeConfig([
    'codecart_abandoned_cart_status'=>1,
    'codecart_abandoned_cart_source'=>'standard',
    'config_store_id'=>0,
    'config_language_id'=>1,
    'codecart_abandoned_cart_idle_minutes'=>1,
    'codecart_abandoned_cart_reminder_delay_minutes'=>60,
    'codecart_abandoned_cart_days'=>30
]);
$boundsModel=new ModelSaleAbandonedCart($db,$bounds);
$boundsModel->getAbandonedCarts(0,10);
$boundSql=end($db->sql);
ck(strpos($boundSql,'INTERVAL 1 MINUTE')!==false,'1 minute inactivity threshold is supported');
ck(strpos($boundSql,'INTERVAL 30 DAY')!==false,'30 day cart history is supported');
$boundsReminder=new \CodeCart\Core\AbandonedCartReminder(new FakeRegistry($db,$bounds));
$reflect=new ReflectionMethod($boundsReminder,'sendAfter');
ck($reflect->invoke($boundsReminder)===60,'Reminder timing must not follow early analytics threshold');
$wait=new FakeConfig([
    'codecart_abandoned_cart_status'=>1,
    'codecart_abandoned_cart_source'=>'standard',
    'codecart_abandoned_cart_idle_minutes'=>90,
    'codecart_abandoned_cart_reminder_delay_minutes'=>5
]);
$waitReminder=new \CodeCart\Core\AbandonedCartReminder(new FakeRegistry($db,$wait));
ck($reflect->invoke($waitReminder)===90,'Reminders must not precede abandoned status');
$cron = file_get_contents(__DIR__ . '/../upload/catalog/controller/cron/cart.php');
ck(strpos($cron, "max(1, min(30,")!==false, 'Guest cart Cron must respect 30-day history setting');
ck(strpos($cron, "codecart_abandoned_cart_status")!==false, 'Guest retention must be gated by enabled analytics');
$view = file_get_contents(__DIR__ . '/../upload/admin/view/template/sale/abandoned_cart_list.twig');
ck(strpos($view, 'name="cart_reminder_delay_minutes"')!==false, 'Reminder delay control must be editable');
ck(strpos($view, 'name="cart_idle_minutes"')!==false && strpos($view, 'min="1"')!==false, '1-minute idle setting must be editable');
ck(strpos($view, 'max="30"')!==false, '30-day history setting must be editable');
print "PASS registered consent OFF, scoped upsert/revoke, multistore fingerprint, SQL consent\n";
