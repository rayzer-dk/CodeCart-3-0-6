<?php
namespace CodeCart\Core;

final class FileIntegrity {
    private $registry;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function check(): array {
        $manifestFile = DIR_SYSTEM . 'config/codecart_integrity.json';
        if (!is_file($manifestFile)) {
            return array('status' => 'warning', 'checked' => 0, 'changed' => 0, 'missing' => 0, 'items' => array(), 'message' => 'Integrity manifest is missing.');
        }

        $raw = @file_get_contents($manifestFile);
        $manifest = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($manifest) || empty($manifest['files']) || !is_array($manifest['files'])) {
            return array('status' => 'warning', 'checked' => 0, 'changed' => 0, 'missing' => 0, 'items' => array(), 'message' => 'Integrity manifest is invalid.');
        }

        $root = dirname(rtrim(DIR_SYSTEM, '/\\')) . DIRECTORY_SEPARATOR;
        $changed = 0;
        $missing = 0;
        $checked = 0;
        $items = array();

        foreach ($manifest['files'] as $relative => $expected) {
            if (!is_string($relative) || !is_string($expected) || !preg_match('/^[a-f0-9]{64}$/', $expected)) {
                continue;
            }
            if (strpos($relative, '..') !== false || preg_match('#^[\\\\/]#', $relative)) {
                continue;
            }
            // Installer files are intentionally removed after a successful installation and
            // legal/license texts are not runtime executable Core. Older manifests included
            // both, which produced permanent false-positive dashboard warnings.
            if (strpos($relative, 'install/') === 0 || preg_match('#(?:^|/)(?:LICENSE|LICENSE\.txt|COPYING)(?:$|\.)#i', $relative)) {
                continue;
            }
            $checked++;
            $file = $root . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relative);
            if (!is_file($file)) {
                $missing++;
                if (count($items) < 20) { $items[] = array('file' => $relative, 'state' => 'missing'); }
                continue;
            }
            $actual = @hash_file('sha256', $file);
            if (!is_string($actual) || !hash_equals($expected, $actual)) {
                $changed++;
                if (count($items) < 20) { $items[] = array('file' => $relative, 'state' => 'changed'); }
            }
        }

        $status = ($changed || $missing) ? 'warning' : 'ok';
        return array(
            'status' => $status,
            'checked' => $checked,
            'changed' => $changed,
            'missing' => $missing,
            'items' => $items,
            'message' => $status === 'ok' ? 'Core files match the build manifest.' : 'One or more monitored Core files differ from the build manifest.'
        );
    }
}
