<?php
namespace CodeCart\Core;

final class Heartbeat {
    private $registry;
    private $log;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->log = $registry->get('log');
    }

    /**
     * True when the SAPI can hand the finished response to the visitor before
     * shutdown work starts (PHP-FPM or LiteSpeed LSAPI).
     */
    public static function canFinishRequest(): bool {
        return function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
    }

    public static function finishRequest(): void {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        }
    }

    /**
     * Marks that a scheduler run started from a loopback request. Used to detect
     * hosts where loopback HTTP requests are blocked, so the heartbeat can fall
     * back to inline execution instead of silently never running tasks.
     */
    public static function markLoopbackRun(): void {
        if (!defined('DIR_STORAGE')) { return; }
        $directory = rtrim(DIR_STORAGE, '/\\') . '/codecart/runtime';
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) { return; }
        @file_put_contents($directory . '/heartbeat.loopback.ok', (string)time(), LOCK_EX);
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

            // On mod_php/CGI the visitor would wait for scheduled work (currency
            // providers, SMTP queue, ...). Hand the work to a detached loopback
            // request instead; fall back to inline execution when loopback is
            // unavailable or was detected as blocked on this host.
            if (!self::canFinishRequest() && $this->spawnLoopback($directory)) {
                return;
            }

            $scheduler = new Scheduler($this->registry);
            $scheduler->runDue(5);
        } catch (\Throwable $e) {
            if ($this->log) { $this->log->write('CodeCart PRO heartbeat failed: ' . $e->getMessage()); }
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function spawnLoopback(string $directory): bool {
        $config = $this->registry->get('config');
        $key = $config ? trim((string)$config->get('codecart_scheduler_key')) : '';
        if ($key === '' || (string)$config->get('codecart_heartbeat_loopback') === '0') { return false; }

        $spawned = $directory . '/heartbeat.loopback';
        $ok = $directory . '/heartbeat.loopback.ok';
        $blocked = $directory . '/heartbeat.loopback.blocked';

        // A previous loopback that never reached the scheduler means the host blocks
        // loopback requests. Use inline execution for 24 hours before trying again.
        if (is_file($blocked) && (time() - (int)@filemtime($blocked)) < 86400) { return false; }
        $lastSpawn = is_file($spawned) ? (int)trim((string)@file_get_contents($spawned)) : 0;
        $lastOk = is_file($ok) ? (int)trim((string)@file_get_contents($ok)) : 0;
        if ($lastSpawn > 0 && $lastOk < $lastSpawn && (time() - $lastSpawn) > 120) {
            @touch($blocked);
            @unlink($spawned);
            if ($this->log) { $this->log->write('CodeCart PRO heartbeat: loopback cron request did not run; using inline scheduler for 24 hours. Configure a server cron for best performance.'); }
            return false;
        }

        $base = '';
        if (function_exists('codecart_is_https') && codecart_is_https() && defined('HTTPS_SERVER')) {
            $base = (string)HTTPS_SERVER;
        } elseif (defined('HTTP_SERVER')) {
            $base = (string)HTTP_SERVER;
        }
        $parts = parse_url($base);
        if (empty($parts['host'])) { return false; }

        $secure = isset($parts['scheme']) && strtolower($parts['scheme']) === 'https';
        $port = isset($parts['port']) ? (int)$parts['port'] : ($secure ? 443 : 80);
        $path = rtrim(isset($parts['path']) ? (string)$parts['path'] : '/', '/') . '/index.php?route=cron/codecart&mode=scheduler&heartbeat=1&key=' . rawurlencode($key);
        $hostHeader = $parts['host'] . (isset($parts['port']) ? ':' . (int)$parts['port'] : '');

        $context = stream_context_create(array('ssl' => array('verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true, 'peer_name' => $parts['host'])));
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(($secure ? 'ssl://' : 'tcp://') . $parts['host'] . ':' . $port, $errno, $errstr, 2, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            @touch($blocked);
            return false;
        }

        stream_set_timeout($socket, 1);
        $request = "GET " . $path . " HTTP/1.1\r\nHost: " . $hostHeader . "\r\nUser-Agent: CodeCart-Heartbeat\r\nConnection: close\r\n\r\n";
        $written = @fwrite($socket, $request);
        @fflush($socket);
        @fclose($socket);

        if ($written !== strlen($request)) {
            return false;
        }

        @file_put_contents($spawned, (string)time(), LOCK_EX);
        return true;
    }
}
