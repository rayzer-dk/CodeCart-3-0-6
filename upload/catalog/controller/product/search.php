<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductSearch extends Controller {
	public function index() {
		$this->load->language('product/search');
		$data['button_compact'] = $this->language->get('button_compact');
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		if (isset($this->request->get['search'])) {
			$search = trim(preg_replace('/\s+/u', ' ', (string)$this->request->get['search']));
		} else {
			$search = '';
		}
		$search_too_short = ($search !== '' && utf8_strlen($search) < 2);

		// Tags are searched only when an explicit tag parameter is supplied.
		// Do not mirror every free-text query into tags: it makes short/noisy searches far too broad.
		if (isset($this->request->get['tag'])) {
			$tag = trim((string)$this->request->get['tag']);
		} else {
			$tag = '';
		}

		if (isset($this->request->get['description'])) {
			$description = $this->request->get['description'];
		} else {
			$description = '';
		}

		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		if (isset($this->request->get['sub_category'])) {
			$sub_category = $this->request->get['sub_category'];
		} else {
			$sub_category = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
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
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$search_view = htmlspecialchars((string)$search, ENT_QUOTES, 'UTF-8');
		$tag_view = htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8');

		if (isset($this->request->get['search'])) {
			$this->document->setTitle($this->language->get('heading_title') .  ' - ' . $search_view);
		} elseif (isset($this->request->get['tag'])) {
			$this->document->setTitle($this->language->get('heading_title') .  ' - ' . $this->language->get('heading_tag') . $tag_view);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));

		$data['search_action'] = $this->url->link('product/search');
		}
		
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['tag'])) {
			$url .= '&tag=' . urlencode(html_entity_decode(trim($this->request->get['tag']), ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['description'])) {
			$url .= '&description=' . $this->request->get['description'];
		}

		if (isset($this->request->get['category_id'])) {
			$url .= '&category_id=' . $this->request->get['category_id'];
		}

		if (isset($this->request->get['sub_category'])) {
			$url .= '&sub_category=' . $this->request->get['sub_category'];
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
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/search', $url)
		);

		if (isset($this->request->get['search'])) {
			$data['heading_title'] = $this->language->get('heading_title') .  ' - ' . $search_view;
		} else {
			$data['heading_title'] = $this->language->get('heading_title');
		}
		
		$this->document->setRobots('noindex,follow');

		$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

		$data['compare'] = $this->url->link('product/compare');

		// Unlimited-depth category selector built from one query. Product filtering itself
		// uses category_path, so sub-category search follows every descendant level.
		$data['categories'] = array();
		$all_categories = $this->model_catalog_category->getAllCategories();
		$children = array();
		foreach ($all_categories as $category_row) {
			$parent_id = (int)$category_row['parent_id'];
			if (!isset($children[$parent_id])) { $children[$parent_id] = array(); }
			$children[$parent_id][] = $category_row;
		}
		$visited = array();
		$append_categories = function($parent_id, $depth) use (&$append_categories, &$children, &$visited, &$data) {
			if ($depth > 32 || empty($children[$parent_id])) { return; }
			foreach ($children[$parent_id] as $category_row) {
				$category_id_value = (int)$category_row['category_id'];
				if (isset($visited[$category_id_value])) { continue; }
				$visited[$category_id_value] = true;
				$data['categories'][] = array(
					'category_id' => $category_id_value,
					'name' => (string)$category_row['name'],
					'depth' => max(0, (int)$depth),
					'prefix' => str_repeat('— ', max(0, (int)$depth))
				);
				$append_categories($category_id_value, $depth + 1);
			}
		};
		$append_categories(0, 0);

		$data['products'] = array();
		$data['search_too_short'] = $search_too_short;
		$data['text_search_min_length'] = $this->language->get('text_search_min_length');

		if ((isset($this->request->get['search']) || isset($this->request->get['tag'])) && !$search_too_short) {
			$filter_data = array(
				'filter_name'         => $search,
				'filter_tag'          => $tag,
				'filter_description'  => $description,
				'filter_category_id'  => $category_id,
				'filter_sub_category' => $sub_category,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => ($page - 1) * $limit,
				'limit'               => $limit
			);

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);
			$data['search_correction'] = '';
			$data['search_suggestion_href'] = '';
			if ($product_total === 0 && utf8_strlen($search) >= 2 && !isset($this->request->get['tag'])) {
				$corrected_search = $this->suggestSearchCorrection($search, $this->model_catalog_product->getSearchSuggestionTerms(400));
				if ($corrected_search !== '' && utf8_strtolower($corrected_search) !== utf8_strtolower($search)) {
					$corrected_filter = $filter_data;
					$corrected_filter['filter_name'] = $corrected_search;
					$corrected_total = $this->model_catalog_product->getTotalProducts($corrected_filter);
					if ($corrected_total > 0) {
						$data['search_correction'] = sprintf($this->language->get('text_search_suggestion'), $corrected_search);
						$suggestion_url = 'search=' . rawurlencode($corrected_search);
						if ($description) { $suggestion_url .= '&description=true'; }
						if ($category_id) { $suggestion_url .= '&category_id=' . (int)$category_id; }
						if ($sub_category) { $suggestion_url .= '&sub_category=true'; }
						$data['search_suggestion_href'] = $this->url->link('product/search', $suggestion_url);
					}
				}
			}

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
					'image_width' => (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
					'image_height'=> (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'),
					'name'        => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')),
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'tax_label'   => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit'),
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url)
				);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/search', 'sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/search', 'sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/search', 'sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/search', 'sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.model&order=DESC' . $url)
			);

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
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
					'href'  => $this->url->link('product/search', $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
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
			$pagination->url = $this->url->link('product/search', $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
				$this->load->model('account/search');

				if ($this->customer->isLogged()) {
					$customer_id = $this->customer->getId();
				} else {
					$customer_id = 0;
				}

				if (isset($this->request->server['REMOTE_ADDR'])) {
					$ip = $this->request->server['REMOTE_ADDR'];
				} else {
					$ip = '';
				}

				$search_data = array(
					'keyword'       => $search,
					'category_id'   => $category_id,
					'sub_category'  => $sub_category,
					'description'   => $description,
					'products'      => $product_total,
					'customer_id'   => $customer_id,
					'ip'            => $ip
				);

				$this->model_account_search->addSearch($search_data);
			}
		}

		$data['search'] = $search_view;
		$data['description'] = $description;
		$data['category_id'] = $category_id;
		$data['sub_category'] = $sub_category;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/search', $data));
	}

	private function suggestSearchCorrection($query, array $terms) {
		$query = trim(preg_replace('/\s+/u', ' ', (string)$query));
		if ($query === '') { return ''; }
		$vocab = array();
		foreach ($terms as $term) {
			foreach (preg_split('/[^\p{L}\p{N}]+/u', utf8_strtolower((string)$term), -1, PREG_SPLIT_NO_EMPTY) as $word) {
				$length = utf8_strlen($word);
				if ($length >= 4 && $length <= 32 && !preg_match('/\d/u', $word)) { $vocab[$word] = true; }
			}
		}
		if (!$vocab) { return ''; }
		$words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
		$changed = false;
		foreach ($words as &$word) {
			$lower = utf8_strtolower($word); $length = utf8_strlen($lower);
			if ($length < 4 || $length > 32 || preg_match('/\d/u', $lower) || isset($vocab[$lower])) { continue; }
			$max_distance = ($length >= 8) ? 2 : 1; $best = ''; $best_distance = $max_distance + 1;
			foreach ($vocab as $candidate => $_unused) {
				if (abs(utf8_strlen($candidate) - $length) > $max_distance) { continue; }
				$distance = $this->unicodeLevenshtein($lower, $candidate, $max_distance);
				if ($distance < $best_distance) { $best = $candidate; $best_distance = $distance; if ($distance === 1) { break; } }
			}
			if ($best !== '' && $best_distance <= $max_distance) { $word = $best; $changed = true; }
		}
		unset($word);
		return $changed ? implode(' ', $words) : '';
	}

	private function unicodeLevenshtein($a, $b, $cutoff = 2) {
		$left = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
		$right = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
		if (abs(count($left) - count($right)) > $cutoff) { return $cutoff + 1; }
		$previous = range(0, count($right));
		foreach ($left as $i => $char_left) {
			$current = array($i + 1); $row_min = $current[0];
			foreach ($right as $j => $char_right) {
				$current[$j + 1] = min($current[$j] + 1, $previous[$j + 1] + 1, $previous[$j] + ($char_left === $char_right ? 0 : 1));
				$row_min = min($row_min, $current[$j + 1]);
			}
			if ($row_min > $cutoff) { return $cutoff + 1; }
			$previous = $current;
		}
		return $previous[count($right)];
	}
}
