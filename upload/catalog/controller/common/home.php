<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		$canonical = $this->url->link('common/home');
		if ($this->config->get('config_seo_pro') && !$this->config->get('config_seopro_addslash')) {
			$canonical = rtrim($canonical, '/');
		}
		$this->document->addLink($canonical, 'canonical');

		if ($this->config->get('config_codecart_structured_data_status')) {
			$home_url = $canonical;
			$is_https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
			$schema_base = $is_https ? $this->config->get('config_ssl') : $this->config->get('config_url');
			$this->document->addStructuredData(array(
				'@type' => 'WebPage',
				'@id' => $home_url . '#webpage',
				'url' => $home_url,
				'name' => (string)$this->config->get('config_meta_title'),
				'description' => (string)$this->config->get('config_meta_description'),
				'isPartOf' => array('@id' => rtrim((string)$schema_base, '/') . '/#website')
			), 'home');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}