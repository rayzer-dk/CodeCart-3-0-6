<?php
class ControllerCommonDeviceAuthorize extends Controller {
    private $error='';
    public function index() {
        if (!$this->user->isLogged()) { $this->response->redirect($this->url->link('common/login','',true)); return; }
        $this->load->language('common/device_authorize');
        $this->document->setTitle($this->language->get('heading_title'));
        $service=new \CodeCart\Core\DeviceAuthorization($this->registry);
        if (!$service->enabled('admin') || !$service->isStorageReady('admin') || $service->isTrusted('admin',(int)$this->user->getId()) || !empty($this->session->data['codecart_admin_device_bypass'])) { $this->finishRedirect(); return; }

        if (($this->request->server['REQUEST_METHOD']??'GET')==='POST') {
            $code=isset($this->request->post['code'])?trim((string)$this->request->post['code']):'';
            $result=$service->verify('admin',(int)$this->user->getId(),$code);
            if (!empty($result['success'])) { $this->finishRedirect(); return; }
            $this->error=$result['message']==='Too many invalid codes.'?$this->language->get('error_attempts'):$this->language->get('error_code');
        }

        if (!$service->hasPendingChallenge('admin',(int)$this->user->getId())) {
            $this->load->model('user/user'); $user=$this->model_user_user->getUser((int)$this->user->getId());
            $email=$user&&isset($user['email'])?(string)$user['email']:'';
            try { $challenge=$service->startChallenge('admin',(int)$this->user->getId()); } catch (\Throwable $e) { $challenge=array(); }
            $sent=false;
            if ($challenge && filter_var($email,FILTER_VALIDATE_EMAIL)) {
                $subject=sprintf($this->language->get('text_subject'),$this->config->get('config_name'));
                $body=sprintf($this->language->get('text_body'),$challenge['code'],10,$this->request->server['REMOTE_ADDR']??'');
                $sent=(new \CodeCart\Core\SystemMailer($this->registry))->sendText($email,$subject,$body);
            }
            if (!$sent) {
                $service->cancelPending('admin',(int)$this->user->getId());
                $this->session->data['codecart_admin_device_bypass']=1;
                try {(new \CodeCart\Core\SystemNotification($this->registry))->notify('security.admin_device.mail','critical',$this->language->get('notice_mail_title'),$this->language->get('notice_mail_message'),'tool/codecart_core');}catch(\Throwable $e){}
                $this->session->data['error_warning']=$this->language->get('error_mail_fail_open');
                $this->finishRedirect(); return;
            }
            $this->session->data['success']=$this->language->get('text_code_sent');
        }

        $data['heading_title']=$this->language->get('heading_title'); $data['text_instruction']=$this->language->get('text_instruction'); $data['entry_code']=$this->language->get('entry_code'); $data['button_verify']=$this->language->get('button_verify'); $data['error_warning']=$this->error; $data['action']=$this->url->link('common/device_authorize','user_token='.$this->session->data['user_token'],true); $data['logout']=$this->url->link('common/logout','user_token='.$this->session->data['user_token'],true); $data['text_logout']=$this->language->get('text_logout');
        $data['header']=$this->load->controller('common/header'); $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('common/device_authorize',$data));
    }
    private function finishRedirect() {
        if (!empty($this->session->data['codecart_admin_device_redirect']) && is_array($this->session->data['codecart_admin_device_redirect'])) {
            $r=$this->session->data['codecart_admin_device_redirect']; unset($this->session->data['codecart_admin_device_redirect']);
            $route=isset($r['route'])?(string)$r['route']:'common/dashboard'; $query=isset($r['query'])&&is_array($r['query'])?$r['query']:array(); $query['user_token']=$this->session->data['user_token'];
            $this->response->redirect($this->url->link($route,http_build_query($query),'SSL')); return;
        }
        $this->response->redirect($this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true));
    }
}
