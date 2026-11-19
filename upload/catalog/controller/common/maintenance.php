<?php
class ControllerCommonMaintenance extends Controller {
	public function index() {
		// Errors start in Ukrainian, independent of legacy OpenCart defaults.
		// A real language selector, prefix, or saved admin preference still wins.
		require_once(DIR_SYSTEM . 'helper/error_page.php');
		$error_language_code = codecart_error_requested_language('catalog', $this->registry);
		$error_language = new Language($error_language_code);
		$error_language->load($error_language_code);
		$this->registry->set('language', $error_language);
		// Keep the storefront language selector and navigation in the same
		// language as the error body; this affects only the current request.
		$this->load->model('localisation/language');
		$error_languages = $this->model_localisation_language->getLanguages();
		if (isset($error_languages[$error_language_code]) && !empty($error_languages[$error_language_code]['status'])) {
			$this->config->set('config_language_id', (int)$error_languages[$error_language_code]['language_id']);
		}
		$this->load->language('common/maintenance');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,nofollow');
		$this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css?v=2.0.0');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_error_label'] = $this->language->get('text_error_label');
		$data['text_visual_status'] = $this->language->get('text_visual_status');
		$data['text_error_description'] = $this->language->get('text_error_description');
		$data['button_retry'] = $this->language->get('button_retry');
		$data['continue'] = $this->url->link('common/home');

		$this->response->setStatusCode(503);

		$this->response->addHeader('Retry-After: 3600');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_maintenance'),
			'href' => $this->url->link('common/maintenance')
		);

		$data['message'] = $this->language->get('text_message');

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/maintenance', $data));
	}
}
