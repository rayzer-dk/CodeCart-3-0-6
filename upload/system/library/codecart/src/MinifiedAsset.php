<?php
namespace CodeCart\Core;

final class MinifiedAsset {
    public static function isCurrent(string $source, string $minified): bool {
        if (!is_file($minified)) {
            return false;
        }

        if (!is_file($source)) {
            return true;
        }

        $sourceTime = (int)@filemtime($source);
        $minifiedTime = (int)@filemtime($minified);
        $signature = md5($source . '|' . $minified . '|' . $sourceTime . '|' . (int)@filesize($source)
            . '|' . $minifiedTime . '|' . (int)@filesize($minified));
        $cache = DIR_CACHE . 'codecart.stylesheet.' . $signature;
        if (is_file($cache)) {
            $value = @file_get_contents($cache);
            if ($value === '1' || $value === '0') {
                return $value === '0';
            }
        }

        $head = (string)@file_get_contents($minified, false, null, 0, 256);
        if (!preg_match('/source [a-z0-9_.-]+ sha256:([a-f0-9]{64})/i', $head, $match)) {
            return $minifiedTime >= $sourceTime;
        }

        $current = hash_equals($match[1], (string)@hash_file('sha256', $source));
        foreach (glob(DIR_CACHE . 'codecart.stylesheet.*') ?: array() as $old) {
            if ((int)@filemtime($old) < time() - 86400) {
                @unlink($old);
            }
        }
        @file_put_contents($cache, $current ? '0' : '1', LOCK_EX);
        return $current;
    }
}
