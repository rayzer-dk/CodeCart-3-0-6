<?php
/**
 * Opt-in, expiring OpenCart 3 Events execution diagnostics.
 * Never include request data, action arguments or exception messages.
 */
class CodeCartEventDiagnostics {
    const TRACE_SECONDS = 600;
    const TRACE_MAX_BYTES = 1048576;

    public static function markerPath() {
        return rtrim(DIR_LOGS, '/\\') . '/codecart-event-trace.enabled';
    }

    public static function tracePath() {
        return rtrim(DIR_LOGS, '/\\') . '/codecart-event-trace.jsonl';
    }

    public static function expiration() {
        $path = self::markerPath();
        if (!is_file($path) || !is_readable($path)) { return 0; }
        $until = (int)trim((string)@file_get_contents($path));
        return $until > time() ? $until : 0;
    }

    public static function start() {
        if (@file_put_contents(self::tracePath(), '', LOCK_EX) === false) { return false; }
        @chmod(self::tracePath(), 0600);
        if (@file_put_contents(self::markerPath(), (string)(time() + self::TRACE_SECONDS), LOCK_EX) === false) { return false; }
        @chmod(self::markerPath(), 0600);
        return true;
    }

    public static function stop() {
        $path = self::markerPath();
        return !is_file($path) || @unlink($path);
    }

    public static function clear() {
        return @file_put_contents(self::tracePath(), '', LOCK_EX) !== false;
    }

    public static function record($eventId, $outcome, $seconds, $exception = null) {
        try {
            $id = (int)$eventId;
            if ($id < 1 || !self::expiration()) { return; }
            $path = self::tracePath();
            clearstatcache(true, $path);
            if (is_file($path) && @filesize($path) >= self::TRACE_MAX_BYTES) { return; }
            $row = array('id' => $id, 'at' => time(), 'result' => $outcome === 'error' ? 'error' : 'ok',
                         'ms' => round(max(0, (float)$seconds) * 1000, 2));
            if ($exception instanceof \Throwable) { $row['exception'] = get_class($exception); }
            $json = json_encode($row, JSON_UNESCAPED_SLASHES);
            if (is_string($json)) { @file_put_contents($path, $json . "\n", FILE_APPEND | LOCK_EX); }
        } catch (\Throwable $ignored) {
            // Diagnostics must not interfere with the order or outcome of an event.
        }
    }

    public static function read() {
        $file = self::tracePath();
        if (!is_file($file) || !is_readable($file)) { return array(); }
        $fp = @fopen($file, 'rb');
        if (!$fp) { return array(); }
        $size = (int)@filesize($file);
        if ($size > self::TRACE_MAX_BYTES) { fseek($fp, -self::TRACE_MAX_BYTES, SEEK_END); fgets($fp); }
        $history = array();
        while (($line = fgets($fp)) !== false) {
            $row = json_decode($line, true);
            if (!is_array($row) || empty($row['id']) || !isset($row['at'], $row['result'])) { continue; }
            $id = (int)$row['id'];
            if ($id < 1) { continue; }
            if (!isset($history[$id])) {
                $history[$id] = array('calls' => 0, 'errors' => 0, 'last' => 0, 'last_ms' => 0, 'exception' => '');
            }
            $history[$id]['calls']++;
            if ($row['result'] === 'error') { $history[$id]['errors']++; }
            if ((int)$row['at'] >= $history[$id]['last']) {
                $history[$id]['last'] = (int)$row['at'];
                $history[$id]['last_ms'] = (float)($row['ms'] ?? 0);
                $history[$id]['exception'] = (string)($row['exception'] ?? '');
            }
        }
        fclose($fp);
        return $history;
    }
}
