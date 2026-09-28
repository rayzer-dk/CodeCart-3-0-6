<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionModuleFeaturedProduct extends Controller {
    public function index($setting) {
        $this->load->language('extension/module/featured_product');
		$this->load->language('extension/module/codecart_slider');
		$language_id = (int)$this->config->get('config_language_id');
		$custom_heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
		$data['heading_title'] = $custom_heading !== '' ? $custom_heading : $this->language->get('heading_title');
		if (isset($setting['show_heading']) && !$setting['show_heading']) { $data['heading_title'] = ''; }
		$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');
        $this->load->language('extension/module/codecart_slider');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
        $data['text_previous_items'] = $this->language->get('text_previous');
        $data['text_next_items'] = $this->language->get('text_next');
        $this->load->model('catalog/cms');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $limit = max(1, min(30, (int)($setting['limit'] ?? 4)));
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
        $data['products'] = array();
        $results = array();

        if (isset($this->request->get['product_id'])) {
            $productId = (int)$this->request->get['product_id'];

            // The product template already renders manual and Auto Relation products.
            // Do not create a second carousel for the same recommendation set.
            $explicit = $this->model_catalog_product->getProductRelated($productId);
            $hasAuto = false;
            if ((bool)$this->config->get('codecart_relation_status') && (bool)$this->config->get('codecart_relation_storefront_status')) {
                $hasAuto = (bool)(new \CodeCart\Core\RelationLayer($this->registry))->getAutoProductIds($productId, 1);
            }
            if ($explicit || $hasAuto) {
                return '';
            }

            // Safe fallback: use the main product category (or first category) and exclude the current item.
            $categories = $this->model_catalog_product->getCategories($productId);
            $categoryId = 0;
            foreach ($categories as $category) {
                if (!empty($category['main_category'])) {
                    $categoryId = (int)$category['category_id'];
                    break;
                }
            }
            if (!$categoryId && $categories) {
                $categoryId = (int)$categories[0]['category_id'];
            }
            if ($categoryId) {
                $candidates = $this->model_catalog_product->getProducts(array(
                    'filter_category_id' => $categoryId,
                    'sort' => 'p.sort_order',
                    'order' => 'ASC',
                    'start' => 0,
                    'limit' => $limit + 1
                ));
                foreach ($candidates as $candidate) {
                    if ((int)$candidate['product_id'] === $productId) { continue; }
                    $results[] = $candidate;
                    if (count($results) >= $limit) { break; }
                }
            }
        } elseif (isset($this->request->get['manufacturer_id'])) {
            $results = $this->model_catalog_cms->getProductRelatedByManufacturer(array(
                'manufacturer_id' => (int)$this->request->get['manufacturer_id'],
                'limit' => $limit
            ));
        } elseif (isset($this->request->get['path'])) {
            $parts = array_filter(array_map('intval', explode('_', (string)$this->request->get['path'])));
            if ($parts) {
                $results = $this->model_catalog_cms->getProductRelatedByCategory(array(
                    'category_id' => (int)array_pop($parts),
                    'limit' => $limit
                ));
            }
        }

        $card_attributes = array();
        $card_mode = (string)$this->config->get('theme_default_product_card_content');
			$data['card_content'] = in_array($card_mode, array('none','description','attributes','both'), true) ? $card_mode : 'attributes';
			$card_attribute_limit = max(1, min(10, (int)$this->config->get('theme_default_product_card_attribute_limit')));
			if (!$this->config->get('theme_default_product_card_attribute_limit')) { $card_attribute_limit = 3; }
        if (in_array($data['card_content'], array('attributes','both'), true) && $results) {
            $product_ids = array();
            foreach ($results as $card_product) { if (!empty($card_product['product_id'])) { $product_ids[] = (int)$card_product['product_id']; } }
            $card_attributes = $this->model_catalog_product->getProductCardAttributes($product_ids, $card_attribute_limit);
        }

        foreach ($results as $product) {
            if (!$product || empty($product['product_id'])) { continue; }
            $image = !empty($product['image'])
                ? $this->model_tool_image->resize($product['image'], $width, $height)
                : $this->model_tool_image->resize('no_image.webp', $width, $height);

            $price = false;
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = \CodeCart\Core\TaxDisplay::primary($this->registry, $product['price'], (int)$product['tax_class_id'], isset($product['tax_display_mode']) ? $product['tax_display_mode'] : 'inherit');
            }
            $special = (float)$product['special']
                ? \CodeCart\Core\TaxDisplay::primary($this->registry, $product['special'], (int)$product['tax_class_id'], isset($product['tax_display_mode']) ? $product['tax_display_mode'] : 'inherit')
                : false;
            $tax = \CodeCart\Core\TaxDisplay::secondary($this->registry, (float)$product['special'] ? $product['special'] : $product['price'], (int)$product['tax_class_id'], isset($product['tax_display_mode']) ? $product['tax_display_mode'] : 'inherit');
            $rating = $this->config->get('config_review_status') ? $product['rating'] : false;
            $descriptionLength = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length');
            if ($descriptionLength < 1) { $descriptionLength = 100; }

            $data['products'][] = array(
                'product_id' => (int)$product['product_id'],
                'attributes' => isset($card_attributes[(int)$product['product_id']]) ? $card_attributes[(int)$product['product_id']] : array(),
                'can_buy' => (int)$product['quantity'] > 0,
                'stock_status' => (string)$product['stock_status'],
                'thumb' => $image,
                'name' => $product['name'],
                'description' => \CodeCart\Core\CardText::excerpt($product['description'], $descriptionLength),
                'price' => $price,
                'special' => $special,
                'tax' => $tax,
					'tax_label' => \CodeCart\Core\TaxDisplay::label($this->config, $this->language, isset($product['tax_display_mode']) ? $product['tax_display_mode'] : 'inherit'),
                'rating' => $rating,
                'href' => $this->url->link('product/product', 'product_id=' . (int)$product['product_id'])
            );
        }

        if (!$data['products']) {
            return '';
        }

        $this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
        $this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');

        return $this->load->view('extension/module/featured_product', $data);
    }
}
