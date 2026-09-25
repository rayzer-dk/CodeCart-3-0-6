<?php
namespace CodeCart\Core;

final class DashboardHealth {
    private $registry;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function commerce(int $lowStockLimit = 5): array {
        $lowStockLimit = max(0, min(1000000, $lowStockLimit));
        $sales = 0.0;
        $orders = 0;
        $average = 0.0;
        $customers = 0;
        $lowStock = 0;
        $pendingOld = 0;
        $abandoned = 0;

        try {
            $q = $this->db->query("SELECT COUNT(*) AS orders, COALESCE(SUM(total),0) AS sales, COALESCE(AVG(total),0) AS average FROM `" . DB_PREFIX . "order` WHERE order_status_id > 0 AND DATE(date_added)=CURDATE()");
            if ($q->num_rows) {
                $orders = (int)$q->row['orders'];
                $sales = (float)$q->row['sales'];
                $average = (float)$q->row['average'];
            }
            $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "customer` WHERE DATE(date_added)=CURDATE()");
            $customers = (int)($q->row['total'] ?? 0);
            $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product` WHERE status='1' AND quantity <= '" . (int)$lowStockLimit . "'");
            $lowStock = (int)($q->row['total'] ?? 0);

            $pendingStatus = (int)$this->config->get('config_order_status_id');
            if ($pendingStatus > 0) {
                $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE order_status_id='" . $pendingStatus . "' AND date_added < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                $pendingOld = (int)($q->row['total'] ?? 0);
            }

            if ($this->tableExists('cart')) {
                $q = $this->db->query("SELECT COUNT(DISTINCT CONCAT(customer_id,':',session_id)) AS total FROM `" . DB_PREFIX . "cart` WHERE date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $abandoned = (int)($q->row['total'] ?? 0);
            }
        } catch (\Throwable $e) {}

        return array(
            'sales' => $sales,
            'orders' => $orders,
            'average' => $average,
            'customers' => $customers,
            'low_stock' => $lowStock,
            'pending_old' => $pendingOld,
            'abandoned' => $abandoned
        );
    }

    public function attention(int $lowStockLimit = 5): array {
        $items = array();
        $commerce = $this->commerce($lowStockLimit);
        if ($commerce['low_stock'] > 0) { $items[] = $this->notice('low_stock', 'warning', $commerce['low_stock']); }
        if ($commerce['pending_old'] > 0) { $items[] = $this->notice('pending_orders', 'warning', $commerce['pending_old']); }

        if (is_dir(dirname(DIR_SYSTEM) . '/install') || is_dir(DIR_CATALOG . '../install')) {
            $items[] = $this->notice('install_dir', 'error', 1);
        }

        try {
            if ($this->tableExists('codecart_queue')) {
                $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` WHERE status='failed'");
                $failed = (int)($q->row['total'] ?? 0);
                if ($failed > 0) { $items[] = $this->notice('queue_failed', 'error', $failed); }
            }
        } catch (\Throwable $e) {}

        $cron = $this->cronState();
        if ($cron['state'] !== 'ok') { $items[] = $this->notice('cron_stale', $cron['state'] === 'error' ? 'error' : 'warning', $cron['age_minutes']); }

        $expected = defined('CODECART_BUILD') ? (string)CODECART_BUILD : '';
        $actual = trim((string)$this->config->get('codecart_core_schema_version'));
        if ($expected !== '' && $actual !== $expected) { $items[] = $this->notice('schema_version', 'warning', $actual !== '' ? $actual : '-'); }

        $db = $this->databaseState();
        if ($db['legacy_tables'] > 0 || $db['legacy_columns'] > 0) {
            $items[] = $this->notice('database_legacy', 'warning', $db['legacy_tables'] + $db['legacy_columns']);
        }

        $disk = $this->diskState();
        if ($disk['state'] !== 'ok') { $items[] = $this->notice('disk_low', $disk['state'], $disk['percent']); }

        $errors = $this->phpErrors24h();
        if ($errors > 0) { $items[] = $this->notice('php_errors', $errors >= 10 ? 'error' : 'warning', $errors); }

        return $items;
    }

    public function system(): array {
        $db = $this->databaseState();
        $cron = $this->cronState();
        $queue = array('pending'=>0,'processing'=>0,'failed'=>0,'done'=>0);
        try {
            if ($this->tableExists('codecart_queue')) {
                $q = $this->db->query("SELECT status,COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` GROUP BY status");
                foreach ($q->rows as $row) { if (isset($queue[$row['status']])) { $queue[$row['status']] = (int)$row['total']; } }
            }
        } catch (\Throwable $e) {}

        $cacheRequested = strtoupper((string)$this->config->get('cache_engine'));
        $cacheActive = $cacheRequested !== '' ? $cacheRequested : 'FILE';
        $cacheFallback = '';
        if ($this->registry->has('cache')) {
            $cache = $this->registry->get('cache');
            if (is_object($cache) && method_exists($cache, 'getRequestedEngine')) { $cacheRequested = strtoupper((string)$cache->getRequestedEngine()); }
            if (is_object($cache) && method_exists($cache, 'getActiveEngine')) { $cacheActive = strtoupper((string)$cache->getActiveEngine()); }
            if (is_object($cache) && method_exists($cache, 'getFallbackReason')) { $cacheFallback = (string)$cache->getFallbackReason(); }
        }

        $mail = (string)$this->config->get('config_mail_engine');
        $smtpOk = true;
        $smtpValue = $mail !== '' ? $mail : 'mail';
        if ($mail === 'smtp') {
            $host = trim((string)$this->config->get('config_mail_smtp_hostname'));
            $port = (int)$this->config->get('config_mail_smtp_port');
            $smtpOk = $host !== '' && $port > 0;
            $smtpValue = $smtpOk ? $host . ':' . $port : 'not configured';
        }

        $disk = $this->diskState();
        $opcache = extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN);

        return array(
            'php' => array('value'=>PHP_VERSION, 'state'=>version_compare(PHP_VERSION,'8.1.0','>=') && version_compare(PHP_VERSION,'8.6.0','<') ? 'ok':'error'),
            'database' => array('value'=>$db['version'], 'state'=>$db['state']),
            'innodb' => array('value'=>$db['innodb_tables'] . '/' . $db['total_tables'], 'state'=>$db['innodb_tables']===$db['total_tables']?'ok':'warning'),
            'utf8mb4' => array('value'=>$db['utf8mb4_tables'] . '/' . $db['total_tables'], 'state'=>$db['utf8mb4_tables']===$db['total_tables']?'ok':'warning'),
            'utf8mb4_columns' => array('value'=>$db['utf8mb4_columns'] . '/' . $db['text_columns'], 'state'=>$db['utf8mb4_columns']===$db['text_columns']?'ok':'warning'),
            'schema' => array('value'=>trim((string)$this->config->get('codecart_core_schema_version')) ?: '-', 'state'=>trim((string)$this->config->get('codecart_core_schema_version'))===(defined('CODECART_BUILD')?(string)CODECART_BUILD:'')?'ok':'warning'),
            'cache' => array('value'=>($cacheRequested && $cacheRequested!==$cacheActive ? $cacheRequested . ' → ' : '') . $cacheActive, 'state'=>$cacheFallback!==''?'warning':'ok', 'detail'=>$cacheFallback),
            'opcache' => array('value'=>$opcache?'enabled':'disabled', 'state'=>$opcache?'ok':'warning'),
            'cron' => array('value'=>$cron['last'] ?: 'never', 'state'=>$cron['state']),
            'queue' => array('value'=>'pending ' . $queue['pending'] . ' / failed ' . $queue['failed'], 'state'=>$queue['failed']?'warning':'ok'),
            'mail' => array('value'=>$smtpValue, 'state'=>$smtpOk?'ok':'warning'),
            'disk' => array('value'=>sprintf('%.1f%% free',$disk['percent']), 'state'=>$disk['state'])
        );
    }

    public function seo(): array {
        $languageId = (int)$this->config->get('config_language_id');
        if ($languageId <= 0) { $languageId = 1; }
        $result = array(
            'missing_seo_product'=>0,'missing_seo_category'=>0,'missing_seo_manufacturer'=>0,'missing_seo_information'=>0,'missing_seo_article'=>0,'missing_seo_blog_category'=>0,
            'missing_meta_product'=>0,'missing_meta_category'=>0,'missing_meta_manufacturer'=>0,'missing_meta_article'=>0,
            'missing_identifier'=>0,'missing_brand'=>0,'orphan_product'=>0
        );
        try {
            if ($this->tableExists('seo_url')) {
                $queries = array(
                    'missing_seo_product' => "SELECT COUNT(*) AS total FROM `".DB_PREFIX."product` p WHERE p.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('product_id=',p.product_id) AND s.keyword<>'')",
                    'missing_seo_category' => "SELECT COUNT(*) AS total FROM `".DB_PREFIX."category` c WHERE c.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('category_id=',c.category_id) AND s.keyword<>'')",
                    'missing_seo_manufacturer' => "SELECT COUNT(*) AS total FROM `".DB_PREFIX."manufacturer` m WHERE NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('manufacturer_id=',m.manufacturer_id) AND s.keyword<>'')"
                );
                if ($this->tableExists('information')) {
                    $queries['missing_seo_information'] = "SELECT COUNT(*) AS total FROM `".DB_PREFIX."information` i WHERE i.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('information_id=',i.information_id) AND s.keyword<>'')";
                }
                if ($this->tableExists('article')) {
                    $queries['missing_seo_article'] = "SELECT COUNT(*) AS total FROM `".DB_PREFIX."article` a WHERE a.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('article_id=',a.article_id) AND s.keyword<>'')";
                }
                if ($this->tableExists('blog_category')) {
                    $queries['missing_seo_blog_category'] = "SELECT COUNT(*) AS total FROM `".DB_PREFIX."blog_category` bc WHERE bc.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('blog_category_id=',bc.blog_category_id) AND s.keyword<>'')";
                }
                foreach ($queries as $key=>$sql) { $q=$this->db->query($sql); $result[$key]=(int)($q->row['total']??0); }
            }

            $meta = array(
                'missing_meta_product' => "SELECT COUNT(*) AS total FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` d ON(d.product_id=p.product_id AND d.language_id='".$languageId."') WHERE p.status='1' AND (d.meta_title IS NULL OR TRIM(d.meta_title)='')",
                'missing_meta_category' => "SELECT COUNT(*) AS total FROM `".DB_PREFIX."category` c LEFT JOIN `".DB_PREFIX."category_description` d ON(d.category_id=c.category_id AND d.language_id='".$languageId."') WHERE c.status='1' AND (d.meta_title IS NULL OR TRIM(d.meta_title)='')",
                'missing_meta_manufacturer' => "SELECT COUNT(*) AS total FROM `".DB_PREFIX."manufacturer` m LEFT JOIN `".DB_PREFIX."manufacturer_description` d ON(d.manufacturer_id=m.manufacturer_id AND d.language_id='".$languageId."') WHERE (d.meta_title IS NULL OR TRIM(d.meta_title)='')"
            );
            if ($this->tableExists('article_description')) {
                $meta['missing_meta_article'] = "SELECT COUNT(*) AS total FROM `".DB_PREFIX."article` a LEFT JOIN `".DB_PREFIX."article_description` d ON(d.article_id=a.article_id AND d.language_id='".$languageId."') WHERE a.status='1' AND (d.meta_title IS NULL OR TRIM(d.meta_title)='')";
            }
            foreach ($meta as $key=>$sql) { $q=$this->db->query($sql); $result[$key]=(int)($q->row['total']??0); }

            $q=$this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."product` WHERE status='1' AND TRIM(COALESCE(upc,''))='' AND TRIM(COALESCE(ean,''))='' AND TRIM(COALESCE(mpn,''))=''");
            $result['missing_identifier']=(int)($q->row['total']??0);
            $q=$this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."product` WHERE status='1' AND manufacturer_id='0'");
            $result['missing_brand']=(int)($q->row['total']??0);
            $q=$this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."product` p WHERE p.status='1' AND NOT EXISTS(SELECT 1 FROM `".DB_PREFIX."product_to_category` pc WHERE pc.product_id=p.product_id)");
            $result['orphan_product']=(int)($q->row['total']??0);
        } catch (\Throwable $e) {}

        $result['schema_enabled'] = (int)$this->config->get('config_codecart_structured_data_status');
        $result['sitemap_enabled'] = (int)$this->config->get('feed_google_sitemap_status');
        $result['total_issues'] = 0;
        foreach ($result as $key=>$value) {
            if (strpos($key,'missing_')===0 || $key==='orphan_product') { $result['total_issues'] += (int)$value; }
        }
        return $result;
    }

    public function security(int $userId): array {
        $https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->registry->get('request')->server) : false;
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
        $storage = realpath(DIR_STORAGE);
        $storageOutside = false;
        if ($documentRoot && $storage) { $storageOutside = strpos($storage, rtrim($documentRoot,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)!==0; }
        $attempts = 0;
        try {
            if ($this->tableExists('codecart_admin_login_attempt')) {
                $q=$this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."codecart_admin_login_attempt` WHERE date_added>=DATE_SUB(NOW(),INTERVAL 24 HOUR)");
                $attempts=(int)($q->row['total']??0);
            }
        } catch (\Throwable $e) {}
        $totp = false;
        try { $totp=(new TotpManager($this->registry))->isEnabled($userId); } catch (\Throwable $e) {}
        $integrity = array('status'=>'warning','checked'=>0,'changed'=>0,'missing'=>0,'items'=>array());
        try { $integrity=(new FileIntegrity($this->registry))->check(); } catch (\Throwable $e) {}

        return array(
            'mfa'=>array('value'=>$totp?'enabled':'disabled','state'=>$totp?'ok':'warning'),
            'failed_login'=>array('value'=>$attempts,'state'=>$attempts>=10?'warning':'ok'),
            'integrity'=>array('value'=>$integrity['changed']+$integrity['missing'],'state'=>$integrity['status'],'detail'=>$integrity),
            'installer'=>array('value'=>(is_dir(DIR_CATALOG.'../install')?'present':'absent'),'state'=>is_dir(DIR_CATALOG.'../install')?'error':'ok'),
            'storage'=>array('value'=>$storageOutside?'outside web root':'inside/unknown','state'=>$storageOutside?'ok':'warning'),
            'https'=>array('value'=>$https?'active':'not detected','state'=>$https?'ok':'warning'),
            'headers'=>array('value'=>(int)$this->config->get('codecart_security_headers_status')?'enabled':'disabled','state'=>(int)$this->config->get('codecart_security_headers_status')?'ok':'warning')
        );
    }

    public function lastPerformance(): array {
        $file = $this->performanceFile();
        if (!is_file($file)) { return array(); }
        $raw=@file_get_contents($file);
        $data=$raw!==false?json_decode($raw,true):null;
        return is_array($data)?$data:array();
    }

    public function runPerformanceCheck(string $url): array {
        if (!function_exists('curl_init')) { throw new \RuntimeException('cURL is unavailable.'); }
        if (!preg_match('#^https?://#i',$url)) { throw new \InvalidArgumentException('Invalid storefront URL.'); }
        $headers=array();
        $ch=curl_init($url);
        $body='';
        $maxBody=4194304;
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,false);
        curl_setopt($ch,CURLOPT_WRITEFUNCTION,function($ch,$chunk) use (&$body,$maxBody){if(strlen($body)+strlen($chunk)>$maxBody){return 0;}$body.=$chunk;return strlen($chunk);});
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,3);
        curl_setopt($ch,CURLOPT_TIMEOUT,8);
        curl_setopt($ch,CURLOPT_ENCODING,'');
        curl_setopt($ch,CURLOPT_USERAGENT,'CodeCart-Performance-Check/1.1');
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
        TlsPolicy::applyCurl($ch);
        curl_setopt($ch,CURLOPT_HEADERFUNCTION,function($ch,$line) use (&$headers){
            $len=strlen($line); $pos=strpos($line,':');
            if($pos!==false){$name=strtolower(trim(substr($line,0,$pos)));$value=trim(substr($line,$pos+1));$headers[$name]=$value;}
            return $len;
        });
        $ok=curl_exec($ch);
        if ($ok===false) { $message=curl_error($ch); throw new \RuntimeException($message?:'Storefront check failed or response exceeded 4 MB.'); }
        $html=$body;
        $info=curl_getinfo($ch);
        $httpVersion=$this->httpVersionName((int)($info['http_version']??0));
        $data=array(
            'checked_at'=>date('Y-m-d H:i:s'),
            'url'=>(string)($info['url']??$url),
            'http_code'=>(int)($info['http_code']??0),
            'ttfb_ms'=>(int)round(((float)($info['starttransfer_time']??0))*1000),
            'total_ms'=>(int)round(((float)($info['total_time']??0))*1000),
            'html_kb'=>round(strlen((string)$html)/1024,1),
            'css_count'=>preg_match_all('#<link[^>]+rel=["\']?stylesheet["\']?[^>]*>#i',(string)$html,$m),
            'js_count'=>preg_match_all('#<script[^>]+src=["\'][^"\']+["\'][^>]*>#i',(string)$html,$m2),
            'http_version'=>$httpVersion,
            'content_encoding'=>isset($headers['content-encoding'])?strtolower($headers['content-encoding']):'',
            'cache_control'=>$headers['cache-control']??'',
            'server'=>$headers['server']??'',
            'vary'=>$headers['vary']??'',
            'cf_ray'=>$headers['cf-ray']??'',
            'cf_cache_status'=>$headers['cf-cache-status']??''
        );
        $this->writeJsonAtomic($this->performanceFile(),$data);
        return $data;
    }

    private function databaseState(): array {
        $result=array('version'=>'unknown','total_tables'=>0,'innodb_tables'=>0,'utf8mb4_tables'=>0,'text_columns'=>0,'utf8mb4_columns'=>0,'legacy_tables'=>0,'legacy_columns'=>0,'state'=>'warning');
        try {
            $q=$this->db->query('SELECT VERSION() AS version'); if($q->num_rows){$result['version']=(string)$q->row['version'];}
            $q=$this->db->query("SELECT COUNT(*) total,SUM(ENGINE='InnoDB') innodb,SUM(TABLE_COLLATION LIKE 'utf8mb4%') utf8mb4,SUM(NOT (ENGINE='InnoDB' AND TABLE_COLLATION LIKE 'utf8mb4%')) legacy FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$this->db->escape(DB_DATABASE)."' AND TABLE_TYPE='BASE TABLE'");
            if($q->num_rows){$result['total_tables']=(int)$q->row['total'];$result['innodb_tables']=(int)$q->row['innodb'];$result['utf8mb4_tables']=(int)$q->row['utf8mb4'];$result['legacy_tables']=(int)$q->row['legacy'];}
            $q=$this->db->query("SELECT COUNT(*) total,SUM(CHARACTER_SET_NAME='utf8mb4') utf8mb4 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$this->db->escape(DB_DATABASE)."' AND CHARACTER_SET_NAME IS NOT NULL");
            if($q->num_rows){$result['text_columns']=(int)$q->row['total'];$result['utf8mb4_columns']=(int)$q->row['utf8mb4'];}
            $result['legacy_columns']=max(0,$result['text_columns']-$result['utf8mb4_columns']);
            $result['state']=($result['innodb_tables']===$result['total_tables']&&$result['utf8mb4_tables']===$result['total_tables']&&$result['utf8mb4_columns']===$result['text_columns'])?'ok':'warning';
        } catch (\Throwable $e) {}
        return $result;
    }

    private function cronState(): array {
        $result=array('state'=>'warning','last'=>'','age_minutes'=>999999);
        try {
            if($this->tableExists('codecart_scheduler')){
                $q=$this->db->query("SELECT date_last,last_status FROM `".DB_PREFIX."codecart_scheduler` WHERE code='core.health.check' LIMIT 1");
                if($q->num_rows&&$q->row['date_last']){
                    $result['last']=(string)$q->row['date_last'];
                    $ts=strtotime((string)$q->row['date_last']);
                    if($ts){$result['age_minutes']=max(0,(int)floor((time()-$ts)/60));}
                    if((string)$q->row['last_status']==='error'){$result['state']='error';}
                    elseif($result['age_minutes']<=90){$result['state']='ok';}
                    else{$result['state']='warning';}
                }
            }
        } catch (\Throwable $e) {}
        return $result;
    }

    private function diskState(): array {
        $free=@disk_free_space(DIR_STORAGE);$total=@disk_total_space(DIR_STORAGE);
        if($free===false||!$total){return array('state'=>'warning','percent'=>0.0,'free'=>0.0,'total'=>0.0);}
        $percent=100*$free/$total;
        $state=($free<1073741824||$percent<8)?'error':($percent<15?'warning':'ok');
        return array('state'=>$state,'percent'=>round($percent,1),'free'=>(float)$free,'total'=>(float)$total);
    }

    private function phpErrors24h(): int {
        if(!(int)$this->config->get('config_error_log')){return 0;}
        $name=basename((string)$this->config->get('config_error_filename'));
        if($name===''){return 0;}
        $file=DIR_LOGS.$name;
        if(!is_file($file)||!is_readable($file)){return 0;}
        $size=@filesize($file); if(!$size){return 0;}
        $read=min((int)$size,1048576);
        $fp=@fopen($file,'rb'); if(!$fp){return 0;}
        if($size>$read){fseek($fp,$size-$read);}
        $data=(string)fread($fp,$read); fclose($fp);
        $cutoff=time()-86400;$count=0;
        foreach(preg_split('/\r?\n/',$data) as $line){
            if(preg_match('/^(\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}:\d{2})/',$line,$m)){ $ts=strtotime($m[1]); if($ts&&$ts>=$cutoff){$count++;} }
        }
        return $count;
    }

    private function notice(string $code,string $state,$value): array { return array('code'=>$code,'state'=>$state,'value'=>$value); }
    private function tableExists(string $table): bool { try{$q=$this->db->query("SHOW TABLES LIKE '".$this->db->escape(DB_PREFIX.$table)."'");return (bool)$q->num_rows;}catch(\Throwable $e){return false;} }
    private function performanceFile(): string { return rtrim(DIR_STORAGE,'/\\').DIRECTORY_SEPARATOR.'codecart'.DIRECTORY_SEPARATOR.'diagnostics'.DIRECTORY_SEPARATOR.'storefront_performance.json'; }
    private function writeJsonAtomic(string $file,array $data): void {
        $dir=dirname($file); if(!is_dir($dir)&&!@mkdir($dir,0750,true)&&!is_dir($dir)){throw new \RuntimeException('Cannot create diagnostics directory.');}
        $json=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); if($json===false){throw new \RuntimeException('Cannot encode diagnostics result.');}
        $tmp=$file.'.tmp.'.bin2hex(random_bytes(4)); if(@file_put_contents($tmp,$json,LOCK_EX)===false){throw new \RuntimeException('Cannot write diagnostics result.');}
        if(!@rename($tmp,$file)){@unlink($tmp);throw new \RuntimeException('Cannot finalize diagnostics result.');}
    }
    private function httpVersionName(int $v): string {
        $map=array();
        if(defined('CURL_HTTP_VERSION_1_0')){$map[CURL_HTTP_VERSION_1_0]='HTTP/1.0';}
        if(defined('CURL_HTTP_VERSION_1_1')){$map[CURL_HTTP_VERSION_1_1]='HTTP/1.1';}
        if(defined('CURL_HTTP_VERSION_2_0')){$map[CURL_HTTP_VERSION_2_0]='HTTP/2';}
        if(defined('CURL_HTTP_VERSION_3')){$map[CURL_HTTP_VERSION_3]='HTTP/3';}
        return $map[$v]??('HTTP '.$v);
    }
}
