<?php
namespace CodeCart\Core;

final class Queue {
    private $registry;
    private $db;
    private $log;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->log = $registry->get('log');
    }

    public function enqueue(string $code, string $route, array $args = array(), int $priority = 100, ?string $availableAt = null, int $maxAttempts = 3): int {
        if (!preg_match('/^[a-z0-9_.:-]{2,128}$/i', $code)) { throw new \InvalidArgumentException('Invalid queue code.'); }
        $route = trim($route, '/');
        if (!preg_match('#^[a-z0-9_/]+$#i', $route)) { throw new \InvalidArgumentException('Invalid queue route.'); }
        $payload = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) { throw new \InvalidArgumentException('Queue arguments are not JSON serializable.'); }
        $availableAt = $availableAt ?: date('Y-m-d H:i:s');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_queue` SET code = '" . $this->db->escape($code) . "', route = '" . $this->db->escape($route) . "', args = '" . $this->db->escape($payload) . "', priority = '" . (int)$priority . "', status = 'pending', attempts = '0', max_attempts = '" . max(1, min(20, $maxAttempts)) . "', available_at = '" . $this->db->escape($availableAt) . "', date_added = NOW(), date_modified = NOW()");
        return (int)$this->db->getLastId();
    }

    public function run(int $limit = 25): array {
        $limit = max(1, min(100, $limit));
        $processed = 0;
        $failed = 0;
        $this->recoverStale();

        for ($i = 0; $i < $limit; $i++) {
            $job = $this->claim();
            if (!$job) { break; }

            $result = $this->executeClaimedJob($job);
            $processed += (int)$result['processed'];
            $failed += (int)$result['failed'];
        }

        return array('status' => 'ok', 'processed' => $processed, 'failed' => $failed);
    }

    public function runOne(int $queueId, bool $retryFailed = false): array {
        if ($queueId < 1) {
            throw new \InvalidArgumentException('Invalid queue id.');
        }

        $this->recoverStale();

        if ($retryFailed) {
            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = 'pending', attempts = '0', available_at = NOW(), locked_at = NULL, locked_by = NULL, last_error = NULL, date_modified = NOW() WHERE queue_id = '" . (int)$queueId . "' AND status = 'failed'");
        } else {
            // An explicit per-row "Run now" action must not be blocked by a future
            // retry/backoff timestamp. Normal workers still respect available_at.
            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET available_at = NOW(), date_modified = NOW() WHERE queue_id = '" . (int)$queueId . "' AND status = 'pending'");
        }

        $job = $this->claimOne($queueId);
        if (!$job) {
            return array('status' => 'skipped', 'processed' => 0, 'failed' => 0, 'queue_id' => $queueId, 'message' => 'Queue job is not pending or is not available yet.');
        }

        return $this->executeClaimedJob($job);
    }

    public function deleteById(int $queueId): bool {
        if ($queueId < 1) {
            throw new \InvalidArgumentException('Invalid queue id.');
        }

        $query = $this->db->query("SELECT status FROM `" . DB_PREFIX . "codecart_queue` WHERE queue_id = '" . (int)$queueId . "' LIMIT 1");
        if (!$query->num_rows) {
            return false;
        }
        if ((string)$query->row['status'] === 'processing') {
            throw new \RuntimeException('A processing queue job cannot be deleted.');
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_queue` WHERE queue_id = '" . (int)$queueId . "' AND status <> 'processing'");
        return !method_exists($this->db, 'countAffected') || (int)$this->db->countAffected() > 0;
    }

    public function purgeCompleted(int $olderThanHours = 24): int {
        $olderThanHours = max(1, min(8760, $olderThanHours));
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_queue` WHERE status = 'done' AND date_modified < DATE_SUB(NOW(), INTERVAL " . (int)$olderThanHours . " HOUR)");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    public function stats(): array {
        $query = $this->db->query("SELECT status, COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` GROUP BY status");
        $result = array('pending' => 0, 'processing' => 0, 'done' => 0, 'failed' => 0);
        foreach ($query->rows as $row) {
            $result[(string)$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    private function executeClaimedJob(array $job): array {
        $queueId = (int)$job['queue_id'];
        $lock = $this->jobLockName($queueId);

        if (!$this->acquireJobLock($lock)) {
            $this->releaseClaim($queueId, 'Unable to acquire worker lock');
            return array('status' => 'skipped', 'processed' => 0, 'failed' => 0, 'queue_id' => $queueId, 'code' => isset($job['code']) ? (string)$job['code'] : '', 'message' => 'Unable to acquire worker lock.');
        }

        $ok = true;
        $message = 'OK';
        try {
            $args = json_decode((string)$job['args'], true);
            if (!is_array($args)) { $args = array(); }
            $result = $this->registry->get('load')->controller((string)$job['route'], $args);
            if ($result === false) { throw new \RuntimeException('Handler returned false'); }
        } catch (\Throwable $e) {
            $ok = false;
            $message = get_class($e) . ': ' . $e->getMessage();
            if ($this->log) { $this->log->write('CodeCart PRO queue #' . $queueId . ' [' . (string)$job['code'] . '] failed: ' . $message); }
        } finally {
            $this->releaseJobLock($lock);
        }

        $attempts = (int)$job['attempts'];
        if ($ok) {
            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = 'done', attempts = '" . $attempts . "', locked_at = NULL, locked_by = NULL, last_error = NULL, date_modified = NOW() WHERE queue_id = '" . $queueId . "'");
        } else {
            $final = $attempts >= (int)$job['max_attempts'];
            $delay = min(3600, 30 * (2 ** max(0, $attempts - 1)));
            $available = date('Y-m-d H:i:s', time() + $delay);
            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = '" . ($final ? 'failed' : 'pending') . "', attempts = '" . $attempts . "', available_at = '" . $this->db->escape($available) . "', locked_at = NULL, locked_by = NULL, last_error = '" . $this->db->escape(substr($message, 0, 2000)) . "', date_modified = NOW() WHERE queue_id = '" . $queueId . "'");
            if ($final) {
                try { (new SystemNotification($this->registry))->notify('queue.' . $queueId, 'error', 'Queue task failed: ' . (string)$job['code'], substr($message, 0, 1200), 'tool/scheduler'); } catch (\Throwable $e) {}
            }
        }

        return array('status' => $ok ? 'ok' : 'error', 'processed' => 1, 'failed' => $ok ? 0 : 1, 'queue_id' => $queueId, 'code' => isset($job['code']) ? (string)$job['code'] : '', 'message' => $message);
    }

    private function recoverStale(): void {
        $query = $this->db->query("SELECT queue_id, attempts, max_attempts FROM `" . DB_PREFIX . "codecart_queue` WHERE status = 'processing' AND locked_at IS NOT NULL AND locked_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY queue_id ASC LIMIT 100");

        foreach ($query->rows as $row) {
            $queueId = (int)$row['queue_id'];
            $lock = $this->jobLockName($queueId);

            if ($this->isJobLockActive($lock)) {
                continue;
            }

            $final = (int)$row['attempts'] >= (int)$row['max_attempts'];
            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = '" . ($final ? 'failed' : 'pending') . "', locked_at = NULL, locked_by = NULL, last_error = " . ($final ? "'Worker stopped before completing the task'" : "last_error") . ", available_at = NOW(), date_modified = NOW() WHERE queue_id = '" . $queueId . "' AND status = 'processing'");
        }
    }

    private function claim(): array {
        $worker = substr((string)gethostname() . ':' . (string)getmypid() . ':' . bin2hex(random_bytes(8)), 0, 96);

        // UPDATE ... ORDER BY ... LIMIT is a short atomic claim supported by the
        // MySQL/MariaDB versions targeted by this build. It avoids keeping a
        // transaction open while workers compete for the same first pending row.
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = 'processing', attempts = attempts + 1, locked_at = NOW(), locked_by = '" . $this->db->escape($worker) . "', date_modified = NOW() WHERE status = 'pending' AND available_at <= NOW() ORDER BY priority ASC, queue_id ASC LIMIT 1");

        if (method_exists($this->db, 'countAffected') && (int)$this->db->countAffected() < 1) {
            return array();
        }

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_queue` WHERE status = 'processing' AND locked_by = '" . $this->db->escape($worker) . "' ORDER BY queue_id ASC LIMIT 1");
        return $query->num_rows ? $query->row : array();
    }

    private function claimOne(int $queueId): array {
        $worker = substr((string)gethostname() . ':' . (string)getmypid() . ':' . bin2hex(random_bytes(8)), 0, 96);
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = 'processing', attempts = attempts + 1, locked_at = NOW(), locked_by = '" . $this->db->escape($worker) . "', date_modified = NOW() WHERE queue_id = '" . (int)$queueId . "' AND status = 'pending' AND available_at <= NOW() LIMIT 1");
        if (method_exists($this->db, 'countAffected') && (int)$this->db->countAffected() < 1) { return array(); }
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_queue` WHERE queue_id = '" . (int)$queueId . "' AND status = 'processing' AND locked_by = '" . $this->db->escape($worker) . "' LIMIT 1");
        return $query->num_rows ? $query->row : array();
    }

    private function releaseClaim(int $queueId, string $message): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_queue` SET status = 'pending', available_at = DATE_ADD(NOW(), INTERVAL 5 SECOND), locked_at = NULL, locked_by = NULL, last_error = '" . $this->db->escape(substr($message, 0, 2000)) . "', date_modified = NOW() WHERE queue_id = '" . $queueId . "' AND status = 'processing'");
    }

    private function jobLockName(int $queueId): string {
        return 'ccq_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX), 0, 16) . '_' . $queueId;
    }

    private function acquireJobLock(string $lock): bool {
        try {
            $query = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 0) AS acquired");
            return !empty($query->row['acquired']);
        } catch (\Throwable $e) {
            if ($this->log) { $this->log->write('CodeCart PRO queue lock error: ' . $e->getMessage()); }
            return false;
        }
    }

    private function releaseJobLock(string $lock): void {
        try {
            $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')");
        } catch (\Throwable $e) {
            // Advisory locks are released automatically when the DB connection closes.
        }
    }

    private function isJobLockActive(string $lock): bool {
        try {
            $query = $this->db->query("SELECT IS_USED_LOCK('" . $this->db->escape($lock) . "') AS owner");
            return isset($query->row['owner']) && $query->row['owner'] !== null;
        } catch (\Throwable $e) {
            // Recovery must be conservative if the lock state cannot be established.
            if ($this->log) { $this->log->write('CodeCart PRO queue stale-check lock error: ' . $e->getMessage()); }
            return true;
        }
    }
}
