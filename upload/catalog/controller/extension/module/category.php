<?php
class ControllerExtensionModuleCategory extends Controller {
	public function index() {
		$this->load->language('extension/module/category');
		$data['heading_title'] = $this->language->get('heading_title');

		$parts = isset($this->request->get['path']) ? explode('_', (string)$this->request->get['path']) : array();
		$data['category_id'] = isset($parts[0]) ? (int)$parts[0] : 0;
		$data['child_id'] = isset($parts[1]) ? (int)$parts[1] : 0;
		$data['collapsed_mobile'] = $this->config->has('module_category_collapsed_mobile') ? (int)$this->config->get('module_category_collapsed_mobile') : 1;
		$data['show_count'] = $this->config->has('module_category_show_count') ? (int)$this->config->get('module_category_show_count') : (int)$this->config->get('config_product_count');

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$compatibility = $this->registry->get('codecart_compatibility_framework');
		$treeContract = $compatibility ? $compatibility->apply('catalog.category_module.full_tree', array('value' => false)) : array('value' => false);
		$uniFullTree = !empty($treeContract['value']);

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
			if (($uniFullTree && $parent_id > 0) || (!$uniFullTree && $parent_id === $data['category_id'])) {
				$children_by_parent[$parent_id][] = $category;
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

		$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/category', $data);
	}
}
