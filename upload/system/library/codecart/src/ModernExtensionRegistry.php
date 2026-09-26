<?php
namespace CodeCart\Core;

final class ModernExtensionRegistry {
    private $systemRoot;
    private $storageRoot;
    private $log;

    public function __construct($registry = null) {
        $this->systemRoot = rtrim(DIR_SYSTEM, '/\\') . '/extension/';
        $this->storageRoot = rtrim(DIR_STORAGE, '/\\') . '/codecart/';
        $this->log = $registry ? $registry->get('log') : null;
    }

    public function refresh(): array {
        $items = array();
        if (is_dir($this->systemRoot)) {
            $dirs = glob($this->systemRoot . '*', GLOB_ONLYDIR);
            if (is_array($dirs)) {
                foreach ($dirs as $dir) {
                    $manifest = $this->readManifest($dir);
                    if ($manifest) { $items[] = $manifest; }
                }
            }
        }
        usort($items, function ($a, $b) { return strcmp($a['code'], $b['code']); });
        $this->writeRegistry($items);
        self::registerNamespaces($items);
        return $items;
    }

    public function all(): array {
        $file = $this->registryFile();
        if (!is_file($file)) { return $this->refresh(); }
        $rootMtime = is_dir($this->systemRoot) ? (int)@filemtime($this->systemRoot) : 0;
        $registryMtime = (int)@filemtime($file);
        if ($rootMtime > $registryMtime) {
            return $this->refresh();
        }
        $json = json_decode((string)file_get_contents($file), true);
        return is_array($json) ? $json : array();
    }

    public static function bootstrap(): void {
        if (!defined('DIR_STORAGE') || !defined('DIR_SYSTEM') || !class_exists('\\CodeCartPsr4')) { return; }
        $file = rtrim(DIR_STORAGE, '/\\') . '/codecart/modern_extensions.json';
        if (!is_file($file)) { return; }
        $items = json_decode((string)file_get_contents($file), true);
        if (!is_array($items)) { return; }
        self::registerNamespaces($items);
    }

    private static function registerNamespaces(array $items): void {
        if (!defined('DIR_SYSTEM') || !class_exists('\\CodeCartPsr4')) { return; }
        $base = realpath(rtrim(DIR_SYSTEM, '/\\') . '/extension');
        if ($base === false) { return; }
        foreach ($items as $item) {
            if (empty($item['namespace']) || empty($item['code'])) { continue; }
            $src = realpath(rtrim(DIR_SYSTEM, '/\\') . '/extension/' . $item['code'] . '/src');
            if ($src === false || strpos($src, $base . DIRECTORY_SEPARATOR) !== 0) { continue; }
            \CodeCartPsr4::register((string)$item['namespace'], $src . DIRECTORY_SEPARATOR);
        }
    }

    private function readManifest(string $dir): array {
        $file = $dir . '/manifest.json';
        if (!is_file($file)) { return array(); }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) { return array(); }
        $folder = basename($dir);
        $code = isset($data['code']) ? trim((string)$data['code']) : '';
        $namespace = isset($data['namespace']) ? trim((string)$data['namespace'], " \t\n\r\\") . '\\' : '';
        if ($code !== $folder || !preg_match('/^[a-z][a-z0-9_.-]{1,63}$/', $code)) { return array(); }
        if ($namespace === '' || !preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)+$/', $namespace)) { return array(); }
        if (!is_dir($dir . '/src')) { return array(); }
        $manifestVersion = isset($data['manifest_version']) ? (int)$data['manifest_version'] : 1;
        if ($manifestVersion !== 1) { return array(); }
        $version = isset($data['version']) ? substr(trim((string)$data['version']), 0, 32) : '';
        if ($version !== '' && !preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', $version)) { return array(); }
        $capabilities = array();
        foreach (array('permissions','migrations','services','events','extension_points','scheduler','queue','assets','api','webhooks','compatibility') as $key) {
            if (isset($data[$key]) && is_array($data[$key])) { $capabilities[$key] = $data[$key]; }
        }
        return array(
            'code' => $code,
            'name' => isset($data['name']) ? substr(trim((string)$data['name']), 0, 128) : $code,
            'version' => $version,
            'namespace' => $namespace,
            'src' => 'system/extension/' . $code . '/src',
            'manifest_version' => $manifestVersion,
            'capabilities' => $capabilities
        );
    }

    private function writeRegistry(array $items): void {
        if (!is_dir($this->storageRoot) && !mkdir($this->storageRoot, 0750, true) && !is_dir($this->storageRoot)) {
            throw new \RuntimeException('Unable to create CodeCart PRO storage directory.');
        }
        $file = $this->registryFile();
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $file)) {
            if (is_file($tmp)) { @unlink($tmp); }
            throw new \RuntimeException('Unable to write Modern Extension registry.');
        }
    }

    private function registryFile(): string {
        return $this->storageRoot . 'modern_extensions.json';
    }
}
