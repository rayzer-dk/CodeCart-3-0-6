<?php
class ControllerExtensionModulefilter extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/filter');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->request->post['module_filter_status'] = !empty($this->request->post['module_filter_status']) ? 1 : 0;
			$this->request->post['module_filter_show_count'] = !empty($this->request->post['module_filter_show_count']) ? 1 : 0;
			$this->request->post['module_filter_hide_zero'] = !empty($this->request->post['module_filter_hide_zero']) ? 1 : 0;
			$this->request->post['module_filter_auto_apply'] = !empty($this->request->post['module_filter_auto_apply']) ? 1 : 0;
			$this->request->post['module_filter_show_reset'] = !empty($this->request->post['module_filter_show_reset']) ? 1 : 0;
			$this->request->post['module_filter_collapsed_mobile'] = !empty($this->request->post['module_filter_collapsed_mobile']) ? 1 : 0;
			$this->model_setting_setting->editSetting('module_filter', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/filter', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/filter', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_filter_status'])) {
			$data['module_filter_status'] = $this->request->post['module_filter_status'];
		} else {
			$data['module_filter_status'] = $this->config->get('module_filter_status');
		}


		$filter_defaults = array(
			'module_filter_show_count' => (int)$this->config->get('config_product_count'),
			'module_filter_hide_zero' => 0,
			'module_filter_auto_apply' => 0,
			'module_filter_show_reset' => 1,
			'module_filter_collapsed_mobile' => 1
		);
		foreach ($filter_defaults as $key => $default) {
			if (isset($this->request->post[$key])) {
				$data[$key] = (int)$this->request->post[$key];
			} elseif ($this->config->has($key)) {
				$data[$key] = (int)$this->config->get($key);
			} else {
				$data[$key] = $default;
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/filter', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/filter')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}