<?php
class ControllerCommonMaintenance extends Controller {
	public function index() {
		$this->load->language('common/maintenance');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,nofollow');
		$this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css');
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
