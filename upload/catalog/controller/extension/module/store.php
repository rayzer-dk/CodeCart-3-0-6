<?php
class ControllerExtensionModuleStore extends Controller {
	public function index() {
		$status = true;

		if ($this->config->get('module_store_admin')) {
			$this->user = new Cart\User($this->registry);

			$status = $this->user->isLogged();
		}

		if ($status) {
			$this->load->language('extension/module/store');

			$data['heading_title'] = $this->language->get('heading_title');
			$data['text_store'] = $this->language->get('text_store');
			$data['collapsed_mobile'] = $this->config->has('module_store_collapsed_mobile') ? (int)$this->config->get('module_store_collapsed_mobile') : 1;

			$data['store_id'] = $this->config->get('config_store_id');

			$data['stores'] = array();

			$data['stores'][] = array(
				'store_id' => 0,
				'name'     => $this->language->get('text_default'),
				'url'      => $this->url->link('common/home')
			);

			$this->load->model('setting/store');

			$results = $this->model_setting_store->getStores();

			foreach ($results as $result) {
				$store_base = !empty($result['ssl']) ? (string)$result['ssl'] : (string)$result['url'];
				$data['stores'][] = array(
					'store_id' => (int)$result['store_id'],
					'name'     => $result['name'],
					'url'      => rtrim($store_base, '/') . '/index.php?route=common/home'
				);
			}

			$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/store', $data);
		}
	}
}
