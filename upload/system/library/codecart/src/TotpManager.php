<?php
namespace CodeCart\Core;

final class TotpManager {
    private $registry;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function isEnabled(int $userId): bool {
        $row = $this->getRow($userId);
        return $row && (int)$row['status'] === 1 && trim((string)$row['secret']) !== '';
    }

    public function createSecret(int $bytes = 20): string {
        $bytes = max(16, min(32, $bytes));
        return $this->base32Encode(random_bytes($bytes));
    }


    public function provisioningLabel(string $username): string {
        $issuer = trim((string)$this->config->get('config_name'));
        if ($issuer === '') { $issuer = 'OpenCart Admin'; }
        return $issuer . ':' . trim($username);
    }

    public function provisioningUri(string $secret, string $username): string {
        $issuer = trim((string)$this->config->get('config_name'));
        if ($issuer === '') { $issuer = 'OpenCart Admin'; }
        $label = $this->provisioningLabel($username);
        return 'otpauth://totp/' . rawurlencode($label) . '?secret=' . rawurlencode($secret) . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    public function enable(int $userId, string $secret, string $code): array {
        $userId = max(1, $userId);
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));
        $matchedCounter = strlen($secret) >= 16 ? $this->matchingCounter($secret, $code) : null;
        if ($matchedCounter === null) {
            throw new \RuntimeException('Invalid authenticator code.');
        }

        $encrypted = $this->encrypt($secret);
        $recoveryCodes = $this->generateRecoveryCodes();
        $recoveryHashes = array();
        foreach ($recoveryCodes as $recoveryCode) {
            $recoveryHashes[] = password_hash($this->normalizeRecoveryCode($recoveryCode), PASSWORD_DEFAULT);
        }
        $encodedRecovery = json_encode($recoveryHashes, JSON_UNESCAPED_SLASHES);
        if ($encodedRecovery === false) {
            throw new \RuntimeException('Could not encode recovery codes.');
        }

        $existing = $this->getRow($userId);
        if ($existing) {
            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_user_mfa` SET type = 'totp', secret = '" . $this->db->escape($encrypted) . "', recovery_codes = '" . $this->db->escape($encodedRecovery) . "', status = '1', last_counter = '" . (int)$matchedCounter . "', date_modified = NOW(), date_used = NULL WHERE user_id = '" . (int)$userId . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_user_mfa` SET user_id = '" . (int)$userId . "', type = 'totp', secret = '" . $this->db->escape($encrypted) . "', recovery_codes = '" . $this->db->escape($encodedRecovery) . "', status = '1', last_counter = '" . (int)$matchedCounter . "', date_added = NOW(), date_modified = NOW()");
        }

        return $recoveryCodes;
    }

    public function disable(int $userId, string $code): void {
        $row = $this->getRow($userId);
        if (!$row || (int)$row['status'] !== 1) {
            return;
        }
        if (!$this->verify($userId, $code, false)) {
            throw new \RuntimeException('Invalid authenticator or recovery code.');
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_user_mfa` SET secret = '', recovery_codes = '[]', status = '0', last_counter = '-1', date_modified = NOW() WHERE user_id = '" . (int)$userId . "'");
    }

    public function verify(int $userId, string $code, bool $consumeRecovery = true): bool {
        $row = $this->getRow($userId);
        if (!$row || (int)$row['status'] !== 1 || trim((string)$row['secret']) === '') {
            return false;
        }

        $secret = $this->decrypt((string)$row['secret']);
        $matchedCounter = $secret !== '' ? $this->matchingCounter($secret, $code) : null;
        if ($matchedCounter !== null) {
            $lastCounter = isset($row['last_counter']) ? (int)$row['last_counter'] : -1;
            if ($matchedCounter <= $lastCounter) {
                return false;
            }

            $this->db->query("UPDATE `" . DB_PREFIX . "codecart_user_mfa` SET last_counter = '" . (int)$matchedCounter . "', date_used = NOW() WHERE user_id = '" . (int)$userId . "' AND status = '1' AND last_counter < '" . (int)$matchedCounter . "'");
            return (int)$this->db->countAffected() === 1;
        }

        $normalized = $this->normalizeRecoveryCode($code);
        if ($normalized === '') {
            return false;
        }
        $hashes = json_decode((string)$row['recovery_codes'], true);
        if (!is_array($hashes)) {
            return false;
        }
        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && password_verify($normalized, $hash)) {
                if ($consumeRecovery) {
                    $currentRecovery = (string)$row['recovery_codes'];
                    unset($hashes[$index]);
                    $encoded = json_encode(array_values($hashes), JSON_UNESCAPED_SLASHES);
                    if ($encoded === false) {
                        return false;
                    }

                    // Atomic compare-and-swap: only the request that still sees the
                    // exact recovery-code set it verified may consume this code.
                    // Concurrent requests using the same code cannot both succeed.
                    $this->db->query("UPDATE `" . DB_PREFIX . "codecart_user_mfa` SET recovery_codes = '" . $this->db->escape($encoded) . "', date_used = NOW(), date_modified = NOW() WHERE user_id = '" . (int)$userId . "' AND status = '1' AND recovery_codes = '" . $this->db->escape($currentRecovery) . "'");
                    return (int)$this->db->countAffected() === 1;
                }
                return true;
            }
        }
        return false;
    }

    public function remainingRecoveryCodes(int $userId): int {
        $row = $this->getRow($userId);
        if (!$row) {
            return 0;
        }
        $hashes = json_decode((string)$row['recovery_codes'], true);
        return is_array($hashes) ? count($hashes) : 0;
    }

    private function verifySecret(string $secret, string $code): bool {
        return $this->matchingCounter($secret, $code) !== null;
    }

    private function matchingCounter(string $secret, string $code): ?int {
        $code = preg_replace('/\D+/', '', $code);
        if (!is_string($code) || strlen($code) !== 6) {
            return null;
        }

        $counter = (int)floor(time() / 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $candidate = $counter + $offset;
            if ($candidate < 0) {
                continue;
            }
            $expected = $this->hotp($secret, $candidate);
            if (hash_equals($expected, $code)) {
                return $candidate;
            }
        }

        return null;
    }

    private function hotp(string $secret, int $counter): string {
        $key = $this->base32Decode($secret);
        if ($key === '') {
            return '000000';
        }
        $high = intdiv($counter, 4294967296);
        $low = $counter % 4294967296;
        $binaryCounter = pack('N2', $high, $low);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string)($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function getRow(int $userId): array {
        try {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_user_mfa` WHERE user_id = '" . (int)$userId . "' LIMIT 1");
            return $query->num_rows ? $query->row : array();
        } catch (\Throwable $e) {
            return array();
        }
    }

    private function encrypt(string $plain): string {
        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('OpenSSL is required for TOTP secret storage.');
        }
        $master = trim((string)$this->config->get('config_encryption'));
        if (strlen($master) < 32) {
            throw new \RuntimeException('Store encryption key is missing or too short.');
        }
        $key = hash('sha256', $master, true);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'codecart-totp-v1', 16);
        if ($cipher === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Could not encrypt TOTP secret.');
        }
        return 'v1.' . $this->base64UrlEncode($iv . $tag . $cipher);
    }

    private function decrypt(string $encoded): string {
        if (strpos($encoded, 'v1.') !== 0 || !function_exists('openssl_decrypt')) {
            return '';
        }
        $raw = $this->base64UrlDecode(substr($encoded, 3));
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $master = trim((string)$this->config->get('config_encryption'));
        if (strlen($master) < 32) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', hash('sha256', $master, true), OPENSSL_RAW_DATA, $iv, $tag, 'codecart-totp-v1');
        return $plain === false ? '' : (string)$plain;
    }

    private function generateRecoveryCodes(): array {
        $codes = array();
        for ($i = 0; $i < 8; $i++) {
            $hex = strtoupper(bin2hex(random_bytes(4)));
            $codes[] = substr($hex, 0, 4) . '-' . substr($hex, 4, 4);
        }
        return $codes;
    }

    private function normalizeRecoveryCode(string $code): string {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($code)));
    }

    private function base32Encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        $output = '';
        for ($i = 0, $len = strlen($bits); $i < $len; $i += 5) {
            $chunk = substr($bits, $i, 5);
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= $alphabet[bindec($chunk)];
        }
        return $output;
    }

    private function base32Decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data));
        if ($data === '') {
            return '';
        }
        $bits = '';
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $position = strpos($alphabet, $data[$i]);
            if ($position === false) {
                return '';
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $output = '';
        for ($i = 0, $len = strlen($bits) - 7; $i < $len; $i += 8) {
            $output .= chr(bindec(substr($bits, $i, 8)));
        }
        return $output;
    }

    private function base64UrlEncode(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value) {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($value, true);
    }
}
