<?php
namespace CodeCart\Core;

/**
 * Durable idempotency guard for commerce and callback operations.
 *
 * Rows are identified by scope + SHA-256(key). A short-lived owner token
 * prevents an old/stalled worker from completing a row after another worker
 * has safely taken ownership. Failed rows may be retried; stale processing
 * rows may be reclaimed after staleAfter seconds.
 */
final class Idempotency {
    private $db;
    private $owners = array();

    public function __construct($registry) {
        $this->db = $registry->get('db');
    }

    public function begin(string $scope, string $key, string $fingerprint = '', int $ttl = 86400, int $staleAfter = 300): array {
        $scope = $this->clean($scope, 96);
        $key = $this->clean($key, 160);
        if ($scope === '' || $key === '') {
            throw new \InvalidArgumentException('Idempotency scope/key required.');
        }

        $ttl = max(60, min(604800, $ttl));
        $staleAfter = max(30, min(86400, $staleAfter));
        $hash = hash('sha256', $key);
        $fp = $fingerprint !== '' ? hash('sha256', $fingerprint) : '';
        $owner = bin2hex(random_bytes(16));
        $ownerKey = $scope . '|' . $hash;

        try {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_idempotency` SET scope='" . $this->db->escape($scope) . "', idempotency_key='" . $this->db->escape($hash) . "', fingerprint='" . $this->db->escape($fp) . "', owner_token='" . $this->db->escape($owner) . "', attempt_count='1', status='processing', result='', date_added=NOW(), date_modified=NOW(), date_expire=DATE_ADD(NOW(),INTERVAL " . (int)$ttl . " SECOND)");
            $this->owners[$ownerKey] = $owner;
            return array('acquired' => true, 'status' => 'processing', 'result' => null, 'owner_token' => $owner, 'attempt_count' => 1);
        } catch (\Throwable $insertError) {
            $query = $this->db->query("SELECT status,fingerprint,result,owner_token,attempt_count,date_modified,date_expire FROM `" . DB_PREFIX . "codecart_idempotency` WHERE scope='" . $this->db->escape($scope) . "' AND idempotency_key='" . $this->db->escape($hash) . "' LIMIT 1");
            if (!$query->num_rows) {
                throw $insertError;
            }

            $row = $query->row;
            if ($fp !== '' && (string)$row['fingerprint'] !== '' && !hash_equals((string)$row['fingerprint'], $fp)) {
                throw new \RuntimeException('Idempotency key reused with different request.');
            }

            $status = (string)$row['status'];
            if ($status === 'completed') {
                return array('acquired' => false, 'status' => 'completed', 'result' => $this->decode((string)$row['result']), 'owner_token' => '', 'attempt_count' => (int)$row['attempt_count']);
            }

            $canRetry = $status === 'failed';
            if ($status === 'processing') {
                $modified = strtotime((string)$row['date_modified']);
                $canRetry = $modified !== false && $modified <= (time() - $staleAfter);
            }

            if ($canRetry) {
                $condition = $status === 'failed'
                    ? "status='failed'"
                    : "status='processing' AND date_modified <= DATE_SUB(NOW(),INTERVAL " . (int)$staleAfter . " SECOND)";

                $this->db->query("UPDATE `" . DB_PREFIX . "codecart_idempotency` SET status='processing', owner_token='" . $this->db->escape($owner) . "', result='', attempt_count=attempt_count+1, date_modified=NOW(), date_expire=DATE_ADD(NOW(),INTERVAL " . (int)$ttl . " SECOND) WHERE scope='" . $this->db->escape($scope) . "' AND idempotency_key='" . $this->db->escape($hash) . "' AND " . $condition);

                $owned = $this->db->query("SELECT owner_token,attempt_count FROM `" . DB_PREFIX . "codecart_idempotency` WHERE scope='" . $this->db->escape($scope) . "' AND idempotency_key='" . $this->db->escape($hash) . "' LIMIT 1");
                if ($owned->num_rows && hash_equals((string)$owned->row['owner_token'], $owner)) {
                    $this->owners[$ownerKey] = $owner;
                    return array('acquired' => true, 'status' => 'processing', 'result' => null, 'owner_token' => $owner, 'attempt_count' => (int)$owned->row['attempt_count']);
                }
            }

            return array('acquired' => false, 'status' => $status, 'result' => $this->decode((string)$row['result']), 'owner_token' => '', 'attempt_count' => (int)$row['attempt_count']);
        }
    }

    public function complete(string $scope, string $key, $result = null, string $ownerToken = ''): bool {
        return $this->finish($scope, $key, 'completed', $result, $ownerToken);
    }

    public function fail(string $scope, string $key, $result = null, string $ownerToken = ''): bool {
        return $this->finish($scope, $key, 'failed', $result, $ownerToken);
    }

    public function get(string $scope, string $key): ?array {
        $scope = $this->clean($scope, 96);
        $key = $this->clean($key, 160);
        if ($scope === '' || $key === '') {
            return null;
        }
        $hash = hash('sha256', $key);
        $query = $this->db->query("SELECT status,result,attempt_count,date_modified,date_expire FROM `" . DB_PREFIX . "codecart_idempotency` WHERE scope='" . $this->db->escape($scope) . "' AND idempotency_key='" . $this->db->escape($hash) . "' LIMIT 1");
        if (!$query->num_rows) {
            return null;
        }
        return array(
            'status' => (string)$query->row['status'],
            'result' => $this->decode((string)$query->row['result']),
            'attempt_count' => (int)$query->row['attempt_count'],
            'date_modified' => (string)$query->row['date_modified'],
            'date_expire' => (string)$query->row['date_expire']
        );
    }

    public function purge(int $limit = 1000): int {
        $limit = max(1, min(10000, $limit));
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_idempotency` WHERE date_expire<NOW() LIMIT " . (int)$limit);
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    private function finish(string $scope, string $key, string $status, $result, string $ownerToken): bool {
        $scope = $this->clean($scope, 96);
        $key = $this->clean($key, 160);
        if ($scope === '' || $key === '') {
            return false;
        }

        $hash = hash('sha256', $key);
        $ownerKey = $scope . '|' . $hash;
        if ($ownerToken === '' && isset($this->owners[$ownerKey])) {
            $ownerToken = $this->owners[$ownerKey];
        }

        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = 'null';
        }

        $sql = "UPDATE `" . DB_PREFIX . "codecart_idempotency` SET status='" . $this->db->escape($status) . "', result='" . $this->db->escape($json) . "', date_modified=NOW() WHERE scope='" . $this->db->escape($scope) . "' AND idempotency_key='" . $this->db->escape($hash) . "' AND status='processing'";
        if ($ownerToken !== '') {
            $sql .= " AND owner_token='" . $this->db->escape($ownerToken) . "'";
        }
        $this->db->query($sql);

        if (isset($this->owners[$ownerKey])) {
            unset($this->owners[$ownerKey]);
        }

        if (method_exists($this->db, 'countAffected')) {
            return (int)$this->db->countAffected() > 0;
        }
        return true;
    }

    private function decode(string $value) {
        if ($value === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function clean(string $value, int $length): string {
        return substr(trim($value), 0, $length);
    }
}
