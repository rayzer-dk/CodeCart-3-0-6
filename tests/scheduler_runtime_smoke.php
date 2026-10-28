<?php
/** Focused regression: no false-green queue or scheduler statuses, real Cron heartbeat and task history. */
define('DB_PREFIX','oc_');
define('DB_DATABASE','scheduler_runtime_smoke');
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/Scheduler.php';
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/Queue.php';
class TestRows {
    public $rows; public $row; public $num_rows;
    public function __construct(array $rows = array()) { $this->rows=$rows; $this->row=$rows ? reset($rows) : array(); $this->num_rows=count($rows); }
}
class TestDb {
    public $queries = array();
    public $job;
    public function escape($s){return addslashes((string)$s);}
    public function query($sql){
        $this->queries[] = $sql;
        if (strpos($sql,'GET_LOCK(')!==false) return new TestRows(array(array('acquired'=>1)));
        if (strpos($sql,'RELEASE_LOCK(')!==false) return new TestRows();
        if (strpos($sql,'SELECT * FROM `oc_codecart_scheduler` WHERE status')===0) return new TestRows(array($this->job));
        if (strpos($sql,'SELECT * FROM `oc_codecart_scheduler` WHERE scheduler_id')===0) return new TestRows(array($this->job));
        if (strpos($sql,'SELECT source, date_last, last_status')===0) return new TestRows(array(array('source'=>'cli','date_last'=>'2026-10-28 05:00:00','last_status'=>'ok','failed'=>0,'age_minutes'=>5)));
        if (strpos($sql,'SELECT COUNT(*) AS total FROM `oc_codecart_scheduler_history`')===0) return new TestRows(array(array('total'=>2)));
        if (strpos($sql,'SELECT task_code, source, result')===0) return new TestRows(array(array('task_code'=>'core.demo','source'=>'cli','result'=>'error','duration_ms'=>20,'message'=>'test','date_added'=>'2026-10-28 05:00:00')));
        if (strpos($sql,'UPDATE `oc_codecart_queue`')===0 || strpos($sql,'UPDATE `oc_codecart_scheduler`')===0 || strpos($sql,'INSERT INTO `oc_codecart_scheduler_history`')===0 || strpos($sql,'INSERT INTO `oc_codecart_scheduler_heartbeat`')===0) return new TestRows();
        if (strpos($sql,'SELECT IS_USED_LOCK(')===0) return new TestRows(array(array('owner'=>null)));
        throw new RuntimeException('Unexpected query: '.$sql);
    }
}
class TestLoad {public $result; public function controller($route,$args=array(),$proxy=false){return $this->result;}}
class TestRegistry {public $db,$load; public function __construct($db,$load){$this->db=$db;$this->load=$load;} public function get($name){if($name==='db')return $this->db;if($name==='load')return $this->load;return null;}}
function ensure($ok,$why){if(!$ok)throw new RuntimeException($why);}
$db=new TestDb();$loader=new TestLoad();$registry=new TestRegistry($db,$loader);
$db->job=array('scheduler_id'=>7,'code'=>'core.demo','route'=>'cron/demo','args'=>'{}','interval_seconds'=>300,'status'=>1);
$scheduler=new \CodeCart\Core\Scheduler($registry);
$loader->result=array('status'=>'error','failed'=>1,'message'=>'failure');
$res=$scheduler->runDue(1,'cli');
ensure($res['status']==='error' && $res['failed']===1,'Scheduler returned false success');
ensure(count(array_filter($db->queries,function($q){return strpos($q,'INSERT INTO `oc_codecart_scheduler_history`')===0;}))===1,'Missing history');
ensure(count(array_filter($db->queries,function($q){return strpos($q,'INSERT INTO `oc_codecart_scheduler_heartbeat`')===0;}))===1,'Missing heartbeat');
ensure($scheduler->hostHeartbeat()['source']==='cli','Host heartbeat missing');
ensure($scheduler->historyCount()===2 && count($scheduler->history(1,25))===1,'History API failed');
$loader->result=array('success'=>true,'message'=>'All done');
$res=$scheduler->runDue(1,'visitor');
ensure($res['status']==='ok' && $res['failed']===0,'Scheduler successful execution failed');
ensure(strpos(end($db->queries),'heartbeat')!==false,'Visitor heartbeat not recorded');

// Queue result validation is executed after an already-claimed job without a live database.
$db2=new TestDb();$loader2=new TestLoad();$registry2=new TestRegistry($db2,$loader2);
$queue=new \CodeCart\Core\Queue($registry2);
$reflection=new ReflectionMethod($queue,'executeClaimedJob');
$job=array('queue_id'=>3,'code'=>'demo','route'=>'cron/demo','args'=>'{}','attempts'=>1,'max_attempts'=>3);
foreach (array(array('status'=>'error','failed'=>1),array('failed'=>2),array('success'=>false),null,false) as $failure) {
    $loader2->result=$failure;
    $r=$reflection->invoke($queue,$job);
    ensure($r['status']==='error' && $r['failed']===1,'Queue falsely successful for '.json_encode($failure));
}
$loader2->result=array('status'=>'ok','failed'=>0,'processed'=>1);
$r=$reflection->invoke($queue,$job);
ensure($r['status']==='ok' && $r['failed']===0,'Queue false failure on success');

// Static invariants to catch accidental regression during migrations and cron integration.
$root=dirname(__DIR__).'/upload/';
$m=file_get_contents($root.'system/library/codecart/migration.php');
ensure(strpos($m, 'SET route = \'" . $this->db->escape($route) . "\', interval_seconds')===false,'Migration overwrites configured task intervals');
$c=file_get_contents($root.'catalog/controller/cron/codecart.php');
ensure(strpos($c, '\'scheduler\' => $scheduler->runDue(20, $source), \'queue\' => $queue->run(25)')===false,'Cron all still duplicates queue worker');
ensure(strpos($c,'scheduled_worker')!==false,'Worker delegation missing');
echo "PASS: scheduler + queue status propagation, heartbeat, history, DB-preserved intervals and single queue worker.\n";
