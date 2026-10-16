<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

function fail(array &$errors, string $message): void {
    $errors[] = $message;
    fwrite(STDERR, "[FAIL] " . $message . PHP_EOL);
}

function pass(string $message): void {
    fwrite(STDOUT, "[PASS] " . $message . PHP_EOL);
}

$required = [
    'upload/index.php',
    'upload/admin/index.php',
    'upload/system/startup.php',
    'upload/system/storage/vendor/autoload.php',
    'composer.json',
    'composer.lock',
];

foreach ($required as $path) {
    if (!is_file($root . '/' . $path)) {
        fail($errors, 'Missing required file: ' . $path);
    }
}
if (!$errors) {
    pass('Required release files are present');
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$phpFiles = [];
$jsonFiles = [];
$xmlFiles = [];
$bomFiles = [];
$debugHits = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

    if (str_starts_with($relative, '.git/') ||
        str_starts_with($relative, 'upload/system/storage/vendor/') ||
        str_starts_with($relative, 'artifacts/') ||
        str_starts_with($relative, '.autopilot/') ||
        str_starts_with($relative, 'source-package/') ||
        str_starts_with($relative, 'bonus-source/') ||
        str_starts_with($relative, 'vendor/')) {
        continue;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        $phpFiles[] = $path;
    } elseif ($ext === 'json') {
        $jsonFiles[] = $path;
    } elseif ($ext === 'xml') {
        $xmlFiles[] = $path;
    }

    $fh = @fopen($path, 'rb');
    if ($fh) {
        $prefix = fread($fh, 3);
        fclose($fh);
        if ($prefix === "\xEF\xBB\xBF") {
            $bomFiles[] = $relative;
        }
    }

    if (in_array($ext, ['php', 'twig', 'js'], true)) {
        $content = @file_get_contents($path);

        if ($content !== false) {
            if (preg_match('/\bvar_dump\s*\(/', $content)) {
                $debugHits[] = $relative;
                continue;
            }

            // print_r($value, true) is used intentionally as a formatter by
            // logging code. Flag only calls that can write directly to output.
            if ($ext === 'php') {
                foreach (preg_split('/\R/', $content) as $line) {
                    if (preg_match('/\bprint_r\s*\(/', $line) &&
                        !preg_match('/\bprint_r\s*\(.*?,\s*true\s*\)/i', $line)) {
                        $debugHits[] = $relative;
                        break;
                    }
                }
            }
        }
    }
}

$beforePhp = count($errors);
foreach ($phpFiles as $path) {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        fail($errors, 'PHP lint failed: ' . str_replace($root . '/', '', $path) . ' :: ' . implode(' ', $out));
    }
    unset($out);
}
if (count($errors) === $beforePhp) {
    pass('PHP lint: ' . count($phpFiles) . ' first-party files');
}

$beforeJson = count($errors);
foreach ($jsonFiles as $path) {
    json_decode((string)file_get_contents($path), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        fail($errors, 'Invalid JSON: ' . str_replace($root . '/', '', $path) . ' :: ' . json_last_error_msg());
    }
}
if (count($errors) === $beforeJson) {
    pass('JSON validation: ' . count($jsonFiles) . ' files');
}

$beforeXml = count($errors);
if (class_exists('DOMDocument')) {
    libxml_use_internal_errors(true);
    foreach ($xmlFiles as $path) {
        $dom = new DOMDocument();
        if (!$dom->load($path, LIBXML_NONET)) {
            fail($errors, 'Invalid XML: ' . str_replace($root . '/', '', $path));
        }
        libxml_clear_errors();
    }
    if (count($errors) === $beforeXml) {
        pass('XML validation: ' . count($xmlFiles) . ' files');
    }
} else {
    fail($errors, 'XML validation requires the PHP DOM extension');
}

if ($bomFiles) {
    fail($errors, 'UTF-8 BOM detected: ' . implode(', ', $bomFiles));
} else {
    pass('UTF-8 BOM: none');
}

if ($debugHits) {
    fail($errors, 'Debug calls detected: ' . implode(', ', array_unique($debugHits)));
} else {
    pass('Debug-call sweep: clean');
}

// First-party minified theme stylesheets must be built from the current source.
// The build writes the source SHA-256 into the first comment of stylesheet.min.css.
$beforeStyles = count($errors);
foreach (array('codecart', 'default') as $themeName) {
    $cssDir = $root . '/upload/catalog/view/theme/' . $themeName . '/stylesheet/';
    if (!is_file($cssDir . 'stylesheet.css')) {
        continue;
    }
    $minHead = is_file($cssDir . 'stylesheet.min.css') ? (string)file_get_contents($cssDir . 'stylesheet.min.css', false, null, 0, 256) : '';
    if (!preg_match('/source stylesheet\.css sha256:([a-f0-9]{64})/', $minHead, $cssMatch)) {
        fail($errors, 'Minified stylesheet has no source hash marker: ' . $themeName);
    } elseif (!hash_equals($cssMatch[1], hash_file('sha256', $cssDir . 'stylesheet.css'))) {
        fail($errors, 'Minified stylesheet is stale for theme ' . $themeName . ': rebuild stylesheet.min.css from stylesheet.css');
    }
}
if (count($errors) === $beforeStyles) {
    pass('Minified theme stylesheets are in sync with source');
}

$gates = [
    'check_language_packs.php',
    'check_uk_locale.php',
    'check_unishop2_compat.php',
    'qa_unishop2_ocmod.php',
    'i18n-check.php',
    'i18n-coverage-check.php',
    'integrity-manifest-check.php',
    'extension-sort-check.php',
    'checker-regression-check.php',
    'notification-check.php',
    'notification-url-check.php',
    'seo-language-url-check.php',
    'seo-language-startup-check.php',
    'qa_lost_urls.php',
    'qa_error_pages.php',
    'qa_session_rotation.php',
    'check_paypal_financial_guards.php',
];
foreach ($gates as $gate) {
    $path = $root . '/tools/' . $gate;
    if (!is_file($path)) {
        fail($errors, 'Missing release gate: ' . $gate);
        continue;
    }
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path), $code);
    if ($code !== 0) {
        fail($errors, 'Release gate failed: ' . $gate . ' (exit ' . $code . ')');
    } else {
        pass('Release gate: ' . $gate);
    }
}

if ($errors) {
    fwrite(STDERR, PHP_EOL . 'RELEASE CHECK FAILED: ' . count($errors) . ' issue(s).' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, PHP_EOL . 'RELEASE CHECK PASSED.' . PHP_EOL);
