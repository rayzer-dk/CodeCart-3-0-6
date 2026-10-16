<?php
$root = dirname(__DIR__);
$path = $root . '/upload/system/config/codecart_integrity.json';
$data = json_decode((string)file_get_contents($path), true);
if (!is_array($data) || !isset($data['files']) || !is_array($data['files'])) { exit(1); }
$schema = json_decode((string)file_get_contents($root . '/upload/system/config/codecart_schema.json'), true);
if (!is_array($schema) || !isset($schema['version']) || !is_string($schema['version']) ||
    !preg_match('/^[0-9]+(?:\.[0-9]+){2,3}$/D', $schema['version'])) {
    fwrite(STDERR, 'Missing or invalid schema version.' . PHP_EOL);
    exit(1);
}
$uploadRoot = realpath($root . '/upload');
foreach ($data['files'] as $relative => $hash) {
    if (!is_string($relative) || !preg_match('#^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9_./-]+$#D', $relative)) {
        fwrite(STDERR, 'Invalid manifest path.' . PHP_EOL);
        exit(2);
    }
    $file = $root . '/upload/' . $relative;
    $resolved = realpath($file);
    if ($resolved === false || !is_file($resolved) || strpos($resolved, $uploadRoot . DIRECTORY_SEPARATOR) !== 0) {
        fwrite(STDERR, 'Missing or invalid path: ' . $relative . PHP_EOL);
        exit(2);
    }
    $digest = hash_file('sha256', $resolved);
    if ($digest === false) { fwrite(STDERR, 'Cannot hash: ' . $relative . PHP_EOL); exit(2); }
    $data['files'][$relative] = $digest;
}
$index = (string)file_get_contents($root . '/upload/index.php');
if (!preg_match("/CODECART_PACKAGE_BUILD'\\s*,\\s*'([^']+)'/", $index, $m)) { exit(3); }
$data['version'] = $schema['version'];
$data['package_build'] = $m[1];
$encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
$temporary = tempnam(dirname($path), '.integrity-');
if ($temporary === false || file_put_contents($temporary, $encoded, LOCK_EX) !== strlen($encoded) || !rename($temporary, $path)) {
    if (is_string($temporary) && is_file($temporary)) { unlink($temporary); }
    fwrite(STDERR, 'Cannot atomically replace integrity manifest.' . PHP_EOL);
    exit(4);
}
echo 'Integrity refreshed for ' . count($data['files']) . ' files.' . PHP_EOL;
