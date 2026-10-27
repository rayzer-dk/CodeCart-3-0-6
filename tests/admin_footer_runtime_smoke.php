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

    require dirname(__DIR__) . '/upload/admin/controller/common/footer.php';

    function renderFooter($login, $urlToken) {
        $controller = new \ControllerCommonFooter();
        $controller->load = new StubLoader();
        $controller->language = new StubLanguage();
        $controller->user = new StubUser($login);
        $controller->request = (object)array('get' => array('user_token' => $urlToken));
        $controller->session = (object)array('data' => array('user_token' => 'valid'));
        $controller->document = new StubDocument();
        return $controller->index();
    }
    function check($ok, $message) { if (!$ok) throw new \RuntimeException($message); }

    $authorized = renderFooter(true, 'valid');
    check($authorized['environment_visible'] === true, 'Authenticated footer is hidden');
    check($authorized['runtime_php_version'] === PHP_VERSION, 'PHP version does not match runtime');
    check($authorized['runtime_server_time_iso'] !== '', 'Missing server time');
    check($authorized['runtime_timezone'] === date_default_timezone_get(), 'Wrong timezone');
    check($authorized['runtime_build'] === '2.1.3', 'Wrong package build');
    check($authorized['runtime_memory_limit'] === (string)ini_get('memory_limit'), 'Memory limit not reported');
    check($authorized['runtime_db_driver'] === 'mysqli', 'DB driver not reported');
    check(isset($authorized['project_links']) && isset($authorized['project_support_methods']), 'Existing footer metadata regressed');

    foreach (array(renderFooter(false, 'valid'), renderFooter(true, 'invalid')) as $guest) {
        check($guest['environment_visible'] === false, 'Diagnostic info should be restricted');
        check($guest['text_codecart_release'] === '', 'Version info should remain restricted');
        check(!isset($guest['runtime_php_version']), 'Private diagnostics leaked to guest');
    }
    echo "Footer environment smoke test: PASS\n";
}
