<?php
namespace CodeCart\Core;

/** Aggregated content 404s, with no network lookups or permanent visitor identity. */
final class LostUrlMonitor {
    private $registry;
    private $config;
    public function __construct($registry) {
        $this->registry = $registry;
        $this->config = $registry->get('config');
    }

    public static function candidate(array $server, array $get, string $base, string $prefix = ''): ?array {
        if (($server['REQUEST_METHOD'] ?? '') !== 'GET' || strtolower((string)($server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest' || stripos((string)($server['HTTP_ACCEPT'] ?? ''), 'application/json') !== false) { return null; }
        $uri = $server['REQUEST_URI'] ?? '';
        if (!is_string($uri) || strlen($uri) > 4096 || preg_match('/[\x00-\x20]/', $uri)) { return null; }
        $parts = parse_url($uri);
        if (!$parts || isset($parts['host']) || isset($parts['scheme'])) { return null; }
        $path = $parts['path'] ?? '/';
        if (strlen($path) > 900 || preg_match('/%2f|%5c/i', $path)) { return null; }
        $decoded = rawurldecode($path);
        if (!preg_match('//u', $decoded) || preg_match('/[\x00-\x20<>"\x27\\\\]/u', $decoded) || preg_match('~(?:^|/)\.\.(?:/|$)~', $decoded)) { return null; }
        $basePath = rtrim((string)(parse_url($base, PHP_URL_PATH) ?: ''), '/');
        if ($basePath !== '' && strpos($decoded, $basePath . '/') === 0) { $decoded = substr($decoded, strlen($basePath)); }
        if (preg_match('/^[a-z][a-z0-9-]{0,15}$/D', $prefix) && strpos($decoded, '/' . $prefix . '/') === 0) { $decoded = substr($decoded, strlen($prefix) + 1); }
        if (preg_match('~(?:^|/)(?:\.[^/]*|wp-[^/]*|wordpress|administrator|admin|system|vendor|storage|image|images|assets|catalog|cache|api|cron|account|checkout|payment|shipping|cgi-bin|phpmyadmin)(?:/|$)~i', $decoded) || preg_match('/\.(?:php(?!$)|php$|js|css|map|json|xml|txt|ico|png|jpe?g|webp|avif|gif|svg|zip|sql|bak|ini|log|asp|aspx|env)(?:$|\/)/i', $decoded)) {
            if (basename($decoded) !== 'index.php' || trim($decoded, '/') !== 'index.php') { return null; }
        }
        $query = array();
        if (!empty($parts['query'])) { parse_str($parts['query'], $query); }
        $route = isset($query['route']) && is_string($query['route']) ? $query['route'] : '';
        $ids = array('product_id' => 'product', 'article_id' => 'article', 'news_id' => 'article', 'uni_news_id' => 'article', 'information_id' => 'content', 'manufacturer_id' => 'content', 'path' => 'content', 'news_path' => 'content');
        $kind = 'unknown'; $safe = array();
        if (trim($decoded, '/') === 'index.php' || $decoded === '/') {
            if (!preg_match('~^(?:product/(?:product|category|manufacturer/info)|information/(?:information|uni_news(?:_story|/info)?)|blog/(?:article|category)|extension/module/uni_news(?:/info)?)$~D', $route)) { return null; }
            $safe['route'] = $route;
            foreach ($ids as $key => $type) {
                if (isset($query[$key]) && is_scalar($query[$key]) && preg_match('/^[0-9]+(?:_[0-9]+)*$/D', (string)$query[$key]) && strlen((string)$query[$key]) <= 100) {
                    $safe[$key] = (string)$query[$key]; if ($kind === 'unknown' || $type === 'product' || $type === 'article') { $kind = $type; }
                }
            }
            if (count($safe) < 2) { return null; }
        } else {
            $last = basename(rtrim($decoded, '/'));
            if (strlen($last) < 3 || !preg_match('/^[\p{L}\p{N}][\p{L}\p{N}_.-]*$/uD', $last)) { return null; }
            foreach ($ids as $key => $type) { if (!empty($get[$key]) && is_scalar($get[$key])) { $kind = $type; break; } }
            if ($kind === 'unknown' && preg_match('~(?:^|/)(?:news|blog|articles?)(?:/|$)~i', $decoded)) { $kind = 'article'; }
        }
        $agent = (string)($server['HTTP_USER_AGENT'] ?? '');
        $bot = (bool)preg_match('/bot|crawl|spider|slurp|headless|python|curl|wget|httpclient|facebookexternalhit|bingpreview/i', $agent);
        if (!$bot && stripos($agent, 'Mozilla/') === false && stripos($agent, 'Opera/') === false) { return null; }
        $ref = self::safeReferer((string)($server['HTTP_REFERER'] ?? ''));
        $refHost = strtolower((string)(parse_url($ref, PHP_URL_HOST) ?: ''));
        $storeHost = strtolower((string)(parse_url($base, PHP_URL_HOST) ?: ''));
        $referred = $refHost !== '' && $refHost === $storeHost;
        foreach (array('google.com','google.dk','google.com.ua','bing.com','duckduckgo.com','yahoo.com','facebook.com','instagram.com','t.co','t.me') as $host) {
            if ($refHost === $host || substr($refHost, -strlen('.' . $host)) === '.' . $host) { $referred = true; }
        }
        $slug = empty($safe) && self::validSlug(basename(rtrim($decoded, '/'))) ? basename(rtrim($decoded, '/')) : '';
        $url = $path . ($safe ? '?' . http_build_query($safe, '', '&', PHP_QUERY_RFC3986) : '');
        return array('url' => $url, 'slug' => $slug, 'kind' => $kind, 'bot' => $bot, 'referer' => $ref, 'referred' => $referred);
    }

    private static function safeReferer(string $url): string {
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20<>"\x27\\\\]/', $url)) { return ''; }
        $p = parse_url($url);
        if (!$p || !in_array(strtolower($p['scheme'] ?? ''), array('http','https'), true) || empty($p['host']) || isset($p['user']) || isset($p['pass'])) { return ''; }
        return substr(strtolower($p['scheme']) . '://' . $p['host'] . (isset($p['port']) ? ':' . (int)$p['port'] : '') . ($p['path'] ?? '/'), 0, 1000);
    }

    public static function validSlug(string $slug): bool { return (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/D', $slug); }

    public function record(array $server, array $get): void {
        if (!$this->config->get('codecart_lost_url_status')) { return; }
        $base = (string)$this->config->get('config_url');
        $item = self::candidate($server, $get, $base, (string)$this->config->get('codecart_language_prefix_current'));
        if (!$item) { return; }
        $db = $this->registry->get('db');
        $store = (int)$this->config->get('config_store_id');
        $hash = hash('sha256', (int)$this->config->get('config_language_id') . '|' . $item['url']);
        $session = $this->registry->get('session');
        if (!$item['bot'] && $session) {
            $seen = $session->data['codecart_lost_url_seen'] ?? array();
            if (isset($seen[$hash]) && time() - (int)$seen[$hash] < 60) { return; }
            $seen[$hash] = time();
            $session->data['codecart_lost_url_seen'] = array_slice($seen, -30, null, true);
        }
        $browser = $item['bot'] ? 0 : 1;
        $ref = $browser && $item['referred'] ? 1 : 0;
        $changes = "browser_hits=browser_hits+" . $browser . ", bot_hits=bot_hits+" . (1 - $browser) . ", referred_hits=referred_hits+" . $ref . ", last_seen=NOW()";
        if ($browser && $item['referer'] !== '') { $changes .= ", referer='" . $db->escape($item['referer']) . "'"; }
        $where = "store_id=" . $store . " AND url_key='" . $db->escape($hash) . "'";
        $db->query("UPDATE `" . DB_PREFIX . "codecart_lost_url` SET " . $changes . " WHERE " . $where);
        // Declared crawlers only contribute to URLs already seen from browsers.
        if ($db->countAffected() || $item['bot']) { return; }
        $lock = 'cc_lost_url_' . $store;
        $q = $db->query("SELECT GET_LOCK('" . $db->escape($lock) . "',0) AS acquired");
        if (empty($q->row['acquired'])) { return; }
        try {
            $q = $db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_lost_url` WHERE store_id=" . $store);
            if ((int)$q->row['total'] >= 5000) { return; }
            $db->query("INSERT INTO `" . DB_PREFIX . "codecart_lost_url` SET store_id=" . $store . ", language_id=" . (int)$this->config->get('config_language_id') . ", url_key='" . $db->escape($hash) . "', url='" . $db->escape($item['url']) . "', slug='" . $db->escape($item['slug']) . "', kind='" . $db->escape($item['kind']) . "', browser_hits=1, bot_hits=0, referred_hits=" . $ref . ", referer='" . $db->escape($item['referer']) . "', status='new', first_seen=NOW(), last_seen=NOW() ON DUPLICATE KEY UPDATE " . $changes);
        } finally { $db->query("SELECT RELEASE_LOCK('" . $db->escape($lock) . "')"); }
    }

    public function install(): void {
        $db = $this->registry->get('db');
        $db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_lost_url` (lost_url_id bigint unsigned NOT NULL AUTO_INCREMENT, store_id int unsigned NOT NULL DEFAULT 0, language_id int unsigned NOT NULL DEFAULT 0, url_key char(64) NOT NULL, url varchar(1024) NOT NULL, slug varchar(191) NOT NULL DEFAULT '', kind varchar(16) NOT NULL DEFAULT 'unknown', browser_hits int unsigned NOT NULL DEFAULT 0, bot_hits int unsigned NOT NULL DEFAULT 0, referred_hits int unsigned NOT NULL DEFAULT 0, referer varchar(1024) NOT NULL DEFAULT '', status varchar(16) NOT NULL DEFAULT 'new', first_seen datetime NOT NULL, last_seen datetime NOT NULL, PRIMARY KEY(lost_url_id), UNIQUE KEY store_url(store_id,url_key), KEY status_seen(store_id,status,last_seen), KEY slug_store(slug,store_id), KEY cleanup_seen(last_seen)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_lost_url_redirect` (redirect_id int unsigned NOT NULL AUTO_INCREMENT, store_id int unsigned NOT NULL DEFAULT 0, language_id int unsigned NOT NULL DEFAULT 0, old_slug varchar(191) NOT NULL, new_slug varchar(191) NOT NULL, date_modified datetime NOT NULL, PRIMARY KEY(redirect_id), UNIQUE KEY store_slug(store_id,language_id,old_slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function redirect(array $server): ?string {
        if (!$this->config->get('codecart_lost_url_status') || ($server['REQUEST_METHOD'] ?? '') !== 'GET' || strtolower((string)($server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest' || stripos((string)($server['HTTP_ACCEPT'] ?? ''), 'application/json') !== false) { return null; }
        $uri = $server['REQUEST_URI'] ?? '';
        if (!is_string($uri) || strlen($uri) > 2048 || preg_match('/[\x00-\x20<>"\x27\\\\]/', $uri)) { return null; }
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || strpos($path, '..') !== false || preg_match('/%2f|%5c/i', $path)) { return null; }
        if (preg_match('~(?:^|/)(?:\.[^/]*|wp-[^/]*|wordpress|administrator|admin|system|vendor|storage|image|images|assets|catalog|cache|api|cron|account|checkout|payment|shipping|cgi-bin|phpmyadmin)(?:/|$)~i', rawurldecode($path))) { return null; }
        $slug = rawurldecode(basename(rtrim($path, '/')));
        if (!self::validSlug($slug)) { return null; }
        $db = $this->registry->get('db'); $store = (int)$this->config->get('config_store_id');
        $q = $db->query("SELECT r.new_slug FROM `" . DB_PREFIX . "codecart_lost_url_redirect` r WHERE r.store_id=" . $store . " AND language_id=" . (int)$this->config->get('config_language_id') . " AND r.old_slug='" . $db->escape($slug) . "' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` s WHERE s.store_id=r.store_id AND s.language_id=r.language_id AND s.keyword=r.old_slug) AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` t WHERE t.store_id=r.store_id AND t.language_id=r.language_id AND t.keyword=r.new_slug) LIMIT 1");
        if (!$q->num_rows || !self::validSlug($q->row['new_slug']) || $q->row['new_slug'] === $slug) { return null; }
        $base = (string)$this->config->get('config_url');
        if (!empty($server['HTTPS']) && strtolower((string)$server['HTTPS']) !== 'off') { $base = (string)$this->config->get('config_ssl') ?: $base; }
        $relative = substr($path, strlen((string)(parse_url($base, PHP_URL_PATH) ?: '/')));
        $configuredPrefix = (string)$this->config->get('codecart_language_prefix_current');
        $prefix = preg_match('/^[a-z][a-z0-9-]{0,15}$/D', $configuredPrefix) && strpos($relative, $configuredPrefix . '/') === 0 ? $configuredPrefix . '/' : '';
        return rtrim($base, '/') . '/' . $prefix . $q->row['new_slug'];
    }

    public function prune(): array {
        if (!$this->config->get('codecart_lost_url_status')) { return array('success'=>true,'message'=>'Disabled'); }
        $db = $this->registry->get('db');
        // Keep unresolved useful addresses and all manually configured redirects.
        $days = self::retentionDays($this->config->get('codecart_lost_url_days'));
        $all = $this->config->get('codecart_lost_url_cleanup_mode') === 'all';
        $db->query("DELETE FROM `" . DB_PREFIX . "codecart_lost_url` WHERE last_seen < DATE_SUB(NOW(),INTERVAL " . $days . " DAY)" . ($all ? '' : " AND (status IN ('ignored','fixed') OR (browser_hits<2 AND referred_hits=0))") . " ORDER BY last_seen ASC LIMIT 500");
        return array('success'=>true,'message'=>'Pruned: ' . (int)$db->countAffected());
    }

    public static function retentionDays($value): int {
        $days = is_scalar($value) ? (int)$value : 0;
        return in_array($days, array(30,60,90), true) ? $days : 60;
    }
}
