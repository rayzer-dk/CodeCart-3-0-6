<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCheckoutCheckout extends Controller {
	public function index() {
		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$this->response->redirect($this->url->link('checkout/cart'));
			}
		}

		$this->load->language('checkout/checkout');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$this->document->addScript('catalog/view/javascript/datetimepicker/codecart-native-datetimepicker.js?v=3.0.6.0-dtp187');
		$this->document->addStyle('catalog/view/javascript/datetimepicker/codecart-native-datetimepicker.css?v=3.0.6.0-dtp187');

		// Guest checkout inserts CAPTCHA markup over AJAX after the header has rendered.
		// Register its assets on the parent checkout page so the inserted block is never unstyled.
		$captcha_code = (string)$this->config->get('config_captcha');
		$captcha_pages = (array)$this->config->get('config_captcha_page');
		if ($captcha_code !== '' && $this->config->get('captcha_' . $captcha_code . '_status') && in_array('guest', $captcha_pages, true)) {
			$this->document->addStyle('catalog/view/javascript/codecart/captcha/local-captcha.css?v=3.0.6.0-captcha-outline4');
			$this->document->addScript('catalog/view/javascript/codecart/captcha/local-captcha.js?v=3.0.6.0-captcha-outline4', 'footer');
		}

		$commerce_style = (string)$this->config->get('theme_default_commerce_style');
		$data['commerce_style'] = $commerce_style === 'modern' ? 'modern' : 'standard';
		$data['quick_checkout'] = $this->isQuickCheckoutEligible();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (!$data['quick_checkout']) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_cart'),
				'href' => $this->url->link('checkout/cart')
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['text_checkout_option'] = sprintf($this->language->get('text_checkout_option'), 1);
		$data['text_checkout_account'] = sprintf($this->language->get('text_checkout_account'), 2);
		$data['text_checkout_payment_address'] = sprintf($this->language->get('text_checkout_payment_address'), 2);
		$data['text_checkout_shipping_address'] = sprintf($this->language->get('text_checkout_shipping_address'), 3);
		$data['text_checkout_shipping_method'] = sprintf($this->language->get('text_checkout_shipping_method'), 4);
		
		if ($this->cart->hasShipping()) {
			$data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 5);
			$data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 6);
		} else {
			$data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 3);
			$data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 4);	
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['logged'] = $this->customer->isLogged();

		if (isset($this->session->data['account'])) {
			$data['account'] = $this->session->data['account'];
		} else {
			$data['account'] = '';
		}

		$data['shipping_required'] = $this->cart->hasShipping();
		$data['text_quick_subtitle'] = $this->language->get('text_quick_subtitle');
		$data['text_quick_contact'] = $this->language->get('text_quick_contact');
		$data['text_quick_shipping'] = $this->language->get('text_quick_shipping');
		$data['text_quick_payment'] = $this->language->get('text_quick_payment');
		$data['text_quick_confirm'] = $this->language->get('text_quick_confirm');
		$data['text_quick_fill_contact'] = $this->language->get('text_quick_fill_contact');
		$data['text_quick_choose_shipping'] = $this->language->get('text_quick_choose_shipping');
		$data['text_quick_cart_updated'] = $this->language->get('text_quick_cart_updated');
		$data['text_quick_login'] = $this->language->get('text_quick_login');
		$data['login'] = $this->url->link('account/login', '', true);
		$data['google_login'] = $data['quick_checkout'] ? $this->load->controller('extension/module/google_login/button', array('context' => 'checkout')) : '';

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		if ($data['quick_checkout']) {
			$data['quick_cart'] = $this->quickCartHtml();
			$this->response->setOutput($this->load->view('checkout/quick_checkout', $data));
		} else {
			$this->response->setOutput($this->load->view('checkout/checkout', $data));
		}
	}

	public function quickCart() {
		if (!$this->isQuickCheckoutEligible()) {
			$this->response->setStatusCode(403);
			return;
		}
		$this->response->setOutput($this->quickCartHtml());
	}

	public function quickCartUpdate() {
		$this->load->language('checkout/cart');
		$json = array();

		if (!$this->isQuickCheckoutEligible() || $this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['redirect'] = $this->url->link('checkout/cart');
		} else {
			$cart_id = isset($this->request->post['cart_id']) ? (int)$this->request->post['cart_id'] : 0;
			$quantity = isset($this->request->post['quantity']) ? max(0, min(9999, (int)$this->request->post['quantity'])) : 0;
			$product_info = null;

			foreach ($this->cart->getProducts() as $product) {
				if ((int)$product['cart_id'] === $cart_id) { $product_info = $product; break; }
			}

			if (!$product_info) {
				$json['error'] = $this->language->get('error_product');
			} elseif ($quantity > 0 && $quantity < (int)$product_info['minimum']) {
				$json['error'] = sprintf($this->language->get('error_minimum'), $product_info['name'], (int)$product_info['minimum']);
			} else {
				if ($quantity > 0) { $this->cart->update($cart_id, $quantity); } else { $this->cart->remove($cart_id); }
				unset($this->session->data['shipping_method'], $this->session->data['shipping_methods'], $this->session->data['payment_method'], $this->session->data['payment_methods']);

				if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
					$json['redirect'] = $this->url->link('checkout/cart');
				} else {
					$json['html'] = $this->quickCartHtml();
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function isQuickCheckoutEligible() {
		if ((string)$this->config->get('theme_default_commerce_style') !== 'modern' || !$this->config->get('config_quick_checkout_status')) { return false; }
		if ($this->customer->isLogged() || !$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price')) { return false; }
		if ($this->cart->hasDownload()) { return false; }
		if (method_exists($this->cart, 'hasRecurringProducts') && $this->cart->hasRecurringProducts()) { return false; }
		return true;
	}

	private function quickCartHtml() {
		$this->load->language('checkout/cart');
		$this->load->model('tool/image');
		$this->load->model('tool/upload');

		$data = array();
		$data['text_quick_cart'] = $this->language->get('text_quick_cart');
		$data['button_remove'] = $this->language->get('button_remove');
		if ($data['text_quick_cart'] === 'text_quick_cart') { $data['text_quick_cart'] = $this->language->get('heading_title'); }
		$data['products'] = array();

		foreach ($this->cart->getProducts() as $product) {
			$option_data = array();
			foreach ($product['option'] as $option) {
				$value = $option['value'];
				if ($option['type'] === 'file') {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
					$value = $upload_info ? $upload_info['name'] : '';
				}
				$option_data[] = array('name' => $option['name'], 'value' => utf8_strlen($value) > 24 ? utf8_substr($value, 0, 24) . '…' : $value);
			}

			$unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
			$data['products'][] = array(
				'cart_id' => (int)$product['cart_id'],
				'thumb' => $product['image'] ? $this->model_tool_image->resize($product['image'], 72, 72) : '',
				'name' => $product['name'],
				'model' => $product['model'],
				'option' => $option_data,
				'quantity' => (int)$product['quantity'],
				'minimum' => max(1, (int)$product['minimum']),
				'price' => $this->currency->format($unit_price, $this->session->data['currency']),
				'total' => $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']),
				'href' => $this->url->link('product/product', 'product_id=' . (int)$product['product_id'])
			);
		}

		$data['vouchers'] = array();
		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$data['vouchers'][] = array('description' => $voucher['description'], 'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency']));
			}
		}

		$totals = array(); $taxes = $this->cart->getTaxes(); $total = 0;
		$total_data = array('totals' => &$totals, 'taxes' => &$taxes, 'total' => &$total);
		$this->load->model('setting/extension');
		$results = $this->model_setting_extension->getExtensions('total');
		$sort_order = array();
		foreach ($results as $key => $value) { $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order'); }
		if ($results) { array_multisort($sort_order, SORT_ASC, $results); }
		foreach ($results as $result) {
			if ($this->config->get('total_' . $result['code'] . '_status')) {
				$this->load->model('extension/total/' . $result['code']);
				$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
			}
		}
		$sort_order = array();
		foreach ($totals as $key => $value) { $sort_order[$key] = $value['sort_order']; }
		if ($totals) { array_multisort($sort_order, SORT_ASC, $totals); }
		$data['totals'] = array();
		foreach ($totals as $value) { $data['totals'][] = array('title' => $value['title'], 'text' => $this->currency->format($value['value'], $this->session->data['currency'])); }

		return $this->load->view('checkout/quick_cart', $data);
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_id = isset($this->request->get['country_id']) ? (int)$this->request->get['country_id'] : 0;
		$country_info = $this->model_localisation_country->getCountry($country_id);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($country_id),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function customfield() {
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}