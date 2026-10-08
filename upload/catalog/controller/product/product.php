<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductProduct extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('product/product');
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$this->load->model('catalog/category');

		if (isset($this->request->get['path'])) {
			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = $path_id;
				} else {
					$path .= '_' . $path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path)
					);
				}
			}

			// Set the last category breadcrumb
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
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
					'text' => $category_info['name'],
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url)
				);
			}
		}

		$this->load->model('catalog/manufacturer');

		if (isset($this->request->get['manufacturer_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_brand'),
				'href' => $this->url->link('product/manufacturer')
			);

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

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

			if ($manufacturer_info) {
				$data['breadcrumbs'][] = array(
					'text' => $manufacturer_info['name'],
					'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
				);
			}
		}

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
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
				'text' => $this->language->get('text_search'),
				'href' => $this->url->link('product/search', $url)
			);
		}

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		$product_tax_display_mode = ($product_info && isset($product_info['tax_display_mode'])) ? (string)$product_info['tax_display_mode'] : 'inherit';
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language, $product_tax_display_mode);

		//check product page open from cateory page
		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
						
			if(empty($this->model_catalog_product->checkProductCategory($product_id, $parts))) {
				$product_info = array();
			}
		}

		//check product page open from manufacturer page
		if (isset($this->request->get['manufacturer_id']) && !empty($product_info)) {
			if($product_info['manufacturer_id'] !=  $this->request->get['manufacturer_id']) {
				$product_info = array();
			}
		}

		if ($product_info) {
			$data['purchase_blocks'] = array();
			$product_context = array(
				'context_type' => 'product',
				'context_id' => $product_id,
				'context_url' => $this->url->link('product/product', 'product_id=' . $product_id, true)
			);
			// Forms on product pages are rendered only from explicit purchase blocks or layout modules.
			if ($this->config->get('theme_default_purchase_blocks_status')) {
				$data['purchase_blocks'] = $this->model_catalog_product->getResolvedPurchaseBlocks($product_id, (int)$this->config->get('config_language_id'));
				foreach ($data['purchase_blocks'] as &$purchase_block) {
					if (isset($purchase_block['type']) && $purchase_block['type'] === 'form' && !empty($purchase_block['form_id'])) {
						$form_settings = $product_context;
						$form_settings['form_id'] = (int)$purchase_block['form_id'];
						$form_settings['mode'] = isset($purchase_block['display']) ? (string)$purchase_block['display'] : 'inline';
						$form_settings['button_text'] = isset($purchase_block['button_text']) ? (string)$purchase_block['button_text'] : '';
						$purchase_block['form_html'] = $this->load->controller('common/codecart_form', $form_settings);
					} elseif (isset($purchase_block['type']) && $purchase_block['type'] === 'info' && !empty($purchase_block['content'])) {
						$purchase_block['content'] = $this->load->controller('common/codecart_form/shortcodes', array('html'=>$purchase_block['content'],'context'=>$product_context));
					}
				}
				unset($purchase_block);
			}

			// Render through OpenCart's view loader instead of a Twig include. This keeps
			// the partial compatible with theme/event resolution and avoids a second
			// Twig-loader namespace dependency on production stores.
			$data['purchase_blocks_html'] = '';
			if (!empty($data['purchase_blocks'])) {
				$data['purchase_blocks_html'] = $this->load->view('common/codecart_purchase_blocks', array('purchase_blocks' => $data['purchase_blocks']));
			}

			// CodeCart: keep a lightweight session-only recently viewed history.
			$recently_viewed = isset($this->session->data['codecart_recently_viewed']) && is_array($this->session->data['codecart_recently_viewed']) ? $this->session->data['codecart_recently_viewed'] : array();
			$recently_viewed = array_values(array_unique(array_filter(array_map('intval', $recently_viewed))));
			$recently_viewed = array_values(array_diff($recently_viewed, array($product_id)));
			array_unshift($recently_viewed, $product_id);
			$this->session->data['codecart_recently_viewed'] = array_slice($recently_viewed, 0, 50);
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
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
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', $url . '&product_id=' . $this->request->get['product_id'])
			);

			if ($product_info['meta_title']) {
				$this->document->setTitle($product_info['meta_title']);
			} else {
				$this->document->setTitle($product_info['name']);
			}
			
			// ocStore compatibility: historical `noindex` field is inverted: 1 = indexing allowed, 0 = noindex.
			if ($product_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
				$this->document->setRobots('noindex,follow');
			}
			
			if ($product_info['meta_h1']) {
				$data['heading_title'] = $product_info['meta_h1'];
			} else {
				$data['heading_title'] = $product_info['name'];
			}
			
			$meta_description = trim((string)$product_info['meta_description']);
			if ($meta_description === '') {
				$meta_description = (string)$product_info['description'];
				for ($i = 0; $i < 2; $i++) {
					$decoded_meta = html_entity_decode($meta_description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
					if ($decoded_meta === $meta_description) { break; }
					$meta_description = $decoded_meta;
				}
				$meta_description = preg_replace('/\s+/u', ' ', trim(strip_tags($meta_description)));
				if (utf8_strlen($meta_description) > 160) {
					$meta_description = rtrim(utf8_substr($meta_description, 0, 157)) . '...';
				}
			}
			$this->document->setDescription($meta_description);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');
			$gallery_engine = in_array((string)$this->config->get('config_theme'), array('default', 'codecart'), true) ? (string)$this->config->get('theme_default_gallery_engine') : 'magnific';
			if (!in_array($gallery_engine, array('photoswipe', 'magnific'), true)) {
				$gallery_engine = 'photoswipe';
			}
			$data['gallery_engine'] = $gallery_engine;
			if ($gallery_engine === 'photoswipe') {
				// Load PhotoSwipe in deterministic dependency order. The previous lazy
				// first-click loader could race when a visitor opened image #2/#3 first.
				$this->document->addStyle('catalog/view/javascript/photoswipe/photoswipe.css?v=' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0'), 'stylesheet', 'screen', 'footer');
				$this->document->addScript('catalog/view/javascript/photoswipe/photoswipe.umd.min.js?v=' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0'), 'footer');
				$this->document->addScript('catalog/view/javascript/photoswipe/photoswipe-lightbox.umd.min.js?v=' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0'), 'footer');
			} else {
				$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
				$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');
			}

			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));

			$this->load->model('catalog/review');

			$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

			$data['product_id'] = (int)$this->request->get['product_id'];
			$data['manufacturer'] = $product_info['manufacturer'];
			$data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];
			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');
			$data['description'] = $this->load->controller('common/codecart_form/shortcodes', array('html'=>$data['description'],'context'=>$product_context));
			$data['extra_tab_title'] = '';
			$data['extra_tab_content'] = '';
			$product_extra_tab = $this->model_catalog_product->getProductExtraTab((int)$product_id, (int)$this->config->get('config_language_id'));
			$extra_tab_mode = isset($product_extra_tab['mode']) ? (int)$product_extra_tab['mode'] : 0;
			if ($extra_tab_mode === 1) {
				$data['extra_tab_title'] = trim((string)($product_extra_tab['title'] ?? ''));
				$data['extra_tab_content'] = html_entity_decode((string)($product_extra_tab['content'] ?? ''), ENT_QUOTES, 'UTF-8');
			} elseif ($extra_tab_mode !== 2 && $this->config->get('theme_default_product_extra_tab_status')) {
				$language_id = (int)$this->config->get('config_language_id');
				$global_titles = (array)$this->config->get('theme_default_product_extra_tab_title');
				$global_contents = (array)$this->config->get('theme_default_product_extra_tab_content');
				$data['extra_tab_title'] = isset($global_titles[$language_id]) ? trim((string)$global_titles[$language_id]) : '';
				$data['extra_tab_content'] = isset($global_contents[$language_id]) ? html_entity_decode((string)$global_contents[$language_id], ENT_QUOTES, 'UTF-8') : '';
			}

			if ($product_info['quantity'] <= 0) {
				$data['stock'] = $product_info['stock_status'];
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $product_info['quantity'];
			} else {
				$data['stock'] = $this->language->get('text_instock');
			}

			$this->load->model('tool/image');

			$data['image_thumb_width'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width');
			$data['image_thumb_height'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height');
			$data['image_additional_width'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width');
			$data['image_additional_height'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height');
			$data['image_related_width'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width');
			$data['image_related_height'] = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height');

			$popup_width = max(1400, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'));
			$popup_height = max(1050, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
			$data['image_popup_width'] = $popup_width;
			$data['image_popup_height'] = $popup_height;
				$gallery_image = !empty($product_info['image']) ? (string)$product_info['image'] : (string)$this->config->get('config_catalog_fallback_image');
				if ($gallery_image === '') {
					$gallery_image = 'no_image.webp';
				}

				$data['popup'] = $this->model_tool_image->display($gallery_image, $popup_width, $popup_height);
				$popup_size = $this->model_tool_image->getDisplaySize($gallery_image, $popup_width, $popup_height);
				$data['image_popup_width'] = (int)$popup_size['width'];
				$data['image_popup_height'] = (int)$popup_size['height'];

				$data['image_display_width'] = max(1, (int)$data['image_thumb_width']);
				$data['image_display_height'] = max(1, (int)$data['image_thumb_height']);
				$display_width = max(1400, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'));
				$display_height = max(1050, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
				$data['thumb'] = $this->model_tool_image->display($gallery_image, $display_width, $display_height);
				if (empty($data['thumb'])) {
					$gallery_image = 'no_image.webp';
					$data['popup'] = $this->model_tool_image->display($gallery_image, $popup_width, $popup_height);
					$data['thumb'] = $this->model_tool_image->display($gallery_image, $display_width, $display_height);
				}
				$resolved_gallery_source = $this->model_tool_image->resolveFilename((string)$product_info['image']);
				$data['image_is_fallback'] = empty($product_info['image']) || $resolved_gallery_source === '' || !is_file(DIR_IMAGE . $resolved_gallery_source);
				$display_size = $this->model_tool_image->getDisplaySize($gallery_image, $display_width, $display_height);
				$data['image_display_width'] = (int)$display_size['width'];
				$data['image_display_height'] = (int)$display_size['height'];

			$this->document->setOgType('product');
			if (!empty($data['popup'])) {
				$this->document->setOgImage($data['popup']);
			}

			$data['images'] = array();

			$results = $this->model_catalog_product->getProductImages($this->request->get['product_id']);

			$additional_popup_width = $popup_width;
			$additional_popup_height = $popup_height;

			foreach ($results as $result) {
				$popup_size = $this->model_tool_image->getDisplaySize($result['image'], $additional_popup_width, $additional_popup_height);
				$image_popup_width = (int)$popup_size['width'];
				$image_popup_height = (int)$popup_size['height'];

				$data['images'][] = array(
					'popup' => $this->model_tool_image->display($result['image'], $additional_popup_width, $additional_popup_height),
					'main' => $this->model_tool_image->display($result['image'], $display_width, $display_height),
					'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height')),
					'width' => $image_popup_width,
					'height' => $image_popup_height
				);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = \CodeCart\Core\TaxDisplay::primary($this->registry, $product_info['price'], (int)$product_info['tax_class_id'], isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit');
			} else {
				$data['price'] = false;
			}

			if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
				$data['special'] = \CodeCart\Core\TaxDisplay::primary($this->registry, $product_info['special'], (int)$product_info['tax_class_id'], isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit');
				$tax_price = (float)$product_info['special'];
			} else {
				$data['special'] = false;
				$tax_price = (float)$product_info['price'];
			}

			$data['tax'] = \CodeCart\Core\TaxDisplay::secondary($this->registry, $tax_price, (int)$product_info['tax_class_id'], isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit');

			$discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);

			$data['discounts'] = array();

			foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => \CodeCart\Core\TaxDisplay::primary($this->registry, $discount['price'], (int)$product_info['tax_class_id'], $product_tax_display_mode)
				);
			}

			$data['options'] = array();
			$has_unavailable_option = false;
			$has_datetime_option = false;
			$option_image_switch_enabled = (bool)$this->config->get('theme_default_option_image_switch_status');
			$compatibility = $this->registry->get('codecart_compatibility_framework');
			$optionSizeContract = $compatibility ? $compatibility->apply('catalog.product.option_image_size', array('width' => 50, 'height' => 50, 'product_id' => (int)$this->request->get['product_id'])) : array('width' => 50, 'height' => 50);
			$uniOptionImageSize = array(max(1, (int)$optionSizeContract['width']), max(1, (int)$optionSizeContract['height']));

			foreach ($this->model_catalog_product->getProductOptions($this->request->get['product_id']) as $option) {
				$product_option_value_data = array();

				if (in_array((string)$option['type'], array('date', 'time', 'datetime'), true)) {
					$has_datetime_option = true;
				}

				foreach ($option['product_option_value'] as $option_value) {
						$option_available = !$option_value['subtract'] || ((int)$option_value['quantity'] > 0);
						if (!$option_available) { $has_unavailable_option = true; }
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = \CodeCart\Core\TaxDisplay::primary($this->registry, $option_value['price'], (int)$product_info['tax_class_id'], $product_tax_display_mode);
						} else {
							$price = false;
						}

						$option_image = trim((string)$option_value['image']);
						$option_image_main = '';
						$option_image_popup = '';
						$option_image_popup_width = 0;
						$option_image_popup_height = 0;
						if ($option_image_switch_enabled && $option_image !== '') {
							$resolved_option_image = $this->model_tool_image->resolveFilename($option_image);
							if ($resolved_option_image !== '' && is_file(DIR_IMAGE . $resolved_option_image)) {
								$option_image = $resolved_option_image;
								$option_image_main = $this->model_tool_image->display($option_image, $display_width, $display_height);
								$option_image_popup = $this->model_tool_image->display($option_image, $popup_width, $popup_height);
								$option_popup_size = $this->model_tool_image->getDisplaySize($option_image, $popup_width, $popup_height);
								$option_image_popup_width = (int)$option_popup_size['width'];
								$option_image_popup_height = (int)$option_popup_size['height'];
							}
						}

						$optionValueData = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $option_image !== '' ? $this->model_tool_image->resize($option_image, $uniOptionImageSize[0], $uniOptionImageSize[1]) : '',
							'image_main'              => $option_image_main,
							'image_popup'             => $option_image_popup,
							'image_popup_width'       => $option_image_popup_width,
							'image_popup_height'      => $option_image_popup_height,
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix'],
							'quantity'                => (int)$option_value['quantity'],
							'subtract'                => (int)$option_value['subtract'],
							'available'               => $option_available ? 1 : 0
						);
						$optionValueContract = $compatibility ? $compatibility->apply('catalog.product.option_value', array('value' => $optionValueData, 'raw_price' => (float)$option_value['price'], 'product_id' => (int)$this->request->get['product_id'])) : array('value' => $optionValueData);
					$optionValueData = isset($optionValueContract['value']) && is_array($optionValueContract['value']) ? $optionValueContract['value'] : $optionValueData;
						$product_option_value_data[] = $optionValueData;
				}

				$data['options'][] = array(
					'product_option_id'    => $option['product_option_id'],
					'product_option_value' => $product_option_value_data,
					'option_id'            => $option['option_id'],
					'name'                 => $option['name'],
					'type'                 => $option['type'],
					'value'                => $option['value'],
					'required'             => $option['required']
				);
			}

			if ($has_datetime_option) {
				// Deterministic dependency order. Do not defer these three files: the product
				// option handler must have a complete picker before the first user click.
				$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
				$this->document->addStyle('catalog/view/javascript/datetimepicker/codecart-datetimepicker-modern.css?v=1.8.7');
				$this->document->addStyle('catalog/view/javascript/datetimepicker/codecart-native-datetimepicker.css?v=3.0.6.0-dtp187');
				$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js?v=2.30.1');
				$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js?v=3.1.3.1');
				$this->document->addScript('catalog/view/javascript/datetimepicker/codecart-native-datetimepicker.js?v=3.0.6.0-dtp187');
			}

			$data['can_buy'] = (int)$product_info['quantity'] > 0;
			$data['has_unavailable_option'] = $has_unavailable_option;
			$data['stock_notify_email'] = $this->customer->isLogged() ? (string)$this->customer->getEmail() : '';
			$data['stock_notify_url'] = $this->url->link('product/product/stockNotify', 'product_id=' . (int)$product_id, true);

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			$data['review_status'] = $this->config->get('config_review_status');

			if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}

			$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
			$data['rating'] = (int)$product_info['rating'];

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}

			$data['share'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);

			$data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);

			$data['products'] = array();

			$results = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);
            if ((bool)$this->config->get('codecart_relation_status') && (bool)$this->config->get('codecart_relation_storefront_status')) {
                $autoLimit = (int)$this->config->get('codecart_relation_product_limit');
                if ($autoLimit < 1) { $autoLimit = 8; }
                $autoLimit = max(1, min(24, $autoLimit));
                $manualIds = array();
                foreach ($results as $manualRelated) { $manualIds[(int)$manualRelated['product_id']] = true; }
                $remainingAuto = max(0, $autoLimit - count($manualIds));
                $autoIds = $remainingAuto > 0 ? (new \CodeCart\Core\RelationLayer($this->registry))->getAutoProductIds((int)$this->request->get['product_id'], $remainingAuto) : array();
                $autoIds = array_values(array_filter($autoIds, static function($id) use ($manualIds) { return !isset($manualIds[(int)$id]); }));
                if ($autoIds) {
                    foreach ($this->model_catalog_product->getProductCardsByIds($autoIds) as $autoProduct) {
                        if (!isset($manualIds[(int)$autoProduct['product_id']])) {
                            $results[(int)$autoProduct['product_id']] = $autoProduct;
                            $manualIds[(int)$autoProduct['product_id']] = true;
                        }
                    }
                }
            }
			$related_card_attributes = array();
			$card_mode = (string)$this->config->get('theme_default_product_card_content');
			$data['card_content'] = in_array($card_mode, array('none','description','attributes','both'), true) ? $card_mode : 'attributes';
			$card_attribute_limit = max(1, min(10, (int)$this->config->get('theme_default_product_card_attribute_limit')));
			if (!$this->config->get('theme_default_product_card_attribute_limit')) { $card_attribute_limit = 3; }
			if (in_array($data['card_content'], array('attributes','both'), true) && $results) {
				$related_ids = array();
				foreach ($results as $related_product) { $related_ids[] = (int)$related_product['product_id']; }
				$related_card_attributes = $this->model_catalog_product->getProductCardAttributes($related_ids, $card_attribute_limit);
			}

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
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
					'attributes'  => isset($related_card_attributes[(int)$result['product_id']]) ? $related_card_attributes[(int)$result['product_id']] : array(),
					'can_buy'     => (int)$result['quantity'] > 0,
					'stock_status'=> (string)$result['stock_status'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')),
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'tax_label'   => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit'),
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}


			if ($data['products']) {
				$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9', 'stylesheet', 'screen', 'footer');
				$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
			}
			$data['text_previous_related'] = $this->language->get('text_previous_related');
			$data['text_next_related'] = $this->language->get('text_next_related');

			$data['tags'] = array();

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = array(
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'tag=' . urlencode(html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8')))
					);
				}
			}

			$data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);
			$data['price_preview_url'] = $this->url->link('product/product/pricePreview', '', true);


			if ($this->config->get('config_codecart_structured_data_status')) {
				$schema_url = $this->url->link('product/product', 'product_id=' . (int)$product_id);
				$schema_currency = isset($this->session->data['currency']) ? (string)$this->session->data['currency'] : (string)$this->config->get('config_currency');
				$schema_source_price = (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) ? (float)$product_info['special'] : (float)$product_info['price'];
				$schema_tax_price = \CodeCart\Core\TaxDisplay::primaryValue($this->registry, $schema_source_price, (int)$product_info['tax_class_id'], $product_tax_display_mode);
				$schema_price = $this->currency->format($schema_tax_price, $schema_currency, '', false);

				$product_schema = array(
					'@type' => 'Product',
					'@id' => $schema_url . '#product',
					'name' => (string)$product_info['name'],
					'url' => $schema_url,
					'description' => $this->schemaText($product_info['description'])
				);

				// Keep structured prices consistent with the storefront visibility rule.
				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$product_schema['offers'] = array(
						'@type' => 'Offer',
						'url' => $schema_url,
						'priceCurrency' => $schema_currency,
						'price' => number_format((float)$schema_price, max(0, (int)$this->currency->getDecimalPlace($schema_currency)), '.', ''),
						'availability' => ((int)$product_info['quantity'] > 0) ? 'https://schema.org/InStock' : ($this->config->get('config_stock_checkout') ? 'https://schema.org/BackOrder' : 'https://schema.org/OutOfStock'),
						'itemCondition' => 'https://schema.org/NewCondition',
						'seller' => array('@id' => rtrim((string)(function_exists('codecart_is_https') && codecart_is_https((array)$this->request->server) ? $this->config->get('config_ssl') : $this->config->get('config_url')), '/') . '/#organization')
					);
				}

				$schema_images = array();
				if (!empty($data['popup'])) {
					$schema_images[] = $data['popup'];
				}
				foreach ((array)$data['images'] as $schema_image) {
					if (!empty($schema_image['popup'])) {
						$schema_images[] = $schema_image['popup'];
					}
				}
				$schema_images = array_values(array_unique($schema_images));
				if ($schema_images) {
					$product_schema['image'] = $schema_images;
				}

				if (!empty($product_info['manufacturer'])) {
					$product_schema['brand'] = array('@type' => 'Brand', 'name' => (string)$product_info['manufacturer']);
				}
				if (!empty($product_info['sku'])) {
					$product_schema['sku'] = (string)$product_info['sku'];
				} elseif (!empty($product_info['model'])) {
					$product_schema['sku'] = (string)$product_info['model'];
				}
				if (!empty($product_info['mpn'])) {
					$product_schema['mpn'] = (string)$product_info['mpn'];
				}

				$gtin_candidates = array((string)$product_info['ean'], (string)$product_info['upc']);
				foreach ($gtin_candidates as $gtin) {
					$gtin = preg_replace('/\D+/', '', $gtin);
					if (in_array(strlen($gtin), array(8, 12, 13, 14), true)) {
						$product_schema['gtin' . strlen($gtin)] = $gtin;
						break;
					}
				}

				if ((int)$product_info['reviews'] > 0 && (float)$product_info['rating'] > 0) {
					$product_schema['aggregateRating'] = array(
						'@type' => 'AggregateRating',
						'ratingValue' => (float)$product_info['rating'],
						'reviewCount' => (int)$product_info['reviews']
					);
				}

				$this->document->addStructuredData($product_schema, 'product');

				$schema_breadcrumbs = array();
				$schema_position = 1;
				foreach ($data['breadcrumbs'] as $breadcrumb) {
					if (empty($breadcrumb['href'])) { continue; }
					$schema_breadcrumbs[] = array(
						'@type' => 'ListItem',
						'position' => $schema_position++,
						'name' => trim(strip_tags(html_entity_decode((string)$breadcrumb['text'], ENT_QUOTES, 'UTF-8'))),
						'item' => (string)$breadcrumb['href']
					);
				}
				if ($schema_breadcrumbs) {
					$this->document->addStructuredData(array('@type' => 'BreadcrumbList', 'itemListElement' => $schema_breadcrumbs), 'breadcrumbs');
				}
			}

			$this->load->language('common/internal_links');
			$data['text_internal_links'] = $this->language->get('text_internal_links');
			$link_category_id = 0;
			if (!empty($this->request->get['path'])) {
				$link_parts = explode('_', (string)$this->request->get['path']);
				$link_category_id = (int)array_pop($link_parts);
			}
			$data['internal_links'] = (new \CodeCart\Core\InternalLinking($this->registry))->product((int)$product_id, $link_category_id, (int)$product_info['manufacturer_id'], 8);


			
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/product', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
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
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/product', $url . '&product_id=' . $product_id)
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


	public function pricePreview() {
		$this->load->language('product/product');
		$json = array();

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->response->setStatusCode(405);
			$json['error'] = 'Method not allowed';
		} else {
			$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
			$quantity = isset($this->request->post['quantity']) ? max(1, (int)$this->request->post['quantity']) : 1;
			$option = isset($this->request->post['option']) && is_array($this->request->post['option']) ? $this->request->post['option'] : array();
			$this->load->model('catalog/product');
			$preview = $this->model_catalog_product->getProductPricePreview($product_id, $quantity, $option);

			if (!$preview) {
				$this->response->setStatusCode(404);
				$json['error'] = $this->language->get('error_product');
			} elseif (!$this->customer->isLogged() && $this->config->get('config_customer_price')) {
				$json['visible'] = false;
			} else {
				$currency = isset($this->session->data['currency']) ? (string)$this->session->data['currency'] : (string)$this->config->get('config_currency');
				$regular_unit = \CodeCart\Core\TaxDisplay::primaryValue($this->registry, (float)$preview['regular'], (int)$preview['tax_class_id'], isset($preview['tax_display_mode']) ? $preview['tax_display_mode'] : 'inherit');
				$current_unit = \CodeCart\Core\TaxDisplay::primaryValue($this->registry, (float)$preview['current'], (int)$preview['tax_class_id'], isset($preview['tax_display_mode']) ? $preview['tax_display_mode'] : 'inherit');
				$quantity = (int)$preview['quantity'];
				$json['visible'] = true;
				$json['quantity'] = $quantity;
				$json['unit'] = $this->currency->format($current_unit, $currency);
				$json['total'] = $this->currency->format($current_unit * $quantity, $currency);
				$json['regular_total'] = !empty($preview['is_special']) ? $this->currency->format($regular_unit * $quantity, $currency) : '';
				$json['quantity_line'] = $quantity > 1 ? ($quantity . ' × ' . $this->currency->format($current_unit, $currency)) : '';
				$secondary_unit = \CodeCart\Core\TaxDisplay::secondaryValue($this->registry, (float)$preview['current'], (int)$preview['tax_class_id'], isset($preview['tax_display_mode']) ? $preview['tax_display_mode'] : 'inherit');
				$json['tax_total'] = $secondary_unit === false ? '' : $this->currency->format((float)$secondary_unit * $quantity, $currency);
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	private function schemaText($value) {
		$text = (string)$value;
		for ($i = 0; $i < 2; $i++) {
			$decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($decoded === $text) { break; }
			$text = $decoded;
		}
		return trim(preg_replace('/\\s+/u', ' ', strip_tags($text)));
	}

	public function review() {
		$this->load->language('product/product');

		$this->load->model('catalog/review');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['reviews'] = array();

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

		$results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

		foreach ($results as $result) {
			$data['reviews'][] = array(
				'author'     => $result['author'],
				'text'       => nl2br($result['text']),
				'rating'     => (int)$result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		$this->response->setOutput($this->load->view('product/review', $data));
	}

	public function write() {
		$this->load->language('product/product');

		$json = array();
		$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$product_id) {
			$json['error'] = $this->language->get('error_product');
		} else {
			$guard = new \CodeCart\Core\FormGuard($this->registry);
			$rate = $guard->consume('product_review', 40, 600, 2);

			if (!$rate['allowed']) {
				$json['error'] = $this->language->get('error_rate_limit');
				$this->response->setStatusCode(429);
				$this->response->addHeader('Retry-After: ' . (int)$rate['retry_after']);
			}

			$this->load->model('catalog/product');
			if (!isset($json['error']) && !$this->model_catalog_product->getProduct($product_id)) {
				$json['error'] = $this->language->get('error_product');
			}

			$name = isset($this->request->post['name']) ? (string)$this->request->post['name'] : '';
			$text = isset($this->request->post['text']) ? (string)$this->request->post['text'] : '';
			$rating = isset($this->request->post['rating']) ? (int)$this->request->post['rating'] : 0;

			if ((utf8_strlen($name) < 3) || (utf8_strlen($name) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}

			if ((utf8_strlen($text) < 25) || (utf8_strlen($text) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}

			if ($rating < 1 || $rating > 5) {
				$json['error'] = $this->language->get('error_rating');
			}

			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');
				if ($captcha) {
					$json['error'] = $captcha;
					$json['captcha_error'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('catalog/review');
				$this->model_catalog_review->addReview($product_id, $this->request->post);
				if ((string)$this->config->get('config_captcha') === 'basic') {
					$this->load->controller('extension/captcha/basic/consume');
					$json['captcha_reset'] = true;
				}
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($json));
	}

	public function getRecurringDescription() {
		$this->load->language('product/product');
		$this->load->model('catalog/product');

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['recurring_id'])) {
			$recurring_id = $this->request->post['recurring_id'];
		} else {
			$recurring_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = $this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		$product_info = $this->model_catalog_product->getProduct($product_id);
		
		$recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

		$json = array();

		if ($product_info && $recurring_info) {
			if (!$json) {
				$frequencies = array(
					'day'        => $this->language->get('text_day'),
					'week'       => $this->language->get('text_week'),
					'semi_month' => $this->language->get('text_semi_month'),
					'month'      => $this->language->get('text_month'),
					'year'       => $this->language->get('text_year'),
				);

				if ($recurring_info['trial_status'] == 1) {
					$price = $this->currency->format($this->tax->calculate($recurring_info['trial_price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$trial_text = sprintf($this->language->get('text_trial_description'), $price, $recurring_info['trial_cycle'], $frequencies[$recurring_info['trial_frequency']], $recurring_info['trial_duration']) . ' ';
				} else {
					$trial_text = '';
				}

				$price = $this->currency->format($this->tax->calculate($recurring_info['price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if ($recurring_info['duration']) {
					$text = $trial_text . sprintf($this->language->get('text_payment_description'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				} else {
					$text = $trial_text . sprintf($this->language->get('text_payment_cancel'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				}

				$json['success'] = $text;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	private function tableExistsForProductPage($table) {
		$table = (string)$table;
		if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
			return false;
		}
		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
		return (bool)$query->num_rows;
	}

	public function stockNotify() {
		$this->load->language('product/product');
		$json=array();
		$product_id=isset($this->request->get['product_id'])?(int)$this->request->get['product_id']:0;
		$email=isset($this->request->post['email'])?trim((string)$this->request->post['email']):'';
		$options=isset($this->request->post['option']) && is_array($this->request->post['option']) ? $this->request->post['option'] : array();
		$guard = new \CodeCart\Core\FormGuard($this->registry);
		$rate = $guard->consume('stock_notify', 12, 600, 2);
		if (!$rate['allowed']) { $json['error']=$this->language->get('error_rate_limit'); $this->response->setStatusCode(429); $this->response->addHeader('Retry-After: '.(int)$rate['retry_after']); }
		if ($this->request->server['REQUEST_METHOD'] !== 'POST') { $this->response->setStatusCode(405); $json['error']=$this->language->get('error_stock_notify'); }
		if (!$product_id || !filter_var($email,FILTER_VALIDATE_EMAIL)) { $json['error']=$this->language->get('error_stock_notify_email'); }
		$this->load->model('catalog/product');
		$product=$product_id?$this->model_catalog_product->getProduct($product_id):array();
		$selection=array('tracked'=>array(),'unavailable'=>false,'label'=>'');
		if (!$product) { $json['error']=$this->language->get('error_product'); }
		else {
			$selection=$this->model_catalog_product->getStockNotificationSelection($product_id,$options);
			if ((int)$product['quantity'] > 0 && empty($selection['unavailable'])) { $json['error']=$this->language->get('error_stock_notify_available'); }
		}
		if (!$json) {
			$customer_id=$this->customer->isLogged()?(int)$this->customer->getId():0;
			$this->model_catalog_product->addStockNotification($product_id,$email,$customer_id,$selection);
			$json['success']=$this->language->get('text_stock_notify_success');
		}
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($json,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
	}

}
