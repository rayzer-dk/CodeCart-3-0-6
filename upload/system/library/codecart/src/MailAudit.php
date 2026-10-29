<?php
namespace CodeCart\Core;

/**
 * Opt-in metadata-only audit of outgoing OpenCart Mail transports.
 * "accepted" means accepted by PHP Mail / SMTP, NOT delivered to inbox.
 * Bodies, attachments, headers, tokens and passwords are never stored.
 */
final class MailAudit {
    private static $registry = null;
    public static function bind($registry) { self::$registry = $registry; }

    public static function enabled() {
        return self::$registry && self::$registry->has('config') && self::$registry->has('db')
            && (bool)self::$registry->get('config')->get('config_mail_audit_status');
    }

    public static function install($db) {
        $db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "codecart_mail_audit (
            audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            store_id INT NOT NULL DEFAULT 0,
            date_added DATETIME NOT NULL,
            engine VARCHAR(12) NOT NULL DEFAULT '',
            sender_email VARCHAR(190) NOT NULL DEFAULT '',
            recipient_email TEXT NOT NULL,
            subject VARCHAR(255) NOT NULL DEFAULT '',
            source VARCHAR(128) NOT NULL DEFAULT '',
            status VARCHAR(16) NOT NULL DEFAULT '',
            error_type VARCHAR(48) NOT NULL DEFAULT '',
            remote_ip VARCHAR(45) NOT NULL DEFAULT '',
            event_key CHAR(64) NULL DEFAULT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (audit_id),
            KEY idx_date (date_added),
            KEY idx_status_date (status, date_added),
            KEY idx_store_date (store_id, date_added),
            UNIQUE KEY idx_event_bucket (event_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function transport($mail, $accepted, $errorType = '') {
        if (!self::enabled()) { return; }
        try {
            $meta = method_exists($mail, 'auditMetadata') ? $mail->auditMetadata() : array();
            if (!$meta) { return; }
            $context = isset($mail->audit_context) ? (string)$mail->audit_context : '';
            self::write(array(
                'engine' => isset($meta['engine']) ? $meta['engine'] : '',
                'sender_email' => isset($meta['from']) ? $meta['from'] : '',
                'recipient_email' => isset($meta['to']) ? $meta['to'] : '',
                'subject' => isset($meta['subject']) ? $meta['subject'] : '',
                'source' => $context !== '' ? $context : self::route(),
                'store_id' => isset($meta['store_id']) ? (int)$meta['store_id'] : null,
                'status' => $accepted ? 'accepted' : 'failed',
                'error_type' => $accepted ? '' : $errorType
            ));
        } catch (\Throwable $ignored) {
            // Observability must never break checkout, registration or alerts.
        }
    }

    public static function blocked($source, $reason, $email = '') {
        if (!self::enabled()) { return; }
        try {
            self::write(array(
                'engine' => '',
                'sender_email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
                'recipient_email' => '',
                'subject' => '',
                'source' => $source,
                'status' => 'blocked',
                'error_type' => $reason
            ));
        } catch (\Throwable $ignored) {}
    }

    private static function route() {
        if (!self::$registry || !self::$registry->has('request')) { return ''; }
        $request = self::$registry->get('request');
        return isset($request->get['route']) ? (string)$request->get['route'] : 'system';
    }

    private static function write(array $values) {
        $db = self::$registry->get('db');
        $config = self::$registry->get('config');
        $fields = array(
            'engine' => 12, 'sender_email' => 190, 'recipient_email' => 2048,
            'subject' => 255, 'source' => 128, 'status' => 16, 'error_type' => 48
        );
        $sql = array();
        foreach ($fields as $field => $max) {
            $value = isset($values[$field]) ? (string)$values[$field] : '';
            $value = preg_replace('/[\x00-\x1f\x7f]/u', ' ', $value);
            if ($value === null) { $value = ''; }
            $value = function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
            $sql[] = $field . " = '" . $db->escape($value) . "'";
        }
        $ip = function_exists('codecart_client_ip') ? (string)codecart_client_ip((array)$_SERVER) : (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '');
        $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
        $sql[] = "remote_ip = '" . $db->escape($ip) . "'";
        $storeId = isset($values['store_id']) && $values['store_id'] !== null ? (int)$values['store_id'] : (int)$config->get('config_store_id');
        $sql[] = "store_id = '" . max(0, $storeId) . "'";
        // Blocked attempts are aggregated per source/IP/reason every five minutes.
        $blocked = isset($values['status']) && $values['status'] === 'blocked';
        $eventKey = $blocked ? hash('sha256', $ip . '|' . $values['source'] . '|' . $values['error_type'] . '|' . floor(time() / 300)) : '';
        $sql[] = 'event_key = ' . ($blocked ? "'" . $db->escape($eventKey) . "'" : 'NULL');
        $db->query("INSERT INTO " . DB_PREFIX . "codecart_mail_audit SET date_added = NOW(), " . implode(', ', $sql) . " ON DUPLICATE KEY UPDATE attempts = LEAST(attempts + 1, 4294967295), date_added = NOW()");
        if (mt_rand(1, 100) === 1) {
            $db->query("DELETE FROM " . DB_PREFIX . "codecart_mail_audit WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        }
    }
}
