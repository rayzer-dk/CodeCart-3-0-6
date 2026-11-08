<?php
class ControllerAccountDeviceAuthorize extends Controller {
    private $error='';
    public function index() {
        if (!$this->customer->isLogged()) { $this->response->redirect($this->url->link('account/login','',true)); return; }
        $this->load->language('account/device_authorize'); $this->document->setTitle($this->language->get('heading_title')); $this->document->setRobots('noindex,nofollow');
        $service=new \CodeCart\Core\DeviceAuthorization($this->registry);
        if (!$service->enabled('customer') || $service->isTrusted('customer',(int)$this->customer->getId())) { $this->finishRedirect(); return; }
        if (($this->request->server['REQUEST_METHOD']??'GET')==='POST') {
            if (!codecart_csrf_validate($this->session,(string)($this->request->post['csrf_token']??''),'account_device_authorize')) {
                $this->error=$this->language->get('error_csrf');
            } else {
                $result=$service->verify('customer',(int)$this->customer->getId(),trim((string)($this->request->post['code']??'')));
                if (!empty($result['success'])) { $this->finishRedirect(); return; }
                $this->error=$result['message']==='Too many invalid codes.'?$this->language->get('error_attempts'):$this->language->get('error_code');
            }
        }
        $retry = $this->session->data['codecart_customer_device_retry'] ?? array();
        $pending = $service->hasPendingChallenge('customer',(int)$this->customer->getId());
        if (!$pending && $service->challengeLimitReached('customer',(int)$this->customer->getId())) {
            $this->error = $this->language->get('error_limit');
        } elseif (!$pending && (int)($retry['entity_id'] ?? 0) === (int)$this->customer->getId() && (int)($retry['next_at'] ?? 0) > time()) {
            if (!$this->error) { $this->error = $this->language->get('error_mail'); }
        } elseif (!$pending) {
            $this->session->data['codecart_customer_device_retry'] = array('entity_id' => (int)$this->customer->getId(), 'next_at' => time() + 60);
            $this->load->model('account/customer'); $customer=$this->model_account_customer->getCustomer((int)$this->customer->getId()); $email=$customer['email']??'';
            try {$challenge=$service->startChallenge('customer',(int)$this->customer->getId());}catch(\Throwable $e){$challenge=array();}
            $sent=false;
            if ($challenge && filter_var($email,FILTER_VALIDATE_EMAIL)) {
                $subject=sprintf($this->language->get('text_subject'),$this->config->get('config_name')); $body=sprintf($this->language->get('text_body'),$challenge['code'],10,$this->request->server['REMOTE_ADDR']??'');
                $sent=(new \CodeCart\Core\SystemMailer($this->registry))->sendText($email,$subject,$body);
            }
            if (!$sent) {
                $service->cancelPending('customer',(int)$this->customer->getId());
                try{(new \CodeCart\Core\SystemNotification($this->registry))->notify('security.customer_device.mail','error',$this->language->get('notice_mail_title'),$this->language->get('notice_mail_message'),'tool/codecart_core');}catch(\Throwable $e){}
                $this->error=$this->language->get('error_mail');
            }
        }
        $data['breadcrumbs']=array(array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/home')),array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('account/device_authorize','',true)));
        foreach(array('heading_title','text_instruction','entry_code','button_verify','text_logout') as $k)$data[$k]=$this->language->get($k); $data['error_warning']=$this->error; $data['action']=$this->url->link('account/device_authorize','',true); $data['logout']=$this->url->link('account/logout','',true);
        $data['csrf_token']=codecart_csrf_token($this->session,'account_device_authorize');
        $data['column_left']=$this->load->controller('common/column_left'); $data['column_right']=$this->load->controller('common/column_right'); $data['content_top']=$this->load->controller('common/content_top'); $data['content_bottom']=$this->load->controller('common/content_bottom'); $data['footer']=$this->load->controller('common/footer'); $data['header']=$this->load->controller('common/header');
        $this->response->setOutput($this->load->view('account/device_authorize',$data));
    }
    private function finishRedirect() {
        $r=isset($this->session->data['codecart_customer_device_redirect'])&&is_array($this->session->data['codecart_customer_device_redirect'])?$this->session->data['codecart_customer_device_redirect']:array();
        unset($this->session->data['codecart_customer_device_redirect']);
        $route=isset($r['route'])?preg_replace('/[^a-zA-Z0-9_\/]/','',(string)$r['route']):'';
        if ($route==='' || in_array($route,array('account/device_authorize','account/login','account/logout'),true)) { $route='account/account'; }
        $query=isset($r['query'])&&is_array($r['query'])?$r['query']:array(); unset($query['route']);
        array_walk_recursive($query,function (&$value) { $value=html_entity_decode((string)$value,ENT_QUOTES,'UTF-8'); });
        $this->response->redirect($this->url->link($route,http_build_query($query),true));
    }
}
