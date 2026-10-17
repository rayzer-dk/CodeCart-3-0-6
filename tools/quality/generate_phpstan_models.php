<?php

declare(strict_types=1);

/**
 * Generate PHPStan-only annotations from actual OpenCart model class declarations.
 * Separate admin/catalog models share names, so never combine them in one scan.
 */
$area = $argv[1] ?? '';
if (!in_array($area, ['admin', 'catalog'], true)) {
    fwrite(STDERR, "Usage: php tools/quality/generate_phpstan_models.php admin|catalog\n");
    exit(2);
}
$root = dirname(__DIR__, 2);
$modelRoot = $root . '/upload/' . $area . '/model';
if (!is_dir($modelRoot)) {
    throw new RuntimeException("Model directory not found: $modelRoot");
}
$models = [];
$bad = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelRoot, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $entry) {
    if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'php') {
        continue;
    }
    $file = $entry->getPathname();
    $route = substr($file, strlen($modelRoot) + 1, -4);
    $route = str_replace(DIRECTORY_SEPARATOR, '/', $route);
    if (!preg_match('~^[A-Za-z0-9_/]+$~D', $route)) {
        $bad[] = "Non-standard model route $route";
        continue;
    }
    $expected = 'Model' . preg_replace('/[^A-Za-z0-9]/', '', $route);
    $content = file_get_contents($file);
    if ($content === false || str_starts_with($content, "\xEF\xBB\xBF")) {
        $bad[] = "Unreadable/BOM PHP model: $file";
        continue;
    }
    $tokens = token_get_all($content);
    $declared = [];
    $count = count($tokens);
    for ($i = 0; $i < $count; ++$i) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_CLASS) {
            continue;
        }
        for ($j = $i + 1; $j < $count; ++$j) {
            $next = $tokens[$j];
            if (is_array($next) && in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if (is_array($next) && $next[0] === T_STRING) {
                $declared[] = $next[1];
            }
            break;
        }
    }
    $matches = array_values(array_filter($declared, static fn (string $x): bool => strcasecmp($x, $expected) === 0));
    if (count($matches) !== 1) {
        $bad[] = "Route $route expected class $expected in $file, found: " . implode(', ', $declared);
        continue;
    }
    $key = 'model_' . str_replace('/', '_', $route);
    $models[$key] = $matches[0];
}
if ($bad) {
    foreach ($bad as $error) {
        fwrite(STDERR, "PHPStan model stub WARNING: $error\n");
    }
}
ksort($models);
$annotations = [
    ' * @mixin \Registry',
    ' * @property \Loader $load',
    ' * @property \Config $config',
    ' * @property \DB $db',
    ' * @property \Request $request',
    ' * @property \Response $response',
    ' * @property \Session $session',
    ' * @property \Language $language',
    ' * @property \Document $document',
    ' * @property \Url $url',
];
foreach ($models as $property => $className) {
    $annotations[] = " * @property-read \\" . $className . ' $' . $property;
}
$header = "<?php\n\n/**\n" . implode("\n", $annotations) . "\n */\n";
$stub = $header . "abstract class Controller {}\n\n" . $header . "abstract class Model {}\n\n";
$stub .= "class Registry {}\nclass Loader {}\nclass Config {}\nclass DB {}\nclass Request {}\nclass Response {}\nclass Session {}\nclass Language {}\nclass Document {}\nclass Url {}\n\n";
foreach (array_unique(array_values($models)) as $className) {
    $stub .= "class $className {}\n";
}
$outputDir = $root . '/build/phpstan-stubs';
if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    throw new RuntimeException("Cannot create $outputDir");
}
$output = "$outputDir/$area.stub";
if (file_put_contents($output, $stub) !== strlen($stub)) {
    throw new RuntimeException("Could not write $output");
}
echo "Generated $area stub from " . count($models) . " verified model classes: $output\n";
