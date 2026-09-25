<?php
class ControllerExtensionCurrencyNbu extends Controller {
    private $error = array();

    public function index() {
        $data = $this->load->language('extension/currency/nbu');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('currency_nbu', $this->request->post);
            if (isset($this->request->post['config_currency_engine'])) {
                $this->model_setting_setting->editSettingValue('config', 'config_currency_engine', (string)$this->request->post['config_currency_engine']);
            }
            if (isset($this->request->post['config_currency_auto'])) {
                $this->model_setting_setting->editSettingValue('config', 'config_currency_auto', (int)$this->request->post['config_currency_auto'] ? '1' : '0');
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/currency/nbu', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/currency/nbu', 'user_token=' . $this->session->data['user_token'], true))
        );
        $data['action'] = $this->url->link('extension/currency/nbu', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true);
        $data['refresh'] = $this->url->link('localisation/currency/refresh', 'user_token=' . $this->session->data['user_token'], true);
        $data['currency_nbu_status'] = isset($this->request->post['currency_nbu_status']) ? (int)$this->request->post['currency_nbu_status'] : (int)$this->config->get('currency_nbu_status');
        $data['currency_nbu_source'] = isset($this->request->post['currency_nbu_source']) ? (string)$this->request->post['currency_nbu_source'] : (string)$this->config->get('currency_nbu_source');
        if (!in_array($data['currency_nbu_source'], array('auto', 'statdirectory', 'exchange'), true)) { $data['currency_nbu_source'] = 'auto'; }
        $data['currency_nbu_timeout'] = isset($this->request->post['currency_nbu_timeout']) ? (int)$this->request->post['currency_nbu_timeout'] : (int)$this->config->get('currency_nbu_timeout');
        if ($data['currency_nbu_timeout'] < 5 || $data['currency_nbu_timeout'] > 60) { $data['currency_nbu_timeout'] = 15; }
        $data['currency_nbu_include_disabled'] = isset($this->request->post['currency_nbu_include_disabled']) ? (int)$this->request->post['currency_nbu_include_disabled'] : (int)$this->config->get('currency_nbu_include_disabled');
        $data['currency_nbu_missing_policy'] = isset($this->request->post['currency_nbu_missing_policy']) ? (string)$this->request->post['currency_nbu_missing_policy'] : (string)$this->config->get('currency_nbu_missing_policy');
        if (!in_array($data['currency_nbu_missing_policy'], array('skip', 'error'), true)) { $data['currency_nbu_missing_policy'] = 'skip'; }
        $data['config_currency_engine'] = isset($this->request->post['config_currency_engine']) ? (string)$this->request->post['config_currency_engine'] : (string)$this->config->get('config_currency_engine');
        $data['config_currency'] = strtoupper((string)$this->config->get('config_currency'));
        $data['config_currency_auto'] = isset($this->request->post['config_currency_auto']) ? (int)$this->request->post['config_currency_auto'] : (int)$this->config->get('config_currency_auto');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/currency/nbu', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/currency/nbu')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->error) {
            $source = isset($this->request->post['currency_nbu_source']) ? (string)$this->request->post['currency_nbu_source'] : 'auto';
            if (!in_array($source, array('auto', 'statdirectory', 'exchange'), true)) {
                $this->error['warning'] = $this->language->get('error_source');
            }
        }
        if (!$this->error) {
            $timeout = isset($this->request->post['currency_nbu_timeout']) ? (int)$this->request->post['currency_nbu_timeout'] : 15;
            if ($timeout < 5 || $timeout > 60) {
                $this->error['warning'] = $this->language->get('error_timeout');
            }
        }
        if (!$this->error) {
            $policy = isset($this->request->post['currency_nbu_missing_policy']) ? (string)$this->request->post['currency_nbu_missing_policy'] : 'skip';
            if (!in_array($policy, array('skip', 'error'), true)) {
                $this->error['warning'] = $this->language->get('error_missing_policy');
            }
        }
        if (!$this->error) {
            $engine = isset($this->request->post['config_currency_engine']) ? (string)$this->request->post['config_currency_engine'] : '';
            if (!in_array($engine, array('', 'nbu', 'ecb'), true)) {
                $this->error['warning'] = $this->language->get('error_engine');
            }
        }

        return !$this->error;
    }

    public function currency($default = '') {
        $this->load->model('extension/currency/nbu');
        return $this->model_extension_currency_nbu->refresh();
    }
}
