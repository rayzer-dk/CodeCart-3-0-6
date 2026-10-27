<?php
/** Pure validation; no network requests. php tests/codecart_external_http_smoke.php */
class CronConfigStub { public function get($name) { return $name === 'config_encryption' ? str_repeat('A', 32) : ''; } }
class CronRegistryStub { public function get($name) { return new CronConfigStub(); } }
if (!defined('DB_DATABASE')) { define('DB_DATABASE', 'cron_smoke'); }
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/ExternalHttpTask.php';
$task = new \CodeCart\Core\ExternalHttpTask(new CronRegistryStub());
$url = 'https://example.org/index.php?route=extension/module/abandoned_cart_pro/cron';
$secret = 'example-secret-' . str_repeat('3', 16);
$commands = array(
    '/usr/bin/curl -L --fail -sS -H "X-ACP-Cron-Key: ' . $secret . '" "' . $url . '" >/dev/null 2>&1',
    '/usr/bin/wget -q -O /dev/null --header="X-ACP-Cron-Key: ' . $secret . '" "' . $url . '" 2>/dev/null'
);
foreach ($commands as $command) {
    $parsed = $task->parseCommand($command);
    if ($parsed['url'] !== $url || $parsed['secret'] !== $secret) { throw new RuntimeException('Cron command parser regression'); }
}
$sealed = $task->protect($secret);
$decrypt = (new ReflectionClass($task))->getMethod('unprotect');
if ($decrypt->invoke($task, $sealed) !== $secret || strpos($sealed, $secret) !== false) { throw new RuntimeException('Encryption regression'); }
foreach (array(
    'curl -H "X-ACP-Cron-Key: ' . $secret . '" "http://example.org/index.php?route=extension/module/abandoned_cart_pro/cron"',
    'curl -H "X-ACP-Cron-Key: ' . $secret . '" "https://127.0.0.1/index.php?route=extension/module/abandoned_cart_pro/cron"',
    'curl -H "X-ACP-Cron-Key: ' . $secret . '" "https://example.org/index.php?route=checkout/checkout"',
    'curl -H "X-ACP-Cron-Key: ' . $secret . '" "https://example.org/index.php?route=extension/module/abandoned_cart_pro/cron&token=abc"',
    'curl -H "X-ACP-Cron-Key: ' . $secret . '" "' . $url . '"; whoami',
) as $bad) {
    try { $task->parseCommand($bad); throw new RuntimeException('Dangerous command accepted'); }
    catch (InvalidArgumentException $exception) { /* correct */ }
}
echo "PASS external HTTPS command parsing, encryption, blocked unsafe addresses and syntax.\n";
