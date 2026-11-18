<?php
/**
 * Read-only audit of translatable language keys in the supported OpenCart locales.
 * No language files or merchant content are modified.
 *
 * php tools/qa_locale_parity.php
 */
$base = dirname(__DIR__) . '/upload';
$locales = array('en-gb', 'ru-ru', 'uk-ua');
$areas = array('admin', 'catalog');
$missing = array();
$totals = array();

foreach ($areas as $area) {
    $directory = $base . '/' . $area . '/language';
    $data = array();
    $paths = array();

    foreach ($locales as $locale) {
        $data[$locale] = array();
        $root = $directory . '/' . $locale;
        if (!is_dir($root)) {
            throw new RuntimeException('Missing locale directory: ' . $root);
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $relative = str_replace('\\', '/', $relative);
            $source = file_get_contents($file->getPathname());
            if ($source === false) {
                throw new RuntimeException('Cannot read language file: ' . $relative);
            }
            preg_match_all('/\\$_\\s*\\[\\s*[\'"]([^\'"]+)[\'"]\\s*\\]\\s*=/', $source, $keys);
            $data[$locale][$relative] = array_fill_keys($keys[1], true);
            $paths[$relative] = true;
        }
    }

    $totals[$area] = array();
    foreach ($locales as $locale) {
        $totals[$area][$locale] = array('files' => count($data[$locale]), 'keys' => 0, 'missing' => 0);
        foreach ($data[$locale] as $keys) {
            $totals[$area][$locale]['keys'] += count($keys);
        }
    }

    foreach (array_keys($paths) as $relative) {
        $reference = array();
        foreach ($locales as $locale) {
            foreach ($data[$locale][$relative] ?? array() as $key => $value) {
                $reference[$key] = true;
            }
        }
        foreach ($locales as $locale) {
            $actual = $data[$locale][$relative] ?? array();
            foreach ($reference as $key => $_present) {
                if (!isset($actual[$key])) {
                    $missing[] = $area . '/' . $locale . '/' . $relative . ': ' . $key;
                    $totals[$area][$locale]['missing']++;
                }
            }
        }
    }
}

echo "CodeCart language-key parity report (English, Russian, Ukrainian)\n";
foreach ($totals as $area => $summary) {
    foreach ($summary as $locale => $stats) {
        echo sprintf("%s/%s: %d files; %d keys; %d missing keys\n", $area, $locale, $stats['files'], $stats['keys'], $stats['missing']);
    }
}
sort($missing, SORT_STRING);
echo "Total missing keys: " . count($missing) . "\n";
foreach (array_slice($missing, 0, 120) as $line) {
    echo "MISSING " . $line . "\n";
}
if (count($missing) > 120) {
    echo "... and " . (count($missing) - 120) . " additional missing keys\n";
}

// Missing locale entries do not fail the build because many third-party modules
// have explicit English fallback. This report is intended for translation QA.
