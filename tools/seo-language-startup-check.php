<?php
/** Test native language selection before SeoPro parses SEO slugs. */
namespace Cart {
 class Customer {public function __construct($r){} public function isLogged(){return false;} }
 class Currency {public function __construct($r){} }
 class Tax {public function __construct($r){}public function setStoreAddress($a,$b){} }
 class Weight {public function __construct($r){} }
 class Length {public function __construct($r){} }
 class Cart {public function __construct($r){} }
}
namespace {
ob_start();
require_once __DIR__.'/seo-language-url-check.php';
if (!defined('HTTP_SERVER')) define('HTTP_SERVER', 'https://shop.test/');
if (!defined('HTTPS_SERVER')) define('HTTPS_SERVER', 'https://shop.test/');
class Encryption {}
class LanguageModel {public $prefixMode=true;public function __construct($enabled){$this->prefixMode=$enabled;}public function getLanguages(){return array(
 'uk-ua'=>array('code'=>'uk-ua','name'=>'Українська','status'=>1,'language_id'=>1,'url_prefix'=>'','locale'=>'uk_UA'),
 'ru-ru'=>array('code'=>'ru-ru','name'=>'Русский','status'=>1,'language_id'=>2,'url_prefix'=>$this->prefixMode?'ru':'','locale'=>'ru_RU'),
);}}
class CurrencyModel {public function getCurrencies(){return array('UAH'=>array('code'=>'UAH'));}}
class LoadMock {private $r;private $enabled;public function __construct($r,$enabled){$this->r=$r;$this->enabled=$enabled;}public function model($name){if($name==='localisation/language')$this->r->set('model_localisation_language',new LanguageModel($this->enabled));if($name==='localisation/currency')$this->r->set('model_localisation_currency',new CurrencyModel());}}
class StartupDB extends DB { public function query($sql){if(strpos($sql,' FROM oc_store ')!==false || strpos($sql,' FROM `oc_setting` ')!==false) return new DataResult(array());return parent::query($sql);} }
require_once __DIR__.'/../upload/catalog/controller/startup/startup.php';
function runStartup($path,$selector,$cookieSession='uk-ua',$enabled=true){
 $r=setup('/','','uk-ua','',false,false,false,false);
 $config=$r->get('config');$config->set('config_seo_url',1);$config->set('config_language','uk-ua');$config->set('config_currency','UAH');
 $req=$r->get('request');$req->server['HTTP_HOST']='shop.test';$req->server['PHP_SELF']='/index.php';
 $req->server['REQUEST_URI']=$path.($selector!==''?'?language='.$selector:'');
 $req->get=array();$req->cookie=array();
 if($selector!=='')$req->get['language']=$selector;
 $str=trim($path,'/');if($str!=='')$req->get['_route_']=$str;
 $r->set('db',new StartupDB());$r->set('load',new LoadMock($r,$enabled));
 $r->set('session',(object)array('data'=>array('language'=>$cookieSession)));
 $r->set('customer',new \Cart\Customer($r));
 (new \ControllerStartupStartup($r))->index();
 return $r;
}
$r=runStartup('/uk-product/','ru-ru');
check('startup legacy query selects target language',$r->get('session')->data['language'],'ru-ru');
check('startup native language prefix derived from DB',$r->get('config')->get('codecart_language_prefix_current'),'ru');
check('startup legacy query recognized',$r->get('config')->get('codecart_language_legacy_query'),1);
$r=runStartup('/ru/ru-product/','uk-ua');
check('real language folder overrides query',$r->get('session')->data['language'],'ru-ru');
check('folder stripped before SeoPro',$r->get('request')->get['_route_'],'ru-product');
$r=runStartup('/uk-product/','','ru-ru');
check('unprefixed URL belongs to main language',$r->get('session')->data['language'],'uk-ua');
$r=runStartup('/uk-product/','ru-ru','uk-ua',false);
check('no native prefix keeps previous language behavior',$r->get('session')->data['language'],'uk-ua');
echo "Startup language regression tests completed.\n";
ob_end_flush();
}
