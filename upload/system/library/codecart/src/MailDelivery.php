<?php
namespace CodeCart\Core;

/**
 * Shared boundary for transactional mail delivery.
 *
 * Normal web requests enqueue mail into the persistent CodeCart queue so SMTP
 * latency cannot hold checkout/account requests open. Queue workers call
 * sendNow(), which always performs the actual transport synchronously.
 */
final class MailDelivery {
    public static function send($registry, $mail, $context = '', $storeId = null) {
        $context = self::context($context);

        // Existing background mail subsystems already own their delivery state
        // and must know the real SMTP result before marking a row as sent.
        if (strpos($context, 'mail-campaign:') === 0 || strpos($context, 'stock-notify:') === 0) {
            return self::sendNow($registry, $mail, $context);
        }

        // Keep attachment-bearing messages synchronous unless the attachment
        // lifecycle is known to be persistent. This prevents a queued worker
        // from referencing a temporary file that has already disappeared.
        if (!is_object($mail) || !method_exists($mail, 'exportQueueData')) {
            return self::sendNow($registry, $mail, $context);
        }

        try {
            $message = $mail->exportQueueData();
            if (!empty($message['attachments'])) {
                self::write($registry, 'Mail queue bypass [' . $context . ']: attachment-bearing message is delivered synchronously.');
                return self::sendNow($registry, $mail, $context);
            }

            $storeId = self::resolveStoreId($registry, $context, $storeId);
            $payload = array(
                'version' => 1,
                'store_id' => (int)$storeId,
                'context' => $context,
                'message' => $message
            );
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new \RuntimeException('Mail payload could not be encoded.');
            }

            $fingerprint = hash('sha256', $encoded);
            $code = 'mail.tx.' . substr($fingerprint, 0, 40);
            $db = $registry ? $registry->get('db') : null;
            if (!$db) {
                throw new \RuntimeException('Database is not available for mail queue.');
            }

            // Duplicate event/callback protection across requests. Different
            // content (status/comment/reset token) produces a different hash.
            $existing = $db->query("SELECT queue_id FROM `" . DB_PREFIX . "codecart_queue` WHERE code = '" . $db->escape($code) . "' AND status IN ('pending','processing','done') AND date_added >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY queue_id DESC LIMIT 1");
            if ($existing->num_rows) {
                return true;
            }

            (new Queue($registry))->enqueue($code, 'cron/mail_delivery', $payload, 20, null, 5);
            return true;
        } catch (\Throwable $e) {
            // Business actions must not lose notifications merely because the
            // queue infrastructure is temporarily unavailable. Fall back to the
            // current synchronous transport in this exceptional path only.
            self::write($registry, 'Mail queue failed [' . $context . '], synchronous fallback: ' . get_class($e) . ': ' . $e->getMessage());
            return self::sendNow($registry, $mail, $context);
        }
    }

    public static function sendNow($registry, $mail, $context = '') {
        $context = self::context($context);
        try {
            $result = $mail->send();
            if ($result === false) {
                self::write($registry, 'Mail delivery failed [' . $context . ']: transport returned false; engine=' . self::engine($registry));
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            self::write($registry, 'Mail delivery failed [' . $context . ']: ' . get_class($e) . ': ' . $e->getMessage() . '; engine=' . self::engine($registry));
            return false;
        }
    }

    public static function additionalRecipients($primary, $raw) {
        $seen = array();
        $primary = trim((string)$primary);
        if ($primary !== '') { $seen[strtolower($primary)] = true; }
        $values = is_array($raw) ? $raw : explode(',', (string)$raw);
        $out = array();
        foreach ($values as $email) {
            $email = trim((string)$email);
            $key = strtolower($email);
            if ($email === '' || isset($seen[$key]) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { continue; }
            $seen[$key] = true;
            $out[] = $email;
        }
        return $out;
    }

    private static function resolveStoreId($registry, $context, $storeId) {
        if ($storeId !== null) { return max(0, (int)$storeId); }
        $config = $registry ? $registry->get('config') : null;
        $resolved = $config ? (int)$config->get('config_store_id') : 0;

        // Order mail can also be triggered from admin where config_store_id is
        // the admin context, not necessarily the order's storefront.
        if (preg_match('/(?:^|:)order:(\\d+)(?:$|:)/', ':' . $context . ':', $m)) {
            try {
                $db = $registry->get('db');
                $q = $db->query("SELECT store_id FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$m[1] . "' LIMIT 1");
                if ($q->num_rows) { $resolved = (int)$q->row['store_id']; }
            } catch (\Throwable $e) {}
        }
        return max(0, $resolved);
    }

    private static function context($context) {
        $context = preg_replace('/[^a-z0-9_.:-]/i', '', (string)$context);
        return $context !== '' ? $context : 'mail';
    }

    private static function engine($registry) {
        $config = $registry ? $registry->get('config') : null;
        return $config ? (string)$config->get('config_mail_engine') : '';
    }

    private static function write($registry, $message) {
        $log = $registry ? $registry->get('log') : null;
        if ($log) { $log->write((string)$message); }
    }
}
