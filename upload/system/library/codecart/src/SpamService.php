<?php
namespace CodeCart\Core;

final class SpamService {
    private $cache;
    private $session;
    private $request;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->cache = $registry->get('cache');
        $this->session = $registry->get('session');
        $this->request = $registry->get('request');
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function consume(string $scope, int $limit = 30, int $window = 600, int $sessionInterval = 2): array {
        // Existing FormGuard protection was always active before Central Spam Service.
        // A missing setting therefore means enabled for backward compatibility.
        $setting = $this->config ? $this->config->get('codecart_spam_service_status') : null;
        if ($setting !== null && $setting !== '' && !(int)$setting) {
            return array('allowed' => true, 'retry_after' => 0, 'service' => 'off');
        }

        $scope = preg_replace('/[^a-z0-9_.-]/i', '', $scope);
        $limit = max(1, $limit);
        $window = max(60, $window);
        $sessionInterval = max(0, $sessionInterval);
        $now = time();

        if ($scope === '') {
            return array('allowed' => false, 'retry_after' => 60, 'service' => 'central');
        }

        $sessionKey = 'codecart_spam_' . $scope;
        if ($sessionInterval > 0 && $this->session) {
            $last = isset($this->session->data[$sessionKey]) ? (int)$this->session->data[$sessionKey] : 0;
            if ($last > 0 && ($now - $last) < $sessionInterval) {
                return array('allowed' => false, 'retry_after' => max(1, $sessionInterval - ($now - $last)), 'service' => 'central');
            }
            $this->session->data[$sessionKey] = $now;
        }

        $ip = $this->clientIp();
        if ($ip === '' || !$this->cache) {
            return array('allowed' => true, 'retry_after' => 0, 'service' => 'central');
        }

        $cacheKey = 'spam.' . $scope . '.' . hash('sha256', $ip);
        $lock = 'cc_spam_' . substr(hash('sha256', $scope . '|' . $ip), 0, 32);
        $acquired = false;

        try {
            if ($this->db) {
                $lockQuery = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 1) AS acquired");
                $acquired = !empty($lockQuery->row['acquired']);
                if (!$acquired) {
                    return array('allowed' => false, 'retry_after' => 1, 'service' => 'central');
                }
            }

            $state = $this->cache->get($cacheKey);
            if (!is_array($state) || empty($state['window_start']) || ($now - (int)$state['window_start']) >= $window) {
                $state = array('window_start' => $now, 'count' => 0);
            }
            if ((int)$state['count'] >= $limit) {
                return array('allowed' => false, 'retry_after' => max(1, $window - ($now - (int)$state['window_start'])), 'service' => 'central');
            }
            $state['count'] = (int)$state['count'] + 1;
            $this->cache->set($cacheKey, $state);
            return array('allowed' => true, 'retry_after' => 0, 'service' => 'central');
        } finally {
            if ($acquired && $this->db) {
                try { $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')"); } catch (\Throwable $e) {}
            }
        }
    }

    private function clientIp(): string {
        if (function_exists('codecart_client_ip')) {
            return (string)codecart_client_ip((array)$this->request->server);
        }
        return isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : '';
    }
}
