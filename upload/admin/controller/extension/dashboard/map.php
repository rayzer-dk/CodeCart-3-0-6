<?php
class ControllerExtensionDashboardMap extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/dashboard/map');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('dashboard_map', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true));
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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/dashboard/map', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/dashboard/map', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true);

		if (isset($this->request->post['dashboard_map_width'])) {
			$data['dashboard_map_width'] = $this->request->post['dashboard_map_width'];
		} else {
			$data['dashboard_map_width'] = $this->config->get('dashboard_map_width');
		}

		$data['columns'] = array();
		
		for ($i = 3; $i <= 12; $i++) {
			$data['columns'][] = $i;
		}
				
		if (isset($this->request->post['dashboard_map_status'])) {
			$data['dashboard_map_status'] = $this->request->post['dashboard_map_status'];
		} else {
			$data['dashboard_map_status'] = $this->config->get('dashboard_map_status');
		}

		if (isset($this->request->post['dashboard_map_sort_order'])) {
			$data['dashboard_map_sort_order'] = $this->request->post['dashboard_map_sort_order'];
		} else {
			$data['dashboard_map_sort_order'] = $this->config->get('dashboard_map_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/dashboard/map_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/dashboard/map')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
		
	public function dashboard() {
		$this->load->language('extension/dashboard/map');

		$this->document->addScript('view/javascript/map/codecart-world-map-data.js?v=3.0.6.0-map110');
		$this->document->addScript('view/javascript/map/codecart-world-map.js?v=3.0.6.0-map110');

		$data['text_map_controls'] = $this->language->get('text_map_controls');
		$data['text_zoom_in'] = $this->language->get('text_zoom_in');
		$data['text_zoom_out'] = $this->language->get('text_zoom_out');
		$data['text_map_reset'] = $this->language->get('text_map_reset');

		$data['user_token'] = $this->session->data['user_token'];
		$data['countries'] = array();
		$this->load->model('extension/dashboard/map');
		foreach ($this->model_extension_dashboard_map->getTotalOrdersByCountry() as $row) {
			$data['countries'][] = array(
				'name' => isset($row['name']) ? (string)$row['name'] : (string)$row['iso_code_2'],
				'iso_code_2' => strtoupper((string)$row['iso_code_2']),
				'total' => (int)$row['total'],
				'amount' => $this->currency->format((float)$row['amount'], $this->config->get('config_currency'))
			);
		}
		usort($data['countries'], function($a, $b) { return $b['total'] <=> $a['total']; });

		$mapValues = array();
		foreach ($data['countries'] as $country) {
			$mapValues[strtolower((string)$country['iso_code_2'])] = (int)$country['total'];
		}
		$data['map_values_json'] = json_encode($mapValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		return $this->load->view('extension/dashboard/map_info', $data);
	}

	public function map() {
		$json = array();

		$this->load->model('extension/dashboard/map');

		$results = $this->model_extension_dashboard_map->getTotalOrdersByCountry();

		foreach ($results as $result) {
			$json[strtolower($result['iso_code_2'])] = array(
				'total'  => $result['total'],
				'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency'))
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
