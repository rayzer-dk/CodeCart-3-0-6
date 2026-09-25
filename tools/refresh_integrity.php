<?php
$root = dirname(__DIR__);
$path = $root . '/upload/system/config/codecart_integrity.json';
$data = json_decode((string)file_get_contents($path), true);
if (!is_array($data) || !isset($data['files']) || !is_array($data['files'])) { exit(1); }
foreach ($data['files'] as $relative => $hash) {
    $file = $root . '/upload/' . $relative;
    if (!is_file($file)) { fwrite(STDERR, 'Missing: ' . $relative . PHP_EOL); exit(2); }
    $data['files'][$relative] = hash_file('sha256', $file);
}
$index = (string)file_get_contents($root . '/upload/index.php');
if (!preg_match("/CODECART_PACKAGE_BUILD'\\s*,\\s*'([^']+)'/", $index, $m)) { exit(3); }
$data['version'] = $m[1];
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
echo 'Integrity refreshed for ' . count($data['files']) . ' files.' . PHP_EOL;
