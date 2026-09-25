<?php
namespace CodeCart\Core;

/**
 * Safe admin-only asset optimization.
 *
 * The optimizer never rewrites source files and never concatenates assets.
 * When enabled it only swaps a local admin CSS/JS URL to an existing,
 * up-to-date sibling *.min.css / *.min.js file. Otherwise the original URL
 * remains untouched, preserving dependency order and extension compatibility.
 */
final class AdminAssetOptimizer {
    private $root;
    private $minifyCss;
    private $minifyJs;

    public function __construct($config) {
        $system = realpath(DIR_SYSTEM);
        $this->root = $system ? dirname($system) : dirname(rtrim(DIR_SYSTEM, '/\\'));
        $this->minifyCss = (bool)$config->get('codecart_admin_minify_css_status');
        $this->minifyJs = (bool)$config->get('codecart_admin_minify_js_status');
    }

    public function styles(array $styles): array {
        if (!$this->minifyCss) { return $styles; }
        foreach ($styles as $key => $style) {
            if (!is_array($style) || empty($style['href'])) { continue; }
            $style['href'] = $this->minifiedVariant((string)$style['href'], 'css');
            $styles[$key] = $style;
        }
        return $styles;
    }

    public function scripts(array $scripts): array {
        if (!$this->minifyJs) { return $scripts; }
        foreach ($scripts as $key => $script) {
            $scripts[$key] = $this->minifiedVariant((string)$script, 'js');
        }
        return $scripts;
    }

    public function assets(array $assets): array {
        foreach ($assets as $id => $asset) {
            if (!is_array($asset) || empty($asset['type']) || empty($asset['url'])) { continue; }
            $type = strtolower((string)$asset['type']);
            if (($type === 'style' && !$this->minifyCss) || ($type === 'script' && !$this->minifyJs)) { continue; }
            if ($type !== 'style' && $type !== 'script') { continue; }
            $ext = $type === 'style' ? 'css' : 'js';
            $optimized = $this->minifiedVariant((string)$asset['url'], $ext);
            $asset['url'] = $optimized;
            if ($type === 'style' && isset($asset['href'])) { $asset['href'] = $optimized; }
            if ($type === 'script' && isset($asset['src'])) { $asset['src'] = $optimized; }
            $assets[$id] = $asset;
        }
        return $assets;
    }

    private function minifiedVariant(string $url, string $ext): string {
        $url = trim($url);
        if ($url === '' || preg_match('#^(?:https?:)?//#i', $url) || strpos($url, 'data:') === 0) { return $url; }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) { return $url; }
        $path = ltrim(str_replace('\\', '/', (string)$parts['path']), '/');
        if (strpos($path, 'admin/') !== 0 && strpos($path, 'view/') !== 0) { return $url; }
        if (!preg_match('#\.' . preg_quote($ext, '#') . '$#i', $path) || preg_match('#\.min\.' . preg_quote($ext, '#') . '$#i', $path)) { return $url; }
        if (strpos($path, '../') !== false || strpos($path, "\0") !== false) { return $url; }

        // Admin URLs are commonly relative to /admin/ (view/...). Resolve both
        // that form and an explicit admin/... path without changing the URL form.
        $diskPath = strpos($path, 'admin/') === 0 ? $path : 'admin/' . $path;
        $source = realpath($this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $diskPath));
        if (!$source || !is_file($source) || strpos($source, $this->root . DIRECTORY_SEPARATOR) !== 0) { return $url; }

        $candidatePath = preg_replace('#\.' . preg_quote($ext, '#') . '$#i', '.min.' . $ext, $path);
        $candidateDiskPath = strpos($candidatePath, 'admin/') === 0 ? $candidatePath : 'admin/' . $candidatePath;
        $candidate = realpath($this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidateDiskPath));
        if (!$candidate || !is_file($candidate) || strpos($candidate, $this->root . DIRECTORY_SEPARATOR) !== 0) { return $url; }
        if (@filemtime($candidate) < @filemtime($source)) { return $url; }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';
        $prefix = isset($parts['path'][0]) && $parts['path'][0] === '/' ? '/' : '';
        return $prefix . $candidatePath . $query . $fragment;
    }
}
