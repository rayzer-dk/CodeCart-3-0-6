<?php
declare(strict_types=1);

// Exercises the application loop extracted from the actual refresh controller;
// it does not start a store, publish a modification cache or connect to a database.
$root = dirname(__DIR__);
require $root . '/upload/system/library/codecart/src/CompatibilityAdapterInterface.php';
require $root . '/upload/system/library/codecart/src/Unishop2Compatibility.php';
require $root . '/upload/system/library/codecart/src/CompatibilityFramework.php';

class Controller {
    public $registry;
    public function __construct($registry) { $this->registry = $registry; }
}
class OcmodQaRegistry {
    private $framework;
    public function __construct() { $this->framework = new \CodeCart\Core\CompatibilityFramework($this); }
    public function get($key) { return $key === 'codecart_compatibility_framework' ? $this->framework : null; }
}
require $root . '/upload/admin/controller/marketplace/modification.php';

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function check($condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
function xml(string $source): DOMDocument {
    $dom = new DOMDocument();
    if (!$dom->loadXML($source, LIBXML_NONET)) { throw new RuntimeException('Invalid XML fixture'); }
    return $dom;
}
function loadPhpFixture(string $source) {
    $path = tempnam(sys_get_temp_dir(), 'codecart-ocmod-qa-');
    if ($path === false) { throw new RuntimeException('Cannot create PHP fixture'); }
    try {
        if (file_put_contents($path, $source) !== strlen($source)) {
            throw new RuntimeException('Cannot write PHP fixture');
        }
        return require $path;
    } finally {
        unlink($path);
    }
}
function methodSource(string $source, string $name): string {
    $tokens = token_get_all($source);
    $capture = false;
    $pending = '';
    $depth = 0;
    $opened = false;
    foreach ($tokens as $token) {
        $value = is_array($token) ? $token[1] : $token;
        if (!$capture) {
            if (is_array($token) && $token[0] === T_FUNCTION) { $pending = 'function'; continue; }
            if ($pending !== '' && is_array($token) && $token[0] === T_STRING) {
                if ($value === $name) { $capture = true; $pending = 'public function ' . $name; }
                else { $pending = ''; }
            }
            continue;
        }
        $pending .= $value;
        if ($token === '{') { $depth++; $opened = true; }
        if ($token === '}') { $depth--; if ($opened && $depth === 0) { return $pending; } }
    }
    throw new RuntimeException('Method not found: ' . $name);
}

$controllerText = (string)file_get_contents($root . '/upload/admin/controller/marketplace/modification.php');
$begin = strpos($controllerText, "if (\$search_element->getAttribute('regex') != 'true') {");
$end = strpos($controllerText, 'if ($status) { $applied_matches++; }', $begin === false ? 0 : $begin);
check($begin !== false && $end !== false, 'Refresh application block not found');
$applicationBlock = substr($controllerText, $begin, $end - $begin);
check(substr_count($applicationBlock, '$this->normalizeCompatibilityAdd(') === 2, 'Both refresh branches must call normalization');
$controller = new ControllerMarketplaceModification(new OcmodQaRegistry());
// Four single-iteration loops retain the refresh block's existing break 4 targets.
$apply = Closure::bind(loadPhpFixture('<?php return function ($content, $operation, $current_code, $current_version, $key) {
    $modification = array($key => $content); $status = false; $log = array();
    $search_element = $operation->getElementsByTagName("search")->item(0);
    $add_element = $operation->getElementsByTagName("add")->item(0);
    $build_errors = 0; $operation_index = 1; $issues = array();
    for ($qaA=0;$qaA<1;$qaA++) { for ($qaB=0;$qaB<1;$qaB++) {
    for ($qaC=0;$qaC<1;$qaC++) { for ($qaD=0;$qaD<1;$qaD++) {' . $applicationBlock . '
    } } } }
    if ($build_errors) { throw new \\RuntimeException("Refresh application failed: " . $mod_issue); }
    return array($modification[$key], $status);
};'), $controller, ControllerMarketplaceModification::class);

$sortAnchor = "if (isset(\$data['sort']) && in_array(\$data['sort'], \$sort_data)) {";
$stockInjection = <<<'PHP'
$uniset = $this->config->get('config_unishop2');
$sort_qty = '';
if ($uniset['sort_qty'] == 1) {
    if (isset($data['sort']) && $data['sort'] == 'p.sort_order') {
        $sort_qty = '(p.quantity > 0) DESC,';
    }
} elseif ($uniset['sort_qty'] == 2) {
    $sort_qty = '(p.quantity > 0) DESC,';
}
PHP;
$vendor = xml('<modification><code>UniShop2 template</code><version>3.6.6.0</version><file path="catalog/model/catalog/product.php">'
    . '<operation><search><![CDATA[' . $sortAnchor . ']]></search><add position="before"><![CDATA[' . $stockInjection . ']]></add></operation>'
    . '<operation><search><![CDATA[$sql .= " ORDER BY]]></search><add position="replace"><![CDATA[$sql .= " ORDER BY ".$sort_qty."]]></add></operation>'
    . '</file></modification>');
$original = null;
foreach ($argv as $argument) {
    if (strpos($argument, '--original=') === 0) { $original = substr($argument, 11); }
}
if ($original !== null) {
    $private = xml((string)file_get_contents($original));
    $xpath = new DOMXPath($private);
    $ops = $xpath->query('//file[@path="catalog/model/catalog/product.php"]/operation');
    $selected = array();
    foreach ($ops as $op) {
        $search = trim($op->getElementsByTagName('search')->item(0)->textContent);
        if ($search === $sortAnchor || $search === '$sql .= " ORDER BY') { $selected[] = $op; }
    }
    check(count($selected) === 2, 'Expected two stock-sort operations in private original');
    $target = $vendor->getElementsByTagName('file')->item(0);
    while ($target->firstChild) { $target->removeChild($target->firstChild); }
    foreach ($selected as $op) { $target->appendChild($vendor->importNode($op, true)); }
}
$normalize = static function ($code, $version, $file, $search, $add) {
    return \CodeCart\Core\Unishop2Compatibility::normalizeOcmodAdd($code, $version, $file, $search, $add);
};
$path = 'catalog/model/catalog/product.php';
$fixed = $normalize('UniShop2 template', '3.6.6.0', $path, $sortAnchor, $stockInjection);
check($fixed !== $stockInjection && substr_count($fixed, "(int)(\$uniset['sort_qty'] ?? 0)") === 2, 'Stock-sort payload was not normalized');
check($normalize('UniShop2 template', '3.6.6.0', $path, $sortAnchor, $fixed) === $fixed, 'Normalization must be idempotent');
foreach (array(
    array('Other extension', '3.6.6.0', $path, $sortAnchor, $stockInjection),
    array('UniShop2 template', '3.6.5.0', $path, $sortAnchor, $stockInjection),
    array('UniShop2 template', '3.6.6.0', 'catalog/model/tool/image.php', $sortAnchor, $stockInjection),
    array('UniShop2 template', '3.6.6.0', $path, '$unknown;', $stockInjection),
    array('UniShop2 template', '3.6.6.0', $path, $sortAnchor, $stockInjection . "\n\$unknown = true;"),
) as $arguments) {
    check($normalize(...$arguments) === $arguments[4], 'Unknown payload/source must remain byte-identical');
}
foreach (array(' ', "\t") as $space) {
    $variation = str_replace("\$uniset['sort_qty'] ==", "(int)" . $space . "\$uniset [ 'sort_qty' ]" . $space . '==', $stockInjection);
    check(substr_count($normalize('UniShop2 template', '3.6.6.0', $path, $sortAnchor, $variation), "?? 0") === 2, 'Casts/spacing variant not normalized');
}

$sitemap = methodSource((string)file_get_contents($root . '/upload/catalog/model/catalog/product.php'), 'getProductsForSitemap');
$syntheticModel = <<<'PHP'
<?php
class OcmodGeneratedModel {
    public $config;
    public $db;
    public function products($data) {
        $sql = 'SELECT';
        $sort_data = array('p.sort_order', 'pd.name');
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        }
        return $sql;
    }
    public function unrelatedHelper() {
        $sql = 'SELECT';
        $sql .= " ORDER BY p.product_id";
        return $sql;
    }
SITEMAP
}
PHP;
$syntheticModel = str_replace('SITEMAP', $sitemap, $syntheticModel);
$refreshModel = static function () use ($vendor, $syntheticModel, $apply, $path) {
    $source = $syntheticModel;
    $code = trim($vendor->getElementsByTagName('code')->item(0)->textContent);
    $version = trim($vendor->getElementsByTagName('version')->item(0)->textContent);
    foreach ($vendor->getElementsByTagName('operation') as $operation) {
        list($source, $matched) = $apply($source, $operation, $code, $version, $path);
        check($matched, 'An expected stock-sort operation did not match');
    }
    return $source;
};
$first = $refreshModel();
check($first === $refreshModel(), 'Repeated refresh is not deterministic');
$sitemapAfter = methodSource($first, 'getProductsForSitemap');
check($sitemapAfter === $sitemap && strpos($sitemapAfter, '$sort_qty') === false, 'Sitemap changed or received stock sorting');
token_get_all($first, TOKEN_PARSE);
loadPhpFixture($first);
$model = new OcmodGeneratedModel();
$model->config = new class {
    public $mode;
    public function get($key) {
        if ($key === 'config_unishop2') { return $this->mode; }
        return $key === 'config_language_id' ? 1 : 0;
    }
};
foreach (array(null, array(), array('sort_qty' => 0), array('sort_qty' => 1), array('sort_qty' => 2), array('sort_qty' => '1'), array('sort_qty' => '2')) as $settings) {
    $model->config->mode = $settings;
    foreach (array('p.sort_order', 'pd.name') as $sort) {
        $mode = (int)($settings['sort_qty'] ?? 0);
        $stock = $mode === 2 || ($mode === 1 && $sort === 'p.sort_order');
        $actualSql = preg_replace('/\s+/', ' ', trim($model->products(array('sort' => $sort))));
        $expectedSql = trim('SELECT ORDER BY ' . ($stock ? '(p.quantity > 0) DESC, ' : '') . $sort);
        check($actualSql === $expectedSql, 'Stock-sort behavior changed: ' . $actualSql);
    }
}
check(preg_replace('/\s+/', ' ', $model->unrelatedHelper()) === 'SELECT ORDER BY p.product_id', 'Undefined stock setting leaked into an unrelated helper');
$model->db = new class {
    public $sql;
    public function query($sql) { $this->sql = $sql; return (object)array('rows' => array()); }
};
if (!defined('DB_PREFIX')) { define('DB_PREFIX', 'qa_'); }
$model->getProductsForSitemap(5, 20);
$sitemapSuffix = ' ORDER BY p.product_id ASC LIMIT 5,20';
check(substr($model->db->sql, -strlen($sitemapSuffix)) === $sitemapSuffix, 'Sitemap SQL changed');
// A regex source operation also enters the normalizer, while unknown regex anchors remain unchanged.
$regex = xml('<operation><search regex="true"><![CDATA[~marker~]]></search><add position="replace"><![CDATA[replacement]]></add></operation>');
list($regexResult, $matched) = $apply('marker', $regex->documentElement, 'Other extension', '1.0', $path);
check($matched && $regexResult === 'replacement', 'Regex refresh application branch failed');
echo 'PASS ' . $checks . ' assertions; PHP ' . PHP_VERSION . '; extracted real refresh application loop; '
    . ($original === null ? 'synthetic vendor operations' : 'two actual private vendor stock-sort operations')
    . '; full refresh/store runtime NOT RUN' . PHP_EOL;
