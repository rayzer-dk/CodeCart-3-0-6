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

    /** Admin-only creation: never upsert or overwrite a pre-existing system task. */
    public function createTask(string $code, string $route, int $interval, bool $status, array $args = array()): void {
        $code = $this->normalizeCode($code);
        $route = $this->normalizeRoute($route);
        if (strpos($code, 'core.') === 0) {
            throw new \InvalidArgumentException('Built-in scheduler code is reserved.');
        }
        $interval = $this->normalizeInterval($interval);
        $payload = $this->encodeArgs($args);
        $duplicate = $this->db->query("SELECT scheduler_id FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code = '" . $this->db->escape($code) . "' OR (route = '" . $this->db->escape($route) . "' AND route != 'cron/codecart_external_http') LIMIT 1");
        if ($duplicate->num_rows) {
            throw new \InvalidArgumentException('Scheduler task code or handler is already registered.');
        }
        // The DB unique key on `code` also prevents simultaneous duplicate inserts.
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_scheduler` SET code = '" . $this->db->escape($code) . "', route = '" . $this->db->escape($route) . "', args = '" . $this->db->escape($payload) . "', interval_seconds = '" . (int)$interval . "', status = '" . (int)$status . "', date_next = NOW(), date_added = NOW(), date_modified = NOW()");
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

    public function runDue(int $limit = 20, string $source = 'visitor'): array {
        $limit = max(1, min(100, $limit));
        $result = $this->withLock(function () use ($limit, $source) {
            $processed = 0;
            $failed = 0;
            $jobs = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_scheduler` WHERE status = '1' AND date_next <= NOW() ORDER BY date_next ASC, scheduler_id ASC LIMIT " . (int)$limit);
            foreach ($jobs->rows as $job) {
                $result = $this->execute($job, $source);
                $processed++;
                if (!$result['success']) { $failed++; }
            }
            return array('status' => $failed > 0 ? 'error' : 'ok', 'processed' => $processed, 'failed' => $failed);
        }, array('status' => 'locked', 'processed' => 0, 'failed' => 0));
        if (($result['status'] ?? '') !== 'locked' && in_array($source, array('cli','web','visitor'), true)) {
            $this->recordHeartbeat($source, $result);
        }
        return $result;
    }

    public function runOne(int $schedulerId): array {
        $task = $this->getTask($schedulerId);
        if (!$task) {
            throw new \RuntimeException('Scheduler task not found.');
        }
        return $this->withLock(function () use ($task) {
            return $this->execute($task, 'manual');
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

    private function execute(array $job, string $source = 'manual'): array {
        $started = microtime(true);
        $ok = true;
        $message = 'OK';
        try {
            $route = $this->normalizeRoute((string)$job['route']);
            // Repair a legacy built-in route stored as just "cron": Action would
            // otherwise try to load a non-existent controller/cron.php and throw
            // "Could not call /cron!". Never reinterpret custom task routes.
            if ($route === 'cron') {
                $builtin_routes = array(
                    'core.stock.notify' => 'cron/stock_notify',
                    'core.queue.worker' => 'cron/queue_worker',
                    'core.mail.campaign' => 'cron/mail_campaign',
                    'core.cart.reminder' => 'cron/cart_reminder'
                );
                $code = (string)$job['code'];
                if (!isset($builtin_routes[$code])) {
                    throw new \RuntimeException('Invalid scheduler route: cron (a controller action is required).');
                }
                $route = $builtin_routes[$code];
                $this->db->query("UPDATE `" . DB_PREFIX . "codecart_scheduler` SET route = '" . $this->db->escape($route) . "', date_modified = NOW() WHERE scheduler_id = '" . (int)$job['scheduler_id'] . "' AND route = 'cron'");
            }
            $args = json_decode((string)$job['args'], true);
            if (!is_array($args)) { $args = array(); }
            if ($route === 'cron/codecart_external_http') {
                $args['_codecart_external_dispatch'] = hash_hmac('sha256', (string)($args['url'] ?? ''), (string)$this->registry->get('config')->get('codecart_scheduler_key'));
            }
            $result = $this->registry->get('load')->controller($route, $args, true);
            if (is_array($result) && array_key_exists('success', $result)) {
                $ok = !empty($result['success']);
                $message = isset($result['message']) && trim((string)$result['message']) !== '' ? trim((string)$result['message']) : ($ok ? 'OK' : 'Task failed without details');
            } elseif (is_array($result) && isset($result['status'])) {
                $ok = in_array((string)$result['status'], array('ok','success','skipped'),true) && (int)($result['failed'] ?? 0) === 0;
                $message = isset($result['message']) ? (string)$result['message'] : 'status=' . (string)$result['status'] . ', processed=' . (int)($result['processed'] ?? 0) . ', failed=' . (int)($result['failed'] ?? 0);
            } elseif (is_array($result) && array_key_exists('failed', $result)) {
                $ok = (int)$result['failed'] === 0;
                $message = 'processed=' . (int)($result['processed'] ?? 0) . ', failed=' . (int)$result['failed'];
            } elseif ($result === true) {
                $message = 'Handler completed.';
            } elseif (is_array($result) && !empty($result)) {
                // Legitimate legacy cron/cart counters; do not report an empty execution.
                $message = json_encode($result, JSON_UNESCAPED_SLASHES);
            } else {
                $ok = false;
                $message = 'Handler returned no execution result.';
            }
            if (is_array($result) && (int)($result['failed'] ?? 0) > 0) {
                $ok = false;
                $message .= ' (' . (int)$result['failed'] . ' failed)';
            }
        } catch (\Throwable $e) {
            $ok = false;
            $message = get_class($e) . ': ' . $e->getMessage();
            if ($this->log) {
                $this->log->write('CodeCart PRO scheduler [' . (string)$job['code'] . '] failed: ' . $message);
            }
        }

        $duration = (int)round((microtime(true) - $started) * 1000);
        $nextSeconds = $this->normalizeInterval((int)$job['interval_seconds']);
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_scheduler` SET date_last = NOW(), date_next = DATE_ADD(NOW(), INTERVAL " . (int)$nextSeconds . " SECOND), last_duration_ms = '" . (int)$duration . "', last_status = '" . ($ok ? 'success' : 'error') . "', last_message = '" . $this->db->escape($this->scrubDiagnostic($message)) . "', date_modified = NOW() WHERE scheduler_id = '" . (int)$job['scheduler_id'] . "'");

        try {
            $notice = new SystemNotification($this->registry);
            $noticeCode = 'scheduler.' . strtolower((string)$job['code']);
            if ($ok) { $notice->resolve($noticeCode); }
            else { $notice->notify($noticeCode, 'error', 'Cron task failed: ' . (string)$job['code'], substr($message,0,1000), 'tool/scheduler'); }
        } catch (\Throwable $e) {}

        $this->recordHistory($job, $source, $ok, $duration, $message);
        return array('success' => $ok, 'status' => $ok ? 'success' : 'error', 'message' => $message, 'duration_ms' => $duration);
    }

    /** History and heartbeat are observational only; never turn a finished task into a retry. */
    private function recordHistory(array $job, string $source, bool $ok, int $duration, string $message): void {
        try {
            $safeMessage = $this->scrubDiagnostic($message);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_scheduler_history` SET scheduler_id = '" . (int)$job['scheduler_id'] . "', task_code = '" . $this->db->escape((string)$job['code']) . "', source = '" . $this->db->escape($source) . "', result = '" . ($ok ? 'success' : 'error') . "', duration_ms = '" . max(0, $duration) . "', message = '" . $this->db->escape($safeMessage) . "', date_added = NOW()");
            // Keep the history bounded without a maintenance job running on every page.
            if (random_int(1, 50) === 1) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_scheduler_history` WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            }
        } catch (\Throwable $e) {
            // During rolling deployment the new history table may not exist yet.
        }
    }

    private function scrubDiagnostic(string $message): string {
        $message = preg_replace('/(X-ACP-Cron-Key\s*:\s*)[^\s,;]+/i', '$1[hidden]', $message);
        $message = preg_replace('/([?&](?:key|token|password|secret|auth)=)[^\s&]+/i', '$1[hidden]', $message);
        return substr((string)$message, 0, 1000);
    }

    private function recordHeartbeat(string $source, array $result): void {
        try {
            $status = (string)($result['status'] ?? 'error');
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_scheduler_heartbeat` SET source = '" . $this->db->escape($source) . "', date_last = NOW(), last_status = '" . $this->db->escape($status) . "', processed = '" . (int)($result['processed'] ?? 0) . "', failed = '" . (int)($result['failed'] ?? 0) . "' ON DUPLICATE KEY UPDATE date_last = NOW(), last_status = VALUES(last_status), processed = VALUES(processed), failed = VALUES(failed)");
        } catch (\Throwable $e) {
            // The scheduler is functional even before the observability migration.
        }
    }

    public function hostHeartbeat(): array {
        try {
            $q = $this->db->query("SELECT source, date_last, last_status, processed, failed, TIMESTAMPDIFF(MINUTE, date_last, NOW()) AS age_minutes FROM `" . DB_PREFIX . "codecart_scheduler_heartbeat` WHERE source IN ('cli', 'web') ORDER BY date_last DESC LIMIT 1");
            return $q->num_rows ? $q->row : array();
        } catch (\Throwable $e) { return array(); }
    }

    public function historyCount(int $taskId = 0): int {
        $where = $taskId > 0 ? " WHERE scheduler_id = '" . $taskId . "'" : '';
        try {
            $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_scheduler_history`" . $where);
            return (int)($q->row['total'] ?? 0);
        } catch (\Throwable $e) { return 0; }
    }

    public function history(int $page = 1, int $limit = 25, int $taskId = 0): array {
        $page = max(1, $page); $limit = max(10, min(100, $limit));
        $offset = ($page - 1) * $limit;
        $where = $taskId > 0 ? " WHERE scheduler_id = '" . $taskId . "'" : '';
        try {
            $q = $this->db->query("SELECT task_code, source, result, duration_ms, message, date_added FROM `" . DB_PREFIX . "codecart_scheduler_history`" . $where . " ORDER BY history_id DESC LIMIT " . (int)$offset . "," . (int)$limit);
            return $q->rows;
        } catch (\Throwable $e) { return array(); }
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
