<?php
class ControllerExtensionModuleRecentlyViewed extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/recently_viewed');
		$this->load->language('extension/module/codecart_slider');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
		$language_id = (int)$this->config->get('config_language_id');
		$custom_heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
		$data['heading_title'] = $custom_heading !== '' ? $custom_heading : $this->language->get('heading_title');
		if (isset($setting['show_heading']) && !$setting['show_heading']) { $data['heading_title'] = ''; }
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);
		$data['text_previous_products'] = $this->language->get('text_previous_products');
		$data['text_next_products'] = $this->language->get('text_next_products');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');

		$limit = max(1, min(30, (int)($setting['limit'] ?? 8)));
		$width = max(40, min(2000, (int)($setting['width'] ?? 200)));
		$height = max(40, min(2000, (int)($setting['height'] ?? 200)));
		$data['image_width'] = $width;
		$data['image_height'] = $height;
		$data['display_mode'] = (isset($setting['display_mode']) && $setting['display_mode'] === 'grid') ? 'grid' : 'carousel';
		$data['columns_desktop'] = max(1, min(6, (int)($setting['columns_desktop'] ?? 4)));
		$data['columns_tablet'] = max(1, min(4, (int)($setting['columns_tablet'] ?? 3)));
		$data['columns_mobile'] = max(1, min(2, (int)($setting['columns_mobile'] ?? 2)));
		$data['autoplay'] = !empty($setting['autoplay']) ? 1 : 0;
		$data['autoplay_delay'] = max(1500, min(20000, (int)($setting['autoplay_delay'] ?? 5000)));
		$data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
		$data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
		$data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
		$data['carousel_step'] = (isset($setting['carousel_step']) && $setting['carousel_step'] === 'page') ? 'page' : 'item';

		$history = isset($this->session->data['codecart_recently_viewed']) && is_array($this->session->data['codecart_recently_viewed']) ? $this->session->data['codecart_recently_viewed'] : array();
		$history = array_values(array_unique(array_filter(array_map('intval', $history))));
		$current_product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
		$display_ids = array();
		foreach ($history as $product_id) {
			if ($product_id > 0 && $product_id !== $current_product_id) {
				$display_ids[] = $product_id;
				if (count($display_ids) >= $limit) { break; }
			}
		}


		if (!$display_ids) {
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$results = $this->model_catalog_product->getProductCardsByIds($display_ids);
		if (!$results) {
			return;
		}

		$card_attributes = array();
		$card_mode = (string)$this->config->get('theme_default_product_card_content');
			$data['card_content'] = in_array($card_mode, array('none','description','attributes','both'), true) ? $card_mode : 'attributes';
			$card_attribute_limit = max(1, min(10, (int)$this->config->get('theme_default_product_card_attribute_limit')));
			if (!$this->config->get('theme_default_product_card_attribute_limit')) { $card_attribute_limit = 3; }
		if (in_array($data['card_content'], array('attributes','both'), true)) {
			$product_ids = array();
			foreach ($results as $result) { $product_ids[] = (int)$result['product_id']; }
			$card_attributes = $this->model_catalog_product->getProductCardAttributes($product_ids, $card_attribute_limit);
		}

		$data['products'] = array();
		foreach ($results as $result) {
			$image = !empty($result['image']) ? $this->model_tool_image->resize($result['image'], $width, $height) : $this->model_tool_image->resize('no_image.webp', $width, $height);
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = \CodeCart\Core\TaxDisplay::primary($this->registry, $result['price'], (int)$result['tax_class_id'], isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit');
			} else {
				$price = false;
			}
			if ($result['special'] !== null && (float)$result['special'] >= 0) {
				$special = \CodeCart\Core\TaxDisplay::primary($this->registry, $result['special'], (int)$result['tax_class_id'], isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit');
				$tax_price = (float)$result['special'];
			} else {
				$special = false;
				$tax_price = (float)$result['price'];
			}
			$tax = \CodeCart\Core\TaxDisplay::secondary($this->registry, $tax_price, (int)$result['tax_class_id'], isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit');
			$rating = $this->config->get('config_review_status') ? $result['rating'] : false;
			$data['products'][] = array(
				'product_id' => (int)$result['product_id'],
				'attributes' => isset($card_attributes[(int)$result['product_id']]) ? $card_attributes[(int)$result['product_id']] : array(),
				'can_buy' => (int)$result['quantity'] > 0,
				'stock_status' => (string)$result['stock_status'],
				'thumb' => $image,
				'name' => $result['name'],
				'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')),
				'price' => $price,
				'special' => $special,
				'tax' => $tax,
					'tax_label' => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($result['tax_display_mode']) ? $result['tax_display_mode'] : 'inherit'),
				'rating' => $rating,
				'href' => $this->url->link('product/product', 'product_id=' . (int)$result['product_id'])
			);
		}

		$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/recently_viewed', $data);
	}
}
