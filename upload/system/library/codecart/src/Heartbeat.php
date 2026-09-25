<?php
namespace CodeCart\Core;

final class Heartbeat {
    private $registry;
    private $log;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->log = $registry->get('log');
    }

    public function run(int $interval = 300): void {
        if (PHP_SAPI === 'cli' || defined('CODECART_CLI') || !defined('DIR_STORAGE')) { return; }
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
        $route = isset($_GET['route']) ? trim((string)$_GET['route'], '/') : '';
        if ($script === 'cron.php' || $script === 'cli.php' || strpos($route, 'cron/') === 0) { return; }

        $directory = rtrim(DIR_STORAGE, '/\\') . '/codecart/runtime';
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) { return; }
        $state = $directory . '/heartbeat.state';
        $handle = @fopen($state, 'c+');
        if (!$handle) { return; }
        if (!@flock($handle, LOCK_EX | LOCK_NB)) { fclose($handle); return; }

        try {
            rewind($handle);
            $last = (int)trim((string)stream_get_contents($handle));
            $now = time();
            if ($last > 0 && ($now - $last) < max(60, $interval)) { return; }
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string)$now);
            fflush($handle);

            $scheduler = new Scheduler($this->registry);
            $scheduler->runDue(5);
        } catch (\Throwable $e) {
            if ($this->log) { $this->log->write('CodeCart PRO heartbeat failed: ' . $e->getMessage()); }
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
