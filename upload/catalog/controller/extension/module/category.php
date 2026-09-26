<?php
class ControllerExtensionModuleCategory extends Controller {
	public function index() {
		$this->load->language('extension/module/category');
		$data['heading_title'] = $this->language->get('heading_title');

		// UniShop2 keeps the standard OpenCart 3 category-module OCMOD contract.
		// Isolate that structure to UniShop; all other themes use CodeCart's optimized module.
		if ((string)$this->config->get('config_theme') === 'unishop2' || is_array($this->config->get('config_unishop2'))) {
			if (isset($this->request->get['path'])) {
				$parts = explode('_', (string)$this->request->get['path']);
			} else {
				$parts = array();
			}

			$data['category_id'] = isset($parts[0]) ? (int)$parts[0] : 0;
			$data['child_id'] = isset($parts[1]) ? (int)$parts[1] : 0;

			$this->load->model('catalog/category');
			$this->load->model('catalog/product');

			$data['categories'] = array();
			$categories = $this->model_catalog_category->getCategories(0);

			foreach ($categories as $category) {
				$children_data = array();

				if ($category['category_id'] == $data['category_id']) {
					$children = $this->model_catalog_category->getCategories($category['category_id']);

					foreach ($children as $child) {
						$filter_data = array('filter_category_id' => $child['category_id'], 'filter_sub_category' => true);
						$children_data[] = array(
							'category_id' => $child['category_id'],
							'name' => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
							'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
						);
					}
				}

				$filter_data = array('filter_category_id' => $category['category_id'], 'filter_sub_category' => true);
				$data['categories'][] = array(
					'category_id' => $category['category_id'],
					'name' => $category['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'children' => $children_data,
					'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}

			return $this->load->view('extension/module/category', $data);
		}

		$parts = isset($this->request->get['path']) ? explode('_', (string)$this->request->get['path']) : array();
		$data['category_id'] = isset($parts[0]) ? (int)$parts[0] : 0;
		$data['child_id'] = isset($parts[1]) ? (int)$parts[1] : 0;
		$data['collapsed_mobile'] = $this->config->has('module_category_collapsed_mobile') ? (int)$this->config->get('module_category_collapsed_mobile') : 1;
		$data['show_count'] = $this->config->has('module_category_show_count') ? (int)$this->config->get('module_category_show_count') : (int)$this->config->get('config_product_count');

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$all_categories = $this->model_catalog_category->getAllCategories();
		$categories = array();
		$children_by_parent = array();
		$count_ids = array();
		foreach ($all_categories as $category) {
			$parent_id = (int)$category['parent_id'];
			if ($parent_id === 0) {
				$categories[] = $category;
				$count_ids[] = (int)$category['category_id'];
			}
			if ($parent_id === $data['category_id']) {
				$children_by_parent[$data['category_id']][] = $category;
				$count_ids[] = (int)$category['category_id'];
			}
		}

		$counts = $data['show_count'] ? $this->model_catalog_product->getCategoryProductCounts($count_ids, true) : array();
		$data['categories'] = array();
		foreach ($categories as $category) {
			$category_id = (int)$category['category_id'];
			$children_data = array();
			if (isset($children_by_parent[$category_id])) {
				foreach ($children_by_parent[$category_id] as $child) {
					$child_id = (int)$child['category_id'];
					$children_data[] = array(
						'category_id' => $child_id,
						'name' => $child['name'],
						'count' => isset($counts[$child_id]) ? (int)$counts[$child_id] : 0,
						'href' => $this->url->link('product/category', 'path=' . $category_id . '_' . $child_id)
					);
				}
			}
			$data['categories'][] = array(
				'category_id' => $category_id,
				'name' => $category['name'],
				'count' => isset($counts[$category_id]) ? (int)$counts[$category_id] : 0,
				'children' => $children_data,
				'href' => $this->url->link('product/category', 'path=' . $category_id)
			);
		}

		$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-8');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/category', $data);
	}
}
