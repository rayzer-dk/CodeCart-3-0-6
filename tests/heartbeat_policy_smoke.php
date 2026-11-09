<?php
/** Visitor heartbeat policy: php tests/heartbeat_policy_smoke.php */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/Heartbeat.php';

$excluded = (new ReflectionClassConstant(\CodeCart\Core\Heartbeat::class, 'NETWORK_TASKS'))->getValue();
$sql = (string)file_get_contents(dirname(__DIR__) . '/upload/install/opencart.sql');

$failures = array();
// Without a server cron and without loopback, the mail queue is the only thing that delivers
// password resets, registrations and order confirmations, so it must never be skipped.
if (in_array('core.queue.worker', $excluded, true)) {
    $failures[] = 'core.queue.worker must run inline when loopback is unavailable.';
}
foreach ($excluded as $code) {
    if (strpos($sql, "'" . $code . "'") === false) {
        $failures[] = 'Excluded task ' . $code . ' is not a seeded scheduler task.';
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS the visitor heartbeat still delivers the transactional mail queue\n";
