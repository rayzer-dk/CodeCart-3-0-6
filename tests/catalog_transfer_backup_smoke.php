<?php
/** Standalone catalog and SQL backup regression contract; no production DB. */
define('DB_PREFIX', 'oc_');
define('DB_DATABASE', 'test_db');
$downloadDir = sys_get_temp_dir() . '/ccptest_' . bin2hex(random_bytes(4));
mkdir($downloadDir, 0700);
define('DIR_DOWNLOAD', $downloadDir . '/');
class Model {
    public $db;
    public function __construct($db = null) { $this->db = $db; }
}
class TestConfig { public function get($key) { return null; } }
class TestDb {
    public $queries = array();
    public $collide = false;
    public function escape($value) { return addslashes((string)$value); }
    public function query($sql) {
        $this->queries[] = $sql;
        if (strpos($sql, 'SHOW TABLES') === 0) return (object)array('rows'=>array(
            array('Tables_in_test_db'=>'oc_product'),array('Tables_in_test_db'=>'oc_product_to_category')
        ),'row'=>array(),'num_rows'=>2);
        if (strpos($sql, 'SHOW CREATE TABLE') === 0) return (object)array('row'=>array(
            'Create Table'=>"CREATE TABLE `oc_product` (\n`product_id` int NOT NULL PRIMARY KEY,\n`sku` varchar(64)\n) ENGINE=InnoDB"
        ),'rows'=>array(),'num_rows'=>1);
        if (strpos($sql, 'SELECT * FROM `oc_product` LIMIT 0,') === 0) return (object)array('rows'=>array(
            array('product_id'=>1,'sku'=>'TEST')
        ),'row'=>array(),'num_rows'=>1);
        if (strpos($sql, 'SELECT product_id, sku, model') === 0) return (object)array(
            'rows'=>$this->collide?array(array('product_id'=>15,'sku'=>'EXISTING','model'=>'X')):array(),
            'row'=>array(),'num_rows'=>$this->collide?1:0);
        return (object)array('rows'=>array(),'row'=>array(),'num_rows'=>0);
    }
}
class TestRegistry {
    private $db;
    public function __construct($db) { $this->db=$db; }
    public function get($key) {
        if ($key==='db')return $this->db;
        if ($key==='config')return new TestConfig();
        return null;
    }
}
function utf8_substr($v,$start,$length=null) { return $length===null?substr($v,$start):substr($v,$start,$length); }
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
function privateCall($object,$name,$arguments=array()){
    $m=new ReflectionMethod($object,$name);$m->setAccessible(true);return $m->invokeArgs($object,$arguments);
}
require_once __DIR__.'/../upload/system/library/codecart/src/CatalogTransfer.php';
require_once __DIR__.'/../upload/admin/model/tool/backup.php';
try {
    $db=new TestDb();
    $transfer=new \CodeCart\Core\CatalogTransfer(new TestRegistry($db));
    check(privateCall($transfer,'safeImagePath',array('catalog/українська картинка.jpg'))===true,'Unicode image path');
    check(privateCall($transfer,'safeImagePath',array('catalog/../config.php'))===false,'Image traversal');
    check(privateCall($transfer,'safeImagePath',array('other/image.jpg'))===false,'Catalog image boundary');
    check(privateCall($transfer,'safeDownloadFilename',array('manual.pdf.123abc'))===true,'Download filename');
    check(privateCall($transfer,'safeDownloadFilename',array('../config.php'))===false,'Download traversal');
    $plans=privateCall($transfer,'orderedPlans',array(array('product_to_category'=>'x','product'=>'x','category'=>'x','option'=>'x','product_option'=>'x')));
    $keys=array_keys($plans);
    check(array_search('category',$keys,true)<array_search('product',$keys,true),'Category dependencies');
    check(array_search('option',$keys,true)<array_search('product_option',$keys,true),'Option dependencies');
    check(array_search('product',$keys,true)<array_search('product_to_category',$keys,true),'Product dependencies');
    $map=privateCall($transfer,'extractMainCategoryMap',array(array(
        'product'=>array('columns'=>array('product_id','main_category_id'),'rows'=>array(array('product_id'=>15,'main_category_id'=>7))),
        'product_to_category'=>array('columns'=>array('product_id','category_id','main_category'),'rows'=>array(array('product_id'=>15,'category_id'=>9,'main_category'=>1)))
    )));
    check($map[15]===9,'Main category relation precedence');
    $db->collide=true;
    $preview=array('errors'=>array(),'warnings'=>array());
    $args=array(array(array('product_id'=>15,'sku'=>'DIFFERENT','model'=>'Y')),&$preview);
    privateCall($transfer,'checkProductIdentityBatch',$args);
    check(count($preview['errors'])===1,'ID collision blocked');
    if(class_exists('ZipArchive')) {
        $package=$downloadDir.'/package.zip';
        $zip=new ZipArchive();
        check($zip->open($package,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true,'Test ZIP creation');
        $zip->addFromString('download/manual.pdf.123abc','bytes');
        $zip->close();
        $preview=array('errors'=>array());
        $args=array(array('manual.pdf.123abc'=>true),$package,&$preview);
        privateCall($transfer,'checkDownloadAvailability',$args);
        check(!$preview['errors'],'Digital asset preflight');
        check(privateCall($transfer,'importPackageDownloads',array($package))===1,'Digital asset count');
        check(file_get_contents(DIR_DOWNLOAD.'manual.pdf.123abc')==='bytes','Digital asset bytes');
    }
    $backup=new ModelToolBackup($db);
    $out=tempnam(sys_get_temp_dir(),'ccpbkp_');
    $backup->backupToFile(array('oc_product'),$out,50);
    $sql=file_get_contents($out);
    check(strpos($sql,'CREATE TABLE IF NOT EXISTS `oc_product`')!==false,'Schema in backup');
    check(strpos($sql,'TRUNCATE TABLE `oc_product`')!==false,'Truncate in backup');
    check(strpos($sql,'INSERT INTO `oc_product`')!==false,'Rows in backup');
    check(in_array('START TRANSACTION WITH CONSISTENT SNAPSHOT',$db->queries,true),'Consistent SQL snapshot');
    check(in_array('ROLLBACK',$db->queries,true),'Snapshot release');
    unlink($out);
    echo "PASS catalog transfer and database backup smoke\n";
} finally {
    foreach(glob($downloadDir.'/*')?:array() as $file)@unlink($file);
    @rmdir($downloadDir);
}
