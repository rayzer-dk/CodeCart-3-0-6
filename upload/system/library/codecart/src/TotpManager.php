<?php
namespace CodeCart\Core;

final class TotpManager {
    // Failed codes per account: 5 per 15 minutes, 20 per 24 hours; per source: 15 per 15 minutes.
    private const LIMITS = array(
        array('account', 5, 900),
        array('account', 20, 86400),
        array('source', 15, 900),
    );

    private $registry;
    private $db;
    private $config;
    private $lockTriggered = false;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function isEnabled(int $userId): bool {
        $row = $this->getRow($userId);
        return $row && (int)$row['status'] === 1 && trim((string)$row['secret']) !== '';
    }

    /** User group IDs for which two-factor verification is mandatory. */
    public function requiredGroups(): array {
        $groups = array();
        foreach (explode(',', (string)$this->config->get('codecart_totp_required_groups')) as $id) {
            if ((int)$id > 0) { $groups[(int)$id] = (int)$id; }
        }
        return array_values($groups);
    }

    public function isRequiredFor(int $userGroupId): bool {
        return $userGroupId > 0 && in_array($userGroupId, $this->requiredGroups(), true);
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
        $encodedRecovery = $this->hashRecoveryCodes($recoveryCodes);

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
        $this->reset($userId);
    }

    /** Removes the second factor without a code; callers must authorize the action. */
    public function reset(int $userId): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_user_mfa` SET secret = '', recovery_codes = '[]', status = '0', last_counter = '-1', date_modified = NOW() WHERE user_id = '" . (int)$userId . "'");
    }

    /** Replaces all recovery codes after the caller has verified a current code. */
    public function regenerateRecoveryCodes(int $userId): array {
        if (!$this->isEnabled($userId)) {
            throw new \RuntimeException('Two-factor verification is not enabled.');
        }
        $codes = $this->generateRecoveryCodes();
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_user_mfa` SET recovery_codes = '" . $this->db->escape($this->hashRecoveryCodes($codes)) . "', date_modified = NOW() WHERE user_id = '" . (int)$userId . "' AND status = '1'");
        return $codes;
    }

    /**
     * Marks the session as verified for $userId after a privilege change: new
     * session ID and admin token, the old ones stop working. Returns the token.
     */
    public function elevateSession(int $userId): string {
        $session = $this->registry->get('session');
        $session->regenerate();
        codecart_set_session_cookie($this->config->get('session_name'), $session->getId());
        $session->data['user_token'] = token(32);
        $session->data['codecart_totp_verified_user_id'] = $userId;
        return $session->data['user_token'];
    }

    /** True when the last failed verify() started a lockout for the account. */
    public function lockTriggered(): bool {
        return $this->lockTriggered;
    }

    public function verify(int $userId, string $code, bool $consumeRecovery = true): bool {
        $this->lockTriggered = false;
        $lock = 'cctotp_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX . '|' . $this->attemptSource()), 0, 56);
        try {
            $query = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 2) AS acquired");
            if ((int)($query->row['acquired'] ?? 0) !== 1) { return false; }
        } catch (\Throwable $e) {
            return false;
        }

        try {
            // Acquire before the transaction so its snapshot includes prior source attempts.
            $this->db->query('START TRANSACTION');
            try {
                $verified = $this->verifyAttempt($userId, $code, $consumeRecovery);
                $this->db->query('COMMIT');
                return $verified;
            } catch (\Throwable $e) {
                $this->db->query('ROLLBACK');
                throw $e;
            }
        } finally {
            $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')");
        }
    }

    public function retryAfter(int $userId): int {
        $retry = 0;
        foreach (self::LIMITS as $limit) {
            $row = $this->failures($userId, $limit[0], $limit[2]);
            if ((int)$row['total'] >= $limit[1]) { $retry = max($retry, (int)$row['retry_after']); }
        }
        return $retry;
    }

    private function failures(int $userId, string $scope, int $window): array {
        $where = $scope === 'account'
            ? "username = '@totp:" . (int)$userId . "' AND ip LIKE 'totp:%'"
            : "ip = '" . $this->db->escape($this->attemptSource()) . "' AND username LIKE '@totp:%'";
        $query = $this->db->query("SELECT COUNT(*) AS total, GREATEST(1, " . (int)$window . " - TIMESTAMPDIFF(SECOND, MIN(date_added), NOW())) AS retry_after FROM `" . DB_PREFIX . "codecart_admin_login_attempt` WHERE " . $where . " AND date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$window . " SECOND)");
        return $query->row;
    }

    private function recordFailure(int $userId): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_admin_login_attempt` SET username = '@totp:" . (int)$userId . "', ip = '" . $this->db->escape($this->attemptSource()) . "', date_added = NOW()");
        foreach (self::LIMITS as $limit) {
            if ($limit[0] === 'account' && (int)$this->failures($userId, 'account', $limit[2])['total'] === $limit[1]) {
                $this->lockTriggered = true;
            }
        }
    }

    private function attemptSource(): string {
        $request = $this->registry->get('request');
        $server = $request ? (array)$request->server : array();
        $ip = codecart_client_ip($server);
        // Primary-password success/disable must never clear or disable MFA limits.
        return 'totp:' . sha1((string)$ip);
    }

    private function verifyAttempt(int $userId, string $code, bool $consumeRecovery): bool {
        // Serialize attempts for one account across sessions and sources.
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_user_mfa` WHERE user_id = '" . (int)$userId . "' LIMIT 1 FOR UPDATE");
        $row = $query->num_rows ? $query->row : array();
        if (!$row || (int)$row['status'] !== 1 || trim((string)$row['secret']) === '') {
            return false;
        }
        if ($this->retryAfter($userId) > 0) { return false; }
        if ($this->matches($userId, $row, $code, $consumeRecovery)) {
            return true;
        }
        $this->recordFailure($userId);
        return false;
    }

    private function matches(int $userId, array $row, string $code, bool $consumeRecovery): bool {
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

    /** Sends a security notice to the user's own e-mail address; delivery problems are logged only. */
    public function notify(int $userId, string $subject, string $text): bool {
        try {
            $query = $this->db->query("SELECT email FROM `" . DB_PREFIX . "user` WHERE user_id = '" . (int)$userId . "' LIMIT 1");
            $email = $query->num_rows ? (string)$query->row['email'] : '';
            return $email !== '' && (new SystemMailer($this->registry))->sendText($email, $subject, $text);
        } catch (\Throwable $e) {
            if ($this->registry->has('log')) { $this->registry->get('log')->write('Two-factor notice not sent: ' . get_class($e)); }
            return false;
        }
    }

    public function remainingRecoveryCodes(int $userId): int {
        $row = $this->getRow($userId);
        if (!$row) {
            return 0;
        }
        $hashes = json_decode((string)$row['recovery_codes'], true);
        return is_array($hashes) ? count($hashes) : 0;
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

    // Database errors propagate: callers must not treat an unreadable MFA state as "not enabled".
    private function getRow(int $userId): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_user_mfa` WHERE user_id = '" . (int)$userId . "' LIMIT 1");
        return $query->num_rows ? $query->row : array();
    }

    private function hashRecoveryCodes(array $codes): string {
        $hashes = array();
        foreach ($codes as $code) {
            $hashes[] = password_hash($this->normalizeRecoveryCode($code), PASSWORD_DEFAULT);
        }
        $encoded = json_encode($hashes, JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('Could not encode recovery codes.');
        }
        return $encoded;
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
