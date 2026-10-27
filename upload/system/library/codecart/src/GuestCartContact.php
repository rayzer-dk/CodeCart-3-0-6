<?php
namespace CodeCart\Core;

/** Optional, consent-only guest contact and one-time cart recovery. */
final class GuestCartContact {
    private $registry;
    private $db;
    private $config;
    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }
    private function table(): string { return '`' . DB_PREFIX . 'codecart_cart_guest_contact`'; }
    public function enabled(): bool {
        return (bool)$this->config->get('codecart_abandoned_cart_status')
            && (bool)$this->config->get('codecart_abandoned_cart_guest_capture')
            && (string)$this->config->get('codecart_abandoned_cart_source') !== 'external';
    }
    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . $this->table() . " (
            `session_id` VARCHAR(128) NOT NULL,
            `store_id` INT NOT NULL DEFAULT 0,
            `email` VARCHAR(254) NOT NULL,
            `firstname` VARCHAR(100) NOT NULL DEFAULT '',
            `telephone` VARCHAR(40) NOT NULL DEFAULT '',
            `consent` TINYINT(1) NOT NULL DEFAULT 0,
            `token_hash` CHAR(64) DEFAULT NULL,
            `token_expires` DATETIME DEFAULT NULL,
            `token_used` DATETIME DEFAULT NULL,
            `recovered_at` DATETIME DEFAULT NULL,
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`store_id`,`session_id`),
            KEY `guest_expiry` (`date_modified`),
            KEY `guest_token` (`token_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    public function saveFromCheckout(string $sessionId, array $post): void {
        if (!$this->enabled() || $sessionId === '' || strlen($sessionId) > 128) { return; }
        $sessionId = $this->db->escape($sessionId);
        $email = trim((string)($post['email'] ?? ''));
        $consent = isset($post['ccp_cart_recovery_consent']) && (string)$post['ccp_cart_recovery_consent'] === '1';
        if (!$consent || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->db->query("DELETE FROM " . $this->table() . " WHERE session_id='" . $sessionId . "' AND store_id='" . (int)$this->config->get('config_store_id') . "'");
            return;
        }
        $firstname = substr(trim((string)($post['firstname'] ?? '')), 0, 100);
        $phone = substr(trim((string)($post['telephone'] ?? '')), 0, 40);
        $this->db->query("INSERT INTO " . $this->table() . " SET session_id='" . $sessionId . "',store_id='" . (int)$this->config->get('config_store_id') . "', email='" . $this->db->escape($email) . "',firstname='" . $this->db->escape($firstname) . "',telephone='" . $this->db->escape($phone) . "',consent=1,date_added=NOW(),date_modified=NOW() ON DUPLICATE KEY UPDATE store_id=VALUES(store_id),email=VALUES(email),firstname=VALUES(firstname),telephone=VALUES(telephone),consent=1,token_hash=NULL,token_expires=NULL,token_used=NULL,recovered_at=NULL,date_modified=NOW()");
    }
    public function issueToken(string $sessionId): string {
        if (!$this->enabled() || $sessionId === '' || strlen($sessionId) > 128) { return ''; }
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $this->db->query("UPDATE " . $this->table() . " SET token_hash='" . $hash . "',token_expires=DATE_ADD(NOW(), INTERVAL 7 DAY),token_used=NULL WHERE session_id='" . $this->db->escape($sessionId) . "' AND store_id='" . (int)$this->config->get('config_store_id') . "' AND consent=1 AND recovered_at IS NULL AND date_modified>=DATE_SUB(NOW(),INTERVAL 7 DAY)");
        return $this->db->countAffected() > 0 ? $token : '';
    }
    public function restore(string $token, $cart): bool {
        if (!$this->enabled() || !preg_match('/^[a-f0-9]{64}$/D', $token)) { return false; }
        $hash = hash('sha256', $token);
        $query = $this->db->query("SELECT session_id FROM " . $this->table() . " WHERE token_hash='" . $hash . "' AND store_id='" . (int)$this->config->get('config_store_id') . "' AND consent=1 AND token_used IS NULL AND recovered_at IS NULL AND token_expires>NOW() AND date_modified>=DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1");
        if (!$query->num_rows) { return false; }
        $session = (string)$query->row['session_id'];
        // Claim the single-use token atomically before changing the visitor cart.
        $this->db->query("UPDATE " . $this->table() . " SET token_used=NOW() WHERE session_id='" . $this->db->escape($session) . "' AND store_id='" . (int)$this->config->get('config_store_id') . "' AND token_hash='" . $hash . "' AND token_used IS NULL AND recovered_at IS NULL AND token_expires>NOW()");
        if (!$this->db->countAffected()) { return false; }
        $items = $this->db->query("SELECT product_id,quantity,`option` FROM `" . DB_PREFIX . "cart` WHERE api_id=0 AND customer_id=0 AND session_id='" . $this->db->escape($session) . "' AND date_added>=DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY cart_id LIMIT 100");
        $restored = 0;
        foreach ($items->rows as $item) {
            $options = json_decode((string)$item['option'], true);
            if (!is_array($options)) { $options = array(); }
            $cart->add((int)$item['product_id'], min(1000, max(1, (int)$item['quantity'])), $options);
            $restored++;
        }
        if ($restored) {
            $this->db->query("UPDATE " . $this->table() . " SET recovered_at=NOW() WHERE session_id='" . $this->db->escape($session) . "' AND store_id='" . (int)$this->config->get('config_store_id') . "' AND token_hash='" . $hash . "' AND token_used IS NOT NULL");
        }
        return $restored > 0;
    }
    public function purge(): void {
        if ($this->enabled()) {
            $this->db->query("DELETE FROM " . $this->table() . " WHERE date_modified<DATE_SUB(NOW(),INTERVAL 7 DAY)");
        }
    }
}
