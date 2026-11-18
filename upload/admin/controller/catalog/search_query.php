<?php
class ControllerCatalogSearchQuery extends Controller {
	public function index() {
		$this->load->language('catalog/search_query');
		$this->document->setTitle($this->language->get('heading_title'));

		// Stores updated by file overlay may not have the table yet; creating it is idempotent.
		\CodeCart\Core\SearchStats::install($this->db);

		$this->load->model('catalog/search_query');

		\CodeCart\Core\SearchIndex::install($this->db);
		$this->load->model('setting/setting');
		$this->load->model('setting/store');
		$this->load->model('localisation/language');

		$store_id = isset($this->request->get['store_id']) ? max(0, (int)$this->request->get['store_id']) : 0;
		$stores = array(array('store_id' => 0, 'name' => (string)$this->config->get('config_name')));
		foreach ($this->model_setting_store->getStores() as $store) {
			$stores[] = array('store_id' => (int)$store['store_id'], 'name' => (string)$store['name']);
		}
		$allowed = array_column($stores, 'store_id');
		if (!in_array($store_id, $allowed, true)) {
			$store_id = 0;
		}
		$settings = $this->model_setting_setting->getSetting('codecart_search', $store_id);
		$data['search_store_id'] = $store_id;
		$data['search_stores'] = array();
		foreach ($stores as $store) {
			$data['search_stores'][] = array(
				'store_id' => $store['store_id'], 'name' => $store['name'],
				'href' => $this->url->link('catalog/search_query', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store['store_id'], true)
			);
		}
		$data['search_index_status'] = !empty($settings['codecart_search_index_status']);
		$data['search_attributes_status'] = !empty($settings['codecart_search_attributes_status']);
		$data['search_languages'] = array();
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$id = (int)$language['language_id'];
			$state = \CodeCart\Core\SearchIndex::state($this->db, $store_id, $id);
			$data['search_languages'][] = array(
				'language_id' => $id,
				'name' => (string)$language['name'],
				'synonyms' => (string)($settings['codecart_search_synonyms_' . $id] ?? ''),
				'ready' => (bool)$state['ready'],
				'indexed' => (int)$state['indexed']
			);
		}
		foreach (array('heading_search_settings', 'text_search_store', 'text_search_index', 'text_search_index_help',
			'text_search_attributes', 'text_search_attributes_help', 'text_search_synonyms', 'text_search_synonyms_help',
			'text_search_index_ready', 'text_search_index_needed', 'text_search_index_count', 'text_search_rebuild_warning',
			'button_search_save', 'button_search_rebuild', 'text_search_saved', 'text_search_permission', 'text_search_too_long') as $key) {
			$data[$key] = $this->language->get($key);
		}
		$data['search_settings_action'] = $this->url->link('catalog/search_query/saveSettings', 'user_token=' . $this->session->data['user_token'], true);
		$data['search_rebuild_action'] = str_replace('&amp;', '&', $this->url->link('catalog/search_query/rebuild', 'user_token=' . $this->session->data['user_token'], true));

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

		$data['error_warning'] = isset($this->session->data['error_warning']) ? $this->session->data['error_warning'] : '';
		unset($this->session->data['error_warning']);
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
	public function saveSettings() {
		$this->load->language('catalog/search_query');
		$token = 'user_token=' . $this->session->data['user_token'];
		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify', 'catalog/search_query')) {
			$this->session->data['error_warning'] = $this->language->get('text_search_permission');
			$this->response->redirect($this->url->link('catalog/search_query', $token, true));
			return;
		}
		$this->load->model('setting/setting');
		$this->load->model('setting/store');
		$this->load->model('localisation/language');
		$store_id = max(0, (int)($this->request->post['store_id'] ?? 0));
		$allowed = array(0);
		foreach ($this->model_setting_store->getStores() as $store) {
			$allowed[] = (int)$store['store_id'];
		}
		if (!in_array($store_id, $allowed, true)) {
			$store_id = 0;
		}
		$settings = array(
			'codecart_search_index_status' => !empty($this->request->post['index_status']) ? 1 : 0,
			'codecart_search_attributes_status' => !empty($this->request->post['attributes_status']) ? 1 : 0
		);
		$input = isset($this->request->post['synonyms']) && is_array($this->request->post['synonyms'])
			? $this->request->post['synonyms'] : array();
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$id = (int)$language['language_id'];
			$value = (string)($input[$id] ?? '');
			if (strlen($value) > 6000) {
				$this->session->data['error_warning'] = $this->language->get('text_search_too_long');
				$this->response->redirect($this->url->link('catalog/search_query', $token . '&store_id=' . $store_id, true));
				return;
			}
			$settings['codecart_search_synonyms_' . $id] = trim($value);
		}
		$this->model_setting_setting->editSetting('codecart_search', $settings, $store_id);
		$this->session->data['success'] = $this->language->get('text_search_saved');
		$this->response->redirect($this->url->link('catalog/search_query', $token . '&store_id=' . $store_id, true));
	}

	public function rebuild() {
		$this->load->language('catalog/search_query');
		$json = array();
		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify', 'catalog/search_query')) {
			$this->response->setStatusCode(403);
			$json['error'] = $this->language->get('text_search_permission');
		} else {
			$this->load->model('setting/store');
			$this->load->model('localisation/language');
			$store_id = max(0, (int)($this->request->post['store_id'] ?? 0));
			$language_id = (int)($this->request->post['language_id'] ?? 0);
			$allowed = array(0);
			foreach ($this->model_setting_store->getStores() as $store) {
				$allowed[] = (int)$store['store_id'];
			}
			$languages = array();
			foreach ($this->model_localisation_language->getLanguages() as $language) {
				$languages[] = (int)$language['language_id'];
			}
			if (!in_array($store_id, $allowed, true) || !in_array($language_id, $languages, true)) {
				$this->response->setStatusCode(400);
				$json['error'] = 'Invalid search index scope.';
			} else {
				try {
					if (($this->request->post['mode'] ?? '') === 'start') {
						\CodeCart\Core\SearchIndex::begin($this->db, $store_id, $language_id);
					}
					$state = \CodeCart\Core\SearchIndex::step($this->db, $store_id, $language_id, 100);
					$json['indexed'] = (int)$state['indexed'];
					$json['ready'] = (bool)$state['ready'];
					$json['done'] = !empty($state['done']);
				} catch (\Throwable $e) {
					$this->response->setStatusCode(500);
					$this->log->write('Search index batch failed: ' . $e->getMessage());
					$json['error'] = 'Search index rebuild failed. Check the error log.';
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->addHeader('X-Content-Type-Options: nosniff');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}

}
