<?php
class ControllerToolNotification extends Controller {
    public function index() {
        $this->load->language('tool/notification');
        $this->document->setTitle($this->language->get('heading_title'));
        $service=new \CodeCart\Core\SystemNotification($this->registry);
        $data['notifications']=$service->present($service->all(200));
        $data['user_token']=$this->session->data['user_token'];
        foreach(array('heading_title','text_list','text_empty','text_read_all','column_level','column_message','column_date','column_status','column_action','text_unread','text_read','text_resolved','text_level_critical','text_level_error','text_level_warning','text_level_info','text_delete','text_clear_read','text_confirm_delete','text_confirm_clear') as $key){$data[$key]=$this->language->get($key);}
        $data['breadcrumbs']=array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('tool/notification','user_token='.$this->session->data['user_token'],true))
        );
        $data['header']=$this->load->controller('common/header');
        $data['column_left']=$this->load->controller('common/column_left');
        $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('tool/notification',$data));
    }
    public function read() {
        $json=array(); $this->load->language('tool/notification');
        if (!$this->user->hasPermission('modify','tool/notification')) $json['error']=$this->language->get('error_permission');
        else { (new \CodeCart\Core\SystemNotification($this->registry))->markRead((int)($this->request->post['notification_id']??0)); $json['success']=true; }
        $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json));
    }
    public function readAll() {
        $json=array(); $this->load->language('tool/notification');
        if (!$this->user->hasPermission('modify','tool/notification')) $json['error']=$this->language->get('error_permission');
        else { (new \CodeCart\Core\SystemNotification($this->registry))->markAllRead(); $json['success']=true; }
        $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json));
    }
    public function delete() {
        $json=array(); $this->load->language('tool/notification');
        if (!$this->user->hasPermission('modify','tool/notification')) $json['error']=$this->language->get('error_permission');
        else { (new \CodeCart\Core\SystemNotification($this->registry))->delete((int)($this->request->post['notification_id']??0)); $json['success']=true; }
        $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json));
    }
    public function clearRead() {
        $json=array(); $this->load->language('tool/notification');
        if (!$this->user->hasPermission('modify','tool/notification')) $json['error']=$this->language->get('error_permission');
        else { (new \CodeCart\Core\SystemNotification($this->registry))->deleteReadAndResolved(); $json['success']=true; }
        $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json));
    }

}
