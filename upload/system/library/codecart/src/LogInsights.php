<?php
namespace CodeCart\Core;

/**
 * Read-only, bounded admin log summaries. Original OpenCart log files are never changed.
 * Analysis is opt-in on the log page, or cached for the admin header.
 */
final class LogInsights {
    private const TAIL_BYTES = 1048576;
    private const CACHE_TTL = 180;

    public static function path($config): string {
        $name = basename(trim((string)$config->get('config_error_filename')));
        if ($name === '') { $name = basename(trim((string)$config->get('error_filename'))); }
        if ($name === '' || !preg_match('/^[A-Za-z0-9._-]+\.log$/i', $name)) { $name = 'error.log'; }
        return rtrim(DIR_LOGS, '/\\') . DIRECTORY_SEPARATOR . $name;
    }

    public static function tail(string $path): string {
        // A log may be truncated and rewritten in the same PHP process (PHP 8.1+).
        clearstatcache(true, $path);
        if (!is_file($path) || !is_readable($path)) { return ''; }
        $size = (int)@filesize($path);
        if ($size < 1) { return ''; }
        $handle = @fopen($path, 'rb');
        if (!$handle) { return ''; }
        $start = max(0, $size - self::TAIL_BYTES);
        if ($start > 0) { fseek($handle, $start); }
        $content = (string)stream_get_contents($handle, self::TAIL_BYTES);
        fclose($handle);
        if ($start > 0) {
            $break = strpos($content, "\n");
            return $break === false ? '' : substr($content, $break + 1);
        }
        return $content;
    }

    /** @return array<int,array{id:string,text:string,count:int,order:int}> */
    public static function group(string $content): array {
        $grouped = array();
        foreach (self::records($content) as $order => $record) {
            // Only the log timestamp is ignored; type, message, file and line stay significant.
            $fingerprint = hash('sha256', self::normalize($record));
            if (!isset($grouped[$fingerprint])) {
                $grouped[$fingerprint] = array('id' => $fingerprint, 'text' => $record, 'count' => 0, 'order' => $order);
            }
            $grouped[$fingerprint]['count']++;
            $grouped[$fingerprint]['text'] = $record; // Most recent occurrence for copying
            $grouped[$fingerprint]['order'] = $order;
        }
        $rows = array_values($grouped);
        usort($rows, static function($a, $b) { return $b['order'] <=> $a['order']; });
        return $rows;
    }

    private static function records(string $content): array {
        $records = array();
        $current = '';
        foreach (preg_split('/\r\n|\n|\r/', $content) ?: array() as $line) {
            $line = rtrim($line);
            if ($line === '') { continue; }
            $start = (bool)preg_match('/^(?:\[?)\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $line)
                || (bool)preg_match('/^(?:PHP )?(?:Warning|Notice|Deprecated|Fatal error|Parse error|Uncaught|Error):/i', $line);
            if ($start || $current === '') {
                if ($current !== '') { $records[] = $current; }
                $current = $line;
            } else {
                // Stack traces and wrapped exceptions belong to the preceding error.
                $current .= "\n" . $line;
            }
        }
        if ($current !== '') { $records[] = $current; }
        return $records;
    }

    private static function normalize(string $record): string {
        $withoutTimestamp = preg_replace(
            '/^\[?\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:\s*[+-]\d{2}:?\d{2})?\]?\s*(?:-\s*)?/',
            '',
            $record
        );
        return trim((string)$withoutTimestamp);
    }

    /**
     * Header count is a maximum 1 MB scan once per 180 seconds, not on each request.
     * If the private cache directory is unwritable, omit the badge rather than scan repeatedly.
     */
    public static function cachedCount($config): ?int {
        if (!defined('DIR_CACHE') || !is_dir(DIR_CACHE) || !is_writable(DIR_CACHE)) { return null; }
        $path = self::path($config);
        if (!is_file($path) || !is_readable($path)) { return 0; }
        $cache = self::cachePath($path);
        if (is_file($cache)) {
            $state = json_decode((string)@file_get_contents($cache), true);
            if (is_array($state) && isset($state['expires'], $state['count'])
                && (int)$state['expires'] > time() && (int)$state['count'] >= 0) {
                return (int)$state['count'];
            }
        }
        $count = count(self::group(self::tail($path)));
        @file_put_contents($cache, json_encode(array('expires' => time() + self::CACHE_TTL, 'count' => $count)), LOCK_EX);
        return $count;
    }

    /** Rebuild from current log on next header request, including after a clear. */
    public static function invalidate($config): void {
        if (!defined('DIR_CACHE')) { return; }
        $cache = self::cachePath(self::path($config));
        if (is_file($cache)) { @unlink($cache); }
    }

    private static function cachePath(string $log): string {
        return rtrim(DIR_CACHE, '/\\') . '/codecart_log_unique_' . substr(hash('sha256', $log), 0, 20) . '.json';
    }
}
