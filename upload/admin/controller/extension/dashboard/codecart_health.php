<?php
class ControllerExtensionDashboardCodeCartHealth extends Controller {
    private $error = array();
    private $sections = array('quick','attention','commerce','system','performance','seo','security');

    public function index() {
        $this->load->language('extension/dashboard/codecart_health');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $post = $this->request->post;
            $post['dashboard_codecart_health_status'] = !empty($post['dashboard_codecart_health_status']) ? 1 : 0;
            foreach ($this->sections as $section) {
                $key = 'dashboard_codecart_health_' . $section . '_status';
                $post[$key] = !empty($post[$key]) ? 1 : 0;
            }
            $post['dashboard_codecart_health_low_stock_limit'] = max(0, min(1000000, (int)($post['dashboard_codecart_health_low_stock_limit'] ?? 5)));
            $allowedQuick = array('product_add','orders','cache','ocmod','diagnostics','scheduler','logs');
            $selectedQuick = isset($post['dashboard_codecart_health_quick_actions']) && is_array($post['dashboard_codecart_health_quick_actions']) ? $post['dashboard_codecart_health_quick_actions'] : array();
            $post['dashboard_codecart_health_quick_actions'] = array_values(array_intersect($allowedQuick, array_map('strval', $selectedQuick)));
            $post['dashboard_codecart_health_width'] = 12;
            $this->model_setting_setting->editSetting('dashboard_codecart_health', $post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $data = array();
        foreach (array('heading_title','text_home','text_extension','text_edit','text_enabled','text_disabled','text_success','text_sections','text_sections_help','text_commerce','text_attention','text_system','text_performance','text_seo','text_security','text_quick','text_quick_help','text_compatibility','entry_status','entry_sort_order','entry_low_stock','button_save','button_cancel','error_permission') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=dashboard',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/dashboard/codecart_health','user_token='.$this->session->data['user_token'],true))
        );
        $data['action'] = $this->url->link('extension/dashboard/codecart_health','user_token='.$this->session->data['user_token'],true);
        $data['cancel'] = $this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true);
        $data['sections'] = array();
        foreach ($this->sections as $section) {
            $key = 'dashboard_codecart_health_' . $section . '_status';
            $default = 1;
            $value = isset($this->request->post[$key]) ? (int)$this->request->post[$key] : $this->settingInt($key, $default);
            $data['sections'][] = array('code'=>$section,'name'=>$this->language->get('text_'.$section),'status'=>$value);
        }
        $quickCodes = array('product_add','orders','cache','ocmod','diagnostics','scheduler','logs');
        if (isset($this->request->post['dashboard_codecart_health_quick_actions']) && is_array($this->request->post['dashboard_codecart_health_quick_actions'])) {
            $selectedQuick = array_map('strval', $this->request->post['dashboard_codecart_health_quick_actions']);
        } else {
            $selectedQuick = $this->config->get('dashboard_codecart_health_quick_actions');
            if (!is_array($selectedQuick)) { $selectedQuick = $quickCodes; }
        }
        $data['quick_action_options'] = array();
        foreach ($quickCodes as $code) { $data['quick_action_options'][] = array('code'=>$code,'label'=>$this->language->get('quick_'.$code),'selected'=>in_array($code,$selectedQuick,true)); }

        $data['dashboard_codecart_health_status'] = isset($this->request->post['dashboard_codecart_health_status']) ? (int)$this->request->post['dashboard_codecart_health_status'] : $this->settingInt('dashboard_codecart_health_status',1);
        $data['dashboard_codecart_health_sort_order'] = isset($this->request->post['dashboard_codecart_health_sort_order']) ? (int)$this->request->post['dashboard_codecart_health_sort_order'] : $this->settingInt('dashboard_codecart_health_sort_order',11);
        $data['dashboard_codecart_health_low_stock_limit'] = isset($this->request->post['dashboard_codecart_health_low_stock_limit']) ? (int)$this->request->post['dashboard_codecart_health_low_stock_limit'] : $this->settingInt('dashboard_codecart_health_low_stock_limit',5);
        $data['header']=$this->load->controller('common/header');
        $data['column_left']=$this->load->controller('common/column_left');
        $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/dashboard/codecart_health_form',$data));
    }

    public function dashboard() {
        $this->load->language('extension/dashboard/codecart_health');
        $this->document->addStyle('view/stylesheet/codecart-dashboard.css?v=3.0.6.0-b125');
        $data['heading_title']=$this->language->get('heading_title_dashboard');
        $data['text_loading']=$this->language->get('text_loading');
        $data['text_settings']=$this->language->get('text_settings');
        $data['setting']=$this->url->link('extension/dashboard/codecart_health','user_token='.$this->session->data['user_token'],true);
        $data['user_token']=$this->session->data['user_token'];
        foreach (array('text_close','text_open','text_loading','text_no_items','text_list_limited','text_security_open') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['sections']=array();
        foreach($this->sections as $section){
            if($this->settingInt('dashboard_codecart_health_'.$section.'_status',1)){
                $data['sections'][]=array('code'=>$section,'title'=>$this->language->get('text_'.$section),'icon'=>$this->sectionIcon($section));
            }
        }
        return $this->load->view('extension/dashboard/codecart_health_info',$data);
    }

    public function section() {
        if (!$this->user->hasPermission('access','extension/dashboard/codecart_health')) { $this->response->setStatusCode(403); return; }
        $this->load->language('extension/dashboard/codecart_health');
        $section = isset($this->request->get['section']) ? preg_replace('/[^a-z]/','',(string)$this->request->get['section']) : '';
        if (!in_array($section,$this->sections,true) || !$this->settingInt('dashboard_codecart_health_'.$section.'_status',1)) { $this->response->setStatusCode(404); return; }

        $health = new \CodeCart\Core\DashboardHealth($this->registry);
        $data = array('section'=>$section,'title'=>$this->language->get('text_'.$section),'icon'=>$this->sectionIcon($section),'user_token'=>$this->session->data['user_token']);
        $data['links']=$this->links();
        $lowStock=$this->settingInt('dashboard_codecart_health_low_stock_limit',5);

        if($section==='commerce'){
            $r=$health->commerce($lowStock);
            $data['items']=array(
                array('code'=>'sales','label'=>$this->language->get('kpi_sales'),'value'=>$this->currency->format((float)$r['sales'],$this->config->get('config_currency')),'state'=>'ok','icon'=>'fa-money','href'=>$data['links']['orders']),
                array('code'=>'orders','label'=>$this->language->get('kpi_orders'),'value'=>(int)$r['orders'],'state'=>'ok','icon'=>'fa-shopping-cart','href'=>$data['links']['orders']),
                array('code'=>'average','label'=>$this->language->get('kpi_average'),'value'=>$this->currency->format((float)$r['average'],$this->config->get('config_currency')),'state'=>'ok','icon'=>'fa-line-chart','href'=>$data['links']['orders']),
                array('code'=>'customers','label'=>$this->language->get('kpi_customers'),'value'=>(int)$r['customers'],'state'=>'ok','icon'=>'fa-user-plus','href'=>$data['links']['customers']),
                array('code'=>'low_stock','label'=>$this->language->get('kpi_low_stock'),'value'=>(int)$r['low_stock'],'state'=>$r['low_stock']?'warning':'ok','icon'=>'fa-cubes','href'=>$data['links']['products']),
                array('code'=>'pending','label'=>$this->language->get('kpi_pending'),'value'=>(int)$r['pending_old'],'state'=>$r['pending_old']?'warning':'ok','icon'=>'fa-clock-o','href'=>$data['links']['orders']),
                array('code'=>'abandoned','label'=>$this->language->get('kpi_abandoned'),'value'=>(int)$r['abandoned'],'state'=>$r['abandoned']?'info':'ok','icon'=>'fa-shopping-basket','href'=>$data['links']['abandoned'])
            );
        } elseif($section==='attention'){
            $items=$health->attention($lowStock);$data['items']=array();
            foreach($items as $item){
                $code=(string)$item['code'];
                $data['items'][]=array('state'=>$item['state'],'title'=>$this->language->get('attention_'.$code),'detail'=>sprintf($this->language->get('attention_'.$code.'_detail'),$item['value']),'href'=>$this->attentionLink($code,$data['links']));
            }
            $data['empty']=$this->language->get('text_no_attention');
        } elseif($section==='system'){
            $rows=$health->system();
            $data['items']=$this->localizedStatusRows($rows,'system_');
            foreach ($data['items'] as &$item) {
                $item['href'] = $this->systemLink($item['code'], $data['links']);
                $helpKey = 'system_' . $item['code'] . '_help';
                $help = $this->language->get($helpKey);
                if ($help !== $helpKey) { $item['detail'] = $help; }
            }
            unset($item);
        } elseif($section==='performance'){
            $data['performance']=$health->lastPerformance();
            $data['button_check']=$this->language->get('button_performance_check');
            $data['check_url']=$this->url->link('extension/dashboard/codecart_health/performanceCheck','user_token='.$this->session->data['user_token'],true);
            $data['text_never_checked']=$this->language->get('text_never_checked');
            $data['performance_items']=array();
            if ($data['performance']) {
                foreach (array('ttfb_ms','total_ms','html_kb','css_count','js_count','http_version','content_encoding','cache_control') as $key) {
                    $value = isset($data['performance'][$key]) ? $data['performance'][$key] : '';
                    if ($key === 'ttfb_ms' || $key === 'total_ms') { $value .= ' ms'; }
                    elseif ($key === 'html_kb') { $value .= ' KB'; }
                    if ($value === '') { $value = '—'; }
                    $data['performance_items'][]=array('label'=>$this->language->get('perf_'.$this->performanceLabelKey($key)),'value'=>$value,'class'=>$key==='cache_control'?' ccp-performance-cache':'');
                }
            }
        } elseif($section==='seo'){
            $r=$health->seo();$data['total_issues']=$r['total_issues'];$data['items']=array();
            foreach(array('missing_seo_product','missing_seo_category','missing_seo_manufacturer','missing_seo_information','missing_seo_article','missing_seo_blog_category','missing_meta_product','missing_meta_category','missing_meta_manufacturer','missing_meta_article','missing_identifier','missing_brand','orphan_product') as $code){
                $value=(int)$r[$code];
                if($value>0){
                    $data['items'][]=array(
                        'code'=>$code,
                        'label'=>$this->language->get('seo_'.$code),
                        'value'=>$value,
                        'state'=>'warning',
                        'url'=>$this->url->link('extension/dashboard/codecart_health/seoItems','user_token='.$this->session->data['user_token'].'&issue='.$code,true)
                    );
                }
            }
            $data['schema_enabled']=$r['schema_enabled'];$data['sitemap_enabled']=$r['sitemap_enabled'];$data['text_schema']=$this->language->get('seo_schema');$data['text_sitemap']=$this->language->get('seo_sitemap');$data['text_seo_issues']=$this->language->get('text_seo_issues');
            $data['empty']=$this->language->get('text_no_seo_issues');
        } elseif($section==='security'){
            $rows=$health->security((int)$this->user->getId());
            $data['items']=$this->localizedStatusRows($rows,'security_');
            $securityUrl=$this->url->link('tool/codecart_core','user_token='.$this->session->data['user_token'].'&tab=security',true);
            foreach($data['items'] as &$securityItem){
                $helpKey='security_'.$securityItem['code'].'_help';
                $help=$this->language->get($helpKey);
                $securityItem['help']=$help!==$helpKey?$help:'';
                $securityItem['href']=$securityUrl;
            }
            unset($securityItem);
            $data['integrity_items']=isset($rows['integrity']['detail']['items'])?$rows['integrity']['detail']['items']:array();
        } elseif($section==='quick'){
            $data['actions']=$this->quickActions($data['links']);
            $data['text_cache_done']=$this->language->get('text_cache_done');
        }
        $this->response->setOutput($this->load->view('extension/dashboard/codecart_health_section',$data));
    }

    public function seoItems() {
        $this->load->language('extension/dashboard/codecart_health');
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $json=array('items'=>array(),'limited'=>false);

        if(!$this->user->hasPermission('access','extension/dashboard/codecart_health')){
            $json['error']=$this->language->get('error_permission');
            $this->response->setOutput(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            return;
        }

        $issue=isset($this->request->get['issue'])?preg_replace('/[^a-z_]/','',(string)$this->request->get['issue']):'';
        $languageId=max(1,(int)$this->config->get('config_language_id'));
        $limit=101;
        $queries=array(
            'missing_seo_product'=>array('product','product_id',"SELECT p.product_id AS id, COALESCE(d.name,CONCAT('#',p.product_id)) AS name FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` d ON(d.product_id=p.product_id AND d.language_id='".$languageId."') WHERE p.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('product_id=',p.product_id) AND s.keyword<>'') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_seo_category'=>array('category','category_id',"SELECT c.category_id AS id, COALESCE(d.name,CONCAT('#',c.category_id)) AS name FROM `".DB_PREFIX."category` c LEFT JOIN `".DB_PREFIX."category_description` d ON(d.category_id=c.category_id AND d.language_id='".$languageId."') WHERE c.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('category_id=',c.category_id) AND s.keyword<>'') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_seo_manufacturer'=>array('manufacturer','manufacturer_id',"SELECT m.manufacturer_id AS id, m.name AS name FROM `".DB_PREFIX."manufacturer` m WHERE NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('manufacturer_id=',m.manufacturer_id) AND s.keyword<>'') ORDER BY m.name ASC LIMIT ".$limit),
            'missing_seo_information'=>array('information','information_id',"SELECT i.information_id AS id, COALESCE(d.title,CONCAT('#',i.information_id)) AS name FROM `".DB_PREFIX."information` i LEFT JOIN `".DB_PREFIX."information_description` d ON(d.information_id=i.information_id AND d.language_id='".$languageId."') WHERE i.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('information_id=',i.information_id) AND s.keyword<>'') ORDER BY d.title ASC LIMIT ".$limit),
            'missing_seo_article'=>array('article','article_id',"SELECT a.article_id AS id, COALESCE(d.name,CONCAT('#',a.article_id)) AS name FROM `".DB_PREFIX."article` a LEFT JOIN `".DB_PREFIX."article_description` d ON(d.article_id=a.article_id AND d.language_id='".$languageId."') WHERE a.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('article_id=',a.article_id) AND s.keyword<>'') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_seo_blog_category'=>array('blog_category','blog_category_id',"SELECT bc.blog_category_id AS id, COALESCE(d.name,CONCAT('#',bc.blog_category_id)) AS name FROM `".DB_PREFIX."blog_category` bc LEFT JOIN `".DB_PREFIX."blog_category_description` d ON(d.blog_category_id=bc.blog_category_id AND d.language_id='".$languageId."') WHERE bc.status='1' AND NOT EXISTS (SELECT 1 FROM `".DB_PREFIX."seo_url` s WHERE s.store_id='0' AND s.language_id='".$languageId."' AND s.query=CONCAT('blog_category_id=',bc.blog_category_id) AND s.keyword<>'') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_meta_product'=>array('product','product_id',"SELECT p.product_id AS id, COALESCE(d.name,CONCAT('#',p.product_id)) AS name FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` d ON(d.product_id=p.product_id AND d.language_id='".$languageId."') WHERE p.status='1' AND (d.meta_title IS NULL OR TRIM(d.meta_title)='') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_meta_category'=>array('category','category_id',"SELECT c.category_id AS id, COALESCE(d.name,CONCAT('#',c.category_id)) AS name FROM `".DB_PREFIX."category` c LEFT JOIN `".DB_PREFIX."category_description` d ON(d.category_id=c.category_id AND d.language_id='".$languageId."') WHERE c.status='1' AND (d.meta_title IS NULL OR TRIM(d.meta_title)='') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_meta_manufacturer'=>array('manufacturer','manufacturer_id',"SELECT m.manufacturer_id AS id, m.name AS name FROM `".DB_PREFIX."manufacturer` m LEFT JOIN `".DB_PREFIX."manufacturer_description` d ON(d.manufacturer_id=m.manufacturer_id AND d.language_id='".$languageId."') WHERE d.meta_title IS NULL OR TRIM(d.meta_title)='' ORDER BY m.name ASC LIMIT ".$limit),
            'missing_meta_article'=>array('article','article_id',"SELECT a.article_id AS id, COALESCE(d.name,CONCAT('#',a.article_id)) AS name FROM `".DB_PREFIX."article` a LEFT JOIN `".DB_PREFIX."article_description` d ON(d.article_id=a.article_id AND d.language_id='".$languageId."') WHERE a.status='1' AND (d.meta_title IS NULL OR TRIM(d.meta_title)='') ORDER BY d.name ASC LIMIT ".$limit),
            'missing_identifier'=>array('product','product_id',"SELECT p.product_id AS id, COALESCE(d.name,CONCAT('#',p.product_id)) AS name FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` d ON(d.product_id=p.product_id AND d.language_id='".$languageId."') WHERE p.status='1' AND TRIM(COALESCE(p.upc,''))='' AND TRIM(COALESCE(p.ean,''))='' AND TRIM(COALESCE(p.mpn,''))='' ORDER BY d.name ASC LIMIT ".$limit),
            'missing_brand'=>array('product','product_id',"SELECT p.product_id AS id, COALESCE(d.name,CONCAT('#',p.product_id)) AS name FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` d ON(d.product_id=p.product_id AND d.language_id='".$languageId."') WHERE p.status='1' AND p.manufacturer_id='0' ORDER BY d.name ASC LIMIT ".$limit),
            'orphan_product'=>array('product','product_id',"SELECT p.product_id AS id, COALESCE(d.name,CONCAT('#',p.product_id)) AS name FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` d ON(d.product_id=p.product_id AND d.language_id='".$languageId."') WHERE p.status='1' AND NOT EXISTS(SELECT 1 FROM `".DB_PREFIX."product_to_category` pc WHERE pc.product_id=p.product_id) ORDER BY d.name ASC LIMIT ".$limit)
        );

        if(!isset($queries[$issue])){
            $json['error']=$this->language->get('text_invalid_issue');
            $this->response->setOutput(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            return;
        }

        try{
            list($type,$idKey,$sql)=$queries[$issue];
            $result=$this->db->query($sql);
            $rows=$result->rows;
            if(count($rows)>100){$json['limited']=true;$rows=array_slice($rows,0,100);}
            foreach($rows as $row){
                $id=(int)$row['id'];
                if($type==='product'){$route='catalog/product/edit';}
                elseif($type==='category'){$route='catalog/category/edit';}
                elseif($type==='manufacturer'){$route='catalog/manufacturer/edit';}
                elseif($type==='information'){$route='catalog/information/edit';}
                elseif($type==='blog_category'){$route='blog/category/edit';}
                else{$route='blog/article/edit';}
                $editHref = str_replace('&amp;', '&', html_entity_decode($this->url->link($route,'user_token='.$this->session->data['user_token'].'&'.$idKey.'='.$id,true), ENT_QUOTES, 'UTF-8'));
                $json['items'][]=array('id'=>$id,'name'=>(string)$row['name'],'href'=>$editHref);
            }
        }catch(\Throwable $e){
            $json['error']=$this->language->get('text_issue_load_failed');
            $this->log->write('Dashboard SEO issue list failed ['.$issue.']: '.$e->getMessage());
        }

        $this->response->setOutput(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }

    public function performanceCheck() {
        $this->load->language('extension/dashboard/codecart_health');
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $json=array();
        ob_start();
        try {
            if(!$this->user->hasPermission('access','extension/dashboard/codecart_health')){throw new \RuntimeException($this->language->get('error_permission'));}
            $url=defined('HTTP_CATALOG')?HTTP_CATALOG:'';
            if(defined('HTTPS_CATALOG')&&preg_match('#^https://#i',HTTPS_CATALOG)){$url=HTTPS_CATALOG;}
            if($url===''){throw new \RuntimeException($this->language->get('text_performance_url_missing'));}
            $result=(new \CodeCart\Core\DashboardHealth($this->registry))->runPerformanceCheck($url);
            $json['success']=true;$json['result']=$result;
        } catch(\Throwable $e) {
            $json['error']=$this->language->get('text_performance_failed');
            $this->log->write('Dashboard performance check failed: '.$e->getMessage());
        }
        $noise=(string)ob_get_clean();
        if(trim($noise)!==''){$this->log->write('Dashboard performance check output suppressed: '.substr(strip_tags($noise),0,1000));}
        $this->response->setOutput(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }

    protected function validate(){if(!$this->user->hasPermission('modify','extension/dashboard/codecart_health')){$this->error['warning']=$this->language->get('error_permission');}return !$this->error;}

    private function settingInt($key,$default=0){$v=$this->config->get($key);return ($v===null||$v==='')?(int)$default:(int)$v;}
    private function languageText($key){$this->load->language('extension/dashboard/codecart_health');return $this->language->get($key);}
    private function sectionIcon($s){$m=array('commerce'=>'fa-shopping-cart','attention'=>'fa-exclamation-triangle','system'=>'fa-heartbeat','performance'=>'fa-tachometer','seo'=>'fa-search','security'=>'fa-shield','quick'=>'fa-bolt');return $m[$s]??'fa-circle';}
    private function links(){
        $t='user_token='.$this->session->data['user_token'];
        return array(
            'products'=>$this->url->link('catalog/product',$t,true),'product_add'=>$this->url->link('catalog/product/add',$t,true),'orders'=>$this->url->link('sale/order',$t,true),'abandoned'=>$this->url->link('sale/abandoned_cart',$t,true),'customers'=>$this->url->link('customer/customer',$t,true),
            'diagnostics'=>$this->url->link('tool/codecart_core',$t,true),'schema'=>$this->url->link('tool/codecart_core',$t.'&tab=schema&schema=1',true),'runtime'=>$this->url->link('tool/codecart_core',$t.'&tab=runtime',true),'scheduler'=>$this->url->link('tool/scheduler',$t,true),'logs'=>$this->url->link('tool/log',$t,true),'modifications'=>$this->url->link('marketplace/modification',$t,true),'settings'=>$this->url->link('setting/setting',$t,true)
        );
    }
    private function attentionLink($code,$l){if(in_array($code,array('low_stock'),true))return $l['products'];if(in_array($code,array('pending_orders'),true))return $l['orders'];if(in_array($code,array('queue_failed','cron_stale'),true))return $l['scheduler'];if(in_array($code,array('schema_version','database_legacy'),true))return $l['schema'];return $l['diagnostics'];}
    private function systemLink($code,$l){
        if(in_array($code,array('database','innodb','utf8mb4','utf8mb4_columns','schema'),true))return $l['schema'];
        if(in_array($code,array('php','disk'),true))return $l['runtime'];
        if(in_array($code,array('cron','queue'),true))return $l['scheduler'];
        if(in_array($code,array('cache','opcache','mail'),true))return $l['settings'];
        return '';
    }
    private function localizedStatusRows(array $rows,$prefix){
        $out=array();
        foreach($rows as $code=>$row){
            $value=$row['value'];
            if($prefix==='security_' && is_string($value)){
                $valueKey=$prefix.'value_'.preg_replace('/[^a-z0-9]+/','_',strtolower($value));
                $translated=$this->language->get($valueKey);
                if($translated!==$valueKey){$value=$translated;}
            }
            $detail = isset($row['detail']) && is_scalar($row['detail']) ? (string)$row['detail'] : '';
            $out[]=array('code'=>$code,'label'=>$this->language->get($prefix.$code),'value'=>$value,'state'=>$row['state'],'detail'=>$detail);
        }
        return $out;
    }
    private function quickActions($links){
        $all = array(
            array('code'=>'product_add','label'=>$this->language->get('quick_product_add'),'icon'=>'fa-plus','href'=>$links['product_add'],'ajax'=>''),
            array('code'=>'orders','label'=>$this->language->get('quick_orders'),'icon'=>'fa-shopping-cart','href'=>$links['orders'],'ajax'=>''),
            array('code'=>'cache','label'=>$this->language->get('quick_cache'),'icon'=>'fa-trash-o','href'=>'#','ajax'=>'index.php?route=common/developer/allcache&user_token='.$this->session->data['user_token']),
            array('code'=>'ocmod','label'=>$this->language->get('quick_ocmod'),'icon'=>'fa-refresh','href'=>'#','ajax'=>'index.php?route=marketplace/modification/refresh&ajax=1&user_token='.$this->session->data['user_token']),
            array('code'=>'diagnostics','label'=>$this->language->get('quick_diagnostics'),'icon'=>'fa-stethoscope','href'=>$links['diagnostics'],'ajax'=>''),
            array('code'=>'scheduler','label'=>$this->language->get('quick_scheduler'),'icon'=>'fa-clock-o','href'=>$links['scheduler'],'ajax'=>''),
            array('code'=>'logs','label'=>$this->language->get('quick_logs'),'icon'=>'fa-file-text-o','href'=>$links['logs'],'ajax'=>'')
        );
        $selected=$this->config->get('dashboard_codecart_health_quick_actions');
        if(!is_array($selected)){$selected=array('product_add','orders','cache','ocmod','diagnostics','scheduler','logs');}
        return array_values(array_filter($all,function($item) use ($selected){return in_array($item['code'],$selected,true);}));
    }
    private function performanceLabelKey($key){$map=array('ttfb_ms'=>'ttfb','total_ms'=>'total','html_kb'=>'html','css_count'=>'css','js_count'=>'js','http_version'=>'http','content_encoding'=>'compression','cache_control'=>'cache');return $map[$key]??$key;}

}
