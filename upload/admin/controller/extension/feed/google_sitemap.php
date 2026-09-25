<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/feed/google_sitemap');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('localisation/language');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('feed_google_sitemap', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));
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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/google_sitemap', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/feed/google_sitemap', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);

		if (isset($this->request->post['feed_google_sitemap_status'])) {
			$data['feed_google_sitemap_status'] = $this->request->post['feed_google_sitemap_status'];
		} else {
			$data['feed_google_sitemap_status'] = $this->config->get('feed_google_sitemap_status');
		}

		$data['data_feed'] = rtrim(defined('HTTPS_CATALOG') && HTTPS_CATALOG ? HTTPS_CATALOG : HTTP_CATALOG, '/') . '/sitemap.xml';

		$data['language_sitemaps'] = array();
		$languages = $this->model_localisation_language->getLanguages();
		$default_code = (string)$this->config->get('config_language');
		$base = rtrim(defined('HTTPS_CATALOG') && HTTPS_CATALOG ? HTTPS_CATALOG : HTTP_CATALOG, '/');
		foreach ($languages as $code => $language) {
			if (empty($language['status'])) { continue; }
			$prefix = $this->resolveLanguagePrefix($language, $code, $default_code);
			$language_slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', (string)$code));
			$language_slug = trim($language_slug, '-');
			if ($language_slug === '') { $language_slug = 'default'; }
			$folder = $prefix !== '' ? '/' . rawurlencode($prefix) : '';
			$data['language_sitemaps'][] = array(
				'name' => isset($language['name']) ? (string)$language['name'] : (string)$code,
				'code' => (string)$code,
				'prefix' => $prefix,
				'products' => $base . $folder . '/sitemap-products-' . rawurlencode($language_slug) . '-1.xml'
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/google_sitemap', $data));
	}

	private function normalizeLanguagePrefix($prefix) {
		$prefix = strtolower(trim((string)$prefix, " /\t\n\r\0\x0B"));
		return ($prefix !== '' && preg_match('/^[a-z0-9][a-z0-9-]{0,31}$/', $prefix)) ? $prefix : '';
	}

	private function resolveLanguagePrefix(array $language, $code, $default_code) {
		if ($this->config->get('langdir_status')) {
			if ($this->config->get('langdir_off') && (string)$code === (string)$default_code) { return ''; }
			$dirs = $this->config->get('langdir_dir');
			if (!is_array($dirs)) { $dirs = (array)$dirs; }
			$language_id = isset($language['language_id']) ? (int)$language['language_id'] : 0;
			return $this->normalizeLanguagePrefix(isset($dirs[$language_id]) ? $dirs[$language_id] : '');
		}
		return $this->normalizeLanguagePrefix(isset($language['url_prefix']) ? $language['url_prefix'] : '');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/feed/google_sitemap')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}