<?php
namespace CodeCart\Core;

final class LoginProtection {
    private $registry;
    private $db;
    private $config;
    private $request;
    private $loginAttemptTableAvailable = null;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->request = $registry->get('request');
    }

    public function enabled(): bool {
        // Existing ocStore shops have no CodeCart protection setting until the
        // administrator completes Installer UPDATE. Never query missing tables
        // during authentication: legacy password validation must still work.
        $value = $this->config->get('codecart_admin_login_protection_status');
        if ($value === null || $value === '' || !(bool)(int)$value) {
            return false;
        }

        if ($this->loginAttemptTableAvailable === null) {
            $this->loginAttemptTableAvailable = false;
            try {
                $table = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'codecart_admin_login_attempt') . "'");
                $this->loginAttemptTableAvailable = (bool)$table->num_rows;
            } catch (\Throwable $e) {
                // Permissions or an incomplete migration are diagnosed by the
                // explicit updater; never run schema DDL inside a login POST.
            }
            if (!$this->loginAttemptTableAvailable) {
                $log = $this->registry->get('log');
                if ($log) {
                    $log->write('CodeCart admin login protection requires Installer UPDATE: login-attempt table is unavailable.');
                }
            }
        }

        return $this->loginAttemptTableAvailable;
    }

    public function isBlocked(string $username): bool {
        if (!$this->enabled()) {
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
        if (!$this->enabled()) {
            return;
        }
        $username = $this->normalizeUsername($username);
        $ip = $this->clientIp();
        if ($username === '' || $ip === '') {
            return;
        }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_admin_login_attempt` SET username = '" . $this->db->escape($username) . "', ip = '" . $this->db->escape($ip) . "', date_added = NOW()");
    }

    /**
     * Re-authentication of a signed-in administrator before a sensitive change.
     * Returns 'ok', 'invalid' or 'blocked'; failures count like failed logins.
     */
    public function confirmPassword(int $userId, string $password): string {
        $query = $this->db->query("SELECT username, password, salt FROM `" . DB_PREFIX . "user` WHERE user_id = '" . (int)$userId . "' AND status = '1' LIMIT 1");
        if (!$query->num_rows) {
            return 'invalid';
        }
        $username = (string)$query->row['username'];
        if ($this->isBlocked($username)) {
            return 'blocked';
        }
        if ($password !== '' && codecart_password_verify($password, $query->row['password'], $query->row['salt'])) {
            return 'ok';
        }
        $this->recordFailure($username);
        return 'invalid';
    }

    public function recordSuccess(string $username): void {
        if (!$this->enabled()) {
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
        if (!$this->enabled()) {
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

    private function normalizeUsername(string $username): string {
        $username = trim($username);
        $username = utf8_strtolower($username);
        return substr($username, 0, 96);
    }

    private function clientIp(): string {
        $server = ($this->request && is_array($this->request->server)) ? $this->request->server : array();
        return substr((string)codecart_client_ip($server), 0, 45);
    }
}
