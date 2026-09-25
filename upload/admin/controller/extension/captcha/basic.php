<?php
class ControllerExtensionCaptchaBasic extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/captcha/basic');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('captcha_basic', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=captcha', true));
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=captcha', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/captcha/basic', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['text_local_help'] = $this->language->get('text_local_help');

		$data['action'] = $this->url->link('extension/captcha/basic', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=captcha', true);

		if (isset($this->request->post['captcha_basic_status'])) {
			$data['captcha_basic_status'] = $this->request->post['captcha_basic_status'];
		} else {
			$data['captcha_basic_status'] = $this->config->get('captcha_basic_status');
		}

		if (isset($this->request->post['captcha_basic_mode'])) {
			$data['captcha_basic_mode'] = $this->request->post['captcha_basic_mode'];
		} else {
			$data['captcha_basic_mode'] = $this->config->get('captcha_basic_mode') ?: 'adaptive';
		}

		if (isset($this->request->post['captcha_basic_min_age_ms'])) {
			$data['captcha_basic_min_age_ms'] = (int)$this->request->post['captcha_basic_min_age_ms'];
		} else {
			$data['captcha_basic_min_age_ms'] = (int)$this->config->get('captcha_basic_min_age_ms');
			if ($data['captcha_basic_min_age_ms'] < 500 || $data['captcha_basic_min_age_ms'] > 5000) { $data['captcha_basic_min_age_ms'] = 900; }
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/captcha/basic', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/captcha/basic')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$mode = isset($this->request->post['captcha_basic_mode']) ? (string)$this->request->post['captcha_basic_mode'] : 'adaptive';
		if (!in_array($mode, array('adaptive', 'visual'), true)) {
			$this->error['warning'] = $this->language->get('error_mode');
		}

		$minAge = isset($this->request->post['captcha_basic_min_age_ms']) ? (int)$this->request->post['captcha_basic_min_age_ms'] : 900;
		if ($minAge < 500 || $minAge > 5000) {
			$this->error['warning'] = $this->language->get('error_min_age');
		}

		return !$this->error;
	}
}
