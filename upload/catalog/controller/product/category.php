<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductCategory extends Controller {
	public function index() {
		$this->load->language('product/category');
		$data['button_compact'] = $this->language->get('button_compact');
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');


		$data['text_empty'] = $this->language->get('text_empty');

        $disallow_params = array();

        if ($this->config->get('config_noindex_disallow_params')) {
            $params = explode ("\r\n", $this->config->get('config_noindex_disallow_params'));
            if(!empty($params)) {
                $disallow_params = $params;
            }
        }

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
            if (!in_array('sort', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
            if (!in_array('order', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit']) && (int)$this->request->get['limit'] > 0) {
			$limit = (int)$this->request->get['limit'];
            if (!in_array('limit', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['path'])) {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path . $url)
					);
				}
			}
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {

			if ($category_info['meta_title']) {
				$this->document->setTitle($category_info['meta_title']);
			} else {
				$this->document->setTitle($category_info['name']);
			}

			// ocStore compatibility: historical `noindex` field is inverted: 1 = indexing allowed, 0 = noindex.
			if ($category_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
				$this->document->setRobots('noindex,follow');
			}

			if ($category_info['meta_h1']) {
				$data['heading_title'] = $category_info['meta_h1'];
			} else {
				$data['heading_title'] = $category_info['name'];
			}

			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			$data['thumb_width'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
			$data['thumb_height'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
			if ($category_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $data['thumb_width'], $data['thumb_height']);
				$og_width = max(300, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'));
				$og_height = max(300, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
				$this->document->setOgImage($this->model_tool_image->resize($category_info['image'], $og_width, $og_height));
			} else {
				$data['thumb'] = '';
			}

			$category_context = array(
				'context_type' => 'category',
				'context_id' => $category_id,
				'context_url' => $this->url->link('product/category', 'path=' . (isset($this->request->get['path']) ? (string)$this->request->get['path'] : $category_id), true)
			);
			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			$data['description'] = $this->load->controller('common/codecart_form/shortcodes', array('html'=>$data['description'],'context'=>$category_context));

			$compatibility = $this->registry->get('codecart_compatibility_framework');
			$subcategoryContract = $compatibility ? $compatibility->apply('catalog.category_page.subcategories', array('enabled' => true, 'images' => true, 'category_id' => (int)$category_id)) : array('enabled' => true, 'images' => true);
			$data['categories'] = [];
			if (!empty($subcategoryContract['enabled'])) {
				$subcategories = $this->model_catalog_category->getCategories((int)$category_id);
				$showSubcategoryImages = !empty($subcategoryContract['images']);
				$subcategoryImageWidth = max(96, min(180, (int)$data['thumb_width']));
				$subcategoryImageHeight = max(72, min(135, (int)$data['thumb_height']));
				foreach ($subcategories as $subcategory) {
					$subcategoryThumb = '';
					if ($showSubcategoryImages) {
						$subcategoryImage = !empty($subcategory['image']) ? (string)$subcategory['image'] : 'no_image.webp';
						$subcategoryThumb = $this->model_tool_image->resize($subcategoryImage, $subcategoryImageWidth, $subcategoryImageHeight);
					}
					$subcategoryItem = array(
						'name' => $subcategory['name'],
						'thumb' => $subcategoryThumb,
						'image_width' => $subcategoryImageWidth,
						'image_height' => $subcategoryImageHeight,
						'count' => null,
						'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . (int)$subcategory['category_id'])
					);
					$data['categories'][] = $subcategoryItem;
				}
			}
			$bannerContract = $compatibility ? $compatibility->apply('catalog.category_page.banner_in_category', array('enabled' => false, 'page' => (int)$page, 'category_id' => (int)$category_id)) : array('enabled' => false);
			$data['banner_in_category'] = !empty($bannerContract['enabled']) ? $this->load->controller('extension/module/uni_banner_in_category', $category_id) : '';
			// Category purchase/content blocks are configuration templates for products in this category.
			// They are intentionally not rendered on the category page itself.

			$data['products'] = array();

			$filter_data = array(
				'filter_category_id'  => $category_id,
				'filter_sub_category' => true,
				'filter_filter'       => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);

			$card_attributes = array();
			$card_mode = (string)$this->config->get('theme_default_product_card_content');
			$data['card_content'] = in_array($card_mode, array('none','description','attributes','both'), true) ? $card_mode : 'attributes';
			$card_attribute_limit = max(1, min(10, (int)$this->config->get('theme_default_product_card_attribute_limit')));
			if (!$this->config->get('theme_default_product_card_attribute_limit')) { $card_attribute_limit = 3; }
			if (in_array($data['card_content'], array('attributes','both'), true) && $results) {
				$product_ids = array();
				foreach ($results as $card_product) { $product_ids[] = (int)$card_product['product_id']; }
				$card_attributes = $this->model_catalog_product->getProductCardAttributes($product_ids, $card_attribute_limit);
			}

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = \CodeCart\Core\TaxDisplay::primary($this->registry, $result['price'], (int)$result['tax_class_id'], isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit');
				} else {
					$price = false;
				}

				if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = \CodeCart\Core\TaxDisplay::primary($this->registry, $result['special'], (int)$result['tax_class_id'], isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit');
					$tax_price = (float)$result['special'];
				} else {
					$special = false;
					$tax_price = (float)$result['price'];
				}

				$tax = \CodeCart\Core\TaxDisplay::secondary($this->registry, $tax_price, (int)$result['tax_class_id'], isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit');

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'   => $result['product_id'],
					'attributes'   => isset($card_attributes[(int)$result['product_id']]) ? $card_attributes[(int)$result['product_id']] : array(),
					'can_buy'      => (int)$result['quantity'] > 0,
					'stock_status' => (string)$result['stock_status'],
					'thumb'        => $image,
					'image_width'  => (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
					'image_height' => (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'),
					'name'        => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')),
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'tax_label'   => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit'),
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=DESC' . $url)
			);

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

            // Canonical rules:
            // - pagination keeps its own page number;
            // - active catalog filters keep only normalized filter IDs, because a
            //   filtered result set is not equivalent to the unfiltered category;
            // - sort/order/limit are presentation controls and are intentionally
            //   excluded from canonical URLs.
            $seo_policy = (new \CodeCart\Core\SeoPolicy($this->registry))->category((int)$category_id, (array)$this->request->get);
            if (!empty($seo_policy['managed']) && !empty($seo_policy['noindex'])) {
                $this->document->setRobots('noindex,follow');
            }
            $canonical_query = 'path=' . $this->request->get['path'];
            if (!empty($seo_policy['managed'])) {
                if (!empty($seo_policy['canonical_filter'])) { $canonical_query .= '&filter=' . $seo_policy['canonical_filter']; }
                if ((int)$seo_policy['canonical_page'] > 1) { $canonical_query .= '&page=' . (int)$seo_policy['canonical_page']; }
            } else {
                $canonical_filter_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$filter)), function($filter_id) { return $filter_id > 0; })));
                if ($canonical_filter_ids) { sort($canonical_filter_ids, SORT_NUMERIC); $canonical_query .= '&filter=' . implode(',', $canonical_filter_ids); }
                if ($page > 1) { $canonical_query .= '&page=' . (int)$page; }
            }

            $this->document->addLink($this->url->link('product/category', $canonical_query), 'canonical');

			if ($this->config->get('config_codecart_structured_data_status')) {
				$category_schema_url = $this->url->link('product/category', $canonical_query);
				if (stripos((string)$this->document->getRobots(), 'noindex') === false) {
					$this->document->addStructuredData(array(
						'@type' => 'CollectionPage',
						'@id' => $category_schema_url . '#collection',
						'url' => $category_schema_url,
						'name' => (string)$category_info['name'],
						'description' => trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$category_info['description'], ENT_QUOTES, 'UTF-8'))))
					), 'category');
				}

				$schema_breadcrumbs = array();
				$schema_position = 1;
				foreach ($data['breadcrumbs'] as $breadcrumb) {
					if (!empty($breadcrumb['href'])) {
						$schema_breadcrumbs[] = array('@type' => 'ListItem', 'position' => $schema_position++, 'name' => trim(strip_tags(html_entity_decode((string)$breadcrumb['text'], ENT_QUOTES, 'UTF-8'))), 'item' => (string)$breadcrumb['href']);
					}
				}
				if ($schema_breadcrumbs) {
					$this->document->addStructuredData(array('@type' => 'BreadcrumbList', 'itemListElement' => $schema_breadcrumbs), 'breadcrumbs');
				}

				if (stripos((string)$this->document->getRobots(), 'noindex') === false && !empty($data['products'])) {
					$item_list = array();
					$position = (($page - 1) * $limit) + 1;
					foreach ($data['products'] as $product) {
						$product_schema = array(
							'@type' => 'Product',
							'@id' => html_entity_decode((string)$product['href'], ENT_QUOTES, 'UTF-8') . '#product',
							'name' => trim(strip_tags(html_entity_decode((string)$product['name'], ENT_QUOTES, 'UTF-8'))),
							'url' => html_entity_decode((string)$product['href'], ENT_QUOTES, 'UTF-8')
						);
						if (!empty($product['thumb'])) { $product_schema['image'] = html_entity_decode((string)$product['thumb'], ENT_QUOTES, 'UTF-8'); }
						$item_list[] = array('@type' => 'ListItem', 'position' => $position++, 'item' => $product_schema);
					}
					$this->document->addStructuredData(array(
						'@type' => 'ItemList',
						'@id' => $category_schema_url . '#products',
						'url' => $category_schema_url,
						'numberOfItems' => count($item_list),
						'itemListElement' => $item_list
					), 'category_products');
				}
			}


			$this->load->language('common/internal_links');
			$data['text_internal_links'] = $this->language->get('text_internal_links');
			$data['internal_links'] = (new \CodeCart\Core\InternalLinking($this->registry))->category((int)$category_id, 8);

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$data['public_documents'] = array();
            if ($this->config->get('codecart_public_documents_status')) {
                $this->load->model('catalog/public_document');
                $data['public_documents'] = $this->model_catalog_public_document->forTarget('category', (int)$category_id);
                if ($data['public_documents']) {
                    $this->load->language('common/public_document');
                    $data['text_public_documents'] = $this->language->get('text_public_documents');
                }
            }
            $this->response->setOutput($this->load->view('product/category', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->load->language('error/not_found');
			$this->document->setRobots('noindex,follow');
			$this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css');
			$data['search'] = $this->url->link('product/search');
			$data['heading_title'] = $this->language->get('heading_title');
			$data['text_error'] = $this->language->get('text_error');
			$this->response->setStatusCode(404);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
