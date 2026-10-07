<?php
namespace CodeCart\Core;

final class LoginProtection {
    private $registry;
    private $db;
    private $config;
    private $request;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->request = $registry->get('request');
    }

    public function enabled(): bool {
        $value = $this->config->get('codecart_admin_login_protection_status');
        return $value === null || $value === '' ? true : (bool)$value;
    }

    public function isBlocked(string $username): bool {
        if (!$this->enabled() || !$this->tableExists()) {
            return false;
        }

        $username = $this->normalizeUsername($username);
        $ip = $this->clientIp();
        if ($username === '' || $ip === '') {
            return false;
        }

        $window = $this->windowMinutes();
        $limit = $this->maxAttempts();

        $pair = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_admin_login_attempt` WHERE username = '" . $this->db->escape($username) . "' AND ip = '" . $this->db->escape($ip) . "' AND date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$window . " MINUTE)");
        if ((int)$pair->row['total'] >= $limit) {
            return true;
        }

        // Limit one source attacking many usernames without allowing another IP to
        // lock a legitimate administrator account globally.
        $ipLimit = max($limit * 3, 12);
        $source = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_admin_login_attempt` WHERE ip = '" . $this->db->escape($ip) . "' AND date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$window . " MINUTE)");
        return (int)$source->row['total'] >= $ipLimit;
    }

    public function recordFailure(string $username): void {
        if (!$this->enabled() || !$this->tableExists()) {
            return;
        }
        $username = $this->normalizeUsername($username);
        $ip = $this->clientIp();
        if ($username === '' || $ip === '') {
            return;
        }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_admin_login_attempt` SET username = '" . $this->db->escape($username) . "', ip = '" . $this->db->escape($ip) . "', date_added = NOW()");
    }

    public function recordSuccess(string $username): void {
        if (!$this->tableExists()) {
            return;
        }
        $username = $this->normalizeUsername($username);
        $ip = $this->clientIp();
        if ($username === '' || $ip === '') {
            return;
        }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_admin_login_attempt` WHERE username = '" . $this->db->escape($username) . "' AND ip = '" . $this->db->escape($ip) . "'");
    }

    public function attemptsForCurrentSource(string $username): int {
        if (!$this->tableExists()) {
            return 0;
        }
        $username = $this->normalizeUsername($username);
        $ip = $this->clientIp();
        if ($username === '' || $ip === '') {
            return 0;
        }
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_admin_login_attempt` WHERE username = '" . $this->db->escape($username) . "' AND ip = '" . $this->db->escape($ip) . "' AND date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$this->windowMinutes() . " MINUTE)");
        return (int)$query->row['total'];
    }

    public function cleanup(int $days = 7): int {
        if (!$this->tableExists()) {
            return 0;
        }
        $days = max(1, min(90, $days));
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_admin_login_attempt` WHERE date_added < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");
        return (int)$this->db->countAffected();
    }

    public function maxAttempts(): int {
        $value = (int)$this->config->get('codecart_admin_login_max_attempts');
        return max(3, min(20, $value > 0 ? $value : 5));
    }

    public function windowMinutes(): int {
        $value = (int)$this->config->get('codecart_admin_login_window_minutes');
        return max(5, min(240, $value > 0 ? $value : 15));
    }

    private function tableExists(): bool {
        try {
            $query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'codecart_admin_login_attempt') . "'");
            return (bool)$query->num_rows;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeUsername(string $username): string {
        $username = trim($username);
        if (function_exists('utf8_strtolower')) {
            $username = utf8_strtolower($username);
        } else {
            $username = strtolower($username);
        }
        return substr($username, 0, 96);
    }

    private function clientIp(): string {
        $server = ($this->request && is_array($this->request->server)) ? $this->request->server : array();
        if (function_exists('codecart_client_ip')) {
            $ip = codecart_client_ip($server);
            return $ip !== '' ? substr($ip, 0, 45) : '';
        }
        if (isset($server['REMOTE_ADDR'])) {
            $ip = trim((string)$server['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return substr($ip, 0, 45);
            }
        }
        return '';
    }
}
