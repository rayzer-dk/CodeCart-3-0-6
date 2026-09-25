<?php
class ControllerExtensionFeedGoogleBase extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/feed/google_base');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('localisation/currency');

		$languages = $this->model_localisation_language->getLanguages();
		$currencies = $this->model_localisation_currency->getCurrencies();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$language = isset($this->request->post['feed_google_base_language']) ? (string)$this->request->post['feed_google_base_language'] : '';
			if (!isset($languages[$language]) || empty($languages[$language]['status'])) $language = (string)$this->config->get('config_language');
			$currency = isset($this->request->post['feed_google_base_currency']) ? strtoupper((string)$this->request->post['feed_google_base_currency']) : '';
			if (!isset($currencies[$currency]) || empty($currencies[$currency]['status'])) $currency = (string)$this->config->get('config_currency');
			$this->request->post['feed_google_base_language'] = $language;
			$this->request->post['feed_google_base_currency'] = $currency;
			$this->request->post['feed_google_base_status'] = !empty($this->request->post['feed_google_base_status']) ? 1 : 0;
			$this->model_setting_setting->editSetting('feed_google_base', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/feed/google_base', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		$data['breadcrumbs'][] = array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/feed/google_base', 'user_token=' . $this->session->data['user_token'], true));
		$data['action'] = $this->url->link('extension/feed/google_base', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);
		$data['diagnose'] = str_replace('&amp;', '&', $this->url->link('extension/feed/google_base/diagnose', 'user_token=' . $this->session->data['user_token'], true));
		$data['user_token'] = $this->session->data['user_token'];

		$data['languages'] = array();
		foreach ($languages as $code => $language_info) if (!empty($language_info['status'])) $data['languages'][$code] = $language_info;
		$data['currencies'] = array();
		foreach ($currencies as $code => $currency_info) if (!empty($currency_info['status'])) $data['currencies'][$code] = $currency_info;

		if (isset($this->request->post['feed_google_base_language'])) $data['feed_google_base_language'] = (string)$this->request->post['feed_google_base_language'];
		else $data['feed_google_base_language'] = (string)$this->config->get('feed_google_base_language');
		if ($data['feed_google_base_language'] === '' || !isset($data['languages'][$data['feed_google_base_language']])) $data['feed_google_base_language'] = (string)$this->config->get('config_language');

		if (isset($this->request->post['feed_google_base_currency'])) $data['feed_google_base_currency'] = strtoupper((string)$this->request->post['feed_google_base_currency']);
		else $data['feed_google_base_currency'] = strtoupper((string)$this->config->get('feed_google_base_currency'));
		if ($data['feed_google_base_currency'] === '' || !isset($data['currencies'][$data['feed_google_base_currency']])) $data['feed_google_base_currency'] = (string)$this->config->get('config_currency');

		$data['feed_google_base_status'] = isset($this->request->post['feed_google_base_status']) ? !empty($this->request->post['feed_google_base_status']) : (bool)$this->config->get('feed_google_base_status');
		$catalog = rtrim(HTTP_CATALOG, '/');
		$data['data_feed'] = $catalog . '/google-merchant-' . rawurlencode($data['feed_google_base_language']) . '.xml';
		$data['data_feed_fallback'] = $catalog . '/index.php?route=extension/feed/google_base&lang=' . rawurlencode($data['feed_google_base_language']);
		$data['language_feeds'] = array();
		foreach ($data['languages'] as $code => $language_info) {
			$data['language_feeds'][] = array(
				'code' => $code,
				'name' => isset($language_info['name']) ? $language_info['name'] : $code,
				'url' => $catalog . '/google-merchant-' . rawurlencode($code) . '.xml',
				'fallback' => $catalog . '/index.php?route=extension/feed/google_base&lang=' . rawurlencode($code)
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/feed/google_base', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/feed/google_base')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
		$this->load->model('extension/feed/google_base');

		$this->model_extension_feed_google_base->install();
	}

	public function uninstall() {
		$this->load->model('extension/feed/google_base');

		$this->model_extension_feed_google_base->uninstall();
	}

	public function import() {
		$this->load->language('extension/feed/google_base');

		$json = array();

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'extension/feed/google_base')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$file = isset($this->request->files['file']) && is_array($this->request->files['file']) ? $this->request->files['file'] : array();
			$check = \CodeCart\Core\UploadGuard::validateGeneric(
				$file,
				"txt",
				"text/plain\napplication/octet-stream",
				4194304,
				128
			);
			if (!$check['ok']) {
				$json['error'] = ($check['code'] === 'filesize') ? $this->language->get('error_filesize') : $this->language->get('error_filetype');
			}
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_success');

			$this->load->model('extension/feed/google_base');

			// Get the contents of the uploaded file
			$content = file_get_contents($this->request->files['file']['tmp_name']);

			$this->model_extension_feed_google_base->import($content);

			unlink($this->request->files['file']['tmp_name']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function category() {
		$this->load->language('extension/feed/google_base');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['google_base_categories'] = array();

		$limit = 10;
		$filter_data = array(
			'start'       => ($page - 1) * $limit,
			'limit'       => $limit
		);
		
		$this->load->model('extension/feed/google_base');
		$results = $this->model_extension_feed_google_base->getCategories($filter_data);

		foreach ($results as $result) {
			$data['google_base_categories'][] = array(
				'google_base_category_id' => $result['google_base_category_id'],
				'google_base_category'    => $result['google_base_category'],
				'category_id'             => $result['category_id'],
				'category'                => $result['category']
			);
		}

		$category_total = $this->model_extension_feed_google_base->getTotalCategories();

		$pagination = new Pagination();
		$pagination->total = $category_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/feed/google_base/category', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($category_total - $limit)) ? $category_total : ((($page - 1) * $limit) + $limit), $category_total, ceil($category_total / $limit));

		$this->response->setOutput($this->load->view('extension/feed/google_base_category', $data));
	}

	public function addCategory() {
		$this->load->language('extension/feed/google_base');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/google_base')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (!empty($this->request->post['google_base_category_id']) && !empty($this->request->post['category_id'])) {
			$this->load->model('extension/feed/google_base');

			$this->model_extension_feed_google_base->addCategory($this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeCategory() {
		$this->load->language('extension/feed/google_base');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/feed/google_base')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/feed/google_base');

			$this->model_extension_feed_google_base->deleteCategory($this->request->post['category_id']);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function diagnose() {
		$this->load->language('extension/feed/google_base');
		$json = array();
		if (!$this->user->hasPermission('access', 'extension/feed/google_base')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/feed/google_base');
			$this->load->model('localisation/language');
			$languages = $this->model_localisation_language->getLanguages();
			$language = (string)$this->config->get('feed_google_base_language');
			if (!isset($languages[$language])) $language = (string)$this->config->get('config_language');
			$language_id = isset($languages[$language]) ? (int)$languages[$language]['language_id'] : (int)$this->config->get('config_language_id');
			$json['data'] = $this->model_extension_feed_google_base->diagnose(0, $language_id);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('extension/feed/google_base');

			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}

			$filter_data = array(
				'filter_name' => html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'),
				'start'       => 0,
				'limit'       => $this->config->get('config_limit_autocomplete')
			);

			$results = $this->model_extension_feed_google_base->getGoogleBaseCategories($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'google_base_category_id' => $result['google_base_category_id'],
					'name'                    => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
