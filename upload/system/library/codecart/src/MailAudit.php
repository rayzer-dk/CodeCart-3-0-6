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
            PRIMARY KEY (audit_id),
            KEY idx_date (date_added),
            KEY idx_status_date (status, date_added),
            KEY idx_store_date (store_id, date_added)
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
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
        $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
        $sql[] = "remote_ip = '" . $db->escape($ip) . "'";
        $sql[] = "store_id = '" . (int)$config->get('config_store_id') . "'";
        $db->query("INSERT INTO " . DB_PREFIX . "codecart_mail_audit SET date_added = NOW(), " . implode(', ', $sql));
        if (mt_rand(1, 100) === 1) {
            $db->query("DELETE FROM " . DB_PREFIX . "codecart_mail_audit WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        }
    }
}
