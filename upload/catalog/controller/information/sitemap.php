<?php
class ControllerInformationSitemap extends Controller {
	public function index() {
		$this->load->language('information/sitemap');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addLink($this->url->link('information/sitemap'), 'canonical');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/sitemap')
		);

		$this->load->model('catalog/category');

		$data['categories'] = array();

		$all_categories = $this->model_catalog_category->getAllCategories();
		$categories_by_parent = array();
		foreach ($all_categories as $category) {
			$categories_by_parent[(int)$category['parent_id']][] = $category;
		}
		$categories_1 = isset($categories_by_parent[0]) ? $categories_by_parent[0] : array();

		foreach ($categories_1 as $category_1) {
			$level_2_data = array();

			$categories_2 = isset($categories_by_parent[(int)$category_1['category_id']]) ? $categories_by_parent[(int)$category_1['category_id']] : array();

			foreach ($categories_2 as $category_2) {
				$level_3_data = array();

				$categories_3 = isset($categories_by_parent[(int)$category_2['category_id']]) ? $categories_by_parent[(int)$category_2['category_id']] : array();

				foreach ($categories_3 as $category_3) {
					$level_3_data[] = array(
						'name' => $category_3['name'],
						'href' => $this->url->link('product/category', 'path=' . $category_1['category_id'] . '_' . $category_2['category_id'] . '_' . $category_3['category_id'])
					);
				}

				$level_2_data[] = array(
					'name'     => $category_2['name'],
					'children' => $level_3_data,
					'href'     => $this->url->link('product/category', 'path=' . $category_1['category_id'] . '_' . $category_2['category_id'])
				);
			}

			$data['categories'][] = array(
				'name'     => $category_1['name'],
				'children' => $level_2_data,
				'href'     => $this->url->link('product/category', 'path=' . $category_1['category_id'])
			);
		}

		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['edit'] = $this->url->link('account/edit', '', true);
		$data['password'] = $this->url->link('account/password', '', true);
		$data['address'] = $this->url->link('account/address', '', true);
		$data['history'] = $this->url->link('account/order', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['search'] = $this->url->link('product/search');
		$data['contact'] = $this->url->link('information/contact');

		$data['blog_latest'] = $this->url->link('blog/latest');
		$data['blog_categories'] = array();
		$this->load->model('blog/category');
		$blog_categories_1 = $this->model_blog_category->getCategories(0);
		foreach ($blog_categories_1 as $blog_category_1) {
			$blog_level_2 = array();
			foreach ($this->model_blog_category->getCategories($blog_category_1['blog_category_id']) as $blog_category_2) {
				$blog_level_2[] = array(
					'name' => $blog_category_2['name'],
					'href' => $this->url->link('blog/category', 'blog_category_id=' . $blog_category_2['blog_category_id'])
				);
			}
			$data['blog_categories'][] = array(
				'name' => $blog_category_1['name'],
				'href' => $this->url->link('blog/category', 'blog_category_id=' . $blog_category_1['blog_category_id']),
				'children' => $blog_level_2
			);
		}

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			$data['informations'][] = array(
				'title' => $result['title'],
				'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
			);
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/sitemap', $data));
	}
}