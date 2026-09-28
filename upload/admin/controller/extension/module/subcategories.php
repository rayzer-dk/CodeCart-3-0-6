<?php
class ControllerExtensionModuleSubcategories extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/subcategories');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/module');
        $this->load->model('localisation/language');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $post = $this->normalise($this->request->post);
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('subcategories', $post);
            } else {
                $this->model_setting_module->editModule((int)$this->request->get['module_id'], $post);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
            return;
        }

        foreach (array('heading_title','text_edit','text_enabled','text_disabled','text_home','text_extension','entry_name','entry_heading','entry_show_image','entry_width','entry_height','entry_limit','entry_status','help_layout','button_save','button_cancel') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
        $suffix = isset($this->request->get['module_id']) ? '&module_id=' . (int)$this->request->get['module_id'] : '';
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/module/subcategories','user_token='.$this->session->data['user_token'].$suffix,true))
        );
        $data['action'] = $this->url->link('extension/module/subcategories','user_token='.$this->session->data['user_token'].$suffix,true);
        $data['cancel'] = $this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true);

        $module = array();
        if (isset($this->request->get['module_id']) && $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $module = $this->model_setting_module->getModule((int)$this->request->get['module_id']);
        }
        $src = $this->request->server['REQUEST_METHOD'] === 'POST' ? $this->request->post : $module;
        $defaults = array('name'=>'','heading'=>array(),'show_image'=>1,'width'=>96,'height'=>72,'limit'=>20,'status'=>0);
        foreach ($defaults as $key=>$value) { $data[$key] = isset($src[$key]) ? $src[$key] : $value; }
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/subcategories', $data));
    }

    private function normalise(array $post): array {
        $post['name'] = utf8_substr(trim(strip_tags((string)($post['name'] ?? ''))), 0, 64);
        $headings = isset($post['heading']) && is_array($post['heading']) ? $post['heading'] : array();
        $post['heading'] = array();
        foreach ($headings as $language_id => $heading) {
            $language_id = (int)$language_id;
            if ($language_id > 0) $post['heading'][$language_id] = utf8_substr(trim(strip_tags((string)$heading)), 0, 120);
        }
        $post['show_image'] = !empty($post['show_image']) ? 1 : 0;
        $post['width'] = max(40, min(400, (int)($post['width'] ?? 96)));
        $post['height'] = max(40, min(400, (int)($post['height'] ?? 72)));
        $post['limit'] = max(1, min(100, (int)($post['limit'] ?? 20)));
        $post['status'] = !empty($post['status']) ? 1 : 0;
        return $post;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/subcategories')) $this->error['warning'] = $this->language->get('error_permission');
        $name = trim((string)($this->request->post['name'] ?? ''));
        if (utf8_strlen($name) < 3 || utf8_strlen($name) > 64) $this->error['name'] = $this->language->get('error_name');
        return !$this->error;
    }
}
