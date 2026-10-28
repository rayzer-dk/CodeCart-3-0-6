<?php
namespace CodeCart\Core;

/** Explicit consent for one registered customer's current cart in a specific store. */
final class RegisteredCartConsent {
    private $registry;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function enabled(): bool {
        return (bool)$this->config->get('codecart_abandoned_cart_status')
            && (string)$this->config->get('codecart_abandoned_cart_source') !== 'external';
    }

    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_cart_registered_consent` (
            `store_id` INT UNSIGNED NOT NULL,
            `customer_id` INT UNSIGNED NOT NULL,
            `session_id` VARCHAR(128) NOT NULL,
            `consent` TINYINT(1) NOT NULL DEFAULT 1,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`store_id`,`customer_id`,`session_id`),
            KEY `consent_expiry` (`date_modified`),
            KEY `session_lookup` (`customer_id`,`session_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function save(int $customerId, string $sessionId, bool $consent): void {
        if (!$this->enabled() || $customerId <= 0 || $sessionId === '' || strlen($sessionId) > 128) { return; }
        $storeId = (int)$this->config->get('config_store_id');
        $where = "store_id='" . $storeId . "' AND customer_id='" . $customerId . "' AND session_id='" . $this->db->escape($sessionId) . "'";
        if (!$consent) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_cart_registered_consent` WHERE " . $where);
            return;
        }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_cart_registered_consent` SET store_id='" . $storeId . "',customer_id='" . $customerId . "',session_id='" . $this->db->escape($sessionId) . "',consent=1,date_modified=NOW() ON DUPLICATE KEY UPDATE consent=1,date_modified=NOW()");
    }

    public function hasConsent(int $customerId, string $sessionId): bool {
        if (!$this->enabled() || $customerId < 1 || $sessionId === '') { return false; }
        try {
            $check = $this->db->query("SELECT consent FROM `" . DB_PREFIX . "codecart_cart_registered_consent` WHERE store_id='" . (int)$this->config->get('config_store_id') . "' AND customer_id='" . $customerId . "' AND session_id='" . $this->db->escape($sessionId) . "' AND date_modified>=DATE_SUB(NOW(),INTERVAL 7 DAY) LIMIT 1");
            return $check->num_rows > 0 && (int)$check->row['consent'] === 1;
        } catch (\Throwable $e) { return false; }
    }

    public function purge(): void {
        if ($this->enabled()) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_cart_registered_consent` WHERE date_modified<DATE_SUB(NOW(),INTERVAL 7 DAY)");
        }
    }
}
