<?php
declare(strict_types=1);

// Loopback-only harness: real router/Loader/gateway, inert database sentinel.
if (PHP_SAPI !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', array('127.0.0.1', '::1'), true)) {
    http_response_code(403);
    exit;
}
$root = dirname(__DIR__);
if (PHP_SAPI === 'cli' && !isset($argv[1])) {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorNumber, $errorMessage);
    if (!$socket) { throw new RuntimeException('Cannot reserve loopback test port: ' . $errorMessage); }
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $log = tempnam(sys_get_temp_dir(), 'codecart-cron-');
    $process = proc_open(array(PHP_BINARY, '-S', $address, '-t', $root), array(0 => array('pipe', 'r'), 1 => array('file', $log, 'a'), 2 => array('file', $log, 'a')), $pipes, $root);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start loopback PHP fixture'); }
    fclose($pipes[0]);
    $checks = 0;
    try {
        $ready = false;
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @stream_socket_client('tcp://' . $address, $errorNumber, $errorMessage, 0.1);
            if ($connection) { fclose($connection); $ready = true; break; }
            usleep(50000);
        }
        if (!$ready) { throw new RuntimeException('Loopback PHP fixture did not become ready: ' . file_get_contents($log)); }
        $cases = array(
            array(array('route' => 'cron/queue_worker'), 403, false),
            array(array('route' => '/cron//queue_worker'), 403, false),
            array(array('route' => 'cron/../queue_worker'), 403, false),
            array(array('route' => 'CrOn/queue_worker'), 403, false),
            array(array('route' => 'cron/stock_notify', 'key' => 'qa-gateway-key'), 403, false),
            array(array('route' => 'cron/codecart'), 403, false),
            array(array('route' => 'cron/codecart/index', 'key' => 'wrong'), 403, false),
            array(array('route' => 'cron/codecart', 'key' => 'qa-gateway-key', 'mode' => 'queue_one', 'queue_id' => 0), 200, false),
            array(array('route' => 'common/home'), 200, false),
            array(array('dispatch' => 'internal'), 200, true)
        );
        foreach ($cases as $case) {
            $url = 'http://' . $address . '/tools/qa_cron_boundary.php?' . http_build_query($case[0]);
            $body = file_get_contents($url, false, stream_context_create(array('http' => array('ignore_errors' => true, 'timeout' => 5))));
            $data = json_decode((string)$body, true);
            preg_match('#^HTTP/\S+\s+(\d+)#', $http_response_header[0] ?? '', $status);
            if (!is_array($data) || (int)($status[1] ?? 0) !== $case[1] || $data['status'] !== $case[1] || ($case[2] ? $data['queries'] < 1 : $data['queries'] !== 0)) {
                throw new RuntimeException('Cron HTTP boundary failed: ' . http_build_query($case[0]));
            }
            $checks++;
        }
        $child = proc_open(array(PHP_BINARY, __FILE__, 'route=cron/queue_worker'), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $childPipes, $root);
        if (!is_resource($child)) { throw new RuntimeException('Cannot run CLI dispatch fixture'); }
        fclose($childPipes[0]);
        $data = json_decode(stream_get_contents($childPipes[1]), true);
        $errors = stream_get_contents($childPipes[2]);
        fclose($childPipes[1]); fclose($childPipes[2]);
        $exit = proc_close($child);
        if ($exit !== 0 || $errors !== '' || !is_array($data) || $data['status'] !== 200 || $data['queries'] < 1) { throw new RuntimeException('CLI internal cron dispatch failed'); }
        $checks++;
        echo 'PASS: cron HTTP gateway, internal Loader and CLI boundary (' . $checks . ' checks, PHP ' . PHP_VERSION . ')' . PHP_EOL;
    } finally {
        proc_terminate($process); proc_close($process); unlink($log);
    }
    exit(0);
}
define('DB_PREFIX', 'qa_');
define('DIR_APPLICATION', $root . '/upload/catalog/');
define('DIR_SYSTEM', $root . '/upload/system/');
require DIR_SYSTEM . 'engine/registry.php';
require DIR_SYSTEM . 'engine/controller.php';
require DIR_SYSTEM . 'engine/action.php';
require DIR_SYSTEM . 'engine/loader.php';
require DIR_SYSTEM . 'library/config.php';
spl_autoload_register(static function ($class) {
    $prefix = 'CodeCart\\Core\\';
    if (strpos($class, $prefix) === 0) { require DIR_SYSTEM . 'library/codecart/src/' . substr($class, strlen($prefix)) . '.php'; }
});
require getenv('CODECART_QA_ROUTER') ?: DIR_APPLICATION . 'controller/startup/router.php';
if (PHP_SAPI === 'cli') { parse_str($argv[1] ?? 'route=cron/queue_worker', $_GET); }
$registry = new Registry();
$config = new Config();
$config->set('codecart_scheduler_key', 'qa-gateway-key');
$config->set('action_default', 'common/home');
$registry->set('config', $config);
$registry->set('request', (object)array('get' => $_GET));
$db = new class {
    public int $queries = 0;
    public function escape($value) { return addslashes((string)$value); }
    public function countAffected() { return 0; }
    public function query($sql) { $this->queries++; return (object)array('rows' => array(), 'row' => array(), 'num_rows' => 0); }
};
$registry->set('db', $db);
$response = new class {
    public int $status = 200;
    public string $output = '';
    public function setStatusCode($status) { $this->status = $status; }
    public function addHeader($header) {}
    public function setOutput($output) { $this->output = $output; }
};
$registry->set('response', $response);
$registry->set('event', new class {
    public function trigger($event, $data) { return $event === 'controller/common/home/before' ? 'PUBLIC' : null; }
});
$load = new Loader($registry);
$registry->set('load', $load);
if (($_GET['dispatch'] ?? '') === 'internal') {
    $result = $load->controller('cron/queue_worker', array('limit' => 1));
} else {
    $result = (new ControllerStartupRouter($registry))->index();
}
http_response_code($response->status);
header('Content-Type: application/json');
echo json_encode(array('sapi' => PHP_SAPI, 'status' => $response->status, 'queries' => $db->queries, 'output' => $response->output, 'result' => $result), JSON_THROW_ON_ERROR);
