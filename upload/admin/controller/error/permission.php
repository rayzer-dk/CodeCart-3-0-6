<?php
class ControllerErrorPermission extends Controller {
	public function index() {
		$this->load->language('error/permission');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,nofollow');
		$this->document->addStyle('view/stylesheet/codecart-error-page.css?v=2.0.0');
		$data['dashboard'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
		$data['button_dashboard'] = $this->language->get('button_dashboard');
		$data['text_error_label'] = $this->language->get('text_error_label');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link(isset($this->request->get['route']) ? $this->request->get['route'] : 'common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setStatusCode(403);
		$this->response->setOutput($this->load->view('error/permission', $data));
	}
}
