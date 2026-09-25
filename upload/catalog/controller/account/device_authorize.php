<?php
class ControllerAccountDeviceAuthorize extends Controller {
    private $error='';
    public function index() {
        if (!$this->customer->isLogged()) { $this->response->redirect($this->url->link('account/login','',true)); return; }
        $this->load->language('account/device_authorize'); $this->document->setTitle($this->language->get('heading_title')); $this->document->setRobots('noindex,nofollow');
        $service=new \CodeCart\Core\DeviceAuthorization($this->registry);
        if (!$service->enabled('customer') || $service->isTrusted('customer',(int)$this->customer->getId()) || !empty($this->session->data['codecart_customer_device_bypass'])) { $this->finishRedirect(); return; }
        if (($this->request->server['REQUEST_METHOD']??'GET')==='POST') {
            $result=$service->verify('customer',(int)$this->customer->getId(),trim((string)($this->request->post['code']??'')));
            if (!empty($result['success'])) { $this->finishRedirect(); return; }
            $this->error=$result['message']==='Too many invalid codes.'?$this->language->get('error_attempts'):$this->language->get('error_code');
        }
        if (!$service->hasPendingChallenge('customer',(int)$this->customer->getId())) {
            $this->load->model('account/customer'); $customer=$this->model_account_customer->getCustomer((int)$this->customer->getId()); $email=$customer['email']??'';
            try {$challenge=$service->startChallenge('customer',(int)$this->customer->getId());}catch(\Throwable $e){$challenge=array();}
            $sent=false;
            if ($challenge && filter_var($email,FILTER_VALIDATE_EMAIL)) {
                $subject=sprintf($this->language->get('text_subject'),$this->config->get('config_name')); $body=sprintf($this->language->get('text_body'),$challenge['code'],10,$this->request->server['REMOTE_ADDR']??'');
                $sent=(new \CodeCart\Core\SystemMailer($this->registry))->sendText($email,$subject,$body);
            }
            if (!$sent) {
                $service->cancelPending('customer',(int)$this->customer->getId()); $this->session->data['codecart_customer_device_bypass']=1;
                try{(new \CodeCart\Core\SystemNotification($this->registry))->notify('security.customer_device.mail','error','Customer device verification mail failed','The current customer session was allowed to continue because the verification e-mail could not be sent. Check mail settings.','tool/codecart_core');}catch(\Throwable $e){}
                $this->finishRedirect(); return;
            }
        }
        $data['breadcrumbs']=array(array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/home')),array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('account/device_authorize','',true)));
        foreach(array('heading_title','text_instruction','entry_code','button_verify','text_logout') as $k)$data[$k]=$this->language->get($k); $data['error_warning']=$this->error; $data['action']=$this->url->link('account/device_authorize','',true); $data['logout']=$this->url->link('account/logout','',true);
        $data['column_left']=$this->load->controller('common/column_left'); $data['column_right']=$this->load->controller('common/column_right'); $data['content_top']=$this->load->controller('common/content_top'); $data['content_bottom']=$this->load->controller('common/content_bottom'); $data['footer']=$this->load->controller('common/footer'); $data['header']=$this->load->controller('common/header');
        $this->response->setOutput($this->load->view('account/device_authorize',$data));
    }
    private function finishRedirect() {
        if (!empty($this->session->data['codecart_customer_device_redirect'])&&is_array($this->session->data['codecart_customer_device_redirect'])) {$r=$this->session->data['codecart_customer_device_redirect'];unset($this->session->data['codecart_customer_device_redirect']);$this->response->redirect($this->url->link($r['route']??'account/account',isset($r['query'])?http_build_query($r['query']):'',true));return;}
        $this->response->redirect($this->url->link('account/account','',true));
    }
}
