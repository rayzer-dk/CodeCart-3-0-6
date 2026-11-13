<?php
class ControllerCatalogSearchQuery extends Controller {
	public function index() {
		$this->load->language('catalog/search_query');
		$this->document->setTitle($this->language->get('heading_title'));

		// Stores updated by file overlay may not have the table yet; creating it is idempotent.
		\CodeCart\Core\SearchStats::install($this->db);

		$this->load->model('catalog/search_query');

		$days = isset($this->request->get['period']) && in_array((int)$this->request->get['period'], array(7, 30, 90), true) ? (int)$this->request->get['period'] : 30;
		$zero_only = isset($this->request->get['filter']) && $this->request->get['filter'] === 'zero';
		$page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
		$limit = 25;
		$token = 'user_token=' . $this->session->data['user_token'];

		$data['queries'] = array();
		foreach ($this->model_catalog_search_query->getQueries($days, $zero_only, ($page - 1) * $limit, $limit) as $row) {
			$data['queries'][] = array(
				'text'         => $row['query_text'],
				'searches'     => (int)$row['searches'],
				'zero_results' => (int)$row['zero_results'],
				'corrected'    => (int)$row['corrected'],
				'corrected_to' => $row['corrected_to'],
				'last_day'     => date($this->language->get('date_format_short'), strtotime($row['last_day']))
			);
		}

		$total = $this->model_catalog_search_query->getTotalQueries($days, $zero_only);
		$url = '&period=' . $days . ($zero_only ? '&filter=zero' : '');

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('catalog/search_query', $token . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? ($page - 1) * $limit + 1 : 0, min($total, $page * $limit), $total, max(1, (int)ceil($total / $limit)));

		$data['periods'] = array();
		foreach (array(7, 30, 90) as $period) {
			$data['periods'][] = array('days' => $period, 'active' => $period === $days, 'href' => $this->url->link('catalog/search_query', $token . '&period=' . $period . ($zero_only ? '&filter=zero' : ''), true));
		}

		$data['filter_all'] = $this->url->link('catalog/search_query', $token . '&period=' . $days, true);
		$data['filter_zero'] = $this->url->link('catalog/search_query', $token . '&period=' . $days . '&filter=zero', true);
		$data['zero_only'] = $zero_only;
		$data['clear'] = $this->url->link('catalog/search_query/clear', $token, true);
		$data['can_modify'] = $this->user->hasPermission('modify', 'catalog/search_query');

		$data['success'] = '';
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token, true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('catalog/search_query', $token, true))
		);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/search_query', $data));
	}

	public function clear() {
		$this->load->language('catalog/search_query');

		if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->user->hasPermission('modify', 'catalog/search_query')) {
			$this->load->model('catalog/search_query');
			$this->model_catalog_search_query->clear();
			$this->session->data['success'] = $this->language->get('text_cleared');
		}

		$this->response->redirect($this->url->link('catalog/search_query', 'user_token=' . $this->session->data['user_token'], true));
	}
}
