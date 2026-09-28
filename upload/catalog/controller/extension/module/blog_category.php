<?php
class ControllerExtensionModuleBlogCategory extends Controller {
	public function index() {
		$this->load->language('extension/module/blog_category');
		$data['heading_title'] = $this->language->get('heading_title');

		$parts = isset($this->request->get['blog_category_id']) ? explode('_', (string)$this->request->get['blog_category_id']) : array();
		$data['blog_category_id'] = isset($parts[0]) ? (int)$parts[0] : 0;
		$data['child_id'] = isset($parts[1]) ? (int)$parts[1] : 0;
		$data['collapsed_mobile'] = $this->config->has('module_blog_category_collapsed_mobile') ? (int)$this->config->get('module_blog_category_collapsed_mobile') : 1;
		$data['show_count'] = $this->config->has('module_blog_category_show_count') ? (int)$this->config->get('module_blog_category_show_count') : (int)$this->config->get('configblog_article_count');

		$this->load->model('blog/category');
		$this->load->model('blog/article');

		$categories = $this->model_blog_category->getCategories(0);
		$children_by_parent = array();
		$count_ids = array();
		foreach ($categories as $category) {
			$category_id = (int)$category['blog_category_id'];
			$count_ids[] = $category_id;
			if ($category_id === $data['blog_category_id']) {
				$children = $this->model_blog_category->getCategories($category_id);
				$children_by_parent[$category_id] = $children;
				foreach ($children as $child) {
					$count_ids[] = (int)$child['blog_category_id'];
				}
			}
		}

		$counts = $data['show_count'] ? $this->model_blog_article->getBlogCategoryArticleCounts($count_ids, true) : array();
		$data['categories'] = array();
		foreach ($categories as $category) {
			$category_id = (int)$category['blog_category_id'];
			$children_data = array();
			if (isset($children_by_parent[$category_id])) {
				foreach ($children_by_parent[$category_id] as $child) {
					$child_id = (int)$child['blog_category_id'];
					$children_data[] = array(
						'blog_category_id' => $child_id,
						'name' => $child['name'],
						'count' => isset($counts[$child_id]) ? (int)$counts[$child_id] : 0,
						'href' => $this->url->link('blog/category', 'blog_category_id=' . $category_id . '_' . $child_id)
					);
				}
			}
			$data['categories'][] = array(
				'blog_category_id' => $category_id,
				'name' => $category['name'],
				'count' => isset($counts[$category_id]) ? (int)$counts[$category_id] : 0,
				'children' => $children_data,
				'href' => $this->url->link('blog/category', 'blog_category_id=' . $category_id)
			);
		}

		$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/blog_category', $data);
	}
}
