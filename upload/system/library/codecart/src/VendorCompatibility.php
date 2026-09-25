<?php
namespace CodeCart\Core;

final class VendorCompatibility {
    public static function packageNames(string $vendorDir): array {
        $vendorDir = rtrim(str_replace('\\', '/', $vendorDir), '/') . '/';
        if (!is_dir($vendorDir)) { return array(); }

        $packages = array();
        $files = glob($vendorDir . '*/*/composer.json');
        if (!is_array($files)) { return array(); }

        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) { continue; }
            $json = json_decode((string)@file_get_contents($file), true);
            if (!is_array($json) || empty($json['name']) || !is_string($json['name'])) { continue; }
            $name = strtolower(trim($json['name']));
            if ($name !== '' && preg_match('#^[a-z0-9_.-]+/[a-z0-9_.-]+$#', $name)) {
                $packages[$name] = true;
            }
        }

        $names = array_keys($packages);
        sort($names, SORT_STRING);
        return $names;
    }

    public static function foreignPackages(string $activeVendor, string $bundledVendor): array {
        $active = self::packageNames($activeVendor);
        if (!$active) { return array(); }

        $bundled = array_fill_keys(self::packageNames($bundledVendor), true);
        $foreign = array();
        foreach ($active as $name) {
            if (!isset($bundled[$name])) { $foreign[] = $name; }
        }
        sort($foreign, SORT_STRING);
        return $foreign;
    }
}
