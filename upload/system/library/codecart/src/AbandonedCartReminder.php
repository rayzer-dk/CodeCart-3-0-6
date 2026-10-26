<?php
namespace CodeCart\Core;

/**
 * Conservative recovery mailer. It never edits carts or sends to guests.
 * Disabled unless explicitly enabled, and only contacts newsletter-opted-in
 * registered customers. Stores an idempotent send ledger in InnoDB.
 */
final class AbandonedCartReminder {
    private $registry;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    private function table(): string {
        return '`' . DB_PREFIX . 'codecart_cart_reminder`';
    }

    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . $this->table() . " (
            `reminder_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cart_key` CHAR(64) NOT NULL,
            `customer_id` INT UNSIGNED NOT NULL,
            `email` VARCHAR(254) NOT NULL,
            `origin` VARCHAR(12) NOT NULL,
            `status` VARCHAR(16) NOT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `error_code` VARCHAR(120) NOT NULL DEFAULT '',
            `date_added` DATETIME NOT NULL,
            `date_sent` DATETIME NULL,
            PRIMARY KEY (`reminder_id`), UNIQUE KEY `cart_key` (`cart_key`),
            KEY `customer_date` (`customer_id`,`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function active(): bool {
        return (bool)$this->config->get('codecart_abandoned_cart_status')
            && (string)$this->config->get('codecart_abandoned_cart_source') !== 'external';
    }

    public function fingerprint(array $cart): string {
        return hash('sha256', (int)$cart['customer_id'] . '|' . (string)$cart['session_id'] . '|' .
            (string)$cart['last_activity'] . '|' . (int)$cart['quantity'] . '|' . (int)$cart['max_cart_id']);
    }

    private function idle(): int {
        return max(60, min(10080, (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60));
    }

    public function getCart(int $customerId, string $sessionId): array {
        if (!$this->active() || $customerId < 1 || $sessionId === '') { return array(); }
        $idle = $this->idle();
        $days = max(1, min(7, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        $sql = "SELECT c.customer_id,c.session_id,MAX(c.date_added) AS last_activity,
                    MAX(c.cart_id) AS max_cart_id,SUM(c.quantity) AS quantity,
                    MAX(cu.firstname) AS firstname,MAX(cu.email) AS email, MAX(cu.newsletter) AS newsletter
                FROM `" . DB_PREFIX . "cart` c
                INNER JOIN `" . DB_PREFIX . "customer` cu ON cu.customer_id=c.customer_id
                WHERE c.api_id=0 AND c.customer_id='" . $customerId . "'
                    AND c.session_id='" . $this->db->escape($sessionId) . "'
                    AND c.date_added >= DATE_SUB(NOW(),INTERVAL " . $days . " DAY)
                GROUP BY c.customer_id,c.session_id
                HAVING MAX(c.date_added) < DATE_SUB(NOW(),INTERVAL " . $idle . " MINUTE) AND SUM(c.quantity)>0
                LIMIT 1";
        $result = $this->db->query($sql);
        return $result->num_rows ? $result->row : array();
    }

    public function history(array $carts): array {
        if (!$carts) { return array(); }
        $keys = array();
        foreach ($carts as $cart) {
            if (isset($cart['cart_key']) && preg_match('/^[a-f0-9]{64}$/', (string)$cart['cart_key'])) {
                $keys[] = "'" . $cart['cart_key'] . "'";
            }
        }
        if (!$keys) { return array(); }
        $result = $this->db->query("SELECT cart_key,status,date_sent,date_added,attempts FROM " . $this->table() . " WHERE cart_key IN (" . implode(',', $keys) . ")");
        $out = array();
        foreach ($result->rows as $row) { $out[$row['cart_key']] = $row; }
        return $out;
    }

    public function send(int $customerId, string $sessionId, string $origin = 'manual'): array {
        if (!$this->active()) { return array('success'=>false,'message'=>'Cart recovery is disabled or source is read-only.'); }
        $cart = $this->getCart($customerId, $sessionId);
        if (!$cart) { return array('success'=>false,'message'=>'Cart is no longer abandoned.'); }
        $email = trim((string)$cart['email']);
        if (!(int)$cart['newsletter'] || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array('success'=>false,'message'=>'Newsletter preference or valid customer email is missing.');
        }
        // Check additional suppression status when marketing suppression table exists.
        try {
            $suppressed = $this->db->query("SELECT email FROM `" . DB_PREFIX . "mail_suppression` WHERE LOWER(email)='" . $this->db->escape(strtolower($email)) . "' LIMIT 1");
            if ($suppressed->num_rows) { return array('success'=>false,'message'=>'Customer has unsubscribed.'); }
        } catch (\Throwable $ignored) {
            // The optional newsletter table is not installed on all stores.
        }
        $key = $this->fingerprint($cart);
        // Once per customer per day, even when their cart contents change.
        $recent = $this->db->query("SELECT reminder_id FROM " . $this->table() . " WHERE customer_id='" . $customerId . "' AND status IN ('sending','sent') AND date_added>DATE_SUB(NOW(),INTERVAL 1 DAY) LIMIT 1");
        if ($recent->num_rows) { return array('success'=>false,'message'=>'Reminder already sent or in progress in the last 24 hours.'); }
        $previous = $this->db->query("SELECT status,attempts,date_added FROM " . $this->table() . " WHERE cart_key='" . $key . "' LIMIT 1");
        if ($previous->num_rows) {
            $old = $previous->row;
            if ($origin !== 'manual' || (string)$old['status'] !== 'failed'
                || (int)$old['attempts'] >= 3 || strtotime((string)$old['date_added']) > time()-600) {
                return array('success'=>false,'message'=>'This cart has already been processed.');
            }
            $this->db->query("UPDATE " . $this->table() . " SET status='sending', attempts=attempts+1,origin='manual',date_added=NOW(),error_code='' WHERE cart_key='" . $key . "' AND status='failed' AND attempts<3");
        } else {
            $this->db->query("INSERT IGNORE INTO " . $this->table() . " SET cart_key='" . $key . "', customer_id='" . $customerId . "', email='" . $this->db->escape($email) . "', origin='" . ($origin === 'auto' ? 'auto' : 'manual') . "', status='sending',date_added=NOW()");
        }
        if (!$this->db->countAffected()) { return array('success'=>false,'message'=>'This cart has already been processed.'); }

        $success = false;
        $error = 'transport_failed';
        try {
            $settings = array();
            $rows = $this->db->query("SELECT `key`,`value` FROM `" . DB_PREFIX . "setting` WHERE store_id=0 AND `key` IN ('config_name','config_email','config_language','config_ssl','config_url','config_mail_engine','config_mail_parameter','config_mail_smtp_hostname','config_mail_smtp_username','config_mail_smtp_password','config_mail_smtp_port','config_mail_smtp_timeout')");
            foreach ($rows->rows as $row) { $settings[$row['key']] = $row['value']; }
            $setting = function($name, $default='') use ($settings) { return isset($settings[$name]) ? $settings[$name] : ($this->config->get($name) ?: $default); };
            $from = (string)$setting('config_email');
            $base = (string)$setting('config_ssl', (string)$setting('config_url'));
            if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($base, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Store sender email or URL is missing.');
            }
            $store = (string)$setting('config_name', 'Store');
            $name = trim((string)$cart['firstname']);
            $link = rtrim($base, '/') . '/index.php?route=account/login';
            $locale = strtolower(str_replace('_', '-', (string)$setting('config_language','en-gb')));
            if (strpos($locale,'uk') === 0) {
                $subject = 'Незавершена покупка — ' . $store;
                $body = 'Вітаємо' . ($name !== '' ? ', ' . $name : '') . "!\n\nУ вашому кошику залишилися товари. Якщо бажаєте завершити покупку, увійдіть у свій обліковий запис: " . $link . "\n\nЯкщо ви вже оформили замовлення, не зважайте на цей лист.\n\n" . $store;
            } elseif (strpos($locale,'ru') === 0) {
                $subject = 'Незавершённая покупка — ' . $store;
                $body = 'Здравствуйте' . ($name !== '' ? ', ' . $name : '') . "!\n\nВ вашей корзине остались товары. Если хотите завершить покупку, войдите в свой аккаунт: " . $link . "\n\nЕсли заказ уже оформлен, проигнорируйте письмо.\n\n" . $store;
            } elseif (strpos($locale,'da') === 0) {
                $subject = 'Din uafsluttede bestilling hos ' . $store;
                $body = 'Hej' . ($name !== '' ? ' ' . $name : '') . ",\n\nDer ligger stadig varer i din indkøbskurv. Hvis du ønsker at fuldføre bestillingen, kan du logge ind her: " . $link . "\n\nHvis du allerede har bestilt, kan du se bort fra denne mail.\n\n" . $store;
            } else {
                $subject = 'Your unfinished order at ' . $store;
                $body = 'Hello' . ($name !== '' ? ' ' . $name : '') . ",\n\nYou left products in your shopping cart. To complete your order, sign in here: " . $link . "\n\nIf you have already ordered, please ignore this message.\n\n" . $store;
            }
            $mail = new \Mail((string)$setting('config_mail_engine','mail'));
            $mail->parameter = $setting('config_mail_parameter');
            $mail->smtp_hostname = $setting('config_mail_smtp_hostname');
            $mail->smtp_username = $setting('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode((string)$setting('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = (int)$setting('config_mail_smtp_port',25);
            $mail->smtp_timeout = (int)$setting('config_mail_smtp_timeout',5);
            $mail->setTo($email);
            $mail->setFrom($from);
            $mail->setSender($store);
            $mail->setSubject($subject);
            $mail->setText($body);
            $mail->setHtml('<div style="font-family:Arial,sans-serif;line-height:1.5">' . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . '</div>');
            $success = MailDelivery::sendNow($this->registry, $mail, 'abandoned-cart:' . substr($key,0,20));
        } catch (\Throwable $e) {
            $error = substr(get_class($e),0,120);
            $logger = $this->registry->get('log');
            if ($logger) { $logger->write('Cart reminder delivery error: ' . $e->getMessage()); }
        }
        $this->db->query("UPDATE " . $this->table() . " SET status='" . ($success ? 'sent' : 'failed') . "', error_code='" . ($success ? '' : $this->db->escape($error)) . "', date_sent=" . ($success ? 'NOW()' : 'NULL') . " WHERE cart_key='" . $key . "' AND status='sending'");
        return array('success'=>$success, 'message'=>$success ? 'Reminder accepted by the mail transport.' : 'Mail transport failed; check the error log.');
    }

    public function runBatch(int $limit = 10): array {
        if (!$this->active() || !(int)$this->config->get('codecart_abandoned_cart_mail_auto')) {
            return array('success'=>true,'message'=>'Abandoned-cart reminders disabled.');
        }
        $limit = max(1,min(20,$limit));
        $idle = $this->idle();
        $days = max(1,min(7,(int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        $results = $this->db->query("SELECT c.customer_id,c.session_id FROM `" . DB_PREFIX . "cart` c INNER JOIN `" . DB_PREFIX . "customer` cu ON cu.customer_id=c.customer_id WHERE c.api_id=0 AND c.customer_id>0 AND cu.newsletter=1 AND cu.email<>'' AND c.date_added>=DATE_SUB(NOW(),INTERVAL " . $days . " DAY) GROUP BY c.customer_id,c.session_id HAVING MAX(c.date_added)<DATE_SUB(NOW(),INTERVAL " . $idle . " MINUTE) ORDER BY MAX(c.date_added) DESC LIMIT " . ($limit * 4));
        $sent = 0; $failed=0; $skipped=0;
        foreach ($results->rows as $row) {
            if ($sent+$failed >= $limit) { break; }
            $out = $this->send((int)$row['customer_id'],(string)$row['session_id'],'auto');
            if ($out['success']) { $sent++; }
            elseif (strpos((string)$out['message'], 'already') !== false || strpos((string)$out['message'], 'progress') !== false || strpos((string)$out['message'], 'processed') !== false) { $skipped++; }
            else { $failed++; }
        }
        return array('success'=>$failed===0,'message'=>'Cart reminders: sent '.$sent.', errors '.$failed.', skipped '.$skipped,'sent'=>$sent,'failed'=>$failed,'skipped'=>$skipped);
    }
}
