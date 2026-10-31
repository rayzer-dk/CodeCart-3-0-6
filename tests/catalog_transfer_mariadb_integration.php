<?php
/** Disposable MariaDB round-trip of catalog ZIP. Never point at a real store. */
define('DB_PREFIX', 'oc_');
define('DB_DATABASE', 'codecart_catalog_qa');
$base = sys_get_temp_dir() . '/ccp_catrun_' . bin2hex(random_bytes(6));
foreach (array('', '/upload', '/image', '/image/catalog', '/download', '/storage', '/storage/backup') as $dir) {
    if (!is_dir($base . $dir) && !mkdir($base . $dir, 0700, true)) {
        throw new RuntimeException('Cannot create QA temp folder');
    }
}
define('DIR_UPLOAD', $base . '/upload/');
define('DIR_IMAGE', $base . '/image/');
define('DIR_DOWNLOAD', $base . '/download/');
define('DIR_STORAGE', $base . '/storage/');
class IntegrationResult {
    public $row = array();
    public $rows = array();
    public $num_rows = 0;
    public function __construct($rows = array()) {
        $this->rows = $rows;
        $this->row = $rows ? $rows[0] : array();
        $this->num_rows = count($rows);
    }
}
class IntegrationDb {
    private $connection;
    public function __construct($connection) { $this->connection = $connection; }
    public function query($sql) {
        $result = $this->connection->query($sql);
        return new IntegrationResult($result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : array());
    }
    public function escape($value) { return $this->connection->real_escape_string((string)$value); }
}
class IntegrationConfig {
    public function get($key) {
        return array('config_language_id'=>1,'config_store_id'=>0)[$key] ?? null;
    }
}
class IntegrationRegistry {
    private $db;
    public function __construct($db) { $this->db=$db; }
    public function get($key) {
        if($key==='db')return $this->db;
        if($key==='config')return new IntegrationConfig();
        return null;
    }
}
function assertion($ok, $message) { if(!$ok)throw new RuntimeException($message); }
require_once __DIR__ . '/../upload/system/library/codecart/src/SimpleXlsx.php';
require_once __DIR__ . '/../upload/system/library/codecart/src/CatalogTransfer.php';
function utf8_substr($v, $start, $length = null) {
    return $length === null ? substr($v, $start) : substr($v, $start, $length);
}
class Model {
    public $db;
    public function __construct($db = null) { $this->db = $db; }
}
require_once __DIR__ . '/../upload/admin/model/tool/backup.php';
class Controller {
    public $user, $load, $request, $session, $language, $db;
    public $model_tool_backup, $response, $cache, $log, $url;
}
require_once __DIR__ . '/../upload/admin/controller/tool/backup.php';
$connection = new mysqli(getenv('QA_DB_HOST') ?: '127.0.0.1', getenv('QA_DB_USER') ?: 'root', getenv('QA_DB_PASS') ?: 'root', DB_DATABASE);
$connection->set_charset('utf8mb4');
$db = new IntegrationDb($connection);
$schema = array(
"CREATE TABLE `oc_language` (language_id int PRIMARY KEY,code varchar(12) NOT NULL,sort_order int NOT NULL DEFAULT 0)",
"CREATE TABLE `oc_category` (category_id int PRIMARY KEY,image varchar(255) NOT NULL DEFAULT '')",
"CREATE TABLE `oc_category_description` (category_id int,language_id int,name varchar(255),PRIMARY KEY(category_id,language_id))",
"CREATE TABLE `oc_category_path` (category_id int,path_id int,level int,PRIMARY KEY(category_id,path_id))",
"CREATE TABLE `oc_product` (product_id int PRIMARY KEY,sku varchar(64),model varchar(64),manufacturer_id int DEFAULT 0,image varchar(255),main_category_id int DEFAULT 0)",
"CREATE TABLE `oc_product_image` (product_image_id int PRIMARY KEY,product_id int,image varchar(255),sort_order int DEFAULT 0)",
"CREATE TABLE `oc_product_to_category` (product_id int,category_id int,main_category int NOT NULL DEFAULT 0,PRIMARY KEY(product_id,category_id))",
"CREATE TABLE `oc_download` (download_id int PRIMARY KEY,filename varchar(255) NOT NULL,mask varchar(255) NOT NULL)",
"CREATE TABLE `oc_product_to_download` (product_id int,download_id int,PRIMARY KEY(product_id,download_id))",
"CREATE TABLE `oc_seo_url` (seo_url_id int AUTO_INCREMENT PRIMARY KEY,store_id int,language_id int,`query` varchar(255),keyword varchar(255))"
);
try {
    foreach($schema as $stmt)$db->query($stmt);
    $db->query("INSERT INTO oc_language VALUES (1,'uk-ua',0)");
    $db->query("INSERT INTO oc_category VALUES (7,''),(9,'')");
    $db->query("INSERT INTO oc_category_description VALUES (7,1,'Основна'),(9,1,'Додаткова')");
    $db->query("INSERT INTO oc_category_path VALUES (7,7,0),(9,9,0)");
    $db->query("INSERT INTO oc_product VALUES (15,'SKU-15','MODEL-15',0,'catalog/товар.png',7)");
    // A single product with a missing image must not prevent the good product
    // and its category/SEO/digital-file dependencies from being exported.
    $db->query("INSERT INTO oc_product VALUES (16,'BAD-16','MODEL-16',0,'catalog/missing.png',7)");
    $db->query("INSERT INTO oc_product_image VALUES (51,15,'catalog/товар.png',0)");
    $db->query("INSERT INTO oc_product_to_category VALUES (15,7,0),(15,9,1),(16,7,1)");
    $db->query("INSERT INTO oc_download VALUES (31,'manual.pdf.abc123','manual.pdf')");
    $db->query("INSERT INTO oc_product_to_download VALUES (15,31)");
    $db->query("INSERT INTO oc_seo_url (store_id,language_id,`query`,keyword) VALUES (0,1,'product_id=15','sku-15')");
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+nN48AAAAASUVORK5CYII=');
    file_put_contents(DIR_IMAGE . 'catalog/товар.png', $png);
    file_put_contents(DIR_DOWNLOAD . 'manual.pdf.abc123', 'test digital content');
    $service = new \CodeCart\Core\CatalogTransfer(new IntegrationRegistry($db));
    $package = $base . '/upload/portable.zip';
    $report = $service->export(array('products','categories'), $package, true);
    assertion(is_file($package), 'No portable ZIP export');
    assertion($report['products']['total'] === 2, 'Detected product total');
    assertion($report['products']['success'] === 1, 'Good product should export');
    assertion($report['products']['skipped'] === 1, 'Missing-image product should be skipped');
    assertion((int)$report['issues'][0]['product_id'] === 16, 'Skipped report must contain bad product ID');
    assertion($report['downloads'] === 1, 'Digital file not packaged');
    assertion($report['images'] >= 1, 'Product image not packaged');
    foreach (array('oc_product_to_download','oc_product_to_category','oc_product_image','oc_category_path','oc_category_description','oc_seo_url','oc_download','oc_product','oc_category') as $table) {
        $db->query('TRUNCATE TABLE `' . $table . '`');
    }
    unlink(DIR_IMAGE . 'catalog/товар.png');
    unlink(DIR_DOWNLOAD . 'manual.pdf.abc123');
    $zip = new ZipArchive();
    assertion($zip->open($package) === true, 'Unable to open exported ZIP');
    $xlsx = $zip->getFromName('catalog.xlsx');
    $zip->close();
    assertion(is_string($xlsx) && strlen($xlsx) > 0, 'Missing catalog.xlsx');
    $token = bin2hex(random_bytes(16));
    $prefix = DIR_UPLOAD . 'codecart_catalog_' . $token;
    file_put_contents($prefix . '.xlsx', $xlsx);
    file_put_contents($prefix . '.json', json_encode(array('xlsx'=>$prefix.'.xlsx','package'=>$package,'created'=>time())));
    $result = $service->importToken($token, false);
    assertion($result['main_categories'] === 1, 'Main category count');
    $product = $db->query('SELECT * FROM oc_product WHERE product_id=15')->row;
    assertion((int)$product['main_category_id'] === 9, 'Main category precedence');
    $categories = $db->query('SELECT category_id,main_category FROM oc_product_to_category WHERE product_id=15 ORDER BY category_id')->rows;
    assertion(count($categories)===2 && (int)$categories[0]['category_id']===7 && (int)$categories[1]['category_id']===9, 'Additional category lost');
    assertion((int)$categories[1]['main_category']===1, 'Main relation flag lost');
    assertion(is_file(DIR_IMAGE . 'catalog/товар.png'), 'Product image was not imported');
    assertion(file_get_contents(DIR_DOWNLOAD . 'manual.pdf.abc123')==='test digital content', 'Digital file was not imported');
    assertion(count($db->query('SELECT * FROM oc_seo_url WHERE `query`="product_id=15"')->rows) === 1, 'SEO URL missing');
    // A schema-aware SQL backup must restore a physically dropped table,
    // not only rows in an already installed schema.
    $sqlPath = DIR_UPLOAD . 'roundtrip.sql';
    $backup = new ModelToolBackup($db);
    $backup->backupToFile(array('oc_download','oc_product_to_download','oc_product','oc_category','oc_product_to_category'), $sqlPath);
    $sqlText = (string)file_get_contents($sqlPath);
    assertion(strpos($sqlText, 'CREATE TABLE IF NOT EXISTS') !== false, 'Missing CREATE TABLE in SQL backup');
    $db->query('DROP TABLE oc_product_to_download');
    $db->query('TRUNCATE TABLE oc_product');
    // Exercise the actual OpenCart controller's resumable POST restore path.
    // This is an authenticated in-session continuation, not a simulated SQL
    // interpreter; it verifies parser, allowlist and CREATE TABLE behavior.
    $controller = new ControllerToolBackup();
    $controller->db = $db;
    $controller->user = new class {
        public function hasPermission($action, $route) { return $action === 'modify' && $route === 'tool/backup'; }
    };
    $controller->request = (object) array(
        'server' => array('REQUEST_METHOD'=>'POST'),
        'files' => array(),
        'get' => array('import'=>basename($sqlPath),'position'=>0)
    );
    $controller->session = (object) array('data'=>array(
        'user_token'=>'test_admin_token',
        'codecart_sql_restore'=>array('file'=>basename($sqlPath),'created'=>time(),'position'=>0,'snapshot'=>'test-snapshot.sql')
    ));
    $controller->language = new class {
        public function get($key) { return $key; }
    };
    $controller->load = new class($controller, $db) {
        private $controller, $db;
        public function __construct($controller, $db) { $this->controller=$controller; $this->db=$db; }
        public function language($route) {}
        public function model($route) {
            if ($route === 'tool/backup') { $this->controller->model_tool_backup = new ModelToolBackup($this->db); }
        }
    };
    $controller->response = new class {
        public $output = '';
        public function addHeader($header) {}
        public function setOutput($output) { $this->output = $output; }
    };
    $controller->cache = new class { public function delete($key) {} };
    $controller->log = new class { public function write($message) { throw new RuntimeException($message); } };
    $controller->url = new class { public function link($route, $args, $ssl=true) { return 'index.php?route='.$route.'&'.$args; } };
    $controller->import();
    $restore = json_decode($controller->response->output, true);
    assertion(is_array($restore) && !isset($restore['error']) && isset($restore['success']), 'Native SQL restore controller did not complete: '.json_encode($restore));
    assertion((int)$db->query('SELECT COUNT(*) AS n FROM oc_product_to_download')->row['n'] === 1, 'SQL restore lost digital relation');
    assertion((int)$db->query('SELECT COUNT(*) AS n FROM oc_product')->row['n'] === 1, 'SQL restore lost product');
    // When target ID belongs to a different SKU, do not overwrite it:
    // skip that product and all source relations while allowing import.
    $db->query("UPDATE oc_product SET sku='UNRELATED-15' WHERE product_id=15");
    $retryToken = bin2hex(random_bytes(16));
    $retryBase = DIR_UPLOAD . 'codecart_catalog_' . $retryToken;
    file_put_contents($retryBase . '.xlsx', $xlsx);
    file_put_contents($retryBase . '.json', json_encode(array('xlsx'=>$retryBase.'.xlsx','package'=>$package,'created'=>time())));
    $resilient = new \CodeCart\Core\CatalogTransfer(new IntegrationRegistry($db));
    $resilient->onProgress(function($state) {});
    $check = $resilient->preflight($retryBase . '.xlsx', $package);
    assertion($check['ok'] === true && $check['skipped_products'] === 1, 'Product collision should be isolated in resilient preview');
    $second = $resilient->importToken($retryToken, false);
    assertion($second['successful_products'] === 0 && $second['skipped_products'] === 1, 'Import must report one skipped product');
    assertion($db->query('SELECT sku FROM oc_product WHERE product_id=15')->row['sku'] === 'UNRELATED-15', 'Conflicting existing product was overwritten');
    echo "PASS MariaDB catalog and SQL schema+data round-trips\n";
} finally {
    foreach (array_reverse($schema) as $sql) { /* Tables remain only in disposable QA database. */ }
    $walk = function($path) use (&$walk) {
        if (!is_dir($path)) { if(is_file($path))@unlink($path); return; }
        foreach(scandir($path) as $child) { if($child!=='.' && $child!=='..')$walk($path.'/'.$child); }
        @rmdir($path);
    };
    $walk($base);
    $connection->close();
}
