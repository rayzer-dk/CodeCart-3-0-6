<?php
class ControllerAccountGdpr extends Controller {
    private $error='';
    public function index() {
        if (!(int)$this->config->get('codecart_gdpr_status')) { $this->response->redirect($this->url->link('account/account','',true)); return; }
        if (!$this->customer->isLogged()) { $this->session->data['redirect']=$this->url->link('account/gdpr','',true); $this->response->redirect($this->url->link('account/login','',true)); return; }
        $this->load->language('account/gdpr'); $this->document->setTitle($this->language->get('heading_title')); $this->document->setRobots('noindex,nofollow');
        $manager=new \CodeCart\Core\GdprManager($this->registry);
        if (($this->request->server['REQUEST_METHOD']??'GET')==='POST') { $this->request($manager); }
        $data['breadcrumbs']=array(array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/home')),array('text'=>$this->language->get('text_account'),'href'=>$this->url->link('account/account','',true)),array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('account/gdpr','',true)));
        foreach(array('heading_title','text_intro','text_export_help','text_delete_help','text_requests','text_no_requests','text_export','text_delete','text_pending','text_confirmed','text_processed','text_superseded','column_type','column_status','column_date','column_action','button_request_export','button_request_delete','button_download','text_confirm_delete_request') as $k)$data[$k]=$this->language->get($k);
        $data['action']=$this->url->link('account/gdpr','',true); $data['download']=$this->url->link('account/gdpr/export','',true); $data['requests']=$manager->getCustomerRequests((int)$this->customer->getId());
        $data['success']=isset($this->session->data['success'])?$this->session->data['success']:''; unset($this->session->data['success']); $data['error_warning']=$this->error;
        $data['column_left']=$this->load->controller('common/column_left');$data['column_right']=$this->load->controller('common/column_right');$data['content_top']=$this->load->controller('common/content_top');$data['content_bottom']=$this->load->controller('common/content_bottom');$data['footer']=$this->load->controller('common/footer');$data['header']=$this->load->controller('common/header');
        $this->response->setOutput($this->load->view('account/gdpr',$data));
    }

    public function confirm() {
        if (!(int)$this->config->get('codecart_gdpr_status')) { $this->response->redirect($this->url->link('common/home')); return; }
        $token=isset($this->request->get['token'])?trim((string)$this->request->get['token']):''; $row=(new \CodeCart\Core\GdprManager($this->registry))->confirm($token);
        $this->load->language('account/gdpr'); $this->session->data['success']=$row?$this->language->get('text_confirm_success'):$this->language->get('error_confirm');
        $this->response->redirect($this->customer->isLogged()?$this->url->link('account/gdpr','',true):$this->url->link('account/login','',true));
    }

    public function export() {
        if (!(int)$this->config->get('codecart_gdpr_status') || !$this->customer->isLogged()) { $this->response->redirect($this->url->link('account/login','',true)); return; }
        $manager=new \CodeCart\Core\GdprManager($this->registry); $request=$manager->getConfirmedRequest((int)$this->customer->getId(),'export');
        if (!$request) { $this->response->redirect($this->url->link('account/gdpr','',true)); return; }
        $data=$manager->exportCustomer((int)$this->customer->getId()); $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($json===false) { throw new \RuntimeException('Unable to encode GDPR export.'); }
        $manager->markExportProcessed((int)$request['request_id']);
        $this->response->addHeader('Content-Type: application/json; charset=utf-8'); $this->response->addHeader('Content-Disposition: attachment; filename="personal-data-'.(int)$this->customer->getId().'-'.date('Ymd-His').'.json"'); $this->response->addHeader('Cache-Control: no-store, private'); $this->response->setOutput($json);
    }

    private function request(\CodeCart\Core\GdprManager $manager) {
        $type=isset($this->request->post['request_type'])?(string)$this->request->post['request_type']:''; if (!in_array($type,array('export','delete'),true)) { $this->error=$this->language->get('error_type'); return; }
        $guard=(new \CodeCart\Core\SpamService($this->registry))->consume('gdpr_request',5,3600,3); if (empty($guard['allowed'])) { $this->error=$this->language->get('error_rate'); return; }
        $this->load->model('account/customer'); $customer=$this->model_account_customer->getCustomer((int)$this->customer->getId()); if (!$customer || !filter_var($customer['email']??'',FILTER_VALIDATE_EMAIL)) { $this->error=$this->language->get('error_email'); return; }
        $request=$manager->createRequest((int)$this->customer->getId(),(string)$customer['email'],$type); $confirm=str_replace('&amp;','&',$this->url->link('account/gdpr/confirm','token='.$request['token'],true));
        $subject=sprintf($this->language->get('text_mail_subject'),$this->config->get('config_name')); $body=sprintf($this->language->get('text_mail_body'),$type,$confirm);
        if (!(new \CodeCart\Core\SystemMailer($this->registry))->sendText((string)$customer['email'],$subject,$body)) { $manager->cancelRequest((int)$request['request_id']); $this->error=$this->language->get('error_mail'); try{(new \CodeCart\Core\SystemNotification($this->registry))->notify('gdpr.mail','error','GDPR confirmation mail failed','A customer GDPR request could not be confirmed because the e-mail could not be sent.','tool/codecart_core');}catch(\Throwable $e){} return; }
        $this->session->data['success']=$this->language->get('text_request_sent'); $this->response->redirect($this->url->link('account/gdpr','',true));
    }
}
