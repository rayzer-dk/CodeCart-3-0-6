<?php
class ControllerExtensionModuleInformation extends Controller {
	public function index() {
		$this->load->language('extension/module/information');

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_contact'] = $this->language->get('text_contact');
		$data['text_sitemap'] = $this->language->get('text_sitemap');
		$data['collapsed_mobile'] = $this->config->has('module_information_collapsed_mobile') ? (int)$this->config->get('module_information_collapsed_mobile') : 1;

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			$data['informations'][] = array(
				'title' => $result['title'],
				'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
			);
		}

		$data['contact'] = $this->url->link('information/contact');
		$data['sitemap'] = $this->url->link('information/sitemap');

		$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/information', $data);
	}
}