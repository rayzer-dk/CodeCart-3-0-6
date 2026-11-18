<?php
/**
 * Regression coverage for a file-overlay upgrade from ocStore 3.0.4.1.
 * A six-character legacy password must work before Installer UPDATE,
 * with no implicit DDL and no dependency on a CodeCart login-attempt table.
 */
require_once __DIR__ . '/../upload/system/helper/general.php';
require_once __DIR__ . '/../upload/system/library/cart/user.php';
require_once __DIR__ . '/../upload/system/library/codecart/src/LoginProtection.php';

if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', 'oc_');
}

function upgradeAssert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class UpgradeFakeConfig {
    public $settings = array('session_name' => 'codecart_test');
    public function get($name) {
        return $this->settings[$name] ?? null;
    }
}

class UpgradeFakeSession {
    public $data = array();
    public function regenerate() {}
    public function getId() { return 'test_session'; }
}

class UpgradeFakeRegistry {
    private $values;
    public function __construct(array $values) { $this->values = $values; }
    public function get($name) { return $this->values[$name] ?? null; }
}

class UpgradeFakeDb {
    public $queries = array();
    private $user;
    public function __construct(array $user) { $this->user = $user; }
    public function escape($value) { return addslashes((string)$value); }
    public function query($sql) {
        $this->queries[] = $sql;
        if (strpos($sql, 'FROM oc_user WHERE username') !== false) {
            return (object)array('num_rows' => 1, 'row' => $this->user);
        }
        if (strpos($sql, 'SHOW COLUMNS FROM `oc_user`') !== false) {
            return (object)array('num_rows' => 1, 'row' => array('Type' => 'varchar(40)'));
        }
        if (strpos($sql, 'FROM oc_user_group') !== false) {
            return (object)array('num_rows' => 1, 'row' => array('permission' => '{"access":[]}'));
        }
        if (strpos($sql, 'SHOW TABLES LIKE') !== false) {
            return (object)array('num_rows' => 0, 'row' => array());
        }
        throw new RuntimeException('Unexpected database operation in legacy login: ' . $sql);
    }
}

$password = '123456';
$salt = 'old123';
$hash = sha1($salt . sha1($salt . sha1($password)));
upgradeAssert(codecart_password_verify($password, $hash, $salt), 'Legacy six-character SHA1 password was rejected');

$conf = new UpgradeFakeConfig();
$db = new UpgradeFakeDb(array('user_id' => 1, 'user_group_id' => 1, 'username' => 'admin', 'password' => $hash, 'salt' => $salt));
$session = new UpgradeFakeSession();
$request = (object)array('server' => array('REMOTE_ADDR' => '127.0.0.1'));
$logger = new class {
    public $messages = array();
    public function write($message) { $this->messages[] = $message; }
};
$registry = new UpgradeFakeRegistry(array('config' => $conf, 'db' => $db, 'session' => $session, 'request' => $request, 'log' => $logger));

$protection = new \CodeCart\Core\LoginProtection($registry);
upgradeAssert(!$protection->isBlocked('admin'), 'Unmigrated store should not query a nonexistent protection table');
upgradeAssert((new \Cart\User($registry))->login('admin', $password), 'Legacy administrator login failed');
$protection->recordSuccess('admin');
upgradeAssert($protection->attemptsForCurrentSource('admin') === 0, 'Legacy rate-limit counter failed');

foreach ($db->queries as $sql) {
    upgradeAssert(strpos($sql, 'codecart_admin_login_attempt') === false, 'Login queried a nonexistent CodeCart table');
    upgradeAssert(strpos(strtoupper($sql), 'ALTER TABLE') === false, 'Login attempted to alter a legacy user table');
    upgradeAssert(strpos($sql, 'SET salt =') === false, 'Legacy 40-character password column was overwritten');
}

$conf->settings['codecart_admin_login_protection_status'] = 1;
$protection = new \CodeCart\Core\LoginProtection($registry);
upgradeAssert(!$protection->isBlocked('admin'), 'A missing protection table caused an exception');
$protection->recordSuccess('admin');
upgradeAssert(count($logger->messages) > 0, 'Missing login-attempt schema was not logged');

foreach (array('codecart','default') as $theme) {
    $path = __DIR__ . '/../upload/catalog/view/theme/' . $theme . '/template/product/category.twig';
    $twig = file_get_contents($path);
    upgradeAssert($twig !== false && strpos($twig, '{% for category in categories %}') !== false, 'Subcategories are not rendered in ' . $theme);
    upgradeAssert(strpos($twig, 'src="{{ category.thumb }}"') !== false, 'Subcategory images are missing from ' . $theme);
    upgradeAssert(strpos($twig, 'href="{{ category.href }}"') !== false, 'Subcategory links are missing from ' . $theme);
}

echo "PASS: legacy six-digit login, no implicit DDL, missing protection schema, and both subcategory image templates\n";
