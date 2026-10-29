<?php
/** Standalone regression test: php tests/admin_footer_runtime_smoke.php */
namespace CodeCart\Core {
    final class Community {
        const EMAIL = 'support@example.test';
        const TELEGRAM = 'https://example.test/community';
        const REPOSITORY = 'https://example.test/repo';
    }
}
namespace {
    define('VERSION', '3.0.6.0');
    define('CODECART_PACKAGE_BUILD', '2.1.3');
    define('DB_DRIVER', 'mysqli');
    class Controller {
        public $load;
        public $language;
        public $user;
        public $request;
        public $session;
        public $document;
        public $db;
    }
    class StubLanguage { public function get($key) { return 'lang:' . $key; } }
    class StubLoader {
        public function language($route) { if ($route !== 'common/footer') throw new \RuntimeException('Unexpected language file'); }
        public function view($route, $data) { if ($route !== 'common/footer') throw new \RuntimeException('Unexpected footer view'); return $data; }
    }
    class StubUser {
        private $active;
        public function __construct($active) { $this->active = $active; }
        public function isLogged() { return $this->active; }
    }
    class StubDocument { public function getAssets($position) { return array(); } }
    class StubDB {
        public $calls = 0;
        public $version;
        public $fail = false;
        public function __construct($version) { $this->version = $version; }
        public function query($sql) {
            $this->calls++;
            if ($this->fail) { throw new \RuntimeException('DB unavailable'); }
            if (strpos($sql, 'SELECT VERSION()') !== 0) { throw new \RuntimeException('Unexpected query'); }
            return (object)array('row' => array(
                'db_version' => $this->version,
                'db_charset' => 'utf8mb4',
                'db_collation' => 'utf8mb4_unicode_ci'
            ));
        }
    }
    require dirname(__DIR__) . '/upload/admin/controller/common/footer.php';
    function check($ok, $message) { if (!$ok) throw new \RuntimeException($message); }
    function renderFooter($logged, $urlToken, $session, $db, $software = 'nginx/1.26.0') {
        $controller = new \ControllerCommonFooter();
        $controller->load = new StubLoader();
        $controller->language = new StubLanguage();
        $controller->user = new StubUser($logged);
        $controller->request = (object)array(
            'get' => array('user_token' => $urlToken),
            'server' => array('SERVER_SOFTWARE' => $software)
        );
        $controller->session = $session;
        $controller->document = new StubDocument();
        $controller->db = $db;
        return $controller->index();
    }
    $session = (object)array('data' => array('user_token' => 'valid'));
    $db = new StubDB('11.4.5-MariaDB');
    $data = renderFooter(true, 'valid', $session, $db);
    check($data['environment_visible'] === true, 'Private footer hidden');
    check($data['runtime_php_version'] === PHP_VERSION, 'Wrong PHP version');
    check($data['runtime_db_name'] === 'MariaDB' && $data['runtime_db_version'] === '11.4.5', 'MariaDB parsing failed');
    check($data['runtime_db_charset'] === 'utf8mb4', 'DB charset missing');
    check($data['runtime_db_collation'] === 'utf8mb4_unicode_ci', 'DB collation missing');
        check($data['runtime_web_server'] === 'Nginx', 'Web server type is wrong');
    $apache = renderFooter(true, 'valid', $session, $db, 'Apache/2.4.63 (Debian)');
    check($apache['runtime_web_server'] === 'Apache', 'Apache label parsing failed');
    $unknown = renderFooter(true, 'valid', $session, $db, 'CompanyPrivateProxy/5.2');
    check($unknown['runtime_web_server'] === '', 'Unexpected web server build exposure');
    $template = file_get_contents(dirname(__DIR__) . '/upload/admin/view/template/common/footer.twig');
    check(strpos($template, 'ccp-footer-main') !== false, 'Main footer row absent');
    check(strpos($template, 'text_runtime_web_server') !== false, 'Web server not rendered');
    check(strpos($template, 'text_runtime_more') === false && strpos($template, 'ccp-footer-runtime-details') === false, 'Legacy system details disclosure still rendered');
    check($data['runtime_build'] === '2.1.3' && $data['runtime_db_driver'] === 'mysqli', 'Original runtime details regressed');
    check(isset($data['project_links']) && isset($data['project_support_methods']), 'Support links regressed');
    renderFooter(true, 'valid', $session, $db);
    check($db->calls === 1, 'DB read should be cached per session');
    $session->data['codecart_footer_db_details']['expires'] = time() - 1;
    $db->version = '8.4.4';
    $mysql = renderFooter(true, 'valid', $session, $db);
    check($mysql['runtime_db_name'] === 'MySQL' && $mysql['runtime_db_version'] === '8.4.4', 'MySQL parsing failed');
    check($db->calls === 2, 'Expired cache should refresh');
    foreach (array(array(false, 'valid'), array(true, 'invalid')) as $case) {
        $guest = renderFooter($case[0], $case[1], $session, $db);
        check($guest['environment_visible'] === false && !isset($guest['runtime_php_version']), 'Sensitive detail leaked');
    }
    check($db->calls === 2, 'Unauthorized visitor triggered DB read');
    $missingDb = new StubDB('');
    $missingDb->fail = true;
    $emptySession = (object)array('data' => array('user_token' => 'valid'));
    $failed = renderFooter(true, 'valid', $emptySession, $missingDb);
    check($failed['runtime_db_version'] === '', 'Failed DB read should hide DB version');
    check($failed['runtime_php_version'] === PHP_VERSION, 'Failed DB read broke footer');
    renderFooter(true, 'valid', $emptySession, $missingDb);
    check($missingDb->calls === 1, 'Failed DB read must also be cached');
    echo "Admin footer environment: PASS\n";
}
