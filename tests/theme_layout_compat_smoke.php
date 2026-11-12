<?php
/** Standalone regression test: php tests/theme_layout_compat_smoke.php */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/upload/system/library/codecart/src/CompatibilityAdapterInterface.php';
require $root . '/upload/system/library/codecart/src/Unishop2Compatibility.php';
require $root . '/upload/system/library/codecart/src/CompatibilityFramework.php';

final class StubConfig {
    public $data = array();
    public function get($key) { return $this->data[$key] ?? null; }
    public function set($key, $value) { $this->data[$key] = $value; }
    public function has($key) { return array_key_exists($key, $this->data); }
}
final class StubRegistry {
    public $items = array();
    public function get($key) { return $this->items[$key] ?? null; }
    public function set($key, $value) { $this->items[$key] = $value; }
}

$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    $checks++;
}

function frameworkFor(array $config): array {
    $registry = new StubRegistry();
    $cfg = new StubConfig();
    $cfg->data = $config;
    $registry->set('config', $cfg);
    return array(new \CodeCart\Core\CompatibilityFramework($registry), $cfg);
}

$stock = array(
    array('layout_module_id' => 3, 'layout_id' => 1, 'code' => 'featured.28', 'position' => 'content_top', 'sort_order' => 2),
    array('layout_module_id' => 4, 'layout_id' => 1, 'code' => 'banner.30', 'position' => 'content_top', 'sort_order' => 9),
);
$base = array('config_theme' => 'unishop2', 'config_unishop2' => array(
    'home_banners_position' => 'content_top', 'home_banners_layout' => '1, 5', 'home_banners_position_n' => 5
));

list($fw, $cfg) = frameworkFor($base);
$out = $fw->apply('catalog.layout.modules', array('layout_id' => 1, 'position' => 'content_top', 'modules' => $stock));
check(count($out['modules']) === 3, 'home banner module is appended for a configured layout/position');
check($out['modules'][1]['code'] === 'uni_home_banner', 'module is ordered by sort_order (between 2 and 9)');
check($out['modules'][1]['layout_module_id'] === 99999999 && $out['modules'][1]['layout_id'] === 1, 'row has the legacy marker id and layout id');
check($cfg->get('module_uni_home_banner_status') === 1, 'home banner module status is enabled for the request');
check(count($stock) === 2, 'input rows are not modified in place');

$again = $fw->apply('catalog.layout.modules', $out);
check(count($again['modules']) === 3, 'applying twice does not duplicate the module');

$out = $fw->apply('catalog.layout.modules', array('layout_id' => 5, 'position' => 'content_top', 'modules' => array()));
check(count($out['modules']) === 1, 'second configured layout id (with space after comma) matches');

$out = $fw->apply('catalog.layout.modules', array('layout_id' => 2, 'position' => 'content_top', 'modules' => $stock));
check(count($out['modules']) === 2, 'other layouts are untouched');
$out = $fw->apply('catalog.layout.modules', array('layout_id' => 1, 'position' => 'content_bottom', 'modules' => $stock));
check(count($out['modules']) === 2, 'other positions are untouched');

$noLayout = $base; unset($noLayout['config_unishop2']['home_banners_layout']);
list($fw2) = frameworkFor($noLayout);
$out = $fw2->apply('catalog.layout.modules', array('layout_id' => 1, 'position' => 'content_top', 'modules' => array()));
check(count($out['modules']) === 1, 'missing home_banners_layout defaults to layout 1');

$other = $base; $other['config_theme'] = 'codecart';
list($fw3, $cfg3) = frameworkFor($other);
$out = $fw3->apply('catalog.layout.modules', array('layout_id' => 1, 'position' => 'content_top', 'modules' => $stock));
check($out['modules'] === $stock && $cfg3->get('module_uni_home_banner_status') === null, 'inactive theme leaves layout modules untouched');

$off = $base; $off['theme_unishop2_status'] = 0;
list($fw4) = frameworkFor($off);
$out = $fw4->apply('catalog.layout.modules', array('layout_id' => 1, 'position' => 'content_top', 'modules' => $stock));
check($out['modules'] === $stock, 'disabled UniShop2 theme leaves layout modules untouched');

list($fw5) = frameworkFor(array());
$out = $fw5->apply('catalog.layout.modules', array('layout_id' => 1, 'position' => 'content_top', 'modules' => $stock));
check($out['modules'] === $stock, 'no UniShop2 settings leaves layout modules untouched');

$registry = new StubRegistry();
$registry->set('config', new StubConfig());
$adapter = new \CodeCart\Core\Unishop2Compatibility($registry);
check($adapter->satisfiesOcmod('UniShop2 template', 'catalog/model/design/layout.php', 'return $query->rows;'), 'OCMOD layout anchor is reported as satisfied by the compatibility layer');
check(!$adapter->satisfiesOcmod('Other mod', 'catalog/model/design/layout.php', 'return $query->rows;'), 'satisfaction applies only to the UniShop2 template modification');

check($adapter->satisfiesOcmod('UniShop2 fix', 'system/library/template/twig.php', '$loader = new \\Twig\\Loader\\ArrayLoader(array($filename . \'.twig\' => $code));'), 'Twig loader-chain operation of the fix modification is reported as satisfied');
check(!$adapter->satisfiesOcmod('UniShop2 fix', 'system/library/template/twig.php', 'unrelated search'), 'other operations of the fix modification stay unsatisfied');
echo "theme_layout_compat_smoke: OK ($checks checks)\n";
