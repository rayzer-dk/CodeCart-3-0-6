<?php
namespace Cache;

class APCu {
    private $expire;
    private $active = false;
    private $prefix;
    private $generation = 1;

    public function __construct($expire = 3600, $prefix = null) {
        $this->expire = max(1, (int)$expire);
        $this->prefix = $prefix !== null ? (string)$prefix : (defined('CACHE_PREFIX') ? (string)CACHE_PREFIX : 'oc_');

        if (!extension_loaded('apcu') || !function_exists('apcu_fetch')) {
            throw new \RuntimeException('APCu cache selected, but the PHP APCu extension is not loaded.');
        }

        if (function_exists('apcu_enabled')) {
            $enabled = (bool)apcu_enabled();
        } else {
            $ini = ini_get('apc.enabled');
            if ($ini === false || $ini === '') {
                $ini = ini_get('apcu.enabled');
            }
            $enabled = !in_array(strtolower(trim((string)$ini)), array('', '0', 'off', 'false', 'no'), true);
        }

        if (!$enabled) {
            throw new \RuntimeException('APCu cache selected, but APCu is disabled in the active PHP runtime.');
        }

        $this->active = true;
        $success = false;
        $gen = apcu_fetch($this->prefix . '__ccp_generation', $success);
        if (!$success) {
            apcu_add($this->prefix . '__ccp_generation', 1);
            $gen = 1;
        }
        $this->generation = max(1, (int)$gen);
    }

    private function clean($key) {
        return preg_replace('/[^A-Za-z0-9_\-\.]/', '_', (string)$key);
    }

    private function physical($key) {
        return $this->prefix . 'g' . $this->generation . ':' . $this->clean($key);
    }

    public function get($key) {
        $ok = false;
        $value = apcu_fetch($this->physical($key), $ok);
        return $ok ? $value : false;
    }

    public function set($key, $value) {
        return (bool)apcu_store($this->physical($key), $value, $this->expire);
    }

    public function delete($key) {
        $key = (string)$key;
        if ($key === '*') {
            $meta = $this->prefix . '__ccp_generation';
            $ok = false;
            $gen = apcu_inc($meta, 1, $ok);
            if (!$ok) {
                apcu_store($meta, 2);
                $gen = 2;
            }
            $this->generation = max(1, (int)$gen);
            return true;
        }

        $prefix = $this->physical($key);
        if (class_exists('\\APCUIterator')) {
            try {
                $it = new \APCUIterator('/^' . preg_quote($prefix, '/') . '/', APC_ITER_KEY);
                $keys = array();
                foreach ($it as $entry) {
                    $entry_key = is_array($entry) && isset($entry['key']) ? $entry['key'] : (is_string($entry) ? $entry : null);
                    if ($entry_key !== null) {
                        $keys[] = $entry_key;
                    }
                }
                if ($keys) {
                    apcu_delete($keys);
                }
                return true;
            } catch (\Throwable $e) {
                // Fall through to the exact-key delete below.
            }
        }

        return (bool)apcu_delete($prefix);
    }

    public function clear() {
        return $this->delete('*');
    }
}
