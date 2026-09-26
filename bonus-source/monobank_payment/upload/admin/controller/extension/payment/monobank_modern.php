<?php
class ControllerExtensionPaymentMonobankModern extends Controller {
    private $error = array();

    public function index() {
        $this->bootstrapModern();
        $this->load->language('extension/payment/monobank_modern');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $post = $this->request->post;
            $post['payment_monobank_modern_status'] = !empty($post['payment_monobank_modern_status']) ? 1 : 0;
            $post['payment_monobank_modern_debug'] = !empty($post['payment_monobank_modern_debug']) ? 1 : 0;
            $tokenInput = trim((string)($post['payment_monobank_modern_token'] ?? ''));
            if ($tokenInput === '') { $tokenInput = (string)$this->config->get('payment_monobank_modern_token'); }
            $post['payment_monobank_modern_token'] = $tokenInput;
            $this->model_setting_setting->editSetting('payment_monobank_modern', $post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment/monobank_modern', 'user_token=' . $this->session->data['user_token'], true));
        }
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        foreach (array('entry_token','entry_total','entry_geo_zone','entry_success_status','entry_processing_status','entry_failure_status','entry_status','entry_debug','entry_sort_order','help_token','help_webhook','button_save','button_cancel','button_test') as $key) { $data[$key] = $this->language->get($key); }
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=payment',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/payment/monobank_modern','user_token='.$this->session->data['user_token'],true))
        );
        $data['action'] = $this->url->link('extension/payment/monobank_modern','user_token='.$this->session->data['user_token'],true);
        $data['cancel'] = $this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=payment',true);
        $data['test_url'] = $this->url->link('extension/payment/monobank_modern/testConnection','user_token='.$this->session->data['user_token'],true);
        $data['webhook_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/monobank_modern/webhook';
        $keys = array('token'=>'','total'=>'0','geo_zone_id'=>'0','success_status_id'=>'0','processing_status_id'=>'0','failure_status_id'=>'0','status'=>'0','debug'=>'0','sort_order'=>'0');
        foreach ($keys as $suffix=>$default) {
            $field='payment_monobank_modern_'.$suffix;
            $data[$field] = isset($this->request->post[$field]) ? $this->request->post[$field] : (($this->config->get($field) !== null) ? $this->config->get($field) : $default);
            if ($suffix === 'token') { $data[$field] = ''; }
        }
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['header']=$this->load->controller('common/header'); $data['column_left']=$this->load->controller('common/column_left'); $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/payment/monobank_modern',$data));
    }

    public function install() {
        if (!$this->user->hasPermission('modify','extension/extension/payment')) { return; }
        $this->bootstrapModern(true);
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "monobank_modern_invoice` (`monobank_invoice_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `order_id` INT UNSIGNED NOT NULL, `invoice_id` VARCHAR(128) NOT NULL, `page_url` VARCHAR(2048) NOT NULL DEFAULT '', `reference` VARCHAR(128) NOT NULL, `amount` BIGINT UNSIGNED NOT NULL, `currency` CHAR(3) NOT NULL DEFAULT 'UAH', `status` VARCHAR(32) NOT NULL DEFAULT 'created', `modified_date` VARCHAR(40) NOT NULL DEFAULT '', `date_added` DATETIME NOT NULL, `date_modified` DATETIME NOT NULL, PRIMARY KEY (`monobank_invoice_id`), UNIQUE KEY `uq_invoice_id` (`invoice_id`), UNIQUE KEY `uq_order_id` (`order_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->load->model('setting/setting');
        if ($this->config->get('payment_monobank_modern_status') === null) { $this->model_setting_setting->editSetting('payment_monobank_modern', array('payment_monobank_modern_status'=>0,'payment_monobank_modern_total'=>0,'payment_monobank_modern_geo_zone_id'=>0,'payment_monobank_modern_sort_order'=>0,'payment_monobank_modern_debug'=>0)); }
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('payment_monobank_modern','payment_monobank_modern_status',0);
    }

    public function testConnection() {
        $this->bootstrapModern(true);
        $this->load->language('extension/payment/monobank_modern');
        $json=array('success'=>false);
        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify','extension/payment/monobank_modern')) { $json['error']=$this->language->get('error_permission'); }
        else {
            try {
                $token = isset($this->request->post['token']) ? trim((string)$this->request->post['token']) : '';
                if ($token === '') { $token = trim((string)$this->config->get('payment_monobank_modern_token')); }
                $api = new \CodeCart\Extension\MonobankPayment\ApiClient($token);
                $details = $api->merchantDetails();
                $json=array('success'=>true,'merchant'=>isset($details['merchantName']) ? (string)$details['merchantName'] : 'OK');
            } catch (\Throwable $e) { $json['error']=$e->getMessage(); }
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($json,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    private function bootstrapModern($refresh=false) {
        if ($refresh || !class_exists('\\CodeCart\\Extension\\MonobankPayment\\ApiClient')) { try { (new \CodeCart\Core\ModernExtensionRegistry($this->registry))->refresh(); } catch (\Throwable $e) {} }
    }
    private function validate() {
        if (!$this->user->hasPermission('modify','extension/payment/monobank_modern')) { $this->error['warning']=$this->language->get('error_permission'); }
        if (isset($this->request->post['payment_monobank_modern_status']) && $this->request->post['payment_monobank_modern_status'] && trim((string)($this->request->post['payment_monobank_modern_token'] ?? '')) === '' && trim((string)$this->config->get('payment_monobank_modern_token')) === '') { $this->error['warning']=$this->language->get('error_token'); }
        return !$this->error;
    }
}
