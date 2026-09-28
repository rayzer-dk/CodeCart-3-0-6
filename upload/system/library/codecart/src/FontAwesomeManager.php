<?php
namespace CodeCart\Core;

/**
 * CodeCart PRO storefront Font Awesome resolver and compatibility scanner.
 *
 * AUTO never rebuilds fonts on production. It scans the real storefront sources
 * plus compiled catalog OCMOD output and switches to Full Compatibility when a
 * class/code point is not present in the prebuilt Core subset.
 */
final class FontAwesomeManager {
    private const CACHE_TTL = 86400;
    private const MAX_FILE_BYTES = 2097152;
    private const MAX_OCCURRENCES = 500;
    private $fullFreeNames = null;
    private $fullFreeCodes = null;
    private $themeDirectory = '';
    private $extraIcons = array();
    private $extraManifest = array();

    public function __construct($themeDirectory = '', array $extraIcons = array()) {
        $themeDirectory = strtolower(trim((string)$themeDirectory));
        $this->themeDirectory = preg_match('/^[a-z0-9_-]+$/', $themeDirectory) ? $themeDirectory : '';
        if ($extraIcons) {
            $file = DIR_SYSTEM . 'config/codecart_fontawesome_extras.json';
            $manifest = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
            if (is_array($manifest) && isset($manifest['icons'], $manifest['shards'])) {
                $this->extraManifest = $manifest;
                foreach (array_slice($extraIcons, 0, 500) as $name) {
                    $name = $this->normalizeName($name);
                    if (!isset($manifest['icons'][$name]) || in_array($name, self::SUPPORTED, true)) { continue; }
                    $row = $manifest['icons'][$name];
                    $ready = true;
                    foreach ($row['styles'] as $shard) {
                        $path = $manifest['shards'][$shard]['file'] ?? '';
                        if (!preg_match('#^catalog/view/javascript/font-awesome/webfonts/extras/[a-z]+-[0-9]+\.woff2$#', $path) || !is_file(dirname(rtrim(DIR_SYSTEM, '/\\')) . '/' . $path)) { $ready = false; break; }
                    }
                    if ($ready) { $this->extraIcons[$name] = $row; }
                }
                ksort($this->extraIcons);
            }
        }
    }
    private const SUPPORTED = array('address-book','align-justify','align-right','angle-down','angle-left','angle-right','angles-right','arrow-down','arrow-left','arrow-right','arrow-right-arrow-left','arrow-rotate-left','arrow-up','arrows-rotate','bag-shopping','ban','bar-chart','bars','basket-shopping','bell','book','book-open','box','boxes-stacked','calendar','calendar-o','camera','camera-retro','caret-down','cart-shopping','chart-bar','check','check-circle','check-circle-o','chevron-down','chevron-left','chevron-right','chevron-up','circle-check','circle-exclamation','circle-info','circle-question','circle-xmark','clock','clock-o','clock-rotate-left','close','cloud-arrow-down','cloud-download','cloud-download-alt','cog','comment','copy','credit-card','discord','download','edit','ellipsis','ellipsis-h','envelope','envelope-o','exchange','exchange-alt','exclamation-circle','exclamation-triangle','eye','eye-slash','facebook','fax','file','file-lines','file-text-o','files-o','filter','fire-flame-curved','floppy-disk','floppy-o','gear','gift','globe','heart','heart-o','history','home','house','image','info-circle','instagram','link','linkedin','lightbulb','lightbulb-o','location-dot','location-pin','lock','magnifying-glass','map-marker','minus','money','money-bill','moon','newspaper','odnoklassniki','paper-plane','pause','pen','pen-to-square','pencil','pencil-square-o','percent','phone','phone-alt','phone-flip','phone-volume','picture-o','pinterest','play','plus','print','refresh','remove','reply','right-from-bracket','right-left','right-to-bracket','rotate','search','share','share-alt','share-nodes','shield-halved','shop','shopping-bag','shopping-cart','sign-in-alt','sign-out-alt','sliders','sort','spinner','star','star-o','store','sun','table-cells','table-cells-large','table-list','tag','tags','telegram','telegram-plane','th','th-large','th-list','threads','thumbs-down','thumbs-up','tiktok','times','times-circle','trash-alt','trash-can','trash-o','triangle-exclamation','truck','twitter','undo','unlock','upload','user','user-plus','users','viber','vk','whatsapp','x-twitter','xmark','youtube');
    private const UTILITIES = array('10x','2x','2xl','3x','4x','5x','6x','7x','8x','9x','beat','beat-fade','border','bounce','brands','classic','fade','flip','flip-both','flip-horizontal','flip-vertical','fw','inverse','lg','li','pull-left','pull-right','pulse','regular','rotate-180','rotate-270','rotate-90','rotate-by','shake','sharp','sm','solid','spin','spin-pulse','spin-reverse','stack','stack-1x','stack-2x','ul','xl','xs');
    private const SUPPORTED_LEGACY_CODES = array('1f310','1f319','1f381','1f3e0','1f3f7','1f441','1f44d','1f44e','1f464','1f499','1f49a','1f49b','1f49c','1f4b3','1f4c4','1f4c5','1f4c6','1f4d4','1f4d6','1f4de','1f4e0','1f4e6','1f4f0','1f4f7','1f504','1f50d','1f512','1f513','1f514','1f517','1f553','1f56e','1f57b','1f57d','1f582','1f58a','1f5a4','1f5a8','1f5b6','1f5b7','1f5b9','1f5cb','1f5ce','1f5d8','1f5d9','1f5e9','1f69a','1f6ab','1f6d2','1f90d','1f90e','1f9e1','2013','2039','203a','2190','2191','2192','2193','21ba','21c4','2212','2304','2329','232a','2399','23f8','23fe','25b6','2600','2665','2699','26a0','26df','2709','270f','2713','2714','2715','2716','274c','2764','2795','2796','2b50','f003','f006','f00c','f014','f016','f040','f053','f054','f05c','f05d','f067','f087','f088','f08a','f0a2','f0e5','f0f6','f101','f112','f16a','f1d9','f230','f283','f295','f29c','f2ba','f2c0','f332','f381','f3fe','f4a1','f541','f80a','f80c');

    public function resolveCatalogMode($requested) {
        $requested = strtolower(trim((string)$requested));
        if ($requested === 'standard') { $requested = 'full'; }
        if ($requested === 'full' || $requested === 'core') { return $requested; }
        $scan = $this->scanCatalog();
        return !empty($scan['unknown']) || !empty($scan['scanner_error']) ? 'full' : 'core';
    }

    public function getSupportedIcons() {
        return array_values(array_unique(array_merge(self::SUPPORTED, array_keys($this->extraIcons))));
    }

    public function isSupported($name) {
        $name = $this->normalizeName($name);
        return $name !== '' && (in_array($name, self::SUPPORTED, true) || isset($this->extraIcons[$name]));
    }

    public function getExtraIcons() {
        return array_keys($this->extraIcons);
    }

    /** Local prebuilt font shards are requested only for selected icon styles. */
    public function extraCss() {
        $faces = array();
        $rules = array();
        foreach ($this->extraIcons as $name => $row) {
            $styles = $row['styles'];
            $ordered = array_merge(array($row['default']), array_diff(array_keys($styles), array($row['default'])));
            foreach ($ordered as $style) {
                $shard = $styles[$style];
                $asset = $this->extraManifest['shards'][$shard];
                $weight = $style === 'solid' ? 900 : 400;
                $family = 'CodeCartExtra-' . $shard;
                $faces[$shard] = '@font-face{font-family:"' . $family . '";font-style:normal;font-weight:' . $weight . ';font-display:swap;src:url("' . $asset['file'] . '?v=' . substr($asset['sha256'], 0, 12) . '") format("woff2")}';
                $short = $style === 'solid' ? 'fas' : ($style === 'regular' ? 'far' : 'fab');
                $selector = '.fa-' . $style . '.fa-' . $name . ',.' . $short . '.fa-' . $name;
                if ($style === $row['default']) { $selector = '.fa.fa-' . $name . ',' . $selector; }
                $rules[] = $selector . '{--fa:"\\' . $row['code'] . '";font-family:"' . $family . '"!important;font-weight:' . $weight . '!important}';
            }
        }
        return implode('', $faces) . implode('', $rules);
    }

    public function scanCatalog($force = false) {
        if ($force) { self::invalidateCache(); }
        $cache = $this->readCache();
        if ($cache !== null) { return $cache; }

        $unknown = array();
        $fullFallback = array();
        $unresolved = array();
        $occurrences = array();
        $files = 0;
        $scannerError = '';

        foreach ($this->catalogRoots() as $rootInfo) {
            $root = $rootInfo['path'];
            $label = $rootInfo['label'];
            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if (!$file->isFile() || $file->isLink()) { continue; }
                    $path = str_replace('\\', '/', $file->getPathname());
                    if ($this->isInactiveThemePath($path)) { continue; }
                    if (strpos($path, '/view/javascript/font-awesome/') !== false) { continue; }
                    $ext = strtolower($file->getExtension());
                    if (!in_array($ext, array('php','twig','js','css','xml'), true) || $file->getSize() > self::MAX_FILE_BYTES) { continue; }

                    $content = @file_get_contents($file->getPathname());
                    if (!is_string($content)) { continue; }
                    // Do not scan third-party Font Awesome distribution CSS as if every
                    // class declared by the library were actually used by the storefront.
                    // We scan real templates/JS/CSS usage instead. This is important for
                    // themes such as UniShop2 that bundle the complete Font Awesome CSS.
                    if ($ext === 'css' && stripos($content, 'Font Awesome') !== false && stripos($content, '@font-face') !== false) { continue; }
                    $files++;
                    $relative = $label . ltrim(substr($path, strlen(str_replace('\\', '/', $root))), '/');

                    if (preg_match_all('/(?<![A-Za-z0-9_-])fa-([a-z0-9][a-z0-9-]*)(?![A-Za-z0-9_-])/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[1] as $match) {
                            $name = strtolower((string)$match[0]);
                            if (in_array($name, self::UTILITIES, true) || $this->isSupported($name)) { continue; }
                            // Ignore Font Awesome webfont filenames such as
                            // fa-solid-900.woff2 / fa-regular-400.woff2.
                            if (preg_match('/^(?:solid|regular|brands)-[0-9]+$/', $name)) { continue; }
                            $unknown[$name] = true;
                            $resolution = $this->isAvailableInFull($name, 'class') ? 'full' : 'unresolved';
                            if ($resolution === 'full') { $fullFallback[$name] = true; } else { $unresolved[$name] = true; }
                            $this->addOccurrence($occurrences, $name, 'class', $relative, $this->lineAtOffset($content, (int)$match[1]), $resolution);
                        }
                    }

                    if (preg_match('/font-family\s*:\s*[\'"]?FontAwesome/i', $content) && preg_match_all('/content\s*:\s*[\'"]\\\\([0-9a-f]{4,6})/i', $content, $codes, PREG_OFFSET_CAPTURE)) {
                        foreach ($codes[1] as $match) {
                            $code = strtolower((string)$match[0]);
                            if (in_array($code, self::SUPPORTED_LEGACY_CODES, true)) { continue; }
                            $key = 'unicode:' . $code;
                            $unknown[$key] = true;
                            $resolution = $this->isAvailableInFull($code, 'unicode') ? 'full' : 'unresolved';
                            if ($resolution === 'full') { $fullFallback[$key] = true; } else { $unresolved[$key] = true; }
                            $this->addOccurrence($occurrences, $key, 'unicode', $relative, $this->lineAtOffset($content, (int)$match[1]), $resolution);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $scannerError = get_class($e) . ': ' . $e->getMessage();
            }
        }

        usort($occurrences, function($a, $b) {
            $cmp = strcmp($a['icon'], $b['icon']);
            if ($cmp !== 0) { return $cmp; }
            $cmp = strcmp($a['file'], $b['file']);
            return $cmp !== 0 ? $cmp : ((int)$a['line'] <=> (int)$b['line']);
        });

        $result = array(
            'format' => 3,
            'unknown' => array_slice(array_keys($unknown), 0, 200),
            'full_fallback' => array_slice(array_keys($fullFallback), 0, 200),
            'unresolved' => array_slice(array_keys($unresolved), 0, 200),
            'occurrences' => array_slice($occurrences, 0, self::MAX_OCCURRENCES),
            'occurrences_truncated' => count($occurrences) > self::MAX_OCCURRENCES,
            'files' => $files,
            'scanned_at' => time(),
            'scanner_error' => $scannerError
        );
        $this->writeCache($result);
        return $result;
    }

    /**
     * Builds a tiny visual safety net for unresolved Font Awesome classes.
     * The scanner still reports the problem; this only avoids an empty square
     * on the CodeCart PRO default storefront until the extension/icon is corrected.
     */
    public function unresolvedPlaceholderCss(?array $scan = null) {
        if ($scan === null) { $scan = $this->scanCatalog(); }
        $names = array();
        foreach (($scan['occurrences'] ?? array()) as $row) {
            if (($row['resolution'] ?? '') !== 'unresolved' || ($row['kind'] ?? '') !== 'class') { continue; }
            $name = $this->normalizeName($row['icon'] ?? '');
            if ($name !== '') { $names[$name] = true; }
        }
        if (!$names) { return ''; }
        ksort($names);
        $rules = array();
        foreach (array_keys($names) as $name) {
            $selector = '.fa-' . $name;
            // U+F059 = circle-question in Font Awesome Free. Force the Solid
            // family so a missing Brands icon also receives a predictable glyph.
            $rules[] = $selector . '{--fa:"\\f059";font-family:"Font Awesome 6 Free"!important;font-weight:900!important}' . $selector . ':before{content:"\\f059"!important}';
        }
        return implode('', $rules);
    }

    public static function invalidateCache() {
        if (!defined('DIR_CACHE')) { return; }
        foreach (glob(rtrim(DIR_CACHE, '/\\') . '/codecart.fontawesome.catalog*.json') ?: array() as $file) {
            if (is_file($file)) { @unlink($file); }
        }
    }

    private function catalogRoots() {
        $roots = array();
        if (defined('DIR_CATALOG') && is_dir(DIR_CATALOG)) {
            $path = rtrim(str_replace('\\', '/', DIR_CATALOG), '/') . '/';
            $roots[] = array('path' => $path, 'label' => 'catalog/');
        } elseif (defined('DIR_APPLICATION') && is_dir(DIR_APPLICATION)) {
            $path = rtrim(str_replace('\\', '/', DIR_APPLICATION), '/') . '/';
            $roots[] = array('path' => $path, 'label' => 'catalog/');
        }
        if (defined('DIR_MODIFICATION') && is_dir(DIR_MODIFICATION . 'catalog/')) {
            $path = rtrim(str_replace('\\', '/', DIR_MODIFICATION . 'catalog/'), '/') . '/';
            $roots[] = array('path' => $path, 'label' => 'modification/catalog/');
        }

        $unique = array();
        $result = array();
        foreach ($roots as $row) {
            $real = realpath($row['path']);
            $key = $real !== false ? str_replace('\\', '/', $real) : $row['path'];
            if (isset($unique[$key])) { continue; }
            $unique[$key] = true;
            $result[] = $row;
        }
        return $result;
    }

    private function isInactiveThemePath($path) {
        if ($this->themeDirectory === '') { return false; }
        $path = str_replace('\\', '/', (string)$path);
        if (!preg_match('#/view/theme/([^/]+)/#i', $path, $match)) { return false; }
        return strtolower((string)$match[1]) !== $this->themeDirectory;
    }

    private function cacheFile() {
        if (!defined('DIR_CACHE')) { return ''; }
        $suffix = $this->themeDirectory !== '' ? '.' . $this->themeDirectory : '';
        // A saved selection and a new release must not reuse the old scan.
        $suffix .= '.' . substr(hash('sha256', implode(',', $this->getSupportedIcons())), 0, 12);
        return rtrim(DIR_CACHE, '/\\') . '/codecart.fontawesome.catalog' . $suffix . '.json';
    }

    private function normalizeName($name) {
        $name = strtolower(trim((string)$name));
        if (strpos($name, 'fa-') === 0) { $name = substr($name, 3); }
        return preg_match('/^[a-z0-9][a-z0-9-]*$/', $name) ? $name : '';
    }

    private function addOccurrence(array &$rows, $icon, $kind, $file, $line, $resolution = 'full') {
        if (count($rows) >= self::MAX_OCCURRENCES + 1) { return; }
        $rows[] = array('icon' => (string)$icon, 'kind' => (string)$kind, 'file' => (string)$file, 'line' => (int)$line, 'resolution' => (string)$resolution);
    }

    private function isAvailableInFull($value, $kind) {
        $this->loadFullManifest();
        if ($kind === 'unicode') {
            return isset($this->fullFreeCodes[strtolower((string)$value)]);
        }
        return isset($this->fullFreeNames[$this->normalizeName($value)]);
    }

    private function loadFullManifest() {
        if (is_array($this->fullFreeNames) && is_array($this->fullFreeCodes)) { return; }
        $this->fullFreeNames = array();
        $this->fullFreeCodes = array();
        if (!defined('DIR_SYSTEM')) { return; }
        $file = rtrim(DIR_SYSTEM, '/\\') . '/config/codecart_fontawesome_core_manifest.json';
        $json = is_file($file) ? @file_get_contents($file) : false;
        $data = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($data)) { return; }
        foreach (($data['full_free_icons'] ?? array()) as $name) {
            $name = $this->normalizeName($name);
            if ($name !== '') { $this->fullFreeNames[$name] = true; }
        }
        foreach (($data['full_free_legacy_codes'] ?? array()) as $code) {
            $code = strtolower(trim((string)$code));
            if (preg_match('/^[0-9a-f]{4,6}$/', $code)) { $this->fullFreeCodes[$code] = true; }
        }
    }

    private function lineAtOffset($content, $offset) {
        if ($offset <= 0) { return 1; }
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function readCache() {
        if (!defined('DIR_CACHE')) { return null; }
        $file = $this->cacheFile();
        if ($file === '' || !is_file($file) || (time() - (int)@filemtime($file)) > self::CACHE_TTL) { return null; }
        $json = @file_get_contents($file);
        $data = is_string($json) ? json_decode($json, true) : null;
        return is_array($data) && isset($data['unknown'], $data['occurrences']) && isset($data['format']) && (int)$data['format'] === 3 ? $data : null;
    }

    private function writeCache(array $data) {
        if (!defined('DIR_CACHE') || !is_dir(DIR_CACHE) || !is_writable(DIR_CACHE)) { return; }
        $file = $this->cacheFile();
        if ($file === '') { return; }
        $tmp = @tempnam(DIR_CACHE, '.cc-fa-');
        if ($tmp === false) { return; }
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (is_string($json) && @file_put_contents($tmp, $json, LOCK_EX) !== false) {
            if (!@rename($tmp, $file)) { @unlink($tmp); }
        } else {
            @unlink($tmp);
        }
    }
}
