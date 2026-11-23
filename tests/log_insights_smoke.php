<?php
declare(strict_types=1);
/** Repeat counts, timestamp grouping, multi-line stack traces, cache expiry on clear. */
$dir = sys_get_temp_dir() . '/cc-log-insights-' . getmypid();
if (!@mkdir($dir, 0700) && !is_dir($dir)) { throw new RuntimeException('Temporary directory unavailable'); }
define('DIR_LOGS', $dir . '/');
define('DIR_CACHE', $dir . '/');
require dirname(__DIR__) . '/upload/system/library/codecart/src/LogInsights.php';
use CodeCart\Core\LogInsights;
$config = new class {
    public function get($key) { return $key === 'config_error_filename' ? 'error.log' : ''; }
};
function verify($yes, $message) { if (!$yes) { throw new RuntimeException($message); } }
try {
    $log = "2026-11-22 10:20:01 - PHP Warning: Undefined variable \$x in /site/a.php on line 21\n"
         . "2026-11-22 10:20:03 - PHP Warning: Undefined variable \$x in /site/a.php on line 21\n"
         . "2026-11-22 10:20:05 - PHP Warning: Undefined variable \$x in /site/a.php on line 22\n"
         . "2026-11-22 10:20:06 - PHP Fatal error: Uncaught RuntimeException: sample\n"
         . "Stack trace:\n#0 /site/index.php(3): run()\n#1 {main}\n"
         . "2026-11-22 10:20:09 - PHP Fatal error: Uncaught RuntimeException: sample\n"
         . "Stack trace:\n#0 /site/index.php(3): run()\n#1 {main}\n";
    file_put_contents(LogInsights::path($config), $log);
    $original = file_get_contents(LogInsights::path($config));
    $rows = LogInsights::group(LogInsights::tail(LogInsights::path($config)));
    verify(count($rows) === 3, 'Expected three distinct errors');
    verify(array_sum(array_column($rows, 'count')) === 5, 'Repeat totals mismatch');
    verify($rows[0]['count'] === 2 && strpos($rows[0]['text'], 'Stack trace') !== false, 'Stack trace grouping broken');
    verify($rows[1]['count'] === 1 && strpos($rows[1]['text'], 'line 22') !== false, 'Different line number was merged');
    verify($rows[2]['count'] === 2, 'Timestamp normalization broken');
    verify(file_get_contents(LogInsights::path($config)) === $original, 'Grouping changed original log');
    verify(LogInsights::cachedCount($config) === 3, 'Header count mismatch');
    // Clearing the real log and invalidating count must not suppress a later identical error.
    file_put_contents(LogInsights::path($config), '');
    LogInsights::invalidate($config);
    verify(LogInsights::cachedCount($config) === 0, 'Cache remained after clear');
    file_put_contents(LogInsights::path($config), "2026-11-23 11:00:00 - PHP Warning: Undefined variable \$x in /site/a.php on line 21\n");
    verify(count(LogInsights::group(LogInsights::tail(LogInsights::path($config)))) === 1, 'Repeated old error not counted after clear');
    LogInsights::invalidate($config);
    verify(LogInsights::cachedCount($config) === 1, 'Header did not recount after new error');
    $large = str_repeat("2026-11-23 11:00:00 - PHP Notice: sample\n", 35000);
    file_put_contents(LogInsights::path($config), $large);
    verify(strlen(LogInsights::tail(LogInsights::path($config))) <= 1048576, 'Unbounded log read');
    echo "Log insights smoke: OK\n";
} finally {
    LogInsights::invalidate($config);
    foreach (glob($dir . '/*') ?: array() as $file) { @unlink($file); }
    @rmdir($dir);
}
