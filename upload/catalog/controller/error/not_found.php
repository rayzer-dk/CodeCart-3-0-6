<?php
class ControllerErrorNotFound extends Controller {
	public function index() {
		// Errors start in Ukrainian, independent of legacy OpenCart defaults.
		// A real language selector, prefix, or saved admin preference still wins.
		require_once(DIR_SYSTEM . 'helper/error_page.php');
		$error_language_code = codecart_error_requested_language('catalog', $this->registry);
		$error_language = new Language($error_language_code);
		$error_language->load($error_language_code);
		$this->registry->set('language', $error_language);
		$this->load->language('error/not_found');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');
		$this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css?v=2.0.0');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_error'] = $this->language->get('text_error');
		$data['text_error_label'] = $this->language->get('text_error_label');
		$data['text_visual_status'] = $this->language->get('text_visual_status');
		$data['button_home'] = $this->language->get('button_home');
		$data['button_search'] = $this->language->get('button_search');
		$data['search'] = $this->url->link('product/search');
		$data['error_code'] = '404';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['route'])) {
			$url_data = $this->request->get;

			unset($url_data['_route_']);

			$route = $url_data['route'];

			unset($url_data['route']);

			$url = '';

			if ($url_data) {
				array_walk_recursive($url_data, function (&$value) {
					$value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
				});
				$url = '&' . http_build_query($url_data, '', '&');
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link($route, $url, $this->request->server['HTTPS'])
			);
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setStatusCode(404);

		$this->response->setOutput($this->load->view('error/not_found', $data));
	}
}