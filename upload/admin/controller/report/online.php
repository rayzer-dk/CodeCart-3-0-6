<?php
class ControllerReportOnline extends Controller {
	public function index() {
		$this->load->language('report/online');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->get['filter_ip'])) {
			$filter_ip = $this->request->get['filter_ip'];
		} else {
			$filter_ip = '';
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}

		$filter_type = isset($this->request->get['filter_type']) ? (string)$this->request->get['filter_type'] : '';

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url = '';

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_ip'])) {
			$url .= '&filter_ip=' . $this->request->get['filter_ip'];
		}

		if ($filter_type !== '') { $url .= '&filter_type=' . urlencode($filter_type); }

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
			
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('report/online', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['refresh'] = $this->url->link('report/online', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$this->load->model('report/online');

		$data['customers'] = array();

		$filter_data = array(
			'filter_ip'       => $filter_ip,
			'filter_customer' => $filter_customer,
			'filter_type'     => $filter_type,
			'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           => $this->config->get('config_limit_admin')
		);

		$customer_total = $this->model_report_online->getTotalOnline($filter_data);

		$results = $this->model_report_online->getOnline($filter_data);

		foreach ($results as $result) {
			$user_agent = isset($result['user_agent']) ? (string)$result['user_agent'] : '';
			$is_bot = (bool)preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headless|python-requests|curl\/|wget\/|httpclient|semrush|ahrefs|mj12bot|bytespider|gptbot|chatgpt-user|claudebot|anthropic-ai|perplexitybot|googleother/i', $user_agent);
			$type = $is_bot ? 'bot' : ((int)$result['customer_id'] > 0 ? 'customer' : 'guest');
			$customer = ((int)$result['customer_id'] > 0 && trim((string)$result['customer_name']) !== '') ? (string)$result['customer_name'] : $this->language->get('text_guest');

			$data['customers'][] = array(
				'customer_id' => $result['customer_id'],
				'ip'          => $result['ip'],
				'customer'    => $customer,
				'url'         => $result['url'],
				'referer'     => $result['referer'],
				'user_agent'   => $user_agent,
				'type'         => $type,
				'type_text'    => $this->language->get('text_type_' . $type),
				'date_added'  => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'edit'        => $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id'], true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode($this->request->get['filter_customer']);
		}

		if (isset($this->request->get['filter_ip'])) {
			$url .= '&filter_ip=' . $this->request->get['filter_ip'];
		}

		if ($filter_type !== '') { $url .= '&filter_type=' . urlencode($filter_type); }

		$pagination = new Pagination();
		$pagination->total = $customer_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('report/online', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($customer_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($customer_total - $this->config->get('config_limit_admin'))) ? $customer_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $customer_total, ceil($customer_total / $this->config->get('config_limit_admin')));

		$data['filter_customer'] = $filter_customer;
		$data['filter_ip'] = $filter_ip;
		$data['filter_type'] = $filter_type;
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('report/online', $data));
	}
}