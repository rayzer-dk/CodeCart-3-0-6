<?php
class ControllerExtensionModuleFilter extends Controller {
	public function index() {
		$parts = isset($this->request->get['path']) ? explode('_', (string)$this->request->get['path']) : array();
		$category_id = (int)end($parts);
		if ($category_id < 1) {
			return;
		}

		$this->load->model('catalog/category');
		$category_info = $this->model_catalog_category->getCategory($category_id);
		if (!$category_info) {
			return;
		}

		$this->load->language('extension/module/filter');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_reset'] = $this->language->get('button_reset');
		$data['auto_apply'] = $this->config->has('module_filter_auto_apply') ? (int)$this->config->get('module_filter_auto_apply') : 0;
		$data['show_reset'] = $this->config->has('module_filter_show_reset') ? (int)$this->config->get('module_filter_show_reset') : 1;
		$data['collapsed_mobile'] = $this->config->has('module_filter_collapsed_mobile') ? (int)$this->config->get('module_filter_collapsed_mobile') : 1;
		$data['show_count'] = $this->config->has('module_filter_show_count') ? (int)$this->config->get('module_filter_show_count') : (int)$this->config->get('config_product_count');
		$data['hide_zero'] = $this->config->has('module_filter_hide_zero') ? (int)$this->config->get('module_filter_hide_zero') : 0;

		$url = '';
		foreach (array('sort', 'order', 'limit') as $key) {
			if (isset($this->request->get[$key])) {
				$url .= '&' . $key . '=' . urlencode((string)$this->request->get[$key]);
			}
		}
		$path = isset($this->request->get['path']) ? (string)$this->request->get['path'] : '';
		$data['action'] = str_replace('&amp;', '&', $this->url->link('product/category', 'path=' . $path . $url));
		$data['reset'] = $data['action'];
		$data['filter_category'] = isset($this->request->get['filter']) ? array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$this->request->get['filter']))))) : array();

		$filter_groups = $this->model_catalog_category->getCategoryFilters($category_id);
		if (!$filter_groups) {
			return;
		}

		$filter_ids = array();
		foreach ($filter_groups as $filter_group) {
			foreach ($filter_group['filter'] as $filter) {
				$filter_ids[] = (int)$filter['filter_id'];
			}
		}
		$this->load->model('catalog/product');
		$counts = ($data['show_count'] || $data['hide_zero']) ? $this->model_catalog_product->getFilterProductCounts($category_id, $filter_ids) : array();

		$data['filter_groups'] = array();
		foreach ($filter_groups as $filter_group) {
			$filter_data = array();
			foreach ($filter_group['filter'] as $filter) {
				$filter_id = (int)$filter['filter_id'];
				$count = isset($counts[$filter_id]) ? (int)$counts[$filter_id] : 0;
				if ($data['hide_zero'] && $count < 1 && !in_array($filter_id, $data['filter_category'], true)) { continue; }
				$filter_data[] = array(
					'filter_id' => $filter_id,
					'name' => $filter['name'],
					'count' => $count
				);
			}
			if (!$filter_data) { continue; }
			$data['filter_groups'][] = array(
				'filter_group_id' => (int)$filter_group['filter_group_id'],
				'name' => $filter_group['name'],
				'filter' => $filter_data
			);
		}

		$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/filter', $data);
	}
}
