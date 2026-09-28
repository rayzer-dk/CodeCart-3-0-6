<?php
class ControllerExtensionModuleFeatured extends Controller {
	public function index($setting) {
		$limit = max(1, min(30, (int)($setting['limit'] ?? 4)));
		$width = max(40, min(2000, (int)($setting['width'] ?? 200)));
		$height = max(40, min(2000, (int)($setting['height'] ?? 200)));
		$data['image_width'] = $width;
		$data['image_height'] = $height;
		$this->load->language('extension/module/featured');
		$this->load->language('extension/module/codecart_slider');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
		$language_id = (int)$this->config->get('config_language_id');
		$custom_heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
		$data['heading_title'] = $custom_heading !== '' ? $custom_heading : $this->language->get('heading_title');
		if (isset($setting['show_heading']) && !$setting['show_heading']) { $data['heading_title'] = ''; }
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');
		$data['text_previous_products'] = $this->language->get('text_previous_products');
		$data['text_next_products'] = $this->language->get('text_next_products');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		$data['display_mode'] = (isset($setting['display_mode']) && $setting['display_mode'] === 'grid') ? 'grid' : 'carousel';
		$data['columns_desktop'] = max(1, min(6, (int)($setting['columns_desktop'] ?? 4)));
		$data['columns_tablet'] = max(1, min(4, (int)($setting['columns_tablet'] ?? 3)));
		$data['columns_mobile'] = max(1, min(2, (int)($setting['columns_mobile'] ?? 2)));
		$mobile_peek = isset($setting['mobile_peek']) ? (string)$setting['mobile_peek'] : '0';
		if (!in_array($mobile_peek, array('0','1.2','1.3','1.4'), true)) { $mobile_peek = '0'; }
		$data['mobile_peek'] = $mobile_peek;
		$data['columns_mobile_effective'] = ($data['display_mode'] === 'carousel' && $mobile_peek !== '0') ? $mobile_peek : $data['columns_mobile'];
		$data['autoplay'] = !empty($setting['autoplay']) ? 1 : 0;
		$data['autoplay_delay'] = max(1500, min(20000, (int)($setting['autoplay_delay'] ?? 5000)));
		$data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
		$data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
		$data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
		$data['carousel_step'] = (isset($setting['carousel_step']) && $setting['carousel_step'] === 'page') ? 'page' : 'item';


		$source = isset($setting['source']) ? (string)$setting['source'] : 'manual';
		if (!in_array($source, array('manual','category','manufacturer','latest','popular','bestseller','special'), true)) {
			$source = 'manual';
		}

		$results = array();
		if ($source === 'latest') {
			$results = $this->model_catalog_product->getLatestProductCards($limit);
		} elseif ($source === 'popular') {
			$results = $this->model_catalog_product->getPopularProductCards($limit);
		} elseif ($source === 'bestseller') {
			$results = $this->model_catalog_product->getBestSellerProductCards($limit);
		} elseif ($source === 'special') {
			$results = $this->model_catalog_product->getSpecialProductCards($limit);
		} else {
			$product_ids = array();
			if ($source === 'category' && !empty($setting['category_id'])) {
				$product_ids = $this->model_catalog_product->getProductIdsByCategory((int)$setting['category_id'], !empty($setting['include_subcategories']), $limit);
			} elseif ($source === 'manufacturer' && !empty($setting['manufacturer_id'])) {
				$product_ids = $this->model_catalog_product->getProductIdsByManufacturer((int)$setting['manufacturer_id'], $limit);
			} else {
				$product_ids = array_values(array_unique(array_filter(array_map('intval', isset($setting['product']) ? (array)$setting['product'] : array()))));
				$product_ids = array_slice($product_ids, 0, $limit);
			}
			if ($product_ids) {
				$results = $this->model_catalog_product->getProductCardsByIds($product_ids);
			}
		}

		if ($results) {
			$product_ids = array();
			foreach ($results as $product_info) { $product_ids[] = (int)$product_info['product_id']; }
			$card_mode = (string)$this->config->get('theme_default_product_card_content');
			$data['card_content'] = in_array($card_mode, array('none','description','attributes','both'), true) ? $card_mode : 'attributes';
			$card_attribute_limit = max(1, min(10, (int)$this->config->get('theme_default_product_card_attribute_limit')));
			if (!$this->config->get('theme_default_product_card_attribute_limit')) { $card_attribute_limit = 3; }
			$card_attributes = (in_array($data['card_content'], array('attributes','both'), true)) ? $this->model_catalog_product->getProductCardAttributes($product_ids, $card_attribute_limit) : array();
			$description_length = max(20, min(1000, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')));

			foreach ($results as $product_info) {
				$image = $product_info['image'] ? $this->model_tool_image->resize($product_info['image'], $width, $height) : $this->model_tool_image->resize('no_image.webp', $width, $height);
				$price = ($this->customer->isLogged() || !$this->config->get('config_customer_price')) ? \CodeCart\Core\TaxDisplay::primary($this->registry, $product_info['price'], (int)$product_info['tax_class_id'], isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit') : false;
				if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
					$special = \CodeCart\Core\TaxDisplay::primary($this->registry, $product_info['special'], (int)$product_info['tax_class_id'], isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit');
					$tax_price = (float)$product_info['special'];
				} else {
					$special = false;
					$tax_price = (float)$product_info['price'];
				}
				$tax = \CodeCart\Core\TaxDisplay::secondary($this->registry, $tax_price, (int)$product_info['tax_class_id'], isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit');
				$rating = $this->config->get('config_review_status') ? $product_info['rating'] : false;

				$data['products'][] = array(
					'product_id' => (int)$product_info['product_id'],
					'attributes' => isset($card_attributes[(int)$product_info['product_id']]) ? $card_attributes[(int)$product_info['product_id']] : array(),
					'can_buy' => (int)$product_info['quantity'] > 0,
					'stock_status' => (string)$product_info['stock_status'],
					'thumb' => $image,
					'name' => $product_info['name'],
					'description' => \CodeCart\Core\CardText::excerpt($product_info['description'], $description_length),
					'price' => $price,
					'special' => $special,
					'tax' => $tax,
					'tax_label' => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($product_info['tax_display_mode']) ? $product_info['tax_display_mode'] : 'inherit'),
					'rating' => $rating,
					'href' => $this->url->link('product/product', 'product_id=' . (int)$product_info['product_id'])
				);
			}
		}

		if ($data['products']) {
			$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/featured', $data);
		}
	}
}