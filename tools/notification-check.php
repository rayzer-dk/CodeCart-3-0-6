<?php
// Safe offline regression checks for the CodeCart notification service.
// No connection to a store database is required.
if (!defined('DB_PREFIX')) { define('DB_PREFIX', 'oc_'); }
class NotificationTestResult { public $num_rows = 1; public $rows = array(); }
class NotificationTestDb {
    public $statements = array();
    public function escape($value) { return addslashes($value); }
    public function countAffected() { return 1; }
    public function query($sql) {
        $this->statements[] = $sql;
        $result = new NotificationTestResult();
        if (strpos($sql, 'SELECT status, COUNT(*)') !== false) {
            $result->rows = array(
                array('status'=>'unread','total'=>2),
                array('status'=>'read','total'=>3),
                array('status'=>'resolved','total'=>1),
                array('status'=>'dismissed','total'=>4)
            );
        }
        return $result;
    }
}
class NotificationTestRegistry {
    public $db;
    public function __construct($db) { $this->db = $db; }
    public function get($name) { return $name === 'db' ? $this->db : null; }
    public function has($name) { return $name === 'db'; }
}
require dirname(__DIR__) . '/upload/system/library/codecart/src/SystemNotification.php';
$db = new NotificationTestDb();
$service = new \CodeCart\Core\SystemNotification(new NotificationTestRegistry($db));
function notifAssert($valid, $message) { if (!$valid) { throw new RuntimeException($message); } }
$counts = $service->counts();
notifAssert($counts['all'] === 6 && $counts['unread'] === 2 && $counts['resolved'] === 1 && $counts['dismissed'] === 4, 'Status totals');
$service->page(2, 50, 'unread');
$query = end($db->statements);
notifAssert(strpos($query, "status='unread'") !== false && strpos($query, 'LIMIT 50,50') !== false, 'Pagination and status filter');
$service->page(1, 50, 'dismissed');
notifAssert(strpos(end($db->statements), "status='dismissed'") !== false, 'Archived status filter');
$service->page(1, 50, 'unsafe-token');
notifAssert(strpos(end($db->statements), "status IN ('unread','read','resolved')") !== false, 'Filter whitelist');
$service->latest(5);
notifAssert(strpos(end($db->statements), "status NOT IN ('resolved','dismissed')") !== false, 'Header excludes dismissed');
notifAssert($service->delete(44), 'Dismiss returns success');
notifAssert(strpos(end($db->statements), "status='dismissed'") !== false && strpos(end($db->statements), "notification_id='44'") !== false, 'Dismiss keeps tombstone');
$service->notify('health.security.display_errors', 'warning', 'PHP errors', 'On', 'tool/codecart_core');
notifAssert(strpos(end($db->statements), "status=IF(") !== false && strpos(end($db->statements), "status='resolved'") !== false, 'Upsert preserves unchanged dismissed notice');
$service->resolve('health.security.display_errors');
notifAssert(strpos(end($db->statements), "status='resolved'") !== false && strpos(end($db->statements), "status!='resolved'") !== false, 'Resolved warnings remain in history');
$service->deleteReadAndResolved();
notifAssert(strpos(end($db->statements), "SET status='dismissed'") !== false, 'Clear read prevents duplicate reappearing');
echo "Notification service regression checks passed.\n";
