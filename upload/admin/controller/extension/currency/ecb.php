<?php
class ControllerExtensionCurrencyEcb extends Controller {
    private $error = array();

    public function index() {
        $data = $this->load->language('extension/currency/ecb');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('currency_ecb', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/currency/ecb', 'user_token=' . $this->session->data['user_token'], true))
        );
        $data['action'] = $this->url->link('extension/currency/ecb', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=currency', true);
        $data['refresh'] = $this->url->link('localisation/currency/refresh', 'user_token=' . $this->session->data['user_token'], true);
        $data['currency_ecb_status'] = isset($this->request->post['currency_ecb_status']) ? (int)$this->request->post['currency_ecb_status'] : (int)$this->config->get('currency_ecb_status');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/currency/ecb', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/currency/ecb')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->error && !empty($this->request->post['currency_ecb_status'])) {
            $this->load->model('localisation/currency');
            if (!$this->model_localisation_currency->getCurrencyByCode('EUR')) {
                $this->error['warning'] = $this->language->get('error_euro');
            }
        }

        return !$this->error;
    }

    public function currency($default = '') {
        $this->load->model('extension/currency/ecb');
        return $this->model_extension_currency_ecb->refresh();
    }
}
