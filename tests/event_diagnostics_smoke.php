<?php
/**
 * Lightweight smoke test. Runs without a database or browser.
 * Covers ordering, early-return, exceptions, opt-in recording and safe metadata.
 */
declare(strict_types=1);
$dir = sys_get_temp_dir() . '/codecart-events-' . getmypid();
if (!@mkdir($dir, 0700) && !is_dir($dir)) { throw new RuntimeException('temp directory failed'); }
define('DIR_LOGS', $dir . '/');
define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
class Action {
    private $fn;
    public function __construct(callable $fn) { $this->fn = $fn; }
    public function execute($registry, array $args = array()) { return ($this->fn)($args); }
    public function getId() { return 'test'; }
}
require DIR_SYSTEM . 'engine/event.php';
require DIR_SYSTEM . 'library/codecart/event_diagnostics.php';
function verify($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
try {
    $queue = array();
    $engine = new Event(new stdClass());
    $engine->register('catalog/a', new Action(function () use (&$queue) { $queue[] = 'last'; return null; }), 10, 101);
    $engine->register('catalog/a', new Action(function () use (&$queue) { $queue[] = 'first'; return null; }), 0, 102);
    $engine->trigger('catalog/a/b');
    verify($queue === array('first', 'last'), 'Event order changed');
    verify(!is_file(CodeCartEventDiagnostics::tracePath()), 'Tracing should default to OFF');
    verify(CodeCartEventDiagnostics::start(), 'Unable to enable trace');
    $engine2 = new Event(new stdClass());
    $engine2->register('before', new Action(function () { return null; }), 0, 201);
    $engine2->register('before', new Action(function () { return 'done'; }), 1, 202);
    $engine2->register('before', new Action(function () { throw new Exception('should never run'); }), 2, 203);
    verify($engine2->trigger('before/foo') === 'done', 'Early return changed');
    $engine2->register('throws', new Action(function () { throw new RuntimeException('private-data'); }), 0, 204);
    try { $engine2->trigger('throws/foo'); verify(false, 'Throwable swallowed'); }
    catch (RuntimeException $e) { verify($e->getMessage() === 'private-data', 'Throwable changed'); }
    $history = CodeCartEventDiagnostics::read();
    verify(($history[201]['calls'] ?? 0) === 1, 'Call not logged');
    verify(($history[203]['calls'] ?? 0) === 0, 'Non-executed event logged');
    verify(($history[204]['errors'] ?? 0) === 1, 'Error not logged');
    verify(strpos((string)file_get_contents(CodeCartEventDiagnostics::tracePath()), 'private-data') === false, 'Sensitive exception text logged');
    verify(CodeCartEventDiagnostics::stop(), 'Unable to stop trace');
    verify(CodeCartEventDiagnostics::expiration() === 0, 'Trace did not stop');
    echo "Events diagnostics smoke: OK\n";
} finally {
    @unlink(CodeCartEventDiagnostics::markerPath());
    @unlink(CodeCartEventDiagnostics::tracePath());
    @rmdir($dir);
}
