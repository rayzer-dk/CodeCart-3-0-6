<?php
class ControllerProductManufacturer extends Controller {
	public function index() {
		$this->load->language('product/manufacturer');
		$data['button_compact'] = $this->language->get('button_compact');
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);

		$this->load->model('catalog/manufacturer');

		$this->load->model('tool/image');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addLink($this->url->link('product/manufacturer'), 'canonical');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_brand'),
			'href' => $this->url->link('product/manufacturer')
		);

		$data['categories'] = array();

		$manufacturer_width = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_manufacturer_width');
		$manufacturer_height = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_manufacturer_height');
		$manufacturer_width = max(104, $manufacturer_width > 0 ? $manufacturer_width : 104);
		$manufacturer_height = max(104, $manufacturer_height > 0 ? $manufacturer_height : 104);

		$results = $this->model_catalog_manufacturer->getManufacturers();

		foreach ($results as $result) {
			if (is_numeric(utf8_substr($result['name'], 0, 1))) {
				$key = '0 - 9';
			} else {
				$key = utf8_substr(utf8_strtoupper($result['name']), 0, 1);
			}

			if (!isset($data['categories'][$key])) {
				$data['categories'][$key]['name'] = $key;
			}

			$thumb = '';
			if (!empty($result['image']) && is_file(DIR_IMAGE . $result['image'])) {
				$thumb = $this->model_tool_image->resize($result['image'], $manufacturer_width, $manufacturer_height);
			}

			$data['categories'][$key]['manufacturer'][] = array(
				'name'  => $result['name'],
				'thumb' => $thumb,
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $result['manufacturer_id'])
			);
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/manufacturer_list', $data));
	}

	public function info() {
		$this->load->language('product/manufacturer');
		$data['button_compact'] = $this->language->get('button_compact');

		$this->load->model('catalog/manufacturer');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		if (isset($this->request->get['manufacturer_id'])) {
			$manufacturer_id = (int)$this->request->get['manufacturer_id'];
		} else {
			$manufacturer_id = 0;
		}

        $disallow_params = array();

        if ($this->config->get('config_noindex_disallow_params')) {
            $params = explode ("\r\n", $this->config->get('config_noindex_disallow_params'));
            if(!empty($params)) {
                $disallow_params = $params;
            }
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
			$page = max(1, (int)$this->request->get['page']);
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit']) && (int)$this->request->get['limit'] > 0) {
			$limit = (int)$this->request->get['limit'];
            if (!in_array('limit', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$limit = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_brand'),
			'href' => $this->url->link('product/manufacturer')
		);

		$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);

		if ($manufacturer_info) {

			if ($manufacturer_info['meta_title']) {
				$this->document->setTitle($manufacturer_info['meta_title']);
			} else {
				$this->document->setTitle($manufacturer_info['name']);
			}

			// ocStore compatibility: historical `noindex` field is inverted: 1 = indexing allowed, 0 = noindex.
			if ($manufacturer_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
				$this->document->setRobots('noindex,follow');
			}

			if ($manufacturer_info['meta_h1']) {
				$data['heading_title'] = $manufacturer_info['meta_h1'];
			} else {
				$data['heading_title'] = $manufacturer_info['name'];
			}

			$this->document->setDescription($manufacturer_info['meta_description']);
			$this->document->setKeywords($manufacturer_info['meta_keyword']);
			$data['description'] = html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8');
			$data['description'] = $this->load->controller('common/codecart_form/shortcodes', array('html'=>$data['description'],'context'=>array('context_type'=>'manufacturer','context_id'=>(int)$manufacturer_id,'context_url'=>$this->url->link('product/manufacturer/info','manufacturer_id=' . (int)$manufacturer_id,true))));


			$data['thumb_width'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_manufacturer_width');
			$data['thumb_height'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_manufacturer_height');
			$data['product_image_width'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
			$data['product_image_height'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
			if ($manufacturer_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($manufacturer_info['image'], $data['thumb_width'], $data['thumb_height']);
				$this->document->setOgImage($this->model_tool_image->resize($manufacturer_info['image'], 500, 500));
			} else {
				$data['thumb'] = '';
			}
			$this->document->setOgType('website');

			$url = '';

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
				'text' => $manufacturer_info['name'],
				'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
			);

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			$data['compare'] = $this->url->link('product/compare');

			$data['products'] = array();

			$filter_data = array(
				'filter_manufacturer_id' => $manufacturer_id,
				'sort'                   => $sort,
				'order'                  => $order,
				'start'                  => ($page - 1) * $limit,
				'limit'                  => $limit
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
					'product_id'  => $result['product_id'],
					'attributes'   => isset($card_attributes[(int)$result['product_id']]) ? $card_attributes[(int)$result['product_id']] : array(),
					'can_buy'      => (int)$result['quantity'] > 0,
					'stock_status' => (string)$result['stock_status'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')),
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'tax_label'   => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit'),
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'manufacturer_id=' . $result['manufacturer_id'] . '&product_id=' . $result['product_id'] . $url)
				);
			}

			$url = '';

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.model&order=DESC' . $url)
			);

			$url = '';

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
					'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url . '&limit=' . $value)
				);
			}

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

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] .  $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

            // Paginated manufacturer pages use their own canonical URL.
            $canonical_query = 'manufacturer_id=' . (int)$this->request->get['manufacturer_id'];
            if ($page > 1) {
                $canonical_query .= '&page=' . (int)$page;
            }
            $manufacturer_canonical = $this->url->link('product/manufacturer/info', $canonical_query);
            $this->document->addLink($manufacturer_canonical, 'canonical');

            if ($this->config->get('config_codecart_structured_data_status') && stripos((string)$this->document->getRobots(), 'noindex') === false) {
                $brand_id = $manufacturer_canonical . '#brand';
                $brand_schema = array(
                    '@type' => 'Brand',
                    '@id' => $brand_id,
                    'name' => (string)$manufacturer_info['name'],
                    'url' => $manufacturer_canonical
                );
                if (!empty($manufacturer_info['image'])) {
                    $brand_schema['logo'] = $this->model_tool_image->resize($manufacturer_info['image'], 500, 500);
                }
                $this->document->addStructuredData($brand_schema, 'manufacturer_brand');

                $page_schema = array(
                    '@type' => 'CollectionPage',
                    '@id' => $manufacturer_canonical . '#collection',
                    'url' => $manufacturer_canonical,
                    'name' => (string)$manufacturer_info['name'],
                    'about' => array('@id' => $brand_id),
                    'mainEntity' => array('@id' => $brand_id)
                );
                $schema_description = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$manufacturer_info['description'], ENT_QUOTES, 'UTF-8'))));
                if ($schema_description !== '') {
                    $page_schema['description'] = $schema_description;
                } elseif (trim((string)$manufacturer_info['meta_description']) !== '') {
                    $page_schema['description'] = trim((string)$manufacturer_info['meta_description']);
                }
                $this->document->addStructuredData($page_schema, 'manufacturer_collection');

                $schema_breadcrumbs = array();
                $schema_position = 1;
                foreach ($data['breadcrumbs'] as $breadcrumb) {
                    if (!empty($breadcrumb['href'])) {
                        $schema_breadcrumbs[] = array(
                            '@type' => 'ListItem',
                            'position' => $schema_position++,
                            'name' => trim(strip_tags(html_entity_decode((string)$breadcrumb['text'], ENT_QUOTES, 'UTF-8'))),
                            'item' => (string)$breadcrumb['href']
                        );
                    }
                }
                if ($schema_breadcrumbs) {
                    $this->document->addStructuredData(array('@type' => 'BreadcrumbList', 'itemListElement' => $schema_breadcrumbs), 'breadcrumbs');
                }

                if (!empty($data['products'])) {
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
                        '@id' => $manufacturer_canonical . '#products',
                        'url' => $manufacturer_canonical,
                        'numberOfItems' => count($item_list),
                        'itemListElement' => $item_list
                    ), 'manufacturer_products');
                }
            }


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

			$this->response->setOutput($this->load->view('product/manufacturer_info', $data));
		} else {
			$url = '';

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
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
				'href' => $this->url->link('product/manufacturer/info', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['continue'] = $this->url->link('common/home');

			$this->load->language('error/not_found');
			$this->document->setRobots('noindex,follow');
			$this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css');
			$data['search'] = $this->url->link('product/search');
			$data['heading_title'] = $this->language->get('heading_title');
			$data['text_error'] = $this->language->get('text_error');
			$this->response->setStatusCode(404);

			$data['header'] = $this->load->controller('common/header');
			$data['footer'] = $this->load->controller('common/footer');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
