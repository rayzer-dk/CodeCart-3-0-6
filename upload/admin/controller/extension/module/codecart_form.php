<?php
class ControllerExtensionModuleCodecartForm extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/codecart_form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/module');
        $this->load->model('design/form');
        $this->load->model('localisation/language');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $payload = $this->normalizePost($this->request->post);
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('codecart_form', $payload);
            } else {
                $this->model_setting_module->editModule((int)$this->request->get['module_id'], $payload);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
        $data['error_form'] = isset($this->error['form_id']) ? $this->error['form_id'] : '';

        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token=' . $this->session->data['user_token'] . '&type=module',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/module/codecart_form','user_token=' . $this->session->data['user_token'] . (isset($this->request->get['module_id']) ? '&module_id=' . (int)$this->request->get['module_id'] : ''),true))
        );

        $data['action'] = $this->url->link('extension/module/codecart_form', 'user_token=' . $this->session->data['user_token'] . (isset($this->request->get['module_id']) ? '&module_id=' . (int)$this->request->get['module_id'] : ''), true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['forms_url'] = $this->url->link('design/form', 'user_token=' . $this->session->data['user_token'], true);

        $module_info = array();
        if (isset($this->request->get['module_id']) && $this->request->server['REQUEST_METHOD'] != 'POST') {
            $module_info = $this->model_setting_module->getModule((int)$this->request->get['module_id']);
        }
        $source = $this->request->server['REQUEST_METHOD'] == 'POST' ? $this->request->post : $module_info;
        $data['name'] = isset($source['name']) ? (string)$source['name'] : '';
        $data['form_id'] = isset($source['form_id']) ? (int)$source['form_id'] : 0;
        $data['mode'] = isset($source['mode']) && (string)$source['mode'] === 'button' ? 'button' : 'inline';
        $data['button_text'] = isset($source['button_text']) && is_array($source['button_text']) ? $source['button_text'] : array();
        $data['status'] = isset($source['status']) ? (int)$source['status'] : 0;
        $data['forms'] = $this->model_design_form->getFormOptions();
        $data['languages'] = $this->model_localisation_language->getLanguages();

        foreach (array('text_edit','text_enabled','text_disabled','text_inline','text_button','text_open_forms','entry_name','entry_form','entry_mode','entry_button_text','entry_status','help_form','help_mode','help_button_text','button_save','button_cancel') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['heading_title'] = $this->language->get('heading_title');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/codecart_form', $data));
    }

    private function normalizePost($data) {
        $out = array();
        $out['name'] = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)(isset($data['name']) ? $data['name'] : '')))), 0, 64);
        $out['form_id'] = max(0, (int)(isset($data['form_id']) ? $data['form_id'] : 0));
        $out['mode'] = isset($data['mode']) && (string)$data['mode'] === 'button' ? 'button' : 'inline';
        $out['button_text'] = array();
        foreach ((array)(isset($data['button_text']) ? $data['button_text'] : array()) as $language_id => $value) {
            $language_id = (int)$language_id;
            if ($language_id > 0) { $out['button_text'][$language_id] = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)$value))), 0, 100); }
        }
        $out['status'] = !empty($data['status']) ? 1 : 0;
        return $out;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/codecart_form')) { $this->error['warning'] = $this->language->get('error_permission'); }
        $name = isset($this->request->post['name']) ? trim((string)$this->request->post['name']) : '';
        if (utf8_strlen($name) < 3 || utf8_strlen($name) > 64) { $this->error['name'] = $this->language->get('error_name'); }
        $form_id = isset($this->request->post['form_id']) ? (int)$this->request->post['form_id'] : 0;
        if ($form_id < 1) { $this->error['form_id'] = $this->language->get('error_form'); }
        return !$this->error;
    }
}
