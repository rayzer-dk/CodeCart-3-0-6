<?php
declare(strict_types=1);

// Contract tests use disposable files, in-memory SQL fixtures and a mail sink.
$root = dirname(__DIR__);
$case = $argv[1] ?? 'stock';
$temp = sys_get_temp_dir() . '/codecart-commerce-' . bin2hex(random_bytes(8));
mkdir($temp . '/controller/cron', 0755, true);
mkdir($temp . '/image/catalog', 0755, true);
$temp = str_replace('\\', '/', realpath($temp));
register_shutdown_function(static function () use ($temp): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) { rmdir($file->getPathname()); }
        else { unlink($file->getPathname()); }
    }
    rmdir($temp);
});
define('DB_PREFIX', 'qa_');
define('DB_DATABASE', 'commerce_fixture');
define('DIR_APPLICATION', $temp . '/');
define('DIR_CATALOG', $root . '/upload/catalog/');
define('DIR_SYSTEM', $root . '/upload/system/');
define('DIR_IMAGE', $temp . '/image/');
define('HTTP_SERVER', 'https://store.example.test/');
require $root . '/upload/system/engine/registry.php';
require $root . '/upload/system/engine/controller.php';
require $root . '/upload/system/engine/model.php';
require $root . '/upload/system/library/config.php';
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});

$checks = 0;
function commerceCheck(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
function commerceRows(array $rows): object {
    return (object)array('rows' => $rows, 'row' => $rows[0] ?? array(), 'num_rows' => count($rows));
}
function commerceRegistry($db): Registry {
    $registry = new Registry();
    $config = new Config();
    foreach (array('config_url' => HTTP_SERVER, 'config_ssl' => HTTP_SERVER, 'config_email' => 'store@example.test', 'config_name' => 'Fixture', 'config_mail_engine' => 'fixture', 'config_mail_smtp_password' => '') as $key => $value) {
        $config->set($key, $value);
    }
    $registry->set('config', $config);
    $registry->set('db', $db);
    return $registry;
}

if ($case === 'stock') {
    require $root . '/upload/system/library/codecart/src/MailDelivery.php';
    require $root . '/upload/catalog/controller/cron/stock_notify.php';
    class Language {
        public function __construct($locale) {}
        public function load($route) {}
        public function get($key) { return $key; }
    }
    #[AllowDynamicProperties]
    class Mail {
        public static array $sent = array();
        public static bool $fail = false;
        private string $email = '';
        public function __construct($engine) {}
        public function setTo($value) { $this->email = $value; }
        public function setFrom($value) {}
        public function setSender($value) {}
        public function setSubject($value) {}
        public function setHtml($value) {}
        public function setText($value) {}
        public function send() { if (self::$fail) { return false; } self::$sent[] = $this->email; return true; }
    }
    class CommerceStockDb {
        public array $rows = array();
        public int $affected = 0;
        public int $optionChecks = 0;
        public function escape($value) { return addslashes((string)$value); }
        public function countAffected() { return $this->affected; }
        public function query($sql) {
            $this->affected = 0;
            if (str_starts_with($sql, 'SELECT sn.*')) {
                $rows = array_values(array_filter($this->rows, static fn($row) => $row['status'] === 'waiting' && (!str_contains($sql, 'p.quantity>0') || $row['quantity'] > 0)));
                usort($rows, static fn($a, $b) => str_contains($sql, 'ORDER BY sn.date_modified') ? ($a['date_modified'] <=> $b['date_modified'] ?: $a['stock_notify_id'] <=> $b['stock_notify_id']) : $a['stock_notify_id'] <=> $b['stock_notify_id']);
                preg_match('/LIMIT (\d+)/', $sql, $m);
                $rows = array_slice($rows, 0, (int)$m[1]);
                if (!str_contains($sql, 'p.quantity,')) { foreach ($rows as &$row) { unset($row['quantity']); } unset($row); }
                return commerceRows($rows);
            }
            if (str_starts_with($sql, 'SELECT COUNT(*)')) {
                $this->optionChecks++;
                return commerceRows(array(array('total' => 1, 'ready' => str_contains($sql, '(900)') ? 1 : 0)));
            }
            if (preg_match("/WHERE stock_notify_id='(\d+)'/", $sql, $m)) {
                $id = (int)$m[1];
                if (str_contains($sql, "SET status='processing'")) {
                    if ($this->rows[$id]['status'] === 'waiting') { $this->rows[$id]['status'] = 'processing'; $this->rows[$id]['attempts']++; $this->affected = 1; }
                } elseif (str_contains($sql, "SET status='sent'")) { $this->rows[$id]['status'] = 'sent'; }
                elseif (str_contains($sql, 'SET status=IF')) { $this->rows[$id]['status'] = $this->rows[$id]['attempts'] >= 5 ? 'failed' : 'waiting'; }
                elseif (str_contains($sql, 'SET date_modified=')) { $this->rows[$id]['date_modified'] = 2; }
            }
            return commerceRows(array());
        }
    }
    function stockRow(int $id, int $quantity, string $options = ''): array {
        return array('stock_notify_id' => $id, 'product_id' => $id, 'store_id' => 0, 'language_id' => 1, 'language_code' => 'en-gb', 'email' => 'subscriber' . $id . '@example.test', 'option_data' => $options, 'option_label' => '', 'quantity' => $quantity, 'status' => 'waiting', 'attempts' => 0, 'date_modified' => 1, 'image' => '', 'name' => 'Fixture', 'price' => 1);
    }
    $db = new CommerceStockDb();
    for ($id = 1; $id <= 60; $id++) { $db->rows[$id] = stockRow($id, 0); }
    $db->rows[61] = stockRow(61, 3);
    $worker = new ControllerCronStockNotify(commerceRegistry($db));
    $result = $worker->index();
    commerceCheck($result['sent'] === 1 && $db->rows[61]['status'] === 'sent', 'Restocked product after 60 unavailable subscriptions must be sent');
    commerceCheck(count(Mail::$sent) === 1 && $db->rows[1]['status'] === 'waiting', 'Unavailable subscriptions must remain waiting');
    $db->rows = array();
    for ($id = 1; $id <= 130; $id++) { $db->rows[$id] = stockRow($id, 2, '[800]'); }
    $db->rows[131] = stockRow(131, 2, '[900]');
    $before = $db->optionChecks;
    $worker->index();
    commerceCheck($db->optionChecks - $before <= 100, 'An option-unavailable scan must be bounded');
    $result = $worker->index();
    commerceCheck($result['sent'] === 1 && $db->rows[131]['status'] === 'sent', 'Deferred unavailable options must yield to later ready options on the next batch');
    commerceCheck($db->rows[1]['attempts'] === 0, 'Unavailable options must not consume delivery attempts');
    $db->rows = array(132 => stockRow(132, 2));
    Mail::$fail = true;
    $result = $worker->index();
    commerceCheck($result['failed'] === 1 && $db->rows[132]['status'] === 'waiting' && $db->rows[132]['attempts'] === 1, 'Mail failure must retain a retryable subscription');
    Mail::$fail = false;
    $worker->index();
    $sent = count(Mail::$sent);
    $worker->index();
    commerceCheck($db->rows[132]['status'] === 'sent' && count(Mail::$sent) === $sent, 'Successful delivery must not repeat');
}

if ($case === 'background') {
    require $root . '/upload/system/engine/action.php';
    require $root . '/upload/system/engine/loader.php';
    require $root . '/upload/system/engine/event.php';
    require $root . '/upload/system/library/codecart/src/Queue.php';
    require $root . '/upload/system/library/codecart/src/Scheduler.php';
    file_put_contents($temp . '/controller/cron/commerce_fixture.php', <<<'PHP'
<?php
class ControllerCronCommerceFixture extends Controller {
    public function index($data) {}
    public function failed($data) { return false; }
    public function structured($data) { return array('success' => false, 'message' => 'Fixture task failed'); }
    public function broken($data) { throw new RuntimeException('Fixture exception'); }
    public function replacement(&$route, &$data) { $route = 'cron/commerce_fixture'; }
    public function output(&$route, &$data) { return array('success' => true); }
    public function after(&$route, &$data, &$output) { $output = array('success' => true); }
}
PHP
    );
    class CommerceJobDb {
        public string $route = '';
        public array $queries = array();
        public int $attempts = 1;
        public bool $claimed = true;
        public function escape($value) { return addslashes((string)$value); }
        public function countAffected() { return $this->claimed ? 1 : 0; }
        public function query($sql) {
            $this->queries[] = $sql;
            if (str_starts_with($sql, 'SELECT GET_LOCK')) { return commerceRows(array(array('acquired' => 1))); }
            if (str_starts_with($sql, 'SELECT * FROM')) {
                return commerceRows(array(array('scheduler_id' => 1, 'queue_id' => 1, 'code' => 'fixture.job', 'route' => $this->route, 'args' => '{}', 'attempts' => $this->attempts, 'max_attempts' => 3, 'interval_seconds' => 300)));
            }
            return commerceRows(array());
        }
    }
    $db = new CommerceJobDb();
    $registry = commerceRegistry($db);
    $events = new Event($registry);
    $registry->set('event', $events);
    $loader = new Loader($registry);
    $registry->set('load', $loader);
    $queue = new \CodeCart\Core\Queue($registry);
    $scheduler = new \CodeCart\Core\Scheduler($registry);
    foreach (array('cron/removed_extension', 'cron/commerce_fixture/missing', 'cron/commerce_fixture/failed', 'cron/commerce_fixture/structured', 'cron/commerce_fixture/broken') as $route) {
        $db->route = $route;
        $db->queries = array();
        $result = $queue->runOne(1);
        commerceCheck($result['status'] === 'error' && $result['failed'] === 1, 'Queue must fail observably for ' . $route);
        commerceCheck((bool)array_filter($db->queries, static fn($sql) => str_contains($sql, "status = 'pending', attempts = '1'") && str_contains($sql, "last_error = '") && str_contains($sql, 'available_at')), 'Queue must persist retry/backoff and error for ' . $route);
        $result = $scheduler->runOne(1);
        commerceCheck($result['success'] === false && $result['message'] !== 'OK', 'Scheduler must persist failure for ' . $route);
        commerceCheck((bool)array_filter($db->queries, static fn($sql) => str_contains($sql, "last_status = 'error'") && str_contains($sql, 'date_next =')), 'Scheduler must retain its next retry for ' . $route);
    }
    commerceCheck($loader->controller('cron/removed_extension') === null, 'Ordinary legacy Loader calls must preserve their missing-route contract');
    $db->route = 'cron/commerce_fixture';
    commerceCheck($queue->runOne(1)['status'] === 'ok' && $scheduler->runOne(1)['success'], 'A valid void controller must remain successful');
    foreach (array('replacement' => 'before', 'output' => 'before', 'after' => 'after') as $method => $when) {
        $db->route = 'cron/removed_extension';
        $trigger = 'controller/' . $db->route . '/' . $when;
        $events->register($trigger, new Action('cron/commerce_fixture/' . $method));
        commerceCheck($queue->runOne(1)['status'] === 'ok' && $scheduler->runOne(1)['success'], 'Event ' . $method . ' must remain a valid task implementation');
        $events->clear($trigger);
    }
    $db->route = 'cron/removed_extension';
    $db->attempts = 3;
    $db->queries = array();
    $queue->runOne(1);
    commerceCheck((bool)array_filter($db->queries, static fn($sql) => str_contains($sql, "status = 'failed', attempts = '3'")), 'Exhausted queue jobs must stop retrying');
    $db->claimed = false;
    commerceCheck($queue->runOne(1)['status'] === 'skipped', 'A competing worker must not execute an unclaimed job');
}

if ($case === 'image') {
    require $root . '/upload/system/helper/utf8.php';
    require $root . '/upload/system/library/image.php';
    require $root . '/upload/catalog/model/tool/image.php';
    commerceCheck(extension_loaded('gd'), 'GD is required for image contract verification');
    $source = imagecreatetruecolor(40, 30);
    imagealphablending($source, false);
    imagesavealpha($source, true);
    imagefill($source, 0, 0, imagecolorallocatealpha($source, 20, 100, 180, 127));
    imagefilledrectangle($source, 10, 10, 25, 20, imagecolorallocatealpha($source, 20, 100, 180, 0));
    $writers = array('png' => 'imagepng', 'jpg' => 'imagejpeg', 'gif' => 'imagegif', 'webp' => 'imagewebp');
    if (function_exists('imageavif') && function_exists('imagecreatefromavif')) { $writers['avif'] = 'imageavif'; }
    $registry = commerceRegistry(null);
    $request = (object)array('server' => array('HTTP_ACCEPT' => ''));
    $registry->set('request', $request);
    $imageModel = new ModelToolImage($registry);
    foreach ($writers as $extension => $writer) {
        $filename = 'catalog/fixture.' . $extension;
        $writer($source, DIR_IMAGE . $filename);
        $hash = hash_file('sha256', DIR_IMAGE . $filename);
        foreach (array(array(200, 150, 40, 30), array(20, 15, 20, 15)) as $dimensions) {
            list($width, $height, $expectedWidth, $expectedHeight) = $dimensions;
            $url = $imageModel->display($filename, $width, $height);
            commerceCheck(str_contains($url, '/image/cache/'), 'Normal ' . $extension . ' display must return a cached derivative');
            $target = DIR_IMAGE . substr($url, strpos($url, '/image/') + 7);
            $info = getimagesize($target);
            if ($extension === 'avif' && (!$info[0] || !$info[1])) {
                $decoded = imagecreatefromavif($target);
                $info[0] = imagesx($decoded); $info[1] = imagesy($decoded);
                imagedestroy($decoded);
            }
            commerceCheck($info[0] === $expectedWidth && $info[1] === $expectedHeight, 'Display ' . $extension . ' dimensions ' . $info[0] . 'x' . $info[1] . ' must match ' . $expectedWidth . 'x' . $expectedHeight);
            commerceCheck(hash_file('sha256', DIR_IMAGE . $filename) === $hash, 'Display must never modify its source');
            if ($extension === 'png' || $extension === 'webp') {
                $decoded = $extension === 'png' ? imagecreatefrompng($target) : imagecreatefromwebp($target);
                $color = imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0));
                commerceCheck($color['alpha'] >= 120, 'Transparent ' . $extension . ' derivatives must retain transparency');
                imagedestroy($decoded);
            }
        }
    }
    $registry->get('config')->set('config_image_webp', 1);
    $registry->get('config')->set('config_image_avif', 1);
    foreach (array('image/webp', 'image/avif,image/webp') as $accept) {
        $request->server['HTTP_ACCEPT'] = $accept;
        $url = $imageModel->display('catalog/fixture.png', 200, 150);
        $target = DIR_IMAGE . substr($url, strpos($url, '/image/') + 7);
        $info = getimagesize($target);
        if ($info[2] === IMAGETYPE_AVIF && (!$info[0] || !$info[1])) {
            $decoded = imagecreatefromavif($target);
            $info[0] = imagesx($decoded); $info[1] = imagesy($decoded);
            imagedestroy($decoded);
        }
        commerceCheck(str_contains($url, '/image/cache/') && $info[0] === 40 && $info[1] === 30, 'Automatic modern output must also stay cached without upscaling');
    }
    imagedestroy($source);
}

if (str_starts_with($case, 'category-')) {
    require $root . '/upload/system/helper/utf8.php';
    require $root . '/upload/system/library/codecart/src/CompatibilityLayer.php';
    require $root . '/upload/system/library/codecart/src/InternalLinking.php';
    require $root . '/upload/catalog/model/extension/feed/google_base.php';
    require $root . '/upload/system/library/seopro.php';
    if ($case === 'category-catalog') {
        require $root . '/upload/catalog/model/catalog/product.php';
        require $root . '/upload/system/library/codecart/src/PurchaseBlocks.php';
    } else {
        require $root . '/upload/admin/model/catalog/product.php';
        require $root . '/upload/system/helper/general.php';
    }
    class CommerceCache {
        public array $values = array();
        public function get($key) { return $this->values[$key] ?? null; }
        public function set($key, $value) { $this->values[$key] = $value; }
        public function delete($key) { foreach (array_keys($this->values) as $name) { if (str_starts_with($name, $key)) { unset($this->values[$name]); } } }
        public function deleteTag($tag, $fallback = '') {}
    }
    class CommerceCategoryDb {
        public bool $relationFlag;
        public bool $productField;
        public array $relations;
        public int $productMain;
        public int $categoryReads = 0;
        public function __construct(bool $relationFlag, bool $productField, array $relations, int $productMain) {
            $this->relationFlag = $relationFlag; $this->productField = $productField; $this->relations = $relations; $this->productMain = $productMain;
        }
        public function escape($value) { return addslashes((string)$value); }
        public function getLastId() { return 1; }
        public function query($sql) {
            if ($sql === 'SHOW TABLES') { return commerceRows(array(array('table' => 'qa_product_to_category'), array('table' => 'qa_product'))); }
            if (str_starts_with($sql, 'SHOW COLUMNS')) {
                $columns = str_contains($sql, '`qa_product_to_category`') ? array('product_id', 'category_id') : array('product_id');
                if (str_contains($sql, '`qa_product_to_category`') && $this->relationFlag) { $columns[] = 'main_category'; }
                if (str_contains($sql, '`qa_product`') && $this->productField) { $columns[] = 'main_category_id'; }
                return commerceRows(array_map(static fn($name) => array('Field' => $name), $columns));
            }
            if (str_contains($sql, "AND main_category='1'")) {
                $this->categoryReads++;
                $rows = array();
                foreach ($this->relations as $id => $flag) { if ($flag) { $rows[] = array('product_id' => 1, 'category_id' => $id); } }
                return commerceRows($rows);
            }
            if (str_starts_with($sql, 'SELECT product_id, main_category_id')) { return commerceRows(array(array('product_id' => 1, 'main_category_id' => $this->productMain))); }
            if (str_starts_with($sql, 'SELECT p.product_id, p.model')) { return commerceRows(array(array('product_id' => 1, 'category_id' => 10))); }
            if (str_starts_with($sql, 'SELECT cd.name')) { return commerceRows(array(array('name' => 'Category'))); }
            if (str_starts_with($sql, 'SELECT product_id, MIN(category_id)')) { return commerceRows($this->relations ? array(array('product_id' => 1, 'category_id' => min(array_keys($this->relations)))) : array()); }
            if (str_starts_with($sql, 'SELECT category_id FROM qa_product_to_category')) {
                if (str_contains($sql, 'main_category') && !$this->relationFlag) { throw new RuntimeException('Unknown column main_category'); }
                $relations = $this->relations;
                if (str_contains($sql, 'main_category')) { arsort($relations); }
                return commerceRows($relations ? array(array('category_id' => array_key_first($relations))) : array());
            }
            if (str_starts_with($sql, 'SELECT category_id, parent_id')) { return commerceRows(array(array('category_id' => 10, 'parent_id' => 0), array('category_id' => 20, 'parent_id' => 0))); }
            if (str_starts_with($sql, 'SELECT keyword FROM qa_seo_url')) {
                preg_match("/query = '([^']+)'/", $sql, $match);
                return commerceRows(array(array('keyword' => str_replace(array('_id=', '='), '', $match[1]))));
            }
            if (str_starts_with($sql, 'SELECT cp.path_id')) {
                preg_match("/cp.category_id='(\d+)'/", $sql, $match);
                $id = (int)$match[1];
                return commerceRows(array(array('path_id' => $id, 'mode' => 1, 'data' => json_encode(array(array('type' => 'form', 'form_id' => $id, 'title' => 'Category'))))));
            }
            if (str_starts_with($sql, 'DELETE FROM qa_product_to_category')) {
                if (preg_match("/AND category_id = '(\d+)'/", $sql, $match)) { unset($this->relations[(int)$match[1]]); }
                else { $this->relations = array(); }
            } elseif (str_starts_with($sql, 'INSERT INTO qa_product_to_category')) {
                if (str_contains($sql, 'main_category') && !$this->relationFlag) { throw new RuntimeException('Unknown column main_category in product category write'); }
                preg_match("/category_id = '(\d+)'/", $sql, $match);
                $this->relations[(int)$match[1]] = str_contains($sql, 'main_category = 1') ? 1 : 0;
                ksort($this->relations);
            } elseif (preg_match("/^UPDATE qa_product SET main_category_id = '(\d+)'/", $sql, $match)) {
                if (!$this->productField) { throw new RuntimeException('Unknown column main_category_id in product write'); }
                $this->productMain = (int)$match[1];
            }
            return commerceRows(array());
        }
    }
    foreach (array(
        array(true, true, array(10 => 0, 20 => 0), 20, 20),
        array(true, true, array(10 => 1, 20 => 0), 20, 10),
        array(false, true, array(10 => 0, 20 => 0), 20, 20),
        array(true, false, array(10 => 0, 20 => 0), 0, 10),
        array(false, false, array(10 => 0, 20 => 0), 0, 10),
        array(true, true, array(), 0, 0)
    ) as $scenario) {
        list($relationFlag, $productField, $relations, $productMain, $expected) = $scenario;
        $db = new CommerceCategoryDb($relationFlag, $productField, $relations, $productMain);
        $registry = commerceRegistry($db);
        $registry->set('codecart_compat', new \CodeCart\Core\CompatibilityLayer($registry));
        $cache = new CommerceCache();
        $registry->set('cache', $cache);
        $registry->set('url', new class { public function link($route, $query) { return $route . '?' . $query; } });
        $config = $registry->get('config');
        foreach (array('config_language_id' => 1, 'config_seo_url' => 1, 'config_seo_pro' => 1, 'config_seo_url_include_path' => 1, 'config_seo_url_cache' => 1, 'codecart_language_prefix_enabled' => 1) as $key => $value) { $config->set($key, $value); }
        if ($case === 'category-internal') {
            $config->set('codecart_internal_linking_status', 1);
            $links = (new \CodeCart\Core\InternalLinking($registry))->product(1);
            commerceCheck($expected ? (($links[0]['href'] ?? '') === 'product/category?path=' . $expected) : !$links, 'Internal links must use canonical main category');
        } elseif ($case === 'category-feed') {
            $rows = (new ModelExtensionFeedGoogleBase($registry))->getMerchantProducts();
            commerceCheck($rows[0]['category_id'] === $expected, 'Merchant feed must use canonical main category');
        } elseif ($case === 'category-admin') {
            $admin = new ModelCatalogProduct($registry);
            commerceCheck($admin->getProductMainCategoryId(1) === $expected, 'Admin main category must follow explicit relation, product field, then lowest relation');
            if ($expected > 0) {
                $data = array_fill_keys(array('model','sku','upc','ean','jan','isbn','mpn','location','quantity','minimum','subtract','stock_status_id','manufacturer_id','shipping','price','points','weight','weight_class_id','length','width','height','length_class_id','status','noindex','tax_class_id','sort_order'), '0');
                $data += array('date_available' => '2026-01-01', 'tax_display_mode' => 'inherit', 'product_description' => array(), 'product_category' => array_keys($relations), 'main_category_id' => $expected);
                $cache->set('seopro.product_categories', array(1 => 'stale'));
                $admin->editProduct(1, $data);
                commerceCheck($admin->getProductMainCategoryId(1) === $expected && $db->relations[$expected] === ($relationFlag ? 1 : 0), 'Normal product save and reopen must retain canonical category on either supported schema');
                commerceCheck($cache->get('seopro.product_categories') === null, 'Normal save must invalidate the SEO category cache');
                $data['main_category_id'] = 20;
                $admin->addProduct($data);
                commerceCheck($admin->getProductMainCategoryId(1) === ($relationFlag || $productField ? 20 : 10), 'Product add persists the selected main category when its schema supports selection');
                $data['main_category_id'] = $expected;
                $admin->editProduct(1, $data);
            }
        } elseif ($case === 'category-seo') {
            $registry->set('request', (object)array('server' => array(), 'get' => array()));
            $cache->set('seopro.product_categories', array(1 => $expected === 10 ? '20' : '10'));
            $seo = new SeoPro($registry);
            $link = $seo->baseRewrite(array('route' => 'product/product', 'product_id' => 1), 1);
            commerceCheck($link[0] === ($expected ? '/category' . $expected : '') . '/product1', 'First request must use canonical category despite a warm legacy cache');
            unset($seo);
            commerceCheck(($cache->get('seopro.product_categories.canonical')[1] ?? null) === ($expected ? (string)$expected : false), 'SEO must persist the resolved category path');
            $before = $db->categoryReads;
            $seo = new SeoPro($registry);
            $again = $seo->baseRewrite(array('route' => 'product/product', 'product_id' => 1), 1);
            commerceCheck($again === $link && $db->categoryReads === $before, 'SEO category cache must be reusable on the next request');
            unset($seo);
        } else {
            $blocks = (new ModelCatalogProduct($registry))->getResolvedPurchaseBlocks(1, 1);
            commerceCheck($expected ? (($blocks[0]['form_id'] ?? 0) === $expected) : $blocks === array(), 'Inherited purchase blocks must use the canonical main category');
        }
        $registry->set('codecart_compat', null);
        if ($case === 'category-internal') {
            $links = (new \CodeCart\Core\InternalLinking($registry))->product(1);
            commerceCheck($expected ? (($links[0]['href'] ?? '') === 'product/category?path=' . $expected) : !$links, 'Internal link fallback must use canonical main category');
        } elseif ($case === 'category-feed') {
            $rows = (new ModelExtensionFeedGoogleBase($registry))->getMerchantProducts();
            commerceCheck($rows[0]['category_id'] === $expected, 'Merchant feed fallback must use canonical main category');
        } elseif ($case === 'category-admin') {
            commerceCheck((new ModelCatalogProduct($registry))->getProductMainCategoryId(1) === $expected, 'Admin reader without a pre-registered service must use canonical precedence');
        } elseif ($case === 'category-catalog') {
            $blocks = (new ModelCatalogProduct($registry))->getResolvedPurchaseBlocks(1, 1);
            commerceCheck($expected ? (($blocks[0]['form_id'] ?? 0) === $expected) : $blocks === array(), 'Purchase reader without a pre-registered service must use canonical precedence');
        } else {
            $cache->values = array();
            $seo = new SeoPro($registry);
            commerceCheck($seo->baseRewrite(array('route' => 'product/product', 'product_id' => 1), 1)[0] === ($expected ? '/category' . $expected : '') . '/product1', 'SEO reader without a pre-registered service must use canonical precedence');
            unset($seo);
        }
    }
}

if ($case === 'bonus') {
    define('DIR_STORAGE', $temp . '/storage/');
    define('DIR_UPLOAD', $temp . '/uploads/');
    require $root . '/upload/admin/controller/marketplace/install.php';
    require $root . '/upload/system/library/codecart/src/ExtensionInstallJournal.php';
    $source = $root . '/bonus-source/unishop2_compatibility';
    $xml = simplexml_load_file($source . '/install.xml');
    $zip = new ZipArchive();
    $filename = $root . '/bonuses/UniShop2_Compatibility_Pro_v' . (string)$xml->version . '.ocmod.zip';
    commerceCheck($zip->open($filename, ZipArchive::CHECKCONS) === true, 'Bundled UniShop archive must open with consistency checks');
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $contents = $zip->getFromIndex($i);
        commerceCheck(is_string($contents) && sprintf('%u', crc32($contents)) === sprintf('%u', $stat['crc']), 'Every nested ZIP entry must pass CRC verification');
    }
    commerceCheck($zip->getFromName('install.xml') === file_get_contents($source . '/install.xml'), 'Bundled OCMOD must match its maintained source');
    $registry = commerceRegistry(null);
    $registry->get('config')->set('error_log', 1);
    $log = new class { public array $messages = array(); public function write($message) { $this->messages[] = $message; } };
    $registry->set('log', $log);
    $registry->set('load', new class { public function language($route) {} });
    $registry->set('language', new class { public function get($key) { return $key === 'error_allowed' ? 'Not allowed: %s' : $key; } });
    $registry->set('user', new class { public function hasPermission($action, $route) { return true; } });
    $registry->set('session', (object)array('data' => array('install' => 'fixture', 'user_token' => 'fixture')));
    $registry->set('request', (object)array('get' => array('extension_install_id' => 1)));
    $registry->set('url', new class { public function link($route, $query, $secure) { return 'https://store.example.test/admin/index.php?route=' . $route . '&' . $query; } });
    $response = new class {
        public string $output = '';
        public function addHeader($header) {}
        public function setOutput($output) { $this->output = $output; }
    };
    $registry->set('response', $response);
    $installer = new ControllerMarketplaceInstall($registry);
    $extract = new ReflectionMethod($installer, 'safeExtractZip');
    $extract->setAccessible(true);
    $extract->invoke($installer, $zip, DIR_UPLOAD . 'tmp-fixture');
    $installer->preflight();
    $result = json_decode($response->output, true);
    commerceCheck(!isset($result['error']) && str_contains($result['next'] ?? '', 'marketplace/install/move'), 'Own installer public preflight must accept the bundled UniShop archive: ' . implode('; ', $log->messages));
    commerceCheck($zip->locateName('upload/') !== false && $zip->locateName('upload/index.html') === false, 'OCMOD archive must explicitly retain upload/ without a disallowed placeholder');
    commerceCheck(!is_file($source . '/upload/index.html'), 'Source tree must not recreate the invalid placeholder on the next build');
    $zip->close();
    $installer->preflight();
    $result = json_decode($response->output, true);
    commerceCheck(!isset($result['error']), 'Repeated installer preflight must remain safe');
}

commerceCheck($checks > 0, 'Unknown or empty test case');
echo 'PASS: commerce defensive ' . $case . ' (' . $checks . ' checks, PHP ' . PHP_VERSION . ')' . PHP_EOL;
