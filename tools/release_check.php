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
        str_starts_with($relative, 'artifacts/')) {
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

foreach ($phpFiles as $path) {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        fail($errors, 'PHP lint failed: ' . str_replace($root . '/', '', $path) . ' :: ' . implode(' ', $out));
    }
    unset($out);
}
if (!$errors) {
    pass('PHP lint: ' . count($phpFiles) . ' first-party files');
}

foreach ($jsonFiles as $path) {
    json_decode((string)file_get_contents($path), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        fail($errors, 'Invalid JSON: ' . str_replace($root . '/', '', $path) . ' :: ' . json_last_error_msg());
    }
}
pass('JSON validation: ' . count($jsonFiles) . ' files');

if (class_exists('DOMDocument')) {
    libxml_use_internal_errors(true);
    foreach ($xmlFiles as $path) {
        $dom = new DOMDocument();
        if (!$dom->load($path, LIBXML_NONET)) {
            fail($errors, 'Invalid XML: ' . str_replace($root . '/', '', $path));
        }
        libxml_clear_errors();
    }
    pass('XML validation: ' . count($xmlFiles) . ' files');
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
pass('Minified theme stylesheets are in sync with source');

$languageCheck = $root . '/tools/check_language_packs.php';
if (is_file($languageCheck)) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($languageCheck), $languageCode);
    if ($languageCode !== 0) {
        fail($errors, 'Language pack gate failed');
    } else {
        pass('Language pack gate');
    }
}

$localeCheck = $root . '/tools/check_uk_locale.php';
if (is_file($localeCheck)) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($localeCheck), $localeCode);
    if ($localeCode !== 0) {
        fail($errors, 'Ukrainian hardcode gate failed');
    } else {
        pass('Ukrainian hardcode gate');
    }
}

$unishopCheck = $root . '/tools/check_unishop2_compat.php';
if (is_file($unishopCheck)) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($unishopCheck), $unishopCode);
    if ($unishopCode !== 0) {
        fail($errors, 'UniShop2 compatibility gate failed');
    } else {
        pass('UniShop2 compatibility gate');
    }
}

if ($errors) {
    fwrite(STDERR, PHP_EOL . 'RELEASE CHECK FAILED: ' . count($errors) . ' issue(s).' . PHP_EOL);
    exit(1);
}

passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/qa_lost_urls.php'), $lostUrlCode);
if ($lostUrlCode !== 0) { fail($errors, 'Lost URL filters, privacy and OFF contract'); exit(1); }

passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/qa_error_pages.php'), $errorPageCode);
if ($errorPageCode !== 0) {
    fail($errors, 'Standalone error pages must render safely without database or Twig');
    exit(1);
}

passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/qa_session_rotation.php'), $sessionCode);
if ($sessionCode !== 0) {
    fail($errors, 'Session rotation must fail closed and retain the successful login state');
    exit(1);
}

fwrite(STDOUT, PHP_EOL . 'RELEASE CHECK PASSED.' . PHP_EOL);
