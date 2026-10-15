<?php
class ControllerCatalogPublicDocument extends Controller {
    private $error = '';
    private function allowed($type) {
        return $this->user->hasPermission($type,'catalog/public_document') || $this->user->hasPermission($type,'catalog/download');
    }
    private function protectedAction() {
        if (!$this->allowed('modify')) { $this->error=$this->language->get('error_permission');return false; }
        if (($this->request->server['REQUEST_METHOD']??'')!=='POST') { $this->error=$this->language->get('error_method');return false; }
        $token=isset($this->request->get['user_token'])?(string)$this->request->get['user_token']:'';
        $session=isset($this->session->data['user_token'])?(string)$this->session->data['user_token']:'';
        if ($session==='' || !hash_equals($session,$token)) { $this->error=$this->language->get('error_permission');return false; }
        return true;
    }
    private function setup() {
        $this->load->language('catalog/public_document');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/public_document');
        // An upgrade may be an overlay without running the install/upgrade wizard.
        // Perform one idempotent DB schema activation on first authorized admin usage.
        if ($this->allowed('modify')) {
            $exists=$this->db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$this->db->escape(DB_PREFIX.'codecart_public_document')."' LIMIT 1");
            if (!$this->config->get('codecart_file_stats_status') || !$exists->num_rows || (string)$this->config->get('codecart_public_documents_schema_version') !== '2.0.8') {
                $this->model_catalog_public_document->install();
            }
        }
    }
    public function index() {
        $this->setup();
        if (!$this->allowed('access')) { $this->response->setStatusCode(403);return; }
        $page=max(1,(int)($this->request->get['page']??1));$limit=25;
        $sort=(string)($this->request->get['sort']??'date_added');
        if (!in_array($sort,array('date_added','downloads','last_download','name'),true))$sort='date_added';
        $order=strtoupper((string)($this->request->get['order']??'DESC'))==='ASC'?'ASC':'DESC';
        $filter=trim((string)($this->request->get['filter_name']??''));
        $rows=$this->model_catalog_public_document->listing(array('start'=>($page-1)*$limit,'limit'=>$limit,'sort'=>$sort,'order'=>$order,'filter_name'=>$filter));
        $total=$this->model_catalog_public_document->total($filter);
        $data=$this->load->language('catalog/public_document');
        $data['rows']=array();$token='user_token='.$this->session->data['user_token'];
        foreach ($rows as $row) {
            $row['edit']=$this->url->link('catalog/public_document/form',$token.'&document_id='.(int)$row['document_id'],true);
            $row['href']=rtrim(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG, '/') . '/index.php?route=information/public_document/download&document_id='.(int)$row['document_id'];
            $data['rows'][]=$row;
        }
        $data['filter_name']=$filter;
        $data['user_token']=$this->session->data['user_token'];
        $filter_param=$filter!==''?'&filter_name='.rawurlencode($filter):'';
        $data['sort_name']=$this->url->link('catalog/public_document',$token.$filter_param.'&sort=name&order='.($sort==='name'&&$order==='ASC'?'DESC':'ASC'),true);
        $data['sort_date']=$this->url->link('catalog/public_document',$token.$filter_param.'&sort=date_added&order='.($sort==='date_added'&&$order==='ASC'?'DESC':'ASC'),true);
        $data['sort_downloads']=$this->url->link('catalog/public_document',$token.$filter_param.'&sort=downloads&order='.($sort==='downloads'&&$order==='ASC'?'DESC':'ASC'),true);
        $data['add']=$this->url->link('catalog/public_document/form',$token,true);
        $data['delete']=$this->url->link('catalog/public_document/delete',$token,true);
        $data['paid']=$this->url->link('catalog/download',$token,true);
        $data['can_modify']=$this->allowed('modify');
        $data['dashboard_url']=$this->url->link('common/dashboard', $token, true);
        $data['current_url']=$this->url->link('catalog/public_document', $token, true);
        $data['text_home']=$this->language->get('text_home');
        $pages=new Pagination();$pages->total=$total;$pages->page=$page;$pages->limit=$limit;
        $pages->url=$this->url->link('catalog/public_document',$token.$filter_param.'&sort='.$sort.'&order='.$order.'&page={page}',true);
        $data['pagination']=$pages->render();
        $data['results']=sprintf($this->language->get('text_pagination'),$total?($page-1)*$limit+1:0,min($total,$page*$limit),$total,max(1,(int)ceil($total/$limit)));
        $data['success']=$this->session->data['success']??'';unset($this->session->data['success']);
        $data['error_warning']=$this->session->data['error_warning']??'';unset($this->session->data['error_warning']);
        $data['header']=$this->load->controller('common/header');$data['column_left']=$this->load->controller('common/column_left');$data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('catalog/public_document_list',$data));
    }
    public function form() {
        $this->setup();
        if (!$this->allowed('access')) { $this->response->setStatusCode(403);return; }
        $id=(int)($this->request->get['document_id']??0);
        $record=$id?$this->model_catalog_public_document->get($id):array();
        if ($id && !$record){$this->response->setStatusCode(404);return;}
        $data=$this->load->language('catalog/public_document');
        $data['error_warning']='';
        if (($this->request->server['REQUEST_METHOD']??'')==='POST') {
            if ($this->protectedAction()) {
                $post=$this->request->post;
                $descriptions=isset($post['document_description'])&&is_array($post['document_description'])?$post['document_description']:array();
                $clean=array();$valid=false;
                foreach ($descriptions as $lid=>$name) {
                    $title=trim(strip_tags((string)$name));
                    if (mb_strlen($title,'UTF-8')>200) $title=mb_substr($title,0,200,'UTF-8');
                    $clean[(int)$lid]=$title;if ($title!=='')$valid=true;
                }
                if (!$valid) $this->error=$this->language->get('error_title');
                // These legacy fields are intentionally absent from the compact form.
                // Editing a document must not silently detach its old category/product link.
                $kind = $record ? (string)$record['kind'] : 'other';
                $target = $record ? (string)$record['target_type'] : 'none';
                $targetId = $record ? (int)$record['target_id'] : 0;
                if (!$this->error) {
                    try {
                        $this->model_catalog_public_document->save($id,array('kind'=>$kind,'target_type'=>$target,'target_id'=>$targetId,'status'=>1,'sort_order'=>$record?(int)$record['sort_order']:0),$clean,$this->request->files['file']??null);
                        $this->session->data['success']=$this->language->get('text_success');
                        $this->response->redirect($this->url->link('catalog/public_document','user_token='.$this->session->data['user_token'],true));return;
                    }catch(\Throwable $e) { $this->log->write('Public documents save: '.$e->getMessage());$this->error=$e->getMessage(); }
                }
            }
            $data['error_warning']=$this->error;
            $record=array_merge($record,$this->request->post);
        }
        $this->load->model('localisation/language');$data['languages']=array_filter($this->model_localisation_language->getLanguages(),function($lang){return !empty($lang['status']);});
        $data['document']=$record;
        $data['titles']=isset($this->request->post['document_description'])?(array)$this->request->post['document_description']:($id?$this->model_catalog_public_document->descriptions($id):array());
        $data['edit_mode']=$id>0;
        $data['kinds']=array(); foreach (array('manual','certificate','catalogue','drawing','other') as $code) $data['kinds'][$code]=$this->language->get('kind_'.$code);
        $data['targets']=array(); foreach (array('none','product','category') as $code) $data['targets'][$code]=$this->language->get('target_'.$code);
        $data['action']=$this->url->link('catalog/public_document/form','user_token='.$this->session->data['user_token'].($id?'&document_id='.$id:''),true);
        $data['cancel']=$this->url->link('catalog/public_document','user_token='.$this->session->data['user_token'],true);
        $data['dashboard_url']=$this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['text_home']=$this->language->get('text_home');
        $data['can_modify']=$this->allowed('modify');
        $data['user_token']=$this->session->data['user_token'];
        $data['target_name']='';
        $type=(string)($record['target_type']??'none');$targetId=(int)($record['target_id']??0);
        if ($targetId>0 && in_array($type,array('product','category'),true)) {
            $this->load->model('catalog/'.$type);
            $model=$type==='product'?$this->model_catalog_product:$this->model_catalog_category;
            $entry=$type==='product'?$model->getProduct($targetId):$model->getCategory($targetId);
            if ($entry && isset($entry['name']))$data['target_name']=strip_tags(html_entity_decode((string)$entry['name'],ENT_QUOTES,'UTF-8'));
        }
        $data['header']=$this->load->controller('common/header');$data['column_left']=$this->load->controller('common/column_left');$data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('catalog/public_document_form',$data));
    }
    public function autocomplete() {
        $this->load->language('catalog/public_document');
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        if (!$this->allowed('access') && !$this->user->hasPermission('access','catalog/product')) {
            $this->response->setStatusCode(403);
            $this->response->setOutput('[]');return;
        }
        if (!$this->config->get('codecart_file_stats_status')) { $this->response->setOutput('[]');return; }
        $this->load->model('catalog/public_document');
        $rows=$this->model_catalog_public_document->autocomplete((string)($this->request->get['filter_name']??''));
        $this->response->setOutput(json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    public function delete() {
        $this->setup();
        if (!$this->protectedAction()) {$this->session->data['error_warning']=$this->error;}
        else {
            $ids=(array)($this->request->post['selected']??array());
            $this->model_catalog_public_document->delete(array_map('intval',$ids));
            $this->session->data['success']=$this->language->get('text_success');
        }
        $this->response->redirect($this->url->link('catalog/public_document','user_token='.$this->session->data['user_token'],true));
    }
}
