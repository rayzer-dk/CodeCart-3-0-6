<?php
// Every entry of the legacy OCMOD anchor table must point at a line that exists in the
// current file; an entry goes stale when that line is edited without updating the table.
$root = dirname(__DIR__) . '/upload/';
$anchors = require $root . 'system/config/codecart_ocmod_anchors.php';
$checks = 0;
$failures = array();

foreach ($anchors as $file => $map) {
    $targets = strpos($file, '*') !== false ? glob($root . $file) : array($root . $file);
    if (!$targets) {
        $failures[] = 'No target file for ' . $file;
        continue;
    }
    foreach ($map as $legacy => $entry) {
        foreach ($targets as $target) {
            if (!is_file($target)) {
                $failures[] = 'Missing file ' . $file;
                continue;
            }
            $code = (string)file_get_contents($target);
            $relative = substr($target, strlen($root));
            $checks++;
            if (!is_array($entry) || count($entry) !== 2 || !is_string($entry[0]) || $entry[0] === '' || !is_bool($entry[1])) {
                $failures[] = 'Malformed entry in ' . $file . ': ' . $legacy;
            } elseif (strpos($code, $legacy) !== false) {
                $failures[] = 'OpenCart line is present again, entry is redundant in ' . $relative . ': ' . $legacy;
            } elseif (strpos($code, $entry[0]) === false) {
                $failures[] = 'CodeCart line not found in ' . $relative . ': ' . $entry[0];
            } elseif (strpos($entry[0], "\n") !== false) {
                $failures[] = 'Entry spans several lines in ' . $file . ': ' . $legacy;
            }
        }
    }
}

// The engine must keep OpenCart 3 semantics around the table.
$engine = (string)file_get_contents($root . 'admin/controller/marketplace/modification.php');
foreach (array('REMAINING OPERATIONS FOR THIS FILE SKIPPED', 'TARGET NOT FOUND - ', 'legacyAnchor($key, $search, (string)$position)') as $needle) {
    $checks++;
    if (strpos($engine, $needle) === false) {
        $failures[] = 'Modification engine lost: ' . $needle;
    }
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo 'PASS: legacy OCMOD anchors (' . $checks . ' checks)' . PHP_EOL;
