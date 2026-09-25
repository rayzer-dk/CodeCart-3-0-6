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

$localeCheck = $root . '/tools/check_uk_locale.php';
if (is_file($localeCheck)) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($localeCheck), $localeCode);
    if ($localeCode !== 0) {
        fail($errors, 'Ukrainian hardcode gate failed');
    } else {
        pass('Ukrainian hardcode gate');
    }
}

if ($errors) {
    fwrite(STDERR, PHP_EOL . 'RELEASE CHECK FAILED: ' . count($errors) . ' issue(s).' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, PHP_EOL . 'RELEASE CHECK PASSED.' . PHP_EOL);
