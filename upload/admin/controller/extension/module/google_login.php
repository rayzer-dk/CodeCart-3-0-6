<?php
class ControllerExtensionModuleGoogleLogin extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/google_login');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $post = is_array($this->request->post) ? $this->request->post : array();
            foreach (array('module_google_login_status','module_google_login_auto_register','module_google_login_show_login','module_google_login_show_checkout') as $key) {
                $post[$key] = !empty($post[$key]) ? '1' : '0';
            }
            $post['module_google_login_client_id'] = substr(trim((string)($post['module_google_login_client_id'] ?? '')), 0, 255);
            $secretInput = substr(trim((string)($post['module_google_login_client_secret'] ?? '')), 0, 255);
            if ($secretInput === '') {
                $secretInput = (string)$this->config->get('module_google_login_client_secret');
            }
            $post['module_google_login_client_secret'] = $secretInput;
            $this->model_setting_setting->editSetting('module_google_login', $post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/module/google_login', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/module/google_login','user_token='.$this->session->data['user_token'],true))
        );
        $data['action'] = $this->url->link('extension/module/google_login', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['redirect_uri'] = HTTPS_CATALOG . 'index.php?route=extension/module/google_login/callback';

        $keys = array('module_google_login_status','module_google_login_client_id','module_google_login_client_secret','module_google_login_auto_register','module_google_login_show_login','module_google_login_show_checkout');
        foreach ($keys as $key) {
            $data[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : $this->config->get($key);
            if ($key === 'module_google_login_client_secret') { $data[$key] = ''; }
        }
        $data['button_show_secret'] = $this->language->get('button_show_secret');
        $data['button_copy'] = $this->language->get('button_copy');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/google_login', $data));
    }

    public function install() {
        if (!$this->user->hasPermission('modify', 'extension/module/google_login')) { return; }
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_google_identity` (`google_identity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,`customer_id` int(11) NOT NULL,`google_sub_hash` char(64) NOT NULL,`date_added` datetime NOT NULL,`date_modified` datetime NOT NULL,PRIMARY KEY (`google_identity_id`),UNIQUE KEY `google_sub_hash` (`google_sub_hash`),UNIQUE KEY `customer_id` (`customer_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function uninstall() {
        if (!$this->user->hasPermission('modify', 'extension/module/google_login')) { return; }
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('module_google_login', 'module_google_login_status', '0');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/google_login')) { $this->error['warning'] = $this->language->get('error_permission'); }
        $enabled = !empty($this->request->post['module_google_login_status']);
        if ($enabled) {
            $id = trim((string)($this->request->post['module_google_login_client_id'] ?? ''));
            $secret = trim((string)($this->request->post['module_google_login_client_secret'] ?? ''));
            if ($secret === '') { $secret = trim((string)$this->config->get('module_google_login_client_secret')); }
            if ($id === '' || $secret === '') { $this->error['warning'] = $this->language->get('error_credentials'); }
        }
        return !$this->error;
    }
}
