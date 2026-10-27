<?php
/** Standalone scheduler regression smoke test: php tests/scheduler_crud_smoke.php */
define('DB_PREFIX', 'oc_');
define('DB_DATABASE', 'scheduler_smoke');
define('DIR_CATALOG', dirname(__DIR__) . '/upload/catalog/');
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/Scheduler.php';

class SmokeResult {
    public $rows;
    public $row;
    public $num_rows;
    public function __construct(array $rows) {
        $this->rows = $rows;
        $this->row = $rows[0] ?? array();
        $this->num_rows = count($rows);
    }
}
class SmokeDB {
    public $tasks = array();
    public $queries = array();
    public function escape($value) { return addslashes((string)$value); }
    public function query($sql) {
        $this->queries[] = $sql;
        if (strpos($sql, 'SELECT scheduler_id, code, route, args, interval_seconds, status, date_last') === 0) { return new SmokeResult(array_values($this->tasks)); }
        if (strpos($sql, 'GET_LOCK(') !== false) { return new SmokeResult(array(array('acquired' => 1))); }
        if (strpos($sql, 'RELEASE_LOCK(') !== false) { return new SmokeResult(array()); }
        if (preg_match('/^SELECT scheduler_id FROM .*codecart_scheduler.* WHERE (.+) LIMIT 1$/', $sql, $match)) {
            $where = $match[1];
            $code = preg_match("/code = '([^']*)'/", $where, $m) ? stripslashes($m[1]) : null;
            $route = preg_match("/route = '([^']*)'/", $where, $m) ? stripslashes($m[1]) : null;
            $rows = array();
            foreach ($this->tasks as $t) {
                if (($code !== null && $t['code'] === $code) || ($route !== null && $t['route'] === $route)) {
                    $rows[] = array('scheduler_id' => $t['scheduler_id']);
                }
            }
            return new SmokeResult(array_slice($rows, 0, 1));
        }
        if (preg_match('/^INSERT INTO .*codecart_scheduler.* SET code = \'([^\']+)\', route = \'([^\']+)\'/', $sql, $m)) {
            $id = count($this->tasks) + 1;
            $this->tasks[$id] = array('scheduler_id'=>$id, 'code'=>stripslashes($m[1]), 'route'=>stripslashes($m[2]), 'args'=>'{}', 'interval_seconds'=>300, 'status'=>1);
            return new SmokeResult(array());
        }
        if (preg_match('/^SELECT \* FROM .*codecart_scheduler.* WHERE scheduler_id = \'(\d+)\' LIMIT 1$/', $sql, $m)) {
            return new SmokeResult(isset($this->tasks[(int)$m[1]]) ? array($this->tasks[(int)$m[1]]) : array());
        }
        if (strpos($sql, 'UPDATE `oc_codecart_scheduler`') === 0) { return new SmokeResult(array()); }
        throw new RuntimeException('Unexpected SQL: ' . $sql);
    }
}
class SmokeLoad {
    public function controller($route, $args = array(), $proxy = false) {
        if ($route !== 'cron/demo') { throw new RuntimeException('Unexpected route'); }
        return array('success' => true, 'message' => 'Done');
    }
}
class SmokeRegistry {
    private $deps;
    public function __construct($db) {
        $this->deps = array('db'=>$db, 'log'=>null, 'load'=>new SmokeLoad());
    }
    public function get($key) { return $this->deps[$key] ?? null; }
}
function expect($true, $message) { if (!$true) { throw new RuntimeException($message); } }
$db = new SmokeDB();
$scheduler = new \CodeCart\Core\Scheduler(new SmokeRegistry($db));
$scheduler->createTask('custom.demo','cron/demo',300,true);
expect(count($db->tasks) === 1, 'New task was not inserted');
try { $scheduler->createTask('custom.demo','cron/another',300,true); throw new RuntimeException('Duplicate code accepted'); }
catch (InvalidArgumentException $e) { expect(strpos($e->getMessage(),'already registered') !== false, 'Wrong duplicate code error'); }
try { $scheduler->createTask('custom.another','cron/demo',300,true); throw new RuntimeException('Duplicate route accepted'); }
catch (InvalidArgumentException $e) { expect(strpos($e->getMessage(),'already registered') !== false, 'Wrong duplicate route error'); }
try { $scheduler->createTask('core.test','cron/test',300,true); throw new RuntimeException('Reserved code accepted'); }
catch (InvalidArgumentException $e) { expect(strpos($e->getMessage(),'reserved') !== false, 'Wrong reserved code error'); }
expect(count($db->tasks) === 1, 'Duplicate attempt modified task count');
expect(!array_filter($db->queries, function($q) { return strpos($q, 'UPDATE ') === 0; }), 'Creating duplicate updated an existing task');
$result = $scheduler->runOne(1);
expect(!empty($result['success']), 'Task execution did not succeed');
expect(count(array_filter($db->queries, function($q) { return strpos($q, 'date_next = DATE_ADD(NOW(), INTERVAL 300 SECOND)') !== false; })) === 1, 'Timezone-safe next run calculation absent');
// Controller validation and actual POST save action (without web server).
class Controller {
    public $request;
    public $session;
    public $language;
}
class SmokeLanguage {
    public function get($key) { return $key; }
}
require_once dirname(__DIR__) . '/upload/admin/controller/tool/scheduler.php';
$adminDB = new SmokeDB();
$adminScheduler = new \CodeCart\Core\Scheduler(new SmokeRegistry($adminDB));
$controller = new ControllerToolScheduler();
$controller->request = (object)array('post'=>array(
    'scheduler_action'=>'save','scheduler_id'=>'0','code'=>'custom.guest_purge',
    'route'=>'cron/cart_guest_purge','interval_seconds'=>'300','status'=>'1','args'=>'{}'
));
$controller->session = (object)array('data'=>array());
$controller->language = new SmokeLanguage();
$method = (new ReflectionClass($controller))->getMethod('processAction');
$method->invoke($controller, $adminScheduler);
expect(count($adminDB->tasks) === 1, 'Admin save failed to create a task');
expect(!empty($controller->session->data['success']), 'Admin did not report success');
try {
    $controller->request->post['code'] = 'custom.guest_purge_dup';
    $method->invoke($controller, $adminScheduler);
    throw new RuntimeException('Admin accepted duplicate handler');
} catch (InvalidArgumentException $e) {
    expect($e->getMessage() === 'error_duplicate_route', 'Admin used wrong duplicate handler error');
}
expect(count($adminDB->tasks) === 1, 'Admin duplicate request changed row count');
echo "OK: create, duplicate safety, reserved codes, next run, admin POST create/duplicate.\n";
