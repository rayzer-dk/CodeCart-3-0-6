<?php
/** Standalone catalog URL regression tests: php tools/seo-language-url-check.php */
error_reporting(E_ALL);
require_once __DIR__.'/../upload/system/library/codecart/psr4.php';
CodeCartPsr4::register('CodeCart\\Core\\', __DIR__.'/../upload/system/library/codecart/src/');
if (!defined('DB_PREFIX')) define('DB_PREFIX', 'oc_');
if (!function_exists('utf8_strlen')) { function utf8_strlen($v) { return strlen((string)$v); } }
if (!function_exists('utf8_strtolower')) { function utf8_strtolower($v) { return strtolower((string)$v); } }
if (!function_exists('codecart_is_https')) { function codecart_is_https() { return true; } }
class Registry { private $data = array(); public function get($k){return isset($this->data[$k]) ? $this->data[$k] : null;} public function set($k,$v){$this->data[$k]=$v;} public function has($k){return isset($this->data[$k]);} }
class Config {private $data; public function __construct($d){$this->data=$d;}public function get($k){return isset($this->data[$k])?$this->data[$k]:null;}public function set($k,$v){$this->data[$k]=$v;} }
class Controller {protected $registry;public function __construct($r){$this->registry=$r;}public function __get($k){return $this->registry->get($k);} }
class Language {public function __construct($x){}public function load($x){} }
class Request {public $get=array();public $post=array();public $server=array();public $cookie=array();}
class Response {public $redirects=array(); public $headers=array();public function redirect($url,$code=302){$this->redirects[]=array($url,$code);} public function addHeader($s){$this->headers[]=$s;} }
class Cache {public function get($k){return array();} public function set($k,$v){} }
class DataResult {public $num_rows;public $row;public $rows;public function __construct($rows){$this->rows=$rows;$this->num_rows=count($rows);$this->row=$rows?reset($rows):array();} }
class DB {public function escape($s){return addslashes($s);}public function query($sql){
 if ($sql === 'SHOW TABLES') return new DataResult(array(array('Tables_in_fixture'=>'oc_product_to_category'),array('Tables_in_fixture'=>'oc_product')));
 if ($sql === 'SHOW COLUMNS FROM `oc_product_to_category`') return new DataResult(array(array('Field'=>'product_id'),array('Field'=>'category_id'),array('Field'=>'main_category')));
 if ($sql === 'SHOW COLUMNS FROM `oc_product`') return new DataResult(array(array('Field'=>'product_id'),array('Field'=>'main_category_id')));
 if (strpos($sql,' FROM `oc_product_to_category` ')!==false || strpos($sql,' FROM `oc_product` ')!==false) return new DataResult(array());
 if (strpos($sql,' FROM oc_category ')!==false || strpos($sql,' FROM oc_category ORDER')!==false) return new DataResult(array());
 if (strpos($sql,' FROM oc_product_to_category ')!==false) return new DataResult(array());
 if (strpos($sql,' FROM oc_language ')!==false) {
  if (strpos($sql,"code = 'uk-ua'")!==false) return new DataResult(array(array('language_id'=>1)));
  if (strpos($sql,"code = 'ru-ru'")!==false) return new DataResult(array(array('language_id'=>2)));
  return new DataResult(array(array('code'=>'uk-ua'),array('code'=>'ru-ru')));
 }
 if (strpos($sql,' FROM oc_seo_url ')!==false) {
  $id=preg_match("/language_id = '(\\d+)'/",$sql,$m)?(int)$m[1]:0;
  $rows=array(
   array('query'=>'product_id=42','keyword'=>'uk-product','language_id'=>1,'store_id'=>0,'seo_url_id'=>1),
   array('query'=>'product_id=42','keyword'=>'ru-product','language_id'=>2,'store_id'=>0,'seo_url_id'=>2),
   array('query'=>'product_id=43','keyword'=>'only-uk-product','language_id'=>1,'store_id'=>0,'seo_url_id'=>3)
  );
  if (strpos($sql,'WHERE keyword IN (')!==false) return new DataResult(array());
  if (preg_match("/keyword = '([^']+)'/",$sql,$m)) $rows=array_values(array_filter($rows,function($r)use($m){return $r['keyword']===$m[1];}));
  if (preg_match("/query = '([^']+)'/",$sql,$m)) $rows=array_values(array_filter($rows,function($r)use($m){return $r['query']===$m[1];}));
  if ($id) $rows=array_values(array_filter($rows,function($r)use($id){return $r['language_id']===$id;}));
  return new DataResult($rows);
 }
 throw new Exception('Unexpected SQL: '.$sql);
}}
require_once __DIR__.'/../upload/system/library/url.php';
require_once __DIR__.'/../upload/system/library/seopro.php';
require_once __DIR__.'/../upload/catalog/controller/startup/seo_url.php';
function setup($path, $query, $lang, $prefix, $routeMatched=false, $enabled=true, $legacy=true, $seo=true) {
 $r=new Registry();$cfg=new Config(array(
 'config_url'=>'https://shop.test/','config_ssl'=>'https://shop.test/',
 'config_language'=>'uk-ua','config_language_id'=>$lang==='ru-ru'?2:1,
 'config_seo_url'=>$seo?1:0,'config_seo_pro'=>$seo?1:0,'config_seo_url_cache'=>0,
 'config_store_id'=>0, 'config_seo_url_include_path'=>0,'config_seopro_addslash'=>1,'config_valide_params'=>'tracking',
 'codecart_language_prefix_enabled'=>$enabled?1:0,
 'codecart_language_prefix_route_matched'=>$routeMatched?1:0,
 'codecart_language_legacy_query'=>$legacy?1:0,
 'codecart_language_prefix_current'=>$prefix,
 'codecart_language_prefix_missing_required'=>0,
 'codecart_lost_url_status'=>0,
 ));
 $req=new Request();$req->server=array('REQUEST_METHOD'=>'GET','HTTPS'=>'on','REQUEST_URI'=>$path.($query!==''?'?'.$query:''));
 if ($path!=='/') {$route=trim($path,'/');if ($prefix!==''&&strpos($route,$prefix.'/')===0) $route=substr($route,strlen($prefix)+1);if ($route!==''&&$route!==$prefix) $req->get['_route_']=$route;}
 parse_str($query,$q);$req->get=array_merge($req->get,$q);
 $resp=new Response();$r->set('config',$cfg);$r->set('request',$req);$r->set('response',$resp);
 $r->set('url',new Url('https://shop.test/','https://shop.test/'));
 $r->set('session',(object)array('data'=>array('language'=>$lang)));
 $r->set('db',new DB());$r->set('cache',new Cache());
 return $r;
}
function check($name,$actual,$expected){if($actual!==$expected){fwrite(STDERR, "FAIL $name: ".var_export($actual,true)." != ".var_export($expected,true)."\n");exit(1);}echo "PASS $name\n";}
// Incoming old URL: default-language slug plus Russian selector, one redirect to Russian alias.
$r=setup('/only-uk-product/','language=ru-ru','ru-ru','ru');
(new ControllerStartupSeoUrl($r))->index();
$redirects=$r->get('response')->redirects;
// The requested Russian translation may not exist. Missing mapping must not redirect to homepage.
check('unknown target alias does not redirect to home',count($redirects),0);
$r=setup('/uk-product/','language=ru-ru','ru-ru','ru');
(new ControllerStartupSeoUrl($r))->index();
check('legacy slug to Russian canonical', $r->get('response')->redirects[0],array('https://shop.test/ru/ru-product/',301));
check('valid SEO entity still decoded',$r->get('request')->get['product_id'],'42');
$r=setup('/ru/ru-product/','language=uk-ua','ru-ru','ru',true);
(new ControllerStartupSeoUrl($r))->index();
check('folder wins over query', $r->get('response')->redirects[0],array('https://shop.test/ru/ru-product/',301));
$r=setup('/ru/ru-product/','tracking=abc','ru-ru','ru',true,true,false);
(new ControllerStartupSeoUrl($r))->index();
check('tracking query is preserved, no redirect',count($r->get('response')->redirects),0);
$r=setup('/ru/ru-product/','language=ru-ru','ru-ru','ru',true,false,false);
(new ControllerStartupSeoUrl($r))->index();
check('legacy non-prefix setup unaffected',count($r->get('response')->redirects),0);
$r=setup('/','', 'ru-ru','ru',true,true,false);
$c=new ControllerStartupSeoUrl($r);$r->get('url')->addRewrite($c);
check('generated link removes legacy selector',$r->get('url')->link('product/product','product_id=42&language=ru-ru&tracking=abc',true),'https://shop.test/ru/ru-product/?tracking=abc');
check('payment endpoint keeps selector',$r->get('url')->link('extension/payment/example','language=ru-ru',true),'https://shop.test/index.php?route=extension/payment/example&amp;language=ru-ru');

$r=setup('/','language=ru-ru','ru-ru','ru');
(new ControllerStartupSeoUrl($r))->index();
check('legacy home to language root',$r->get('response')->redirects[0],array('https://shop.test/ru/',301));
$r=setup('/uk-product/','language=ru-ru','ru-ru','ru');
$r->get('request')->server['REQUEST_METHOD']='POST';
$r->get('request')->post=array('key'=>'value');
(new ControllerStartupSeoUrl($r))->index();
check('POST never redirected',count($r->get('response')->redirects),0);
$r=setup('/uk-product/','language=ru-ru','ru-ru','ru');
$r->get('request')->server['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
(new ControllerStartupSeoUrl($r))->index();
check('AJAX never redirected',count($r->get('response')->redirects),0);
$r=setup('/uk-product/','language=ru-ru','ru-ru','ru',false,true,true,false);
$r->get('request')->get=array('route'=>'product/product','product_id'=>'42','language'=>'ru-ru');
$r->get('request')->server['REQUEST_URI']='/index.php?route=product/product&product_id=42&language=ru-ru';
(new ControllerStartupSeoUrl($r))->index();
check('prefix-only mode removes selector', $r->get('response')->redirects[0],array('https://shop.test/ru/index.php?route=product/product&product_id=42',301));
echo "SEO URL regression tests completed.\n";

