<?php
$root = dirname(__DIR__) . '/upload/';
define('DIR_SYSTEM', $root . 'system/');
define('DIR_STORAGE', sys_get_temp_dir() . '/codecart_compat_ext_' . getmypid() . '/');
@mkdir(DIR_STORAGE, 0750, true);

final class CodeCartPsr4 {
    private static $map = array();
    public static function register($prefix, $dir) { self::$map[$prefix] = $dir; spl_autoload_register(array(__CLASS__, 'load')); }
    public static function load($class) {
        foreach (self::$map as $prefix => $dir) {
            if (strpos($class, $prefix) === 0) {
                $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($file)) { require_once $file; return; }
            }
        }
    }
}

require DIR_SYSTEM . 'library/codecart/src/CompatibilityAdapterInterface.php';
require DIR_SYSTEM . 'library/codecart/src/ModernExtensionRegistry.php';
require DIR_SYSTEM . 'library/codecart/src/Unishop2Compatibility.php';
require DIR_SYSTEM . 'library/codecart/src/CompatibilityFramework.php';

$ext = DIR_SYSTEM . 'extension/smoke_theme_compat/';
@mkdir($ext . 'src', 0750, true);
file_put_contents($ext . 'manifest.json', json_encode(array(
    'manifest_version' => 1,
    'code' => 'smoke_theme_compat',
    'name' => 'Smoke Theme Compatibility',
    'version' => '1.0.0',
    'namespace' => 'Smoke\\ThemeCompat',
    'compatibility' => array('adapters' => array('Smoke\\ThemeCompat\\ThemeAdapter'))
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents($ext . 'src/ThemeAdapter.php', <<<'SRC'
<?php
namespace Smoke\ThemeCompat;
final class ThemeAdapter implements \CodeCart\Core\CompatibilityAdapterInterface {
    public function __construct($registry) {}
    public function id(): string { return 'theme.smoke'; }
    public function priority(): int { return 500; }
    public function active(): bool { return true; }
    public function adapt(string $contract, array $payload): array {
        if ($contract === 'catalog.banner.item') { $payload['item']['external_adapter'] = 1; }
        return $payload;
    }
}
SRC
);

final class SmokeConfig { public function get($k) { return $k === 'config_theme' ? 'default' : null; } public function has($k) { return false; } }
final class SmokeLog { public function write($m) {} }
final class SmokeRegistry { private $d; public function __construct() { $this->d=array('config'=>new SmokeConfig(),'log'=>new SmokeLog()); } public function get($k) { return $this->d[$k] ?? null; } }

$framework = new \CodeCart\Core\CompatibilityFramework(new SmokeRegistry());
$out = $framework->apply('catalog.banner.item', array('item' => array('title' => 'x'), 'width' => 1, 'height' => 1));
$ok = !empty($out['item']['external_adapter']);

@unlink($ext . 'src/ThemeAdapter.php');
@unlink($ext . 'manifest.json');
@rmdir($ext . 'src');
@rmdir($ext);
@unlink(DIR_STORAGE . 'codecart/modern_extensions.json');
@rmdir(DIR_STORAGE . 'codecart');
@rmdir(DIR_STORAGE);

if (!$ok) { fwrite(STDERR, "Installable adapter contract failed\n"); exit(1); }
echo "Installable compatibility adapter smoke: PASS\n";
