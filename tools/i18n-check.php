<?php
/**
 * CodeCart PRO multilingual integrity check.
 * Usage: php tools/i18n-check.php
 * The checker loads trusted project language PHP files; do not run it on unreviewed third-party code.
 */
error_reporting(E_ALL);
$projectRoot = dirname(__DIR__);
$uploadRoot = $projectRoot . '/upload';
$languages = array('uk-ua', 'en-gb', 'ru-ru');
$domains = array('admin', 'catalog', 'install');
$issues = array();
$filesChecked = 0;
$keysCompared = 0;
$allFilesChecked = 0;

function loadCodeCartLanguageFile($file) {
    $_ = array();
    ob_start();
    try { include $file; } finally { ob_end_clean(); }
    return $_;
}

function codecartFormatParameters($value) {
    if (!is_string($value)) { return null; }
    preg_match_all('/(?<!%)%(?!%)(?:\d+\$)?[-+#.\d]*[bcdeEfFgGosuxX]/', $value, $matches);
    $params = $matches[0];
    sort($params, SORT_STRING);
    return $params;
}

foreach ($domains as $domain) {
    $base = $uploadRoot . '/' . $domain . '/language';
    foreach ($languages as $language) {
        $folder = $base . '/' . $language;
        if (!is_dir($folder)) { $issues[] = 'Missing language directory: ' . $domain . '/' . $language; continue; }
        $walker = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
        foreach ($walker as $file) {
            if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php') { continue; }
            $contents = file_get_contents($file->getPathname());
            $allFilesChecked++;
            // Support multiple assignments on the same line without treating comment examples as active keys.
            $definedKeys = array();
            foreach (preg_split('/\r\n|\n|\r/', $contents) as $line) {
                if (strpos(ltrim($line), '//') === 0) { continue; }
                preg_match_all('~\$_\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*=~', $line, $matches);
                foreach ($matches[1] as $key) {
                    if (isset($definedKeys[$key])) { $issues[] = 'Duplicate key: ' . $file->getPathname() . ': ' . $key; }
                    $definedKeys[$key] = true;
                }
            }
            if (substr($contents, 0, 3) === "\xEF\xBB\xBF") { $issues[] = 'UTF-8 BOM: ' . $file->getPathname(); }
            if (function_exists('mb_check_encoding') && !mb_check_encoding($contents, 'UTF-8')) { $issues[] = 'Invalid UTF-8: ' . $file->getPathname(); }
        }
    }

    $ukrainianFolder = $base . '/uk-ua';
    $walker = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ukrainianFolder, FilesystemIterator::SKIP_DOTS));
    foreach ($walker as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php') { continue; }
        $relative = substr($file->getPathname(), strlen($ukrainianFolder) + 1);
        // Legacy language packs without a matching core controller are preserved
        // for optional extensions, but are outside the currently executable route set.
        if (!is_file($uploadRoot . '/' . $domain . '/controller/' . $relative)) { continue; }
        $reference = loadCodeCartLanguageFile($file->getPathname());
        $filesChecked++;
        foreach (array('en-gb','ru-ru') as $language) {
            $translatedFile = $base . '/' . $language . '/' . $relative;
            if (!is_file($translatedFile)) { $issues[] = 'Missing active translation file: ' . $domain . '/' . $language . '/' . $relative; continue; }
            $translation = loadCodeCartLanguageFile($translatedFile);
            foreach ($reference as $key => $value) {
                $keysCompared++;
                if (!array_key_exists($key, $translation)) {
                    $issues[] = 'Missing key ' . $domain . '/' . $language . '/' . $relative . ': ' . $key;
                } elseif (gettype($value) !== gettype($translation[$key])) {
                    $issues[] = 'Type mismatch ' . $domain . '/' . $language . '/' . $relative . ': ' . $key;
                } elseif (is_string($value) && codecartFormatParameters($value) !== codecartFormatParameters($translation[$key])) {
                    $issues[] = 'Format parameter mismatch ' . $domain . '/' . $language . '/' . $relative . ': ' . $key;
                }
            }
        }
    }
}

echo 'Language files checked: ' . $allFilesChecked . PHP_EOL;
echo 'Active route translations compared: ' . $filesChecked . PHP_EOL;
echo 'Translation values compared: ' . $keysCompared . PHP_EOL;
echo 'Issues: ' . count($issues) . PHP_EOL;
foreach ($issues as $issue) { echo ' - ' . $issue . PHP_EOL; }
exit($issues ? 1 : 0);
