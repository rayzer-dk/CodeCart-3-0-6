<?php
/** Defensive contract tests. No network, mail delivery or persistent site data. */
error_reporting(E_ALL);
ob_start();
require dirname(__DIR__) . '/upload/system/engine/registry.php';
require dirname(__DIR__) . '/upload/system/engine/controller.php';
require dirname(__DIR__) . '/upload/system/library/config.php';
require dirname(__DIR__) . '/upload/system/library/request.php';
define('DB_PREFIX', 'qa_');
define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
define('DIR_APPLICATION', dirname(__DIR__) . '/upload/catalog/');
spl_autoload_register(function ($class) {
    $prefix = 'CodeCart\\Core\\';
    if (strpos($class, $prefix) === 0) {
        require DIR_SYSTEM . 'library/codecart/src/' . substr($class, strlen($prefix)) . '.php';
    }
});
$failures = 0;
$checks = 0;
function check($condition, string $name): void {
    global $failures, $checks;
    $checks++;
    if (!$condition) { $failures++; }
    echo ($condition ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
}
function result(array $rows = array()): object {
    return (object)array('rows' => $rows, 'row' => $rows[0] ?? array(), 'num_rows' => count($rows));
}
function registry($db = null): Registry {
    $registry = new Registry();
    $registry->set('config', new Config());
    $request = new Request();
    $request->server = array('REMOTE_ADDR' => '192.0.2.10', 'REQUEST_METHOD' => 'GET');
    $registry->set('request', $request);
    $registry->set('session', (object)array('data' => array()));
    $registry->set('db', $db);
    return $registry;
}

final class DefensiveMfaDb {
    public array $mfa = array();
    public array $attempts = array();
    public array $queries = array();
    public $lockResult = 1;
    public bool $lockError = false;
    public string $failQuery = '';
    private int $affected = 0;
    public function escape($value): string { return addslashes((string)$value); }
    public function countAffected(): int { return $this->affected; }
    public function query(string $sql): object {
        $this->queries[] = $sql;
        $this->affected = 0;
        if ($this->failQuery !== '' && strpos($sql, $this->failQuery) === 0) { throw new RuntimeException('QA database failure'); }
        if (strpos($sql, 'SELECT GET_LOCK(') === 0) {
            if ($this->lockError) { throw new RuntimeException('QA advisory lock unavailable'); }
            return result(array(array('acquired' => $this->lockResult)));
        }
        if (strpos($sql, 'SELECT RELEASE_LOCK(') === 0) { return result(array(array('released' => 1))); }
        if (in_array($sql, array('START TRANSACTION', 'COMMIT', 'ROLLBACK'), true)) { return result(); }
        if (strpos($sql, 'SHOW TABLES') === 0) { return result(array(array('table' => 'qa_codecart_admin_login_attempt'))); }
        if (strpos($sql, 'codecart_admin_login_attempt') !== false) {
            preg_match("/username = '([^']+)'/", $sql, $name);
            preg_match("/ip = '([^']+)'/", $sql, $source);
            if (strpos($sql, 'SELECT ') === 0) {
                $matches = array_filter($this->attempts, function ($row) use ($name, $source) {
                    return $row['time'] > time() - 900 && (!$name || $row['username'] === $name[1]) && (!$source || $row['ip'] === $source[1]);
                });
                $times = array_column($matches, 'time');
                return result(array(array('total' => count($times), 'retry_after' => $times ? max(1, 900 - (time() - min($times))) : 0)));
            }
            if (strpos($sql, 'INSERT ') === 0) {
                $this->attempts[] = array('username' => $name[1], 'ip' => $source[1], 'time' => time());
            } elseif (strpos($sql, 'DELETE ') === 0) {
                $this->attempts = array_values(array_filter($this->attempts, function ($row) use ($name, $source) {
                    return ($name && $row['username'] !== $name[1]) || ($source && $row['ip'] !== $source[1]);
                }));
            }
            return result();
        }
        if (strpos($sql, 'codecart_user_mfa') !== false) {
            if (strpos($sql, 'SELECT ') === 0) { return result($this->mfa ? array($this->mfa) : array()); }
            if (preg_match("/SET last_counter = '(\d+)'/", $sql, $counter)) {
                if ((int)$this->mfa['last_counter'] < (int)$counter[1]) { $this->mfa['last_counter'] = (int)$counter[1]; $this->affected = 1; }
            } elseif (preg_match("/SET recovery_codes = '((?:\\\\.|[^'])*)'/", $sql, $codes)) {
                $this->mfa['recovery_codes'] = stripslashes($codes[1]); $this->affected = 1;
            }
            return result();
        }
        throw new RuntimeException('Unexpected SQL in MFA fixture: ' . $sql);
    }
}

$case = $argv[1] ?? ($_GET['case'] ?? 'html');
if (!in_array($case, array('html', 'totp', 'cart', 'device-admin', 'device-catalog'), true)) {
    check(false, 'Unknown or empty test case: ' . $case);
    echo 'RESULT ' . $checks . ' checks, ' . $failures . ' failures' . PHP_EOL;
    exit(1);
}
final class DefensiveSessionAdapter {
    public array $records = array();
    public string $failure = '';
    public function read($id): array { return $this->records[$id] ?? array(); }
    public function write($id, $data): bool {
        if ($this->failure === 'throw') { throw new RuntimeException('QA session write unavailable'); }
        if ($this->failure === 'false') { return false; }
        $this->records[$id] = $data; return true;
    }
    public function destroy($id): bool { unset($this->records[$id]); return true; }
}
final class DefensiveCartDb {
    public array $rows = array();
    public array $customer = array();
    public function escape($value): string { return addslashes((string)$value); }
    public function query(string $sql): object {
        if (strpos($sql, 'FROM qa_customer ') !== false) { return result(array($this->customer)); }
        if (strpos($sql, 'UPDATE qa_customer ') === 0) { return result(); }
        if (strpos($sql, 'qa_cart') === false) { throw new RuntimeException('Unexpected cart SQL: ' . $sql); }
        $where = explode(' WHERE ', $sql, 2)[1] ?? '';
        preg_match_all("/`?(api_id|customer_id|session_id|cart_id|product_id|recurring_id|option)`? = '((?:\\\\.|[^'])*)'/", $where, $matches, PREG_SET_ORDER);
        $filtered = array_filter($this->rows, function ($row) use ($matches) {
            foreach ($matches as $match) { if ((string)$row[$match[1]] !== stripslashes($match[2])) { return false; } }
            return true;
        });
        if (strpos($sql, 'SELECT COUNT') === 0) { return result(array(array('total' => count($filtered)))); }
        if (strpos($sql, 'SELECT ') === 0) { return result(array_values($filtered)); }
        if (strpos($sql, 'DELETE ') === 0) { foreach ($filtered as $key => $row) { unset($this->rows[$key]); } return result(); }
        if (strpos($sql, 'UPDATE ') === 0) {
            foreach ($filtered as $key => $row) {
                if (preg_match("/SET session_id = '([^']+)'/", $sql, $session)) { $this->rows[$key]['session_id'] = $session[1]; }
                if (preg_match('/SET quantity = \(quantity \+ (\d+)\)/', $sql, $quantity)) { $this->rows[$key]['quantity'] += (int)$quantity[1]; }
            }
            return result();
        }
        if (strpos($sql, 'INSERT ') === 0) {
            preg_match_all("/`?(api_id|customer_id|session_id|product_id|recurring_id|option|quantity)`? = '((?:\\\\.|[^'])*)'/", $sql, $values, PREG_SET_ORDER);
            $row = array('cart_id' => count($this->rows) + 100);
            foreach ($values as $value) { $row[$value[1]] = stripslashes($value[2]); }
            $this->rows[] = $row;
            return result();
        }
        throw new RuntimeException('Unsupported cart SQL: ' . $sql);
    }
}
final class DefensiveResponse {
    public array $redirects = array();
    public array $headers = array();
    public int $status = 200;
    public function redirect($url, $status = 302): void { $this->redirects[] = $url; }
    public function setOutput($output): void {}
    public function addHeader($header): void { $this->headers[] = $header; }
    public function setStatusCode($status): void { $this->status = $status; }
}
final class DefensiveDeviceDb {
    public array $rows = array();
    public int $starts = 0;
    public bool $failStart = false;
    private int $lastId = 0;
    public function escape($value): string { return addslashes((string)$value); }
    public function getLastId(): int { return $this->lastId; }
    public function query(string $sql): object {
        if (strpos($sql, 'SHOW TABLES') === 0) { return result(strpos($sql, 'notification') !== false ? array() : array(array('table' => 'qa'))); }
        if (strpos($sql, 'INSERT ') === 0) {
            if (strpos($sql, "status='pending'") !== false) { $this->starts++; if ($this->failStart) { throw new RuntimeException('QA challenge unavailable'); } }
            preg_match_all("/`?(authorize_id|user_id|customer_id|token|code_hash|attempts|status|date_expire)`?='((?:\\\\.|[^'])*)'/", $sql, $matches, PREG_SET_ORDER);
            $row = array('authorize_id' => ++$this->lastId);
            foreach ($matches as $match) { $row[$match[1]] = stripslashes($match[2]); }
            $this->rows[$this->lastId] = $row;
            return result();
        }
        $where = explode(' WHERE ', $sql, 2)[1] ?? '';
        preg_match_all("/`?(authorize_id|user_id|customer_id|token|status)`?='([^']*)'/", $where, $matches, PREG_SET_ORDER);
        $rows = array_filter($this->rows, function ($row) use ($matches) {
            foreach ($matches as $match) { if ((string)($row[$match[1]] ?? '') !== $match[2]) { return false; } }
            return true;
        });
        if (strpos($sql, 'SELECT ') === 0) { return result(array_values($rows)); }
        if (strpos($sql, 'UPDATE ') === 0) {
            $set = explode(' WHERE ', $sql)[0];
            preg_match_all("/(status|attempts)='([^']*)'/", $set, $values, PREG_SET_ORDER);
            foreach ($rows as $id => $row) { foreach ($values as $value) { $this->rows[$id][$value[1]] = $value[2]; } }
            return result();
        }
        throw new RuntimeException('Unexpected device SQL: ' . $sql);
    }
}
final class DefensiveLanguage {
    public array $strings = array();
    public function get($key) { return $this->strings[$key] ?? $key; }
}
final class DefensiveLoad {
    public array $viewData = array();
    public string $email = 'qa@example.invalid';
    private Registry $registry;
    private string $area;
    public function __construct(Registry $registry, string $area) { $this->registry = $registry; $this->area = $area; }
    public function language($route): void {
        $_ = array(); require dirname(__DIR__) . '/upload/' . $this->area . '/language/en-gb/' . $route . '.php';
        $this->registry->get('language')->strings = $_;
    }
    public function model($route): void {
        $account = new class($this->email) {
            private string $email;
            public function __construct($email) { $this->email = $email; }
            public function getUser($id): array { return array('email' => $this->email); }
            public function getCustomer($id): array { return array('email' => $this->email); }
        };
        $this->registry->set('model_' . str_replace('/', '_', $route), $account);
    }
    public function controller($route): string { return ''; }
    public function view($route, $data): string { $this->viewData = $data; return ''; }
}
if (strpos($case, 'device-') === 0) {
    class Mail {
        public static string $mode = 'throw';
        public static int $calls = 0;
        public static string $code = '';
        public $parameter, $smtp_hostname, $smtp_username, $smtp_password, $smtp_port, $smtp_timeout;
        public function __construct($engine) {}
        public function setTo($value): void {}
        public function setFrom($value): void {}
        public function setSender($value): void {}
        public function setSubject($value): void {}
        public function setText($value): void { preg_match('/\b(\d{6})\b/', $value, $match); self::$code = $match[1] ?? ''; }
        public function send() { self::$calls++; if (self::$mode === 'throw') { throw new RuntimeException('QA mail unavailable'); } return self::$mode !== 'false'; }
    }
    require DIR_SYSTEM . 'engine/action.php';
    $admin = $case === 'device-admin'; $area = $admin ? 'admin' : 'catalog'; $type = $admin ? 'admin' : 'customer';
    require dirname(__DIR__) . '/upload/' . $area . '/controller/' . ($admin ? 'common' : 'account') . '/device_authorize.php';
    require dirname(__DIR__) . '/upload/' . $area . '/controller/startup/device_authorization.php';
    foreach (array('throw', 'false', 'invalid-email', 'challenge') as $failure) {
        $db = new DefensiveDeviceDb(); $db->failStart = $failure === 'challenge';
        $registry = registry($db);
        $registry->get('config')->set('codecart_' . $type . '_device_authorize_status', 1);
        $registry->get('config')->set('config_name', 'QA');
        $registry->get('session')->data['user_token'] = 'qa-token';
        $registry->get('request')->get['route'] = $admin ? 'common/dashboard' : 'account/account';
        $registry->set($admin ? 'user' : 'customer', new class {
            public function isLogged(): bool { return true; }
            public function getId(): int { return 7; }
        });
        $registry->set('response', new DefensiveResponse());
        $registry->set('language', new DefensiveLanguage());
        $load = new DefensiveLoad($registry, $area); if ($failure === 'invalid-email') { $load->email = 'invalid'; }
        $registry->set('load', $load);
        $registry->set('document', new class { public function setTitle($title): void {} public function setRobots($robots): void {} });
        $registry->set('url', new class { public function link($route, $query = '', $ssl = false): string { return $route; } });
        $registry->set('log', new class { public function write($message): void {} });
        $class = $admin ? 'ControllerCommonDeviceAuthorize' : 'ControllerAccountDeviceAuthorize';
        Mail::$mode = $failure; Mail::$calls = 0;
        (new $class($registry))->index();
        check(!$registry->get('response')->redirects && empty($registry->get('session')->data['codecart_' . $type . '_device_bypass']), $type . ' ' . $failure . ' cannot authorize');
        check((new ControllerStartupDeviceAuthorization($registry))->index() instanceof Action, $type . ' ' . $failure . ' protected route remains gated');
        $calls = Mail::$calls; $starts = $db->starts;
        (new $class($registry))->index();
        check(Mail::$calls === $calls && $db->starts === $starts && !empty($load->viewData['error_warning']), $type . ' ' . $failure . ' retry is limited and explained');
        $db->failStart = false; $load->email = 'qa@example.invalid'; Mail::$mode = 'success';
        $registry->get('session')->data['codecart_' . $type . '_device_retry'] = array('entity_id' => 7, 'next_at' => time() - 1);
        (new $class($registry))->index();
        $registry->get('request')->server['REQUEST_METHOD'] = 'POST';
        $registry->get('request')->post['code'] = Mail::$code;
        (new $class($registry))->index();
        check($registry->get('response')->redirects && !(new ControllerStartupDeviceAuthorization($registry))->index(), $type . ' successful retry and code authorize normally');
    }
}
if ($case === 'cart') {
    require DIR_SYSTEM . 'library/session.php';
    require DIR_SYSTEM . 'library/cart/customer.php';
    require DIR_SYSTEM . 'library/cart/cart.php';
    require DIR_SYSTEM . 'library/cart/user.php';
    require DIR_SYSTEM . 'helper/general.php';
    function utf8_strtolower($text) { return mb_strtolower($text, 'UTF-8'); }
    $db = new DefensiveCartDb();
    $db->customer = array('customer_id' => 7, 'firstname' => 'QA', 'lastname' => 'Customer', 'customer_group_id' => 1, 'email' => 'qa@example.invalid', 'telephone' => '', 'newsletter' => 0, 'address_id' => 0, 'password' => codecart_password_hash('qa-password-example'), 'salt' => '');
    $registry = registry($db);
    $registry->get('config')->set('session_name', 'QA_SESSION');
    $adapter = new DefensiveSessionAdapter();
    $reflection = new ReflectionClass(Session::class);
    $session = $reflection->newInstanceWithoutConstructor();
    $property = $reflection->getProperty('adaptor'); $property->setAccessible(true); $property->setValue($session, $adapter);
    $old = str_repeat('a', 32); $session->start($old); $registry->set('session', $session);
    $base = array('cart_id' => 1, 'api_id' => 0, 'customer_id' => 0, 'session_id' => $old, 'product_id' => 10, 'recurring_id' => 0, 'option' => '{"21":"22"}', 'quantity' => 2);
    $db->rows = array($base, array_merge($base, array('cart_id' => 2, 'customer_id' => 7, 'session_id' => str_repeat('b', 32), 'quantity' => 3)), array_merge($base, array('cart_id' => 3, 'api_id' => 1)), array_merge($base, array('cart_id' => 4, 'customer_id' => 8)), array_merge($base, array('cart_id' => 5, 'option' => '{"21":"23"}', 'quantity' => 1)));
    $customer = new \Cart\Customer($registry); $registry->set('customer', $customer);
    check(!$customer->login('qa@example.invalid', 'wrong-password') && $session->getId() === $old && $db->rows[0]['session_id'] === $old, 'failed login preserves guest session and cart');
    check($customer->login('qa@example.invalid', 'qa-password-example'), 'successful customer authentication');
    $new = $session->getId();
    check($old !== $new && !isset($adapter->records[$old]) && ($adapter->records[$new]['customer_id'] ?? 0) === 7, 'authenticated session securely rotates');
    check($db->rows[0]['session_id'] === $new && $db->rows[4]['session_id'] === $new, 'all guest cart option variants follow successful rotation');
    check($db->rows[2]['session_id'] === $old && $db->rows[3]['session_id'] === $old, 'API and another customer rows stay untouched');
    new \Cart\Cart($registry);
    $owned = array_values(array_filter($db->rows, function ($row) use ($new) { return (int)$row['api_id'] === 0 && (int)$row['customer_id'] === 7 && $row['session_id'] === $new; }));
    $quantities = array(); foreach ($owned as $row) { $quantities[$row['option']] = (int)$row['quantity']; }
    check(count($owned) === 2 && ($quantities['{"21":"22"}'] ?? 0) === 5 && ($quantities['{"21":"23"}'] ?? 0) === 1, 'guest saved-cart merge preserves quantity and option identity');
    define('HTTP_SERVER', 'http://example.invalid/'); define('HTTPS_SERVER', 'https://example.invalid/');
    foreach (array('DB_DRIVER', 'DB_HOSTNAME', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE', 'DB_PORT') as $constant) { define($constant, 'qa'); }
    $_ = array(); require DIR_SYSTEM . 'config/catalog.php'; $catalogName = $_['session_name'];
    $_ = array(); require DIR_SYSTEM . 'config/default.php'; require DIR_SYSTEM . 'config/admin.php';
    check($_['session_name'] !== $catalogName, 'admin authentication cookie isolated from storefront');
    foreach (array('false', 'throw') as $failure) {
        $failedAdapter = new DefensiveSessionAdapter(); $failedAdapter->failure = $failure;
        $failedSession = $reflection->newInstanceWithoutConstructor();
        $property->setValue($failedSession, $failedAdapter); $failedSession->start($old);
        $registry->set('session', $failedSession);
        $registry->set('db', $db);
        $db->rows = array($base);
        $failedCustomer = new \Cart\Customer($registry);
        try { $failedCustomer->login('qa@example.invalid', 'qa-password-example'); } catch (RuntimeException $e) {}
        check(!$failedCustomer->isLogged() && empty($failedSession->data['customer_id']), 'session write ' . $failure . ' cannot leave an authenticated customer');
        check($db->rows[0]['session_id'] === $old && $failedSession->getId() === $old, 'session write ' . $failure . ' leaves guest cart on its original ID');
        $registry->set('db', new class($db->customer['password']) {
            private string $password;
            public function __construct($password) { $this->password = $password; }
            public function escape($value) { return addslashes((string)$value); }
            public function query($sql) { return result(array(array('user_id' => 7, 'username' => 'qa', 'user_group_id' => 1, 'password' => $this->password, 'salt' => ''))); }
        });
        $failedAdmin = new \Cart\User($registry);
        try { $failedAdmin->login('qa', 'qa-password-example'); } catch (RuntimeException $e) {}
        check(!$failedAdmin->isLogged() && empty($failedSession->data['user_id']), 'session write ' . $failure . ' cannot leave an authenticated admin');
    }
}
if ($case === 'html') {
    require DIR_SYSTEM . 'helper/utf8.php';
    $request = new Request();
    $input = '<h2>Український опис</h2><p class="lead">A &amp; B</p><table><tr><td>42</td></tr></table><a href="https://example.invalid/help" target="_blank">Help</a><img src="image/cache/item.webp" alt="Item"><iframe src="https://www.youtube.com/embed/example" allowfullscreen></iframe>';
    $stored = \CodeCart\Core\SafeRichHtml::sanitize($request->clean($input));
    $rendered = html_entity_decode($stored, ENT_QUOTES, 'UTF-8');
    check(strpos($rendered, '<h2>Український опис</h2>') !== false, 'rich HTML survives native entity storage');
    check(strpos($rendered, '<table>') !== false && strpos($rendered, '<img ') !== false && strpos($rendered, '<iframe ') !== false, 'tables images and trusted media retained');
    check(strpos($rendered, 'noopener') !== false, 'parsed external link receives safe rel');
    check(\CodeCart\Core\SafeRichHtml::sanitize($request->clean($rendered)) === $stored, 'native rich HTML edit save is stable');
    // Inert unknown attributes and tags exercise the allowlist without executable payloads.
    $unknown = \CodeCart\Core\SafeRichHtml::sanitize($request->clean('<p unknown-qa="marker">Keep</p><qa-widget>Text</qa-widget>'));
    $unknown = html_entity_decode($unknown, ENT_QUOTES, 'UTF-8');
    check(strpos($unknown, 'unknown-qa') === false && strpos($unknown, '<qa-widget') === false && strpos($unknown, 'Keep') !== false, 'parser removes unknown markup while retaining text');
    check(strpos($rendered, 'A &amp; B') !== false, 'text entities survive one storefront decode');
    $literal = '<p>&lt;b&gt;Literal &quot;quotes&quot; &amp; symbols&lt;/b&gt;</p>';
    $block = array(array('type' => 'info', 'content' => $literal));
    $encodedBlock = \CodeCart\Core\PurchaseBlocks::encode($block);
    check(\CodeCart\Core\PurchaseBlocks::encode($encodedBlock) === $encodedBlock, 'raw purchase-block JSON survives stored entities on reopen');
    check(\CodeCart\Core\PurchaseBlocks::encode($request->clean($encodedBlock)) === $encodedBlock, 'native POST purchase-block JSON survives save and reopen');
    foreach (array(false, true) as $encoded) {
        $candidate = '<p onclick="void(0)" style="color: red; background-image: url(javascript:void(0))">Keep</p><img/src="image/cache/item.png" onerror="void(0)"><a href="jav&#x61;script:void(0)">Link</a><iframe src="https://example.invalid/embed" srcdoc="marker"></iframe><svg><a onload="void(0)">SVG</a></svg>';
        $clean = \CodeCart\Core\SafeRichHtml::sanitize($encoded ? $request->clean($candidate) : $candidate, $encoded);
        if ($encoded) { $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8'); }
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true); $dom->loadHTML('<!doctype html><html><body>' . $clean . '</body></html>'); libxml_clear_errors(); libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        check($xpath->query('//@*[starts-with(translate(name(), "ON", "on"), "on") or name()="srcdoc"]')->length === 0, 'parsed raw/native HTML has no active event or srcdoc attributes');
        check(!preg_match('/javascript:|<svg/i', $clean) && $xpath->query('//iframe[@src]')->length === 0, 'raw/native HTML drops active URLs and untrusted media sources');
        check(strpos($clean, 'Keep') !== false && strpos($clean, 'color:') !== false, 'raw/native HTML preserves text and safe formatting');
    }
    $media = '<video controls src="https://example.invalid/demo.mp4" poster="image/cache/poster.webp"><source src="https://example.invalid/demo.webm" type="video/webm"></video><audio controls src="https://example.invalid/demo.mp3"></audio>';
    $clean = \CodeCart\Core\SafeRichHtml::sanitize($media, false);
    check(strpos($clean, '<video') !== false && strpos($clean, '<audio') !== false && strpos($clean, '<source') !== false, 'allowed HTML5 media survive sanitization');
    check(\CodeCart\Core\SafeRichHtml::sanitize($clean, false) === $clean, 'raw HTML5 media edit save is stable');
    $raster = imagecreatetruecolor(2, 2);
    foreach (array('png', 'jpeg', 'gif', 'webp', 'avif') as $format) {
        ob_start(); call_user_func('image' . $format, $raster); $bytes = ob_get_clean();
        $uri = 'data:image/' . $format . ';base64,' . base64_encode($bytes);
        foreach (array(false, true) as $encoded) {
            $html = '<p>Inline raster</p><img src="' . $uri . '" alt="Inline">';
            $stored = \CodeCart\Core\SafeRichHtml::sanitize($encoded ? $request->clean($html) : $html, $encoded);
            $rendered = $encoded ? html_entity_decode($stored, ENT_QUOTES, 'UTF-8') : $stored;
            check(strpos($rendered, $uri) !== false, $format . ' inline raster survives raw/native storage');
            check(\CodeCart\Core\SafeRichHtml::sanitize($encoded ? $request->clean($rendered) : $rendered, $encoded) === $stored, $format . ' inline raster repeat save remains stable');
        }
    }
    imagedestroy($raster);
    foreach (array('data:image/svg+xml;base64,' . base64_encode('<svg/>'), 'data:text/html;base64,' . base64_encode('<p>text</p>'), 'data:image/webp;base64,invalid!', 'data:image/webp;base64,' . base64_encode('not an image'), 'data:image/png;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg"/>')) as $uri) {
        $clean = \CodeCart\Core\SafeRichHtml::sanitize('<img src="' . $uri . '" alt="Untrusted">', false);
        check(strpos($clean, 'data:') === false, 'unsupported or corrupt inline raster is rejected');
    }
    $clean = \CodeCart\Core\SafeRichHtml::sanitize('<img src="data:image/png;base64,' . base64_encode($bytes) . '" alt="Raster">', false);
    check(strpos($clean, 'data:image/avif;base64,' . base64_encode($bytes)) !== false, 'allowed raster MIME mismatch normalizes to the actual image bytes');
    $config = \HTMLPurifier_Config::createDefault();
    $config->set('Cache.DefinitionImpl', null);
    $config->set('URI.AllowedSchemes', array('https' => true, 'data' => true));
    $independent = new \HTMLPurifier($config);
    check(strpos($independent->purify('<img src="data:image/avif;base64,' . base64_encode($bytes) . '" alt="Raster">'), 'data:') === false, 'ordinary purifier does not inherit the scoped raster extension');
    foreach (array('URI.Disable', 'URI.DisableResources', 'URI.AllowedSchemes') as $restriction) {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('URI.AllowedSchemes', array('https' => true, 'data' => true));
        $config->set($restriction, $restriction === 'URI.AllowedSchemes' ? array('https' => true) : true);
        $config->getHTMLDefinition(true)->addAttribute('img', 'src', new \CodeCart\Core\SafeRasterImageUri(true));
        $restricted = new \HTMLPurifier($config);
        check(strpos($restricted->purify('<img src="data:image/avif;base64,' . base64_encode($bytes) . '" alt="Raster">'), 'data:') === false, 'inline raster obeys ' . $restriction);
    }
    foreach (array(false, true) as $post) {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('URI.AllowedSchemes', array('https' => true, 'data' => true));
        $config->getHTMLDefinition(true)->addAttribute('img', 'src', new \CodeCart\Core\SafeRasterImageUri(true));
        $filter = new class extends \HTMLPurifier_URIFilter {
            public $name = 'QaDenyRaster';
            public $always_load = true;
            public function filter(&$uri, $config, $context) { return !$context->get('EmbeddedURI', true); }
        };
        $filter->post = $post;
        $config->getDefinition('URI', true)->registerFilter($filter);
        $filtered = new \HTMLPurifier($config);
        check(strpos($filtered->purify('<img src="data:image/avif;base64,' . base64_encode($bytes) . '" alt="Raster">'), 'data:') === false, 'inline raster obeys ' . ($post ? 'post' : 'pre') . ' URI filters');
    }
}
if ($case === 'totp') {
    define('DB_DATABASE', 'qa_auth');
    $db = new DefensiveMfaDb();
    $registry = registry($db);
    $registry->get('config')->set('config_encryption', str_repeat('q', 40));
    $registry->get('config')->set('codecart_admin_login_protection_status', 0);
    $manager = new \CodeCart\Core\TotpManager($registry);
    $secret = $manager->createSecret();
    $reflection = new ReflectionClass($manager);
    $encrypt = $reflection->getMethod('encrypt'); $encrypt->setAccessible(true);
    $hotp = $reflection->getMethod('hotp'); $hotp->setAccessible(true);
    $code = $hotp->invoke($manager, $secret, (int)floor(time() / 30));
    $recovery = 'ABCD-1234';
    $db->mfa = array('status' => 1, 'secret' => $encrypt->invoke($manager, $secret), 'last_counter' => -1, 'recovery_codes' => json_encode(array(password_hash('ABCD1234', PASSWORD_DEFAULT))));
    for ($i = 0; $i < 5; $i++) { check(!$manager->verify(7, 'invalid'), 'invalid MFA attempt rejected ' . ($i + 1)); }
    $nextRegistry = registry($db);
    $nextRegistry->get('config')->set('config_encryption', str_repeat('q', 40));
    $nextRegistry->get('config')->set('codecart_admin_login_protection_status', 0);
    $nextSession = new \CodeCart\Core\TotpManager($nextRegistry);
    check(!$nextSession->verify(7, $code), 'TOTP throttle persists across service and session replacement');
    check(!$nextSession->verify(7, $recovery), 'recovery code uses same throttle');
    check(method_exists($nextSession, 'retryAfter') && $nextSession->retryAfter(7) > 0, 'blocked MFA exposes controlled retry');
    (new \CodeCart\Core\LoginProtection($nextRegistry))->recordSuccess('qa-admin');
    check($nextSession->retryAfter(7) > 0, 'primary-password success cannot clear MFA throttle while protection is OFF');
    $nextRegistry->get('request')->server['REMOTE_ADDR'] = '192.0.2.11';
    check(!$nextSession->verify(7, $code), 'changing source cannot bypass account MFA throttle');
    foreach ($db->attempts as &$attempt) { $attempt['time'] -= 901; } unset($attempt);
    check($nextSession->verify(7, $code), 'correct TOTP works after cooldown');
    check(!$nextSession->verify(7, $code), 'accepted TOTP cannot replay');
    check($nextSession->verify(7, $recovery), 'recovery code works after cooldown');
    check(!$nextSession->verify(7, $recovery), 'recovery code consumes atomically');
    $db->attempts = array();
    for ($i = 0; $i < 15; $i++) { $nextSession->verify(20 + intdiv($i, 4), 'invalid'); }
    check($nextSession->retryAfter(99) > 0 && !$nextSession->verify(99, $code), 'source throttle limits attempts across different accounts');
    foreach (array(0, null) as $lockResult) {
        $db->lockResult = $lockResult; $db->attempts = array(); $db->mfa['last_counter'] = -1; $db->queries = array();
        check(!$nextSession->verify(7, $code), 'unavailable source lock fails closed for ' . var_export($lockResult, true));
        check(count($db->queries) === 1 && strpos($db->queries[0], 'SELECT GET_LOCK(') === 0 && !$db->attempts, 'denied source lock starts no transaction and consumes no code or attempt');
    }
    $db->lockResult = 1; $db->lockError = true; $db->queries = array(); $db->mfa['last_counter'] = -1;
    check(!$nextSession->verify(7, $code), 'source lock query exception fails closed');
    check(count($db->queries) === 1 && !$db->attempts, 'source lock exception starts no transaction or attempt');
    $db->lockError = false; $db->queries = array(); $db->mfa['last_counter'] = -1;
    check($nextSession->verify(7, $code), 'valid code still works after source lock service recovers');
    $expectedLock = 'cctotp_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX . '|totp:' . sha1('192.0.2.11')), 0, 56);
    check(strlen($expectedLock) <= 64 && $db->queries[0] === "SELECT GET_LOCK('" . $expectedLock . "', 2) AS acquired" && $db->queries[1] === 'START TRANSACTION', 'source lock is database-namespaced, bounded and acquired before transaction snapshot');
    check(array_slice($db->queries, -2) === array('COMMIT', "SELECT RELEASE_LOCK('" . $expectedLock . "')"), 'successful attempt commits before releasing source lock');
    foreach (array('SELECT * FROM `qa_codecart_user_mfa`', 'COMMIT', 'START TRANSACTION') as $failure) {
        $db->failQuery = $failure; $db->attempts = array(); $db->mfa['last_counter'] = -1; $db->queries = array();
        $thrown = false;
        try { $nextSession->verify(7, $code); } catch (RuntimeException $error) { $thrown = true; }
        check($thrown && end($db->queries) === "SELECT RELEASE_LOCK('" . $expectedLock . "')", 'database exception releases acquired source lock: ' . $failure);
        if ($failure !== 'START TRANSACTION') { check(array_slice($db->queries, -2, 1) === array('ROLLBACK'), 'failed transaction rolls back before source lock release'); }
        $db->failQuery = '';
    }
    $db->queries = array(); $registry->get('request')->server['REMOTE_ADDR'] = '192.0.2.12';
    $manager->verify(7, 'invalid');
    $otherLock = 'cctotp_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX . '|totp:' . sha1('192.0.2.12')), 0, 56);
    check($db->queries[0] === "SELECT GET_LOCK('" . $otherLock . "', 2) AS acquired" && $otherLock !== $expectedLock, 'different source has its own advisory lock');
    $db->queries = array();
    $manager->verify(8, 'invalid');
    check($db->queries[0] === "SELECT GET_LOCK('" . $otherLock . "', 2) AS acquired", 'different accounts on the same source share one advisory lock');
}
if ($checks === 0) { check(false, 'Unknown or empty test case: ' . $case); }
echo 'RESULT ' . $checks . ' checks, ' . $failures . ' failures' . PHP_EOL;
exit($failures ? 1 : 0);
