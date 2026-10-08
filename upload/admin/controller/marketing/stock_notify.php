<?php
class ControllerMarketingStockNotify extends Controller {
    public function index() {
        $this->load->language('marketing/stock_notify');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('marketing/stock_notify');
        $this->load->model('setting/store');

        $filter_product = isset($this->request->get['filter_product']) ? trim((string)$this->request->get['filter_product']) : '';
        $filter_email = isset($this->request->get['filter_email']) ? trim((string)$this->request->get['filter_email']) : '';
        $filter_status = isset($this->request->get['filter_status']) ? (string)$this->request->get['filter_status'] : '';
        $filter_store_id = isset($this->request->get['filter_store_id']) ? (string)$this->request->get['filter_store_id'] : '';
        $sort = isset($this->request->get['sort']) ? (string)$this->request->get['sort'] : 'sn.date_added';
        $order = isset($this->request->get['order']) && strtoupper((string)$this->request->get['order']) === 'ASC' ? 'ASC' : 'DESC';
        $page = max(1, isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1);
        $limit = isset($this->request->get['limit']) ? max(10, min(200, (int)$this->request->get['limit'])) : 25;
        $filter = array('filter_product'=>$filter_product,'filter_email'=>$filter_email,'filter_status'=>$filter_status,'filter_store_id'=>$filter_store_id,'sort'=>$sort,'order'=>$order,'start'=>($page-1)*$limit,'limit'=>$limit);

        $data['rows'] = $this->model_marketing_stock_notify->getRows($filter);
        $data['total'] = $this->model_marketing_stock_notify->getTotal($filter);
        $data['heading_title'] = $this->language->get('heading_title');
        foreach (array('column_product','column_email','column_status','column_store','column_date','column_sent','text_no_results','text_all','text_waiting','text_processing','text_sent','text_failed','button_filter','button_delete','button_retry','entry_product','entry_email','entry_status','entry_store','entry_limit','text_confirm','text_main_store','help_subscriptions') as $k) { $data[$k]=$this->language->get($k); }
        $data['user_token'] = $this->session->data['user_token'];
        $data['filter_product']=$filter_product; $data['filter_email']=$filter_email; $data['filter_status']=$filter_status; $data['filter_store_id']=$filter_store_id; $data['limit']=$limit; $data['sort']=$sort; $data['order']=$order;
        $data['stores'] = $this->model_setting_store->getStores();
        $data['delete'] = $this->url->link('marketing/stock_notify/delete','user_token='.$data['user_token'],true);
        $data['retry'] = $this->url->link('marketing/stock_notify/retry','user_token='.$data['user_token'],true);
        $data['breadcrumbs']=array(array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$data['user_token'],true)),array('text'=>$data['heading_title'],'href'=>$this->url->link('marketing/stock_notify','user_token='.$data['user_token'],true)));

        $url=''; foreach(array('filter_product','filter_email','filter_status','filter_store_id','limit') as $key){ if(isset($this->request->get[$key]) && $this->request->get[$key] !== ''){$url.='&'.$key.'='.urlencode((string)$this->request->get[$key]);} }
        $toggle = $order === 'ASC' ? 'DESC' : 'ASC';
        foreach(array('product'=>'pd.name','email'=>'sn.email','status'=>'sn.status','store'=>'sn.store_id','date'=>'sn.date_added','sent'=>'sn.date_sent') as $key=>$field){ $data['sort_'.$key]=$this->url->link('marketing/stock_notify','user_token='.$data['user_token'].$url.'&sort='.$field.'&order='.$toggle,true); }
        $pagination=new Pagination(); $pagination->total=$data['total']; $pagination->page=$page; $pagination->limit=$limit; $pagination->url=$this->url->link('marketing/stock_notify','user_token='.$data['user_token'].$url.'&sort='.urlencode($sort).'&order='.$order.'&page={page}',true); $data['pagination']=$pagination->render();
        $data['results']=sprintf($this->language->get('text_pagination'),$data['total']?($page-1)*$limit+1:0,min($page*$limit,$data['total']),$data['total'],max(1,(int)ceil($data['total']/$limit)));
        $data['success']=isset($this->session->data['success'])?$this->session->data['success']:''; unset($this->session->data['success']);
        $data['header']=$this->load->controller('common/header'); $data['column_left']=$this->load->controller('common/column_left'); $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('marketing/stock_notify',$data));
    }

    public function delete() { $this->mutate('delete'); }
    public function retry() { $this->mutate('retry'); }
    private function mutate($action) {
        $this->load->language('marketing/stock_notify');
        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify','marketing/stock_notify')) { $this->session->data['error_warning']=$this->language->get('error_permission'); }
        else { $this->load->model('marketing/stock_notify'); $ids=isset($this->request->post['selected'])?(array)$this->request->post['selected']:array(); if($action==='delete'){$this->model_marketing_stock_notify->deleteIds($ids);}else{$this->model_marketing_stock_notify->retryIds($ids);} $this->session->data['success']=$this->language->get('text_success'); }
        $this->response->redirect($this->url->link('marketing/stock_notify','user_token='.$this->session->data['user_token'],true));
    }
}
