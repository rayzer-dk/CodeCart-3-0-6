<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/upload/system/storage/vendor/autoload.php';

$twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader());
$directories = [
    $root . '/upload/admin/view/template',
    $root . '/upload/catalog/view/theme/default/template',
    $root . '/upload/catalog/view/theme/codecart/template',
];
$count = 0;
$errors = [];
foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'twig') {
            continue;
        }
        $count++;
        try {
            $source = new \Twig\Source((string) file_get_contents($file->getPathname()), $file->getPathname());
            $twig->parse($twig->tokenize($source));
        } catch (\Twig\Error\Error $error) {
            $errors[] = $file->getPathname() . ':' . $error->getTemplateLine() . ': ' . $error->getMessage();
        }
    }
}
$report = $root . '/build/twig-syntax-report.txt';
if (!is_dir(dirname($report))) {
    mkdir(dirname($report), 0775, true);
}
file_put_contents($report, "Twig templates: $count; diagnostics: " . count($errors) . PHP_EOL . implode(PHP_EOL, $errors) . PHP_EOL);
echo "Twig templates: $count; diagnostics: " . count($errors) . PHP_EOL;
echo implode(PHP_EOL, array_slice($errors, 0, 50)) . PHP_EOL;
// Syntax errors are deterministic and must block CI; PHP/Twig context hints remain advisory.
exit($errors === [] ? 0 : 1);
