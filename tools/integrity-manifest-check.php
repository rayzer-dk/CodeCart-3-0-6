<?php
/**
 * CodeCart PRO: release-only check of the curated Core integrity manifest.
 * Read-only. Usage: php tools/integrity-manifest-check.php
 * Optional: php tools/integrity-manifest-check.php --root=/path/to/upload
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(2);
}

$uploadRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'upload';
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--root=') === 0) {
        $uploadRoot = substr($arg, strlen('--root='));
    } else {
        fwrite(STDERR, "Unknown argument. Use --root=/path/to/upload.\n");
        exit(2);
    }
}

$uploadRoot = realpath($uploadRoot);
if ($uploadRoot === false || !is_dir($uploadRoot)) {
    fwrite(STDERR, "Upload root does not exist.\n");
    exit(2);
}

$manifestPath = $uploadRoot . DIRECTORY_SEPARATOR . 'system/config/codecart_integrity.json';
$manifestRaw = is_file($manifestPath) ? file_get_contents($manifestPath) : false;
$manifest = $manifestRaw !== false ? json_decode($manifestRaw, true) : null;
if (!is_array($manifest) || ($manifest['algorithm'] ?? null) !== 'sha256' || empty($manifest['files']) || !is_array($manifest['files'])) {
    fwrite(STDERR, "Missing or invalid SHA-256 manifest.\n");
    exit(2);
}

$matched = 0;
$failed = array();
$schemaPath = $uploadRoot . DIRECTORY_SEPARATOR . 'system/config/codecart_schema.json';
$schemaRaw = is_file($schemaPath) ? file_get_contents($schemaPath) : false;
$schema = $schemaRaw !== false ? json_decode($schemaRaw, true) : null;
if (!is_array($schema) || !isset($schema['version']) || !isset($manifest['version']) ||
    (string)$schema['version'] !== (string)$manifest['version']) {
    $failed[] = 'Manifest version does not match the release schema version.';
}
foreach ($manifest['files'] as $relative => $expectedHash) {
    if (!is_string($relative) || !is_string($expectedHash) || !preg_match('/^[a-f0-9]{64}$/D', $expectedHash) ||
        !preg_match('#^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9_./-]+$#D', $relative)) {
        $failed[] = 'Invalid manifest entry';
        continue;
    }
    // Installer is deliberately not monitored: a successful installation removes it.
    if (strpos($relative, 'install/') === 0) {
        $failed[] = 'Installer must not be monitored: ' . $relative;
        continue;
    }
    $target = $uploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    // Never follow symlinks to outside the upload root.
    $realTarget = realpath($target);
    if ($realTarget === false || !is_file($realTarget) ||
        strpos($realTarget, $uploadRoot . DIRECTORY_SEPARATOR) !== 0) {
        $failed[] = 'Missing or invalid path: ' . $relative;
        continue;
    }
    $actual = hash_file('sha256', $realTarget);
    if (!is_string($actual) || !hash_equals($expectedHash, $actual)) {
        $failed[] = 'Changed: ' . $relative;
    } else {
        $matched++;
    }
}

// Required first-party components: omitted hashes are a build error.
$required = array(
    'system/library/codecart/src/FileIntegrity.php',
    'system/library/codecart/src/DashboardHealth.php',
    'system/library/cart/user.php',
    'system/library/seopro.php',
    'admin/controller/startup/permission.php',
    'admin/controller/user/user_permission.php',
    'admin/controller/tool/notification.php',
    'admin/controller/extension/dashboard/codecart_health.php',
    'admin/view/template/extension/dashboard/codecart_health_section.twig',
    'admin/view/template/common/filemanager.twig',
    'catalog/controller/common/language.php',
    'catalog/controller/startup/seo_url.php'
);
foreach (array('en-gb', 'ru-ru', 'uk-ua') as $locale) {
    $required[] = 'admin/language/' . $locale . '/extension/dashboard/codecart_health.php';
}
foreach (glob($uploadRoot . '/system/library/codecart/src/*.php') ?: array() as $libraryFile) {
    $required[] = 'system/library/codecart/src/' . basename($libraryFile);
}
foreach (array_unique($required) as $relative) {
    if (!array_key_exists($relative, $manifest['files'])) {
        $failed[] = 'Unmonitored Core file: ' . $relative;
    }
}

printf("CodeCart integrity: %d/%d hashes match; %d issues.\n", $matched, count($manifest['files']), count($failed));
foreach ($failed as $issue) {
    echo $issue, "\n";
}
exit(count($failed) ? 1 : 0);
