<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/php-source.php';

// Language pack gate: every first-party en-gb language file must exist in uk-ua and
// ru-ru with the same keys and the same printf placeholders. A partial pack would
// overwrite a merchant's complete legacy pack during an overlay update and make the
// storefront/admin fall back to English.
$root = dirname(__DIR__);
$languages = array('uk-ua', 'ru-ru');
$errors = array();

$load = static function (string $file): array {
    if (!is_file($file)) {
        return array();
    }
    $_ = array();
    include $file;
    return is_array($_) ? $_ : array();
};

$placeholders = static function ($value): string {
    if (!is_string($value)) {
        return '';
    }
    preg_match_all('/%(?:\d+\$)?[-+ 0#]*\d*(?:\.\d+)?[sdfu]/', str_replace('%%', '', $value), $match);
    $list = $match[0];
    sort($list);
    return implode(',', $list);
};

foreach (array('catalog', 'admin') as $area) {
    $base = $root . '/upload/' . $area . '/language/';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base . 'en-gb', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (substr((string)$file, -4) !== '.php') {
            continue;
        }
        $relative = substr((string)$file, strlen($base . 'en-gb/'));
        $reference = $load((string)$file);
        foreach ($languages as $language) {
            $target = $base . $language . '/' . str_replace('en-gb', $language, $relative);
            if (!is_file($target)) {
                $errors[] = $area . '/' . $language . '/' . $relative . ': missing file';
                continue;
            }
            $values = $load($target);
            foreach ($reference as $key => $value) {
                if (!array_key_exists($key, $values)) {
                    $errors[] = $area . '/' . $language . '/' . $relative . ': missing key ' . $key;
                } elseif ($placeholders($value) !== $placeholders($values[$key])) {
                    $errors[] = $area . '/' . $language . '/' . $relative . ': placeholder mismatch in ' . $key;
                }
            }
        }
    }
}


// Per-route gate: every key a controller reads with $this->language->get('key') or renders in
// its twig view must exist in the language files that controller loads (plus the main file)
// for every language. Otherwise the raw key (e.g. "text_blog") is shown in the UI.
foreach (array('catalog', 'admin') as $area) {
    $controllerDir = $root . '/upload/' . $area . '/controller';
    $templateDir = $area === 'admin' ? $root . '/upload/admin/view/template/' : $root . '/upload/catalog/view/theme/codecart/template/';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (substr((string)$file, -4) !== '.php') { continue; }
        $references = ccpLanguageReferences((string)file_get_contents((string)$file));
        $source = $references['code'];
        $routes = $references['routes'];
        if (!$routes && !$references['aliases']) { continue; }
        $needed = $references['needed'];
        preg_match_all('/\$data\[\s*[\'"]([a-z0-9_]+)[\'"]\s*\]\s*=/', $source, $match);
        $assigned = array_fill_keys($match[1], true);
        preg_match_all('/load->view\(\s*[\'"]([a-z0-9_\/]+)[\'"]/', $source, $match);
        foreach (array_unique($match[1]) as $view) {
            $template = $templateDir . $view . '.twig';
            if (!is_file($template)) { continue; }
            preg_match_all('/\{\{\s*((?:text|entry|button|column|error|help|tab|heading|legend|placeholder|success|warning)_[a-z0-9_]+)\s*(?:\||\}\})/', (string)file_get_contents($template), $tm);
            foreach ($tm[1] as $key) { if (!isset($assigned[$key]) && !isset($needed[$key])) { $needed[$key] = 'twig'; } }
        }
        $availableBy = array();
        $union = array();
        foreach (array('en-gb', 'uk-ua', 'ru-ru') as $language) {
            $localeRoot = $root . '/upload/' . $area . '/language/' . $language . '/';
            foreach (ccpLanguageAliasErrors($references, static function ($route) use ($localeRoot, $load) {
                $path = $localeRoot . $route . '.php';
                return is_file($path) ? $load($path) : null;
            }) as $error) {
                $errors[] = $area . '/' . $language . ': ' . substr((string)$file, strlen($controllerDir) + 1) . ' ' . $error;
            }
            $available = $load($root . '/upload/' . $area . '/language/' . $language . '/' . $language . '.php');
            foreach ($routes as $route) { $available += $load($root . '/upload/' . $area . '/language/' . $language . '/' . $route . '.php'); }
            $availableBy[$language] = $available;
            $union += $available;
        }
        foreach ($availableBy as $language => $available) {
            foreach ($needed as $key => $origin) {
                // A twig variable that no language defines is controller data (e.g. a form value), not a phrase.
                if ($origin === 'twig' && !array_key_exists($key, $union)) { continue; }
                if (!array_key_exists($key, $available)) {
                    $errors[] = $area . '/' . $language . ': ' . substr((string)$file, strlen($controllerDir) + 1) . ' uses missing key ' . $key;
                }
            }
        }
    }
}

if ($errors) {
    fwrite(STDERR, 'Language pack gate failed (' . count($errors) . "):\n  " . implode("\n  ", array_slice($errors, 0, 50)) . "\n");
    exit(1);
}

fwrite(STDOUT, "PASS: en-gb/uk-ua/ru-ru pack files, placeholders and statically recognized controller/Twig keys and language aliases checked. Dynamic routes/keys are not covered.\n");
