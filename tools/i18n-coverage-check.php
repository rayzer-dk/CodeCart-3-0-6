<?php
/**
 * CodeCart PRO localization coverage checker.
 * Usage: php tools/i18n-coverage-check.php
 * Read-only: checks static language references, matching file sets, shared
 * dictionary key symmetry, value types and sprintf-style placeholders.
 * Loads only the trusted language files shipped with this repository.
 */
error_reporting(E_ALL);
$root = dirname(__DIR__) . '/upload';
$domains = array('admin', 'catalog', 'install');
$locales = array('uk-ua', 'ru-ru', 'en-gb');
$issues = array();
$sharedFiles = 0;
$sharedValues = 0;
$staticReferences = 0;
$legacyCount = 0;

function ccpI18nPaths($dir) {
    $items = array();
    if (!is_dir($dir)) { return $items; }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php') { continue; }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        $items[$relative] = $file->getPathname();
    }
    return $items;
}

function ccpI18nValues($file) {
    $_ = array();
    ob_start();
    try { include $file; } finally { ob_end_clean(); }
    return $_;
}

function ccpI18nSignature($value) {
    if (is_array($value)) {
        $result = array();
        foreach ($value as $key => $item) { $result[$key] = ccpI18nSignature($item); }
        ksort($result);
        return $result;
    }
    if (!is_string($value)) { return gettype($value); }
    preg_match_all('/(?<!%)%(?!%)(?:\d+\$)?[-+#.\d]*[bcdeEfFgGosuxX]/', $value, $matches);
    $formats = $matches[0];
    sort($formats);
    return array('type' => 'string', 'placeholders' => $formats);
}

// Analyze static loads, not dynamic prefix expressions.
$referenced = array();
foreach ($domains as $domain) {
    $referenced[$domain] = array();
    $codeFolder = $root . '/' . $domain . '/controller';
    foreach (ccpI18nPaths($codeFolder) as $sourceFile) {
        $code = file_get_contents($sourceFile);
        // Exclude commented-out examples and commented legacy code.
        $tokens = token_get_all($code);
        $codeWithoutComments = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) { $codeWithoutComments .= $token[1]; }
            } else { $codeWithoutComments .= $token; }
        }
        preg_match_all('~(?:->load->language|->language->load)\s*\(\s*([\'"])([^\'"\r\n]+)\1~', $codeWithoutComments, $matches);
        foreach ($matches[2] as $route) {
            if ($route === '' || substr($route, -1) === '/' || strpos($route, '/') === false) { continue; }
            $staticReferences++;
            $referenced[$domain][$route . '.php'] = true;
        }
    }
}

foreach ($domains as $domain) {
    $byLocale = array();
    $paths = array();
    foreach ($locales as $locale) {
        $byLocale[$locale] = ccpI18nPaths($root . '/' . $domain . '/language/' . $locale);
        $paths = array_merge($paths, array_fill_keys(array_keys($byLocale[$locale]), true));
    }
    foreach ($paths as $relative => $unused) {
        $required = isset($referenced[$domain][$relative]) || is_file($root . '/' . $domain . '/controller/' . $relative);
        $allExist = true;
        foreach ($locales as $locale) {
            if (!isset($byLocale[$locale][$relative])) {
                $allExist = false;
                if ($required) { $issues[] = 'Missing active language file: ' . $domain . '/' . $locale . '/' . $relative; }
            }
        }
        if (!$allExist) { if (!$required) { $legacyCount++; } continue; }
        $sharedFiles++;
        $values = array();
        foreach ($locales as $locale) { $values[$locale] = ccpI18nValues($byLocale[$locale][$relative]); }
        if (!is_array($values['uk-ua'])) { continue; }
        $keys = array();
        foreach ($locales as $locale) {
            if (!is_array($values[$locale])) { $issues[] = 'Invalid dictionary: ' . $domain . '/' . $locale . '/' . $relative; continue 2; }
            $keys = array_merge($keys, array_fill_keys(array_keys($values[$locale]), true));
        }
        foreach ($keys as $key => $unused2) {
            foreach ($locales as $locale) {
                if (!array_key_exists($key, $values[$locale])) {
                    $issues[] = 'Missing key: ' . $domain . '/' . $locale . '/' . $relative . ': ' . $key;
                    continue 2;
                }
            }
            $sharedValues++;
            $reference = ccpI18nSignature($values['uk-ua'][$key]);
            foreach (array('ru-ru', 'en-gb') as $locale) {
                if ($reference !== ccpI18nSignature($values[$locale][$key])) {
                    $issues[] = 'Type/format/array mismatch: ' . $domain . '/' . $locale . '/' . $relative . ': ' . $key;
                }
            }
        }
    }
    foreach ($referenced[$domain] as $relative => $unused) {
        foreach ($locales as $locale) {
            if (!isset($byLocale[$locale][$relative])) {
                $issues[] = 'Missing file for static language load: ' . $domain . '/' . $locale . '/' . $relative;
            }
        }
    }
}
$issues = array_values(array_unique($issues));
echo 'Shared translation files: ' . $sharedFiles . PHP_EOL;
echo 'Keys compared across 3 languages: ' . $sharedValues . PHP_EOL;
echo 'Static language loads examined: ' . $staticReferences . PHP_EOL;
echo 'Legacy-only resources without active controllers: ' . $legacyCount . PHP_EOL;
echo 'Errors: ' . count($issues) . PHP_EOL;
foreach ($issues as $issue) { echo ' - ' . $issue . PHP_EOL; }
exit($issues ? 1 : 0);
