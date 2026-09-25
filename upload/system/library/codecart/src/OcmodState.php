<?php
namespace CodeCart\Core;

final class OcmodState {
    public static function get() {
        $path = self::path();
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return array('status' => 'unknown');
        }

        $json = @file_get_contents($path);
        if ($json === false || trim($json) === '') {
            return array('status' => 'unknown');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return array('status' => 'unknown');
        }

        $expectedBuild = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : '';
        $stateBuild = isset($data['package_build']) ? (string)$data['package_build'] : '';
        if ($expectedBuild !== '' && $stateBuild !== $expectedBuild && in_array(isset($data['status']) ? (string)$data['status'] : 'unknown', array('clean', 'issues'), true)) {
            $data['status'] = 'dirty';
            $data['reason'] = 'package_build_changed';
        }

        return $data;
    }

    public static function markDirty($reason = 'changed', array $context = array()) {
        $current = self::get();
        $dirtyAt = isset($current['dirty_at']) && (string)$current['dirty_at'] !== '' ? (string)$current['dirty_at'] : date('c');

        return self::write(array(
            'status' => 'dirty',
            'reason' => self::shortText($reason, 120),
            'context' => self::sanitizeContext($context),
            'dirty_at' => $dirtyAt,
            'clean_at' => isset($current['clean_at']) ? (string)$current['clean_at'] : '',
            'summary' => isset($current['summary']) && is_array($current['summary']) ? $current['summary'] : array(),
            'core_build' => defined('CODECART_BUILD') ? (string)CODECART_BUILD : '',
            'package_build' => defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : ''
        ));
    }

    public static function markClean(array $summary = array()) {
        $errorCount = isset($summary['error']) ? (int)$summary['error'] : 0;
        if (isset($summary['failed_modifications'])) {
            $errorCount = max($errorCount, (int)$summary['failed_modifications']);
        }

        return self::write(array(
            'status' => $errorCount > 0 ? 'issues' : 'clean',
            'reason' => $errorCount > 0 ? 'refresh_completed_with_issues' : 'refresh_completed',
            'context' => array(),
            'dirty_at' => '',
            'clean_at' => date('c'),
            'summary' => self::sanitizeContext($summary),
            'core_build' => defined('CODECART_BUILD') ? (string)CODECART_BUILD : '',
            'package_build' => defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : ''
        ));
    }

    private static function path() {
        if (!defined('DIR_STORAGE')) {
            return '';
        }

        return rtrim((string)DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'codecart' . DIRECTORY_SEPARATOR . 'ocmod-state.json';
    }

    private static function write(array $payload) {
        $path = self::path();
        if ($path === '') {
            return false;
        }

        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return false;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            $suffix = str_replace('.', '', uniqid('', true));
        }

        $temp = $path . '.tmp-' . $suffix;
        if (@file_put_contents($temp, $json, LOCK_EX) === false) {
            @unlink($temp);
            return false;
        }

        @chmod($temp, 0640);
        if (!@rename($temp, $path)) {
            @unlink($temp);
            return false;
        }

        return true;
    }

    private static function sanitizeContext(array $context) {
        $safe = array();
        foreach ($context as $key => $value) {
            $key = self::shortText($key, 80);
            if ($key === '') {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $safe[$key] = $value;
            } elseif (is_string($value)) {
                $safe[$key] = self::shortText($value, 500);
            }
        }
        return $safe;
    }

    private static function shortText($value, $limit) {
        $value = trim(preg_replace('/\s+/u', ' ', (string)$value));
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, (int)$limit, 'UTF-8');
        }
        return substr($value, 0, (int)$limit);
    }
}
