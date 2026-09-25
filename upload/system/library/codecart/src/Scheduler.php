<?php
namespace CodeCart\Core;

final class Scheduler {
    private $registry;
    private $db;
    private $log;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
    }

    public function register(string $code, string $route, int $interval, bool $status = true, array $args = array()): void {
        $code = $this->normalizeCode($code);
        $route = $this->normalizeRoute($route);
        $interval = $this->normalizeInterval($interval);
        $payload = $this->encodeArgs($args);

        $query = $this->db->query("SELECT scheduler_id FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code = '" . $this->db->escape($code) . "' LIMIT 1");
        if ($query->num_rows) {
            $this->save((int)$query->row['scheduler_id'], $code, $route, $interval, $status, $args);
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_scheduler` SET code = '" . $this->db->escape($code) . "', route = '" . $this->db->escape($route) . "', args = '" . $this->db->escape($payload) . "', interval_seconds = '" . (int)$interval . "', status = '" . (int)$status . "', date_next = NOW(), date_added = NOW(), date_modified = NOW()");
        }
    }

    public function save(int $schedulerId, string $code, string $route, int $interval, bool $status, array $args = array()): void {
        $schedulerId = max(1, $schedulerId);
        $existing = $this->getTask($schedulerId);
        if (!$existing) {
            throw new \RuntimeException('Scheduler task not found.');
        }

        $code = $this->normalizeCode($code);
        $route = $this->normalizeRoute($route);
        $interval = $this->normalizeInterval($interval);
        $payload = $this->encodeArgs($args);

        if (strpos((string)$existing['code'], 'core.') === 0 && $code !== (string)$existing['code']) {
            throw new \RuntimeException('Built-in scheduler task code cannot be renamed.');
        }

        $duplicate = $this->db->query("SELECT scheduler_id FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code = '" . $this->db->escape($code) . "' AND scheduler_id != '" . (int)$schedulerId . "' LIMIT 1");
        if ($duplicate->num_rows) {
            throw new \RuntimeException('Scheduler code already exists.');
        }

        $next = (!$existing['status'] && $status) ? "NOW()" : "DATE_ADD(NOW(), INTERVAL " . (int)$interval . " SECOND)";
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_scheduler` SET code = '" . $this->db->escape($code) . "', route = '" . $this->db->escape($route) . "', args = '" . $this->db->escape($payload) . "', interval_seconds = '" . (int)$interval . "', status = '" . (int)$status . "', date_next = " . $next . ", date_modified = NOW() WHERE scheduler_id = '" . (int)$schedulerId . "'");
    }

    public function unregister(string $code): void {
        $code = $this->normalizeCode($code);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code = '" . $this->db->escape($code) . "'");
    }

    public function deleteTask(int $schedulerId): void {
        $task = $this->getTask($schedulerId);
        if (!$task) {
            return;
        }
        if (strpos((string)$task['code'], 'core.') === 0) {
            throw new \RuntimeException('Built-in scheduler tasks can be disabled, but not deleted.');
        }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_scheduler` WHERE scheduler_id = '" . (int)$schedulerId . "'");
    }

    public function setStatus(int $schedulerId, bool $status): void {
        if (!$this->getTask($schedulerId)) {
            throw new \RuntimeException('Scheduler task not found.');
        }
        $next = $status ? ', date_next = NOW()' : '';
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_scheduler` SET status = '" . (int)$status . "'" . $next . ", date_modified = NOW() WHERE scheduler_id = '" . (int)$schedulerId . "'");
    }

    public function runDue(int $limit = 20): array {
        $limit = max(1, min(100, $limit));
        return $this->withLock(function () use ($limit) {
            $processed = 0;
            $failed = 0;
            $jobs = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_scheduler` WHERE status = '1' AND date_next <= NOW() ORDER BY date_next ASC, scheduler_id ASC LIMIT " . (int)$limit);
            foreach ($jobs->rows as $job) {
                $result = $this->execute($job);
                $processed++;
                if (!$result['success']) { $failed++; }
            }
            return array('status' => 'ok', 'processed' => $processed, 'failed' => $failed);
        }, array('status' => 'locked', 'processed' => 0, 'failed' => 0));
    }

    public function runOne(int $schedulerId): array {
        $task = $this->getTask($schedulerId);
        if (!$task) {
            throw new \RuntimeException('Scheduler task not found.');
        }
        return $this->withLock(function () use ($task) {
            return $this->execute($task);
        }, array('success' => false, 'status' => 'locked', 'message' => 'Scheduler is already running.', 'duration_ms' => 0));
    }

    public function getTask(int $schedulerId): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_scheduler` WHERE scheduler_id = '" . (int)$schedulerId . "' LIMIT 1");
        return $query->num_rows ? $query->row : array();
    }

    public function listTasks(): array {
        $query = $this->db->query("SELECT scheduler_id, code, route, args, interval_seconds, status, date_last, date_next, last_duration_ms, last_status, last_message, date_added, date_modified FROM `" . DB_PREFIX . "codecart_scheduler` ORDER BY code ASC");
        return $query->rows;
    }

    private function execute(array $job): array {
        $started = microtime(true);
        $ok = true;
        $message = 'OK';
        try {
            $route = $this->normalizeRoute((string)$job['route']);
            $args = json_decode((string)$job['args'], true);
            if (!is_array($args)) { $args = array(); }
            $result = $this->registry->get('load')->controller($route, $args);
            if (is_array($result) && array_key_exists('success', $result)) {
                $ok = !empty($result['success']);
                $message = isset($result['message']) && trim((string)$result['message']) !== '' ? trim((string)$result['message']) : ($ok ? 'OK' : 'Task failed without details');
            } elseif ($result === false) {
                $ok = false;
                $message = 'Handler returned false';
            }
        } catch (\Throwable $e) {
            $ok = false;
            $message = get_class($e) . ': ' . $e->getMessage();
            if ($this->log) {
                $this->log->write('CodeCart PRO scheduler [' . (string)$job['code'] . '] failed: ' . $message);
            }
        }

        $duration = (int)round((microtime(true) - $started) * 1000);
        $next = date('Y-m-d H:i:s', time() + $this->normalizeInterval((int)$job['interval_seconds']));
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_scheduler` SET date_last = NOW(), date_next = '" . $this->db->escape($next) . "', last_duration_ms = '" . (int)$duration . "', last_status = '" . ($ok ? 'success' : 'error') . "', last_message = '" . $this->db->escape(substr($message, 0, 1000)) . "', date_modified = NOW() WHERE scheduler_id = '" . (int)$job['scheduler_id'] . "'");

        try {
            $notice = new SystemNotification($this->registry);
            $noticeCode = 'scheduler.' . strtolower((string)$job['code']);
            if ($ok) { $notice->resolve($noticeCode); }
            else { $notice->notify($noticeCode, 'error', 'Cron task failed: ' . (string)$job['code'], substr($message,0,1000), 'tool/scheduler'); }
        } catch (\Throwable $e) {}

        return array('success' => $ok, 'status' => $ok ? 'success' : 'error', 'message' => $message, 'duration_ms' => $duration);
    }

    private function withLock(callable $callback, array $lockedResult): array {
        $lock = 'codecart_scheduler_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX), 0, 32);
        $got = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 0) AS acquired");
        if (empty($got->row['acquired'])) {
            return $lockedResult;
        }
        try {
            return $callback();
        } finally {
            try { $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')"); } catch (\Throwable $e) {}
        }
    }

    private function encodeArgs(array $args): string {
        $payload = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new \InvalidArgumentException('Invalid scheduler arguments.');
        }
        return $payload;
    }

    private function normalizeInterval(int $interval): int {
        return max(60, min(31536000, $interval));
    }

    private function normalizeCode(string $code): string {
        $code = trim($code);
        if ($code === '' || !preg_match('/^[a-z0-9_.:-]{2,128}$/i', $code)) {
            throw new \InvalidArgumentException('Invalid scheduler code.');
        }
        return $code;
    }

    private function normalizeRoute(string $route): string {
        $route = trim($route, '/');
        if ($route === '' || !preg_match('#^[a-z0-9_/]+$#i', $route)) {
            throw new \InvalidArgumentException('Invalid scheduler route.');
        }
        return $route;
    }
}
