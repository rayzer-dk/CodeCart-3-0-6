<?php
namespace CodeCart\Core;

final class SecurityAudit {
    private $registry;
    private $db;
    private $request;
    private $log;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->log = $registry->get('log');
    }

    public function add(string $event, string $outcome = 'info', string $severity = 'info', string $actorType = '', int $actorId = 0, string $username = '', array $context = array()): void {
        if (!$this->tableExists()) {
            return;
        }

        $event = $this->normalizeToken($event, 96, 'security.event');
        $outcome = $this->normalizeToken($outcome, 24, 'info');
        $severity = $this->normalizeToken($severity, 16, 'info');
        $actorType = $this->normalizeToken($actorType, 24, '');
        $username = substr(trim($username), 0, 96);
        $ip = $this->clientIp();
        $userAgent = $this->userAgent();

        $safeContext = $this->sanitizeContext($context);
        $encoded = json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $encoded = '{}';
        }
        if (strlen($encoded) > 8000) {
            $encoded = json_encode(array('note' => 'Context omitted because it exceeded the audit limit.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        try {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_security_audit` SET actor_type = '" . $this->db->escape($actorType) . "', actor_id = '" . (int)$actorId . "', username = '" . $this->db->escape($username) . "', event = '" . $this->db->escape($event) . "', outcome = '" . $this->db->escape($outcome) . "', severity = '" . $this->db->escape($severity) . "', ip = '" . $this->db->escape($ip) . "', user_agent = '" . $this->db->escape($userAgent) . "', context = '" . $this->db->escape($encoded) . "', date_added = NOW()");
        } catch (\Throwable $e) {
            if ($this->log) {
                $this->log->write('Security audit write failed: ' . $e->getMessage());
            }
        }
    }

    public function recent(int $limit = 50): array {
        if (!$this->tableExists()) {
            return array();
        }

        $limit = max(1, min(200, $limit));
        try {
            $query = $this->db->query("SELECT audit_id, actor_type, actor_id, username, event, outcome, severity, ip, user_agent, context, date_added FROM `" . DB_PREFIX . "codecart_security_audit` ORDER BY audit_id DESC LIMIT " . (int)$limit);
            foreach ($query->rows as &$row) {
                $decoded = json_decode((string)$row['context'], true);
                $row['context_data'] = is_array($decoded) ? $decoded : array();
            }
            unset($row);
            return $query->rows;
        } catch (\Throwable $e) {
            return array();
        }
    }

    public function cleanup(int $days = 90): int {
        if (!$this->tableExists()) {
            return 0;
        }
        $days = max(7, min(730, $days));
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_security_audit` WHERE date_added < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");
        return (int)$this->db->countAffected();
    }

    private function tableExists(): bool {
        try {
            $query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'codecart_security_audit') . "'");
            return (bool)$query->num_rows;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function clientIp(): string {
        if ($this->request && function_exists('codecart_client_ip')) {
            $ip = (string)codecart_client_ip((array)$this->request->server);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return substr($ip, 0, 45);
            }
        }
        if ($this->request && isset($this->request->server['REMOTE_ADDR'])) {
            $ip = trim((string)$this->request->server['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return substr($ip, 0, 45);
            }
        }
        return '';
    }

    private function userAgent(): string {
        if ($this->request && isset($this->request->server['HTTP_USER_AGENT'])) {
            return substr(trim((string)$this->request->server['HTTP_USER_AGENT']), 0, 512);
        }
        return '';
    }

    private function normalizeToken(string $value, int $max, string $fallback): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_.:-]+/', '.', $value);
        $value = trim((string)$value, '.');
        if ($value === '') {
            $value = $fallback;
        }
        return substr($value, 0, $max);
    }

    private function sanitizeContext(array $context): array {
        $blocked = array('password', 'passwd', 'secret', 'token', 'license_key', 'api_key', 'authorization', 'cookie');
        $safe = array();
        foreach ($context as $key => $value) {
            $name = substr((string)$key, 0, 96);
            if (in_array(strtolower($name), $blocked, true)) {
                $safe[$name] = '[redacted]';
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $safe[$name] = is_string($value) ? substr($value, 0, 1000) : $value;
            } elseif (is_array($value)) {
                $safe[$name] = $this->sanitizeContext($value);
            } else {
                $safe[$name] = gettype($value);
            }
        }
        return $safe;
    }
}
