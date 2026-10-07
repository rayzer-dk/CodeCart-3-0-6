<?php
function token($length = 32) {
	// Create random token
	$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	
	$max = strlen($string) - 1;
	
	$token = '';
	
	for ($i = 0; $i < $length; $i++) {
		$token .= $string[random_int(0, $max)];
	}	
	
	return $token;
}

/**
 * Backwards support for timing safe hash string comparisons
 * 
 * http://php.net/manual/en/function.hash-equals.php
 */

if(!function_exists('hash_equals')) {
	function hash_equals($known_string, $user_string) {
		$known_string = (string)$known_string;
		$user_string = (string)$user_string;

		if(strlen($known_string) != strlen($user_string)) {
			return false;
		} else {
			$res = $known_string ^ $user_string;
			$ret = 0;

			for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);

			return !$ret;
		}
	}
}

/**
 * A cross-platform wrapper for glob() that simulates GLOB_BRACE 
 * on systems that do not support it natively.
 */
if (!defined('GLOB_BRACE')) {
    define('GLOB_BRACE', 0);
}
function safe_glob(string $pattern, int $flags = 0): array|false {
    // 1. If GLOB_BRACE is supported and provided, use native glob
    if ((GLOB_BRACE !== 0) && ($flags & GLOB_BRACE)) {
        return glob($pattern, $flags);
    }

    // 2. Fallback: Manually parse {a,b,c} patterns
    if (preg_match('/\{([^}]+)\}/', $pattern, $matches)) {
        $files = [];
        $parts = explode(',', $matches[1]);
        
        foreach ($parts as $part) {
            // Replace the braced section with the current variation
            $subPattern = str_replace($matches[0], trim($part), $pattern);
            
            // Recursively call to handle multiple sets of braces
            $result = safe_glob($subPattern, $flags);
            
            if (is_array($result)) {
                $files = array_merge($files, $result);
            }
        }
        
        // Remove duplicates and sort if GLOB_NOSORT isn't set
        $files = array_unique($files);
        if (!($flags & GLOB_NOSORT)) {
            sort($files);
        }
        
        return $files;
    }

    // 3. Normal glob behavior for non-braced patterns
    return glob($pattern, $flags);
}


/**
 * Hash a new account password with PHP's current password algorithm.
 */
/**
 * Build a simple, stable SEO keyword from a title. Used only for newly created entities.
 */
function codecart_seo_slug($text) {
    $text = (string)$text;
    for ($i = 0; $i < 2; $i++) {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decoded === $text) { break; }
        $text = $decoded;
    }
    $text = utf8_strtolower(trim(strip_tags($text)));

    $map = array(
        'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ye','ж'=>'zh','з'=>'z','и'=>'y','і'=>'i','ї'=>'yi','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ь'=>'','ю'=>'yu','я'=>'ya','ы'=>'y','э'=>'e','ё'=>'yo','ъ'=>'',
        'ä'=>'a','ö'=>'o','ü'=>'u','å'=>'a','æ'=>'ae','ø'=>'o','ß'=>'ss'
    );
    $text = strtr($text, $map);

    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii !== false && $ascii !== '') {
            $text = strtolower($ascii);
        }
    }

    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim((string)$text, '-');
    $text = preg_replace('/-+/', '-', $text);

    return substr($text, 0, 180);
}

function codecart_unique_seo_keyword($db, $keyword, $store_id, $language_id) {
    $base = codecart_seo_slug($keyword);
    if ($base === '') {
        $base = 'item';
    }

    $candidate = $base;
    $suffix = 2;
    while (true) {
        $query = $db->query("SELECT seo_url_id FROM `" . DB_PREFIX . "seo_url` WHERE store_id = '" . (int)$store_id . "' AND language_id = '" . (int)$language_id . "' AND keyword = '" . $db->escape($candidate) . "' LIMIT 1");
        if (!$query->num_rows) {
            return $candidate;
        }
        $tail = '-' . $suffix++;
        $candidate = substr($base, 0, max(1, 180 - strlen($tail))) . $tail;
    }
}

function codecart_fill_new_seo_urls($db, $existing, array $names, array $store_ids) {
    $result = is_array($existing) ? $existing : array();
    if (!$store_ids) {
        $store_ids = array(0);
    }
    $store_ids = array_values(array_unique(array_map('intval', $store_ids)));

    foreach ($store_ids as $store_id) {
        foreach ($names as $language_id => $name) {
            $language_id = (int)$language_id;
            if (!isset($result[$store_id][$language_id]) || trim((string)$result[$store_id][$language_id]) === '') {
                $result[$store_id][$language_id] = codecart_unique_seo_keyword($db, $name, $store_id, $language_id);
            }
        }
    }
    return $result;
}

function codecart_password_hash($password) {
    return password_hash((string)$password, PASSWORD_DEFAULT);
}

/**
 * Verify a modern password_hash() value or an OpenCart legacy SHA1/MD5 hash.
 */
function codecart_password_verify($password, $hash, $salt = '') {
    $password = (string)$password;
    $hash = (string)$hash;
    $salt = (string)$salt;

    if ($hash === '') {
        return false;
    }

    // OpenCart Request::clean() historically HTML-escapes every POST scalar.
    // Passwords are opaque secrets and must not depend on HTML escaping.  During
    // upgrades we must nevertheless accept hashes created from the old cleaned
    // representation, so verify both forms and migrate on the next password write.
    $candidates = array($password);
    $decoded = html_entity_decode($password, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($decoded !== $password) {
        $candidates[] = $decoded;
    }

    $info = password_get_info($hash);
    if (!empty($info['algo'])) {
        foreach ($candidates as $candidate) {
            if (password_verify($candidate, $hash)) {
                return true;
            }
        }
        return false;
    }

    foreach ($candidates as $candidate) {
        $legacy_sha1 = sha1($salt . sha1($salt . sha1($candidate)));
        if (hash_equals($hash, $legacy_sha1) || hash_equals($hash, md5($candidate))) {
            return true;
        }
    }

    return false;
}

/**
 * Recover the original password text from the legacy Request::clean() value.
 * Passwords are never rendered as HTML; entity decoding here restores the value
 * the browser submitted while remaining backward compatible in verification.
 */
function codecart_password_input($password) {
    return html_entity_decode((string)$password, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Return true when a stored password should be upgraded after a successful login.
 */
function codecart_password_needs_rehash($hash) {
    $hash = (string)$hash;
    $info = password_get_info($hash);

    if (empty($info['algo'])) {
        return true;
    }

    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

/**
 * Defensive on-demand widening for legacy stores that have not opened admin yet.
 * Only the known account tables are accepted.
 */
function codecart_ensure_password_column($db, $table) {
    if (!in_array($table, array('user', 'customer'), true)) {
        throw new InvalidArgumentException('Unsupported password table');
    }

    $query = $db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` LIKE 'password'");
    if ($query->num_rows && preg_match('/varchar\\((\\d+)\\)/i', $query->row['Type'], $match) && (int)$match[1] < 255) {
        $db->query("ALTER TABLE `" . DB_PREFIX . $table . "` MODIFY `password` varchar(255) NOT NULL");
    }
}


/**
 * Detect HTTPS without treating the common string "off" as enabled.
 * X-Forwarded-Proto is accepted as a compatibility fallback for reverse proxies.
 */
function codecart_ip_in_cidr($ip, $cidr) {
    $ip = trim((string)$ip);
    $cidr = trim((string)$cidr);

    if ($ip === '' || $cidr === '') {
        return false;
    }

    if (strpos($cidr, '/') === false) {
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($cidr, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ip_bin = inet_pton($ip);
        $cidr_bin = inet_pton($cidr);
        return $ip_bin !== false && $cidr_bin !== false && hash_equals($cidr_bin, $ip_bin);
    }

    list($network, $prefix) = explode('/', $cidr, 2);
    if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($network, FILTER_VALIDATE_IP)) {
        return false;
    }

    $ip_bin = inet_pton($ip);
    $network_bin = inet_pton($network);

    if ($ip_bin === false || $network_bin === false || strlen($ip_bin) !== strlen($network_bin) || !ctype_digit((string)$prefix)) {
        return false;
    }

    $prefix = (int)$prefix;
    $max_bits = strlen($ip_bin) * 8;
    if ($prefix < 0 || $prefix > $max_bits) {
        return false;
    }

    $full_bytes = intdiv($prefix, 8);
    $remaining_bits = $prefix % 8;

    if ($full_bytes > 0 && !hash_equals(substr($network_bin, 0, $full_bytes), substr($ip_bin, 0, $full_bytes))) {
        return false;
    }

    if ($remaining_bits > 0) {
        $mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
        if ((ord($network_bin[$full_bytes]) & $mask) !== (ord($ip_bin[$full_bytes]) & $mask)) {
            return false;
        }
    }

    return true;
}

function codecart_is_trusted_proxy($remote_addr = null) {
    if (!defined('CODECART_TRUSTED_PROXIES') || trim((string)CODECART_TRUSTED_PROXIES) === '') {
        return false;
    }

    $remote_addr = $remote_addr !== null ? (string)$remote_addr : (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '');
    if (!filter_var($remote_addr, FILTER_VALIDATE_IP)) {
        return false;
    }

    foreach (explode(',', (string)CODECART_TRUSTED_PROXIES) as $cidr) {
        if (codecart_ip_in_cidr($remote_addr, trim($cidr))) {
            return true;
        }
    }

    return false;
}

function codecart_is_https(?array $server = null) {
    $server = $server !== null ? $server : $_SERVER;

    if (isset($server['HTTPS'])) {
        $https = strtolower((string)$server['HTTPS']);
        if ($https !== '' && $https !== 'off' && $https !== '0') {
            return true;
        }
    }

    if (isset($server['SERVER_PORT']) && (int)$server['SERVER_PORT'] === 443) {
        return true;
    }

    $remote_addr = isset($server['REMOTE_ADDR']) ? (string)$server['REMOTE_ADDR'] : '';

    if (!codecart_is_trusted_proxy($remote_addr)) {
        return false;
    }

    if (!empty($server['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower(trim(explode(',', (string)$server['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($proto === 'https') {
            return true;
        }
    }

    if (!empty($server['HTTP_X_FORWARDED_SSL']) && strtolower(trim((string)$server['HTTP_X_FORWARDED_SSL'])) === 'on') {
        return true;
    }

    if (!empty($server['HTTP_X_FORWARDED_PORT']) && (int)trim(explode(',', (string)$server['HTTP_X_FORWARDED_PORT'])[0]) === 443) {
        return true;
    }

    return false;
}

function codecart_parse_url_safe($url) {
    try {
        $parts = parse_url((string)$url);
    } catch (ValueError $e) {
        return false;
    }

    return is_array($parts) ? $parts : false;
}

function codecart_is_safe_redirect($url, array $base_urls) {
    $url = trim((string)$url);
    if ($url === '' || preg_match('/[\r\n]/', $url)) {
        return false;
    }

    $target = codecart_parse_url_safe($url);
    if (!is_array($target) || empty($target['scheme']) || empty($target['host']) || isset($target['user']) || isset($target['pass'])) {
        return false;
    }

    $target_scheme = strtolower((string)$target['scheme']);
    if (!in_array($target_scheme, array('http', 'https'), true)) {
        return false;
    }

    $target_host = strtolower(rtrim((string)$target['host'], '.'));
    $target_port = isset($target['port']) ? (int)$target['port'] : ($target_scheme === 'https' ? 443 : 80);
    $target_path = isset($target['path']) ? rawurldecode((string)$target['path']) : '/';

    if (strpos($target_path, "\\") !== false || preg_match('/[\x00-\x1F\x7F]/', $target_path)) {
        return false;
    }

    foreach (explode('/', str_replace('\\', '/', $target_path)) as $segment) {
        if ($segment === '..') {
            return false;
        }
    }

    foreach ($base_urls as $base_url) {
        $base = codecart_parse_url_safe((string)$base_url);
        if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            continue;
        }

        $base_scheme = strtolower((string)$base['scheme']);
        $base_host = strtolower(rtrim((string)$base['host'], '.'));
        $base_port = isset($base['port']) ? (int)$base['port'] : ($base_scheme === 'https' ? 443 : 80);
        $base_path = isset($base['path']) ? rawurldecode((string)$base['path']) : '/';

        if (strpos($base_path, "\\") !== false || preg_match('/[\x00-\x1F\x7F]/', $base_path)) {
            continue;
        }

        $base_path = rtrim('/' . ltrim($base_path, '/'), '/') . '/';

        if ($target_scheme !== $base_scheme || $target_host !== $base_host || $target_port !== $base_port) {
            continue;
        }

        $normalized_target = '/' . ltrim($target_path, '/');
        if ($base_path === '/' || $normalized_target === rtrim($base_path, '/') || strpos($normalized_target, $base_path) === 0) {
            return true;
        }
    }

    return false;
}

function codecart_cache_prefix() {
    if (defined('CACHE_PREFIX') && (string)CACHE_PREFIX !== '') {
        return (string)CACHE_PREFIX;
    }

    $database = defined('DB_DATABASE') ? (string)DB_DATABASE : '';
    $prefix = defined('DB_PREFIX') ? (string)DB_PREFIX : '';
    $system = defined('DIR_SYSTEM') ? (string)realpath(DIR_SYSTEM) : '';

    return 'oc_' . substr(hash('sha256', $database . '|' . $prefix . '|' . $system), 0, 16) . '_';
}

/**
 * Normalize a SQL DATE value for strict MySQL/MariaDB modes.
 * Legacy 0000-00-00 and empty values are mapped to an explicit caller-provided boundary.
 */
function codecart_normalize_date($value, $fallback = '1970-01-01') {
    $value = trim((string)$value);

    if ($value === '' || strpos($value, '0000-00-00') === 0) {
        return (string)$fallback;
    }

    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $match)) {
        return (string)$fallback;
    }

    $year = (int)$match[1];
    $month = (int)$match[2];
    $day = (int)$match[3];

    if ($year < 1000 || $year > 9999 || !checkdate($month, $day, $year)) {
        return (string)$fallback;
    }

    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/**
 * Write the OpenCart session cookie with safe defaults while respecting server policy.
 */
function codecart_set_session_cookie($name, $session_id, $lifetime_override = null) {
    $lifetime = $lifetime_override !== null ? max(0, (int)$lifetime_override) : (int)ini_get('session.cookie_lifetime');
    $path = (string)ini_get('session.cookie_path');
    $domain = (string)ini_get('session.cookie_domain');
    $same_site = strtolower(trim((string)ini_get('session.cookie_samesite')));
    $secure = codecart_is_https();

    $options = array(
        'expires' => $lifetime ? time() + $lifetime : 0,
        'path' => $path !== '' ? $path : '/',
        'secure' => $secure,
        'httponly' => true
    );

    if ($domain !== '') {
        $options['domain'] = $domain;
    }

    // SameSite=None is ignored by modern browsers without Secure.
    if (in_array($same_site, array('lax', 'strict'), true) || ($same_site === 'none' && $secure)) {
        $options['samesite'] = ucfirst($same_site);
    }

    return setcookie((string)$name, (string)$session_id, $options);
}

/**
 * Normalize proxy/client IP input to a single valid IPv4/IPv6 address.
 */

/**
 * Return a per-session CSRF token for a storefront scope.
 *
 * The token is kept in server-side session storage. It is never placed in a
 * URL, so it cannot leak through Referer headers or access logs.
 */
function codecart_csrf_token($session, $scope = 'storefront') {
    $scope = preg_replace('/[^a-z0-9_.-]/i', '', (string)$scope);
    if ($scope === '') { $scope = 'storefront'; }

    if (!isset($session->data['codecart_csrf']) || !is_array($session->data['codecart_csrf'])) {
        $session->data['codecart_csrf'] = array();
    }

    $token = isset($session->data['codecart_csrf'][$scope]) ? (string)$session->data['codecart_csrf'][$scope] : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            $bytes = function_exists('openssl_random_pseudo_bytes') ? openssl_random_pseudo_bytes(32) : false;
            if (!is_string($bytes) || strlen($bytes) !== 32) {
                throw new \RuntimeException('Unable to generate CSRF token securely.', 0, $e);
            }
            $token = bin2hex($bytes);
        }
        $session->data['codecart_csrf'][$scope] = $token;
    }

    return $token;
}

/**
 * Validate a storefront CSRF token without consuming it.
 */
function codecart_csrf_validate($session, $token, $scope = 'storefront') {
    $scope = preg_replace('/[^a-z0-9_.-]/i', '', (string)$scope);
    if ($scope === '') { $scope = 'storefront'; }
    $expected = isset($session->data['codecart_csrf'][$scope]) ? (string)$session->data['codecart_csrf'][$scope] : '';
    $token = is_string($token) ? $token : '';

    return $expected !== '' && strlen($expected) === 64 && strlen($token) === 64 && hash_equals($expected, $token);
}

function codecart_normalize_ip($value) {
    foreach (explode(',', (string)$value) as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return '';
}


/**
 * Resolve the client IP behind explicitly trusted reverse proxies.
 *
 * The chain is evaluated from the immediate peer backwards. This prevents a
 * client-supplied left-most X-Forwarded-For value from bypassing rate limits
 * when a trusted proxy appends the real client address.
 */
function codecart_client_ip(?array $server = null) {
    $server = $server !== null ? $server : $_SERVER;
    $remote = isset($server['REMOTE_ADDR']) ? codecart_normalize_ip($server['REMOTE_ADDR']) : '';

    if ($remote === '' || !codecart_is_trusted_proxy($remote)) {
        return $remote;
    }

    $chain = array();
    if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', (string)$server['HTTP_X_FORWARDED_FOR']) as $candidate) {
            $candidate = codecart_normalize_ip($candidate);
            if ($candidate !== '') {
                $chain[] = $candidate;
            }
        }
    }

    $chain[] = $remote;

    for ($i = count($chain) - 1; $i >= 0; $i--) {
        $ip = $chain[$i];
        if (!codecart_is_trusted_proxy($ip)) {
            return $ip;
        }
    }

    // All hops are trusted proxies. Return the furthest valid address rather
    // than accepting an arbitrary malformed header value.
    return $chain ? $chain[0] : $remote;
}
