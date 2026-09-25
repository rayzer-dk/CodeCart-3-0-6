<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCheckoutGuest extends Controller {
	public function index() {
		$this->load->language('checkout/checkout');
		$this->load->helper('codecart_checkout');

		$data['quick_checkout'] = !empty($this->request->get['quick']);
		$data['compact_delivery_checkout'] = $data['quick_checkout'] && $this->useCompactCarrierCheckout();
		$field_language_keys = array(
			'firstname' => 'entry_firstname', 'lastname' => 'entry_lastname', 'email' => 'entry_email',
			'telephone' => 'entry_telephone', 'company' => 'entry_company', 'address_1' => 'entry_quick_address_1',
			'address_2' => 'entry_quick_address_2', 'city' => 'entry_city', 'postcode' => 'entry_postcode',
			'country' => 'entry_country', 'zone' => 'entry_zone'
		);
		foreach ($field_language_keys as $checkout_field => $language_key) {
			$is_digital_email = ($checkout_field === 'email' && $this->isDigitalCheckout());
			if ($data['compact_delivery_checkout'] && !$this->isDigitalCheckout()) {
				$visible = in_array($checkout_field, array('firstname', 'lastname', 'telephone'), true);
				$required = $visible;
				$data['checkout_field_' . $checkout_field] = $visible;
				$data['checkout_field_' . $checkout_field . '_required'] = $required;
			} else {
				$data['checkout_field_' . $checkout_field] = $is_digital_email ? true : codecart_checkout_field_visible($this->config, $checkout_field);
				$data['checkout_field_' . $checkout_field . '_required'] = $is_digital_email ? true : codecart_checkout_field_required($this->config, $checkout_field);
			}
			$data['checkout_label_' . $checkout_field] = codecart_checkout_field_label($this->config, (int)$this->config->get('config_language_id'), $checkout_field, $this->language->get($language_key));
		}

		$data['customer_groups'] = array();

		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');

			$customer_groups = $this->model_account_customer_group->getCustomerGroups();

			foreach ($customer_groups as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}

		if (isset($this->session->data['guest']['customer_group_id'])) {
			$data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
		} else {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (isset($this->session->data['guest']['firstname'])) {
			$data['firstname'] = $this->session->data['guest']['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->session->data['guest']['lastname'])) {
			$data['lastname'] = $this->session->data['guest']['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->session->data['guest']['email'])) {
			$data['email'] = (string)$this->session->data['guest']['email'];
			if ($data['email'] === codecart_checkout_email_fallback($this->config)) { $data['email'] = ''; }
		} else {
			$data['email'] = '';
		}

		if (isset($this->session->data['guest']['telephone'])) {
			$data['telephone'] = $this->session->data['guest']['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->session->data['payment_address']['company'])) {
			$data['company'] = $this->session->data['payment_address']['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->session->data['payment_address']['address_1'])) {
			$data['address_1'] = $this->session->data['payment_address']['address_1'];
		} else {
			$data['address_1'] = '';
		}

		if (isset($this->session->data['payment_address']['address_2'])) {
			$data['address_2'] = $this->session->data['payment_address']['address_2'];
		} else {
			$data['address_2'] = '';
		}

		if (isset($this->session->data['payment_address']['postcode'])) {
			$data['postcode'] = $this->session->data['payment_address']['postcode'];
		} elseif (isset($this->session->data['shipping_address']['postcode'])) {
			$data['postcode'] = $this->session->data['shipping_address']['postcode'];
		} else {
			$data['postcode'] = '';
		}

		if (isset($this->session->data['payment_address']['city'])) {
			$data['city'] = $this->session->data['payment_address']['city'];
		} else {
			$data['city'] = '';
		}

		if (isset($this->session->data['payment_address']['country_id'])) {
			$data['country_id'] = $this->session->data['payment_address']['country_id'];
		} elseif (isset($this->session->data['shipping_address']['country_id'])) {
			$data['country_id'] = $this->session->data['shipping_address']['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->session->data['payment_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['payment_address']['zone_id'];
		} elseif (isset($this->session->data['shipping_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['shipping_address']['zone_id'];
		} else {
			$data['zone_id'] = (int)$this->config->get('config_zone_id');
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		$this->load->model('account/custom_field');

		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();
		// Modern compact checkout keeps account-level custom fields. This allows the
		// standard customer-group mechanism (for example Private person / Company)
		// to reveal requisites without bringing postal address fields back.


		if (isset($this->session->data['guest']['custom_field'])) {
			$guest_custom_field = is_array($this->session->data['guest']['custom_field']) ? $this->session->data['guest']['custom_field'] : array();

			if (isset($this->session->data['payment_address']['custom_field'])) {
				$address_custom_field = $this->session->data['payment_address']['custom_field'];
			} else {
				$address_custom_field = array();
			}

			$data['guest_custom_field'] = $guest_custom_field + $address_custom_field;
		} else {
			$data['guest_custom_field'] = array();
		}

		$data['shipping_required'] = $this->cart->hasShipping();

		if (isset($this->session->data['guest']['shipping_address'])) {
			$data['shipping_address'] = $this->session->data['guest']['shipping_address'];
		} else {
			$data['shipping_address'] = true;
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('guest', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
		} else {
			$data['captcha'] = '';
		}
		
		$this->response->setOutput($this->load->view('checkout/guest', $data));
	}

	public function save() {
		$this->load->language('checkout/checkout');
		$this->load->helper('codecart_checkout');

		$compact_delivery_checkout = !empty($this->request->get['quick']) && $this->useCompactCarrierCheckout() && !$this->isDigitalCheckout();
		$json = array();

		// CodeCart: normalize optional/malformed checkout POST so PHP 8.x does not emit undefined-key warnings.
		$this->request->post = array_merge(array('firstname' => '', 'lastname' => '', 'email' => '', 'telephone' => '', 'company' => '', 'address_1' => '', 'address_2' => '', 'city' => '', 'postcode' => '', 'country_id' => '', 'zone_id' => ''), is_array($this->request->post) ? $this->request->post : array());

		if ($compact_delivery_checkout) {
			$this->request->post['company'] = '';
			$this->request->post['address_1'] = '';
			$this->request->post['address_2'] = '';
			$this->request->post['city'] = '';
			$this->request->post['postcode'] = '';
			$this->request->post['email'] = '';
		}

		foreach (array('firstname', 'lastname', 'email', 'telephone', 'company', 'address_1', 'address_2', 'city', 'postcode') as $checkout_field) {
			if (!codecart_checkout_field_visible($this->config, $checkout_field)) {
				$this->request->post[$checkout_field] = '';
			}
		}

		// Hidden/blank location fields fall back to the store location, so a one-country shop can ask only for region.
		if (!codecart_checkout_field_visible($this->config, 'country') || $this->request->post['country_id'] === '') {
			$this->request->post['country_id'] = (int)$this->config->get('config_country_id');
		}
		if (!codecart_checkout_field_visible($this->config, 'zone') || $this->request->post['zone_id'] === '') {
			$this->request->post['zone_id'] = (int)$this->config->get('config_zone_id');
		}

		// CodeCart: normalize nested custom-field payloads before validation.
		if (!isset($this->request->post['custom_field']) || !is_array($this->request->post['custom_field'])) {
			$this->request->post['custom_field'] = array();
		}
		if (!isset($this->request->post['custom_field']['account']) || !is_array($this->request->post['custom_field']['account'])) {
			$this->request->post['custom_field']['account'] = array();
		}
		if (!isset($this->request->post['custom_field']['address']) || !is_array($this->request->post['custom_field']['address'])) {
			$this->request->post['custom_field']['address'] = array();
		}

		// Validate if customer is logged in.
		if ($this->customer->isLogged()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		// Check if guest checkout is available.
		if (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		if (!$json) {
			$firstname_len = utf8_strlen($this->request->post['firstname']);
			if ((($compact_delivery_checkout || codecart_checkout_field_required($this->config, 'firstname')) && $firstname_len < 1) || $firstname_len > 32) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			$lastname_len = utf8_strlen($this->request->post['lastname']);
			if ((($compact_delivery_checkout || codecart_checkout_field_required($this->config, 'lastname')) && $lastname_len < 1) || $lastname_len > 32) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}

			$email = trim((string)$this->request->post['email']);
			if ($email !== '') {
				if (utf8_strlen($email) > 96 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$json['error']['email'] = $this->language->get('error_email');
				}
			} elseif ($this->isDigitalCheckout() || (!$compact_delivery_checkout && codecart_checkout_field_required($this->config, 'email'))) {
				$json['error']['email'] = $this->language->get('error_email');
			}

			$telephone_len = utf8_strlen($this->request->post['telephone']);
			if ((($compact_delivery_checkout || codecart_checkout_field_required($this->config, 'telephone')) && $telephone_len < 3) || $telephone_len > 32 || ($telephone_len > 0 && ($telephone_len < 3 || !preg_match('/^[0-9+()\s.\-]+$/', (string)$this->request->post['telephone'])))) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}

			if (utf8_strlen($this->request->post['company']) > 40) {
				$json['error']['company'] = $this->language->get('error_company');
			}

			$address_len = utf8_strlen($this->request->post['address_1']);
			if (((!$compact_delivery_checkout && codecart_checkout_field_required($this->config, 'address_1')) && $address_len < 1) || $address_len > 128 || ($address_len > 0 && $address_len < 1)) {
				$json['error']['address_1'] = $this->language->get('error_address_1');
			}

			if (utf8_strlen($this->request->post['address_2']) > 128) {
				$json['error']['address_2'] = $this->language->get('error_address_1');
			}

			$city_len = utf8_strlen($this->request->post['city']);
			if (((!$compact_delivery_checkout && codecart_checkout_field_required($this->config, 'city')) && $city_len < 2) || $city_len > 128 || ($city_len > 0 && $city_len < 2)) {
				$json['error']['city'] = $this->language->get('error_city');
			}

			$this->load->model('localisation/country');
			$country_info = $this->model_localisation_country->getCountry((int)$this->request->post['country_id']);

			$postcode_len = utf8_strlen($this->request->post['postcode']);
			if ($postcode_len > 10 || ((!$compact_delivery_checkout && codecart_checkout_field_required($this->config, 'postcode')) && $country_info && $country_info['postcode_required'] && ($postcode_len < 3 || $postcode_len > 10))) {
				$json['error']['postcode'] = $this->language->get('error_postcode');
			}

			if (!$compact_delivery_checkout && codecart_checkout_field_required($this->config, 'country') && !$country_info) {
				$json['error']['country'] = $this->language->get('error_country');
			}

			if (!$compact_delivery_checkout && codecart_checkout_field_required($this->config, 'zone') && (!is_numeric($this->request->post['zone_id']) || (int)$this->request->post['zone_id'] <= 0)) {
				$json['error']['zone'] = $this->language->get('error_zone');
			}

			// Customer Group
			if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
				$customer_group_id = $this->request->post['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}

			// Custom field validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

			foreach ($custom_fields as $custom_field) {
				// Address custom fields are irrelevant in compact carrier/pickup checkout,
				// but account fields remain active so company requisites can be required
				// by the selected customer group using standard OpenCart configuration.
				if ($compact_delivery_checkout && $custom_field['location'] !== 'account') {
					continue;
				}
				if($custom_field['location'] == 'affiliate') {
					continue;
				}
				
				if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                    $json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                }
			}

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('guest', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error']['captcha'] = $captcha;
				}
			}
		}

		if (!$json) {
			if (trim((string)$this->request->post['email']) === '') {
				$this->request->post['email'] = codecart_checkout_email_fallback($this->config);
			}
			$this->session->data['account'] = 'guest';

			$this->session->data['guest']['customer_group_id'] = $customer_group_id;
			$this->session->data['guest']['firstname'] = $this->request->post['firstname'];
			$this->session->data['guest']['lastname'] = $this->request->post['lastname'];
			$this->session->data['guest']['email'] = $this->request->post['email'];
			$this->session->data['guest']['telephone'] = $this->request->post['telephone'];

			if (isset($this->request->post['custom_field']['account'])) {
				$this->session->data['guest']['custom_field'] = $this->request->post['custom_field']['account'];
			} else {
				$this->session->data['guest']['custom_field'] = array();
			}

			$this->session->data['payment_address']['firstname'] = $this->request->post['firstname'];
			$this->session->data['payment_address']['lastname'] = $this->request->post['lastname'];
			$this->session->data['payment_address']['company'] = $this->request->post['company'];
			$this->session->data['payment_address']['address_1'] = $this->request->post['address_1'];
			$this->session->data['payment_address']['address_2'] = $this->request->post['address_2'];
			$this->session->data['payment_address']['postcode'] = $this->request->post['postcode'];
			$this->session->data['payment_address']['city'] = $this->request->post['city'];
			$this->session->data['payment_address']['country_id'] = $this->request->post['country_id'];
			$this->session->data['payment_address']['zone_id'] = $this->request->post['zone_id'];

			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info) {
				$this->session->data['payment_address']['country'] = $country_info['name'];
				$this->session->data['payment_address']['iso_code_2'] = $country_info['iso_code_2'];
				$this->session->data['payment_address']['iso_code_3'] = $country_info['iso_code_3'];
				$this->session->data['payment_address']['address_format'] = $country_info['address_format'];
			} else {
				$this->session->data['payment_address']['country'] = '';
				$this->session->data['payment_address']['iso_code_2'] = '';
				$this->session->data['payment_address']['iso_code_3'] = '';
				$this->session->data['payment_address']['address_format'] = '';
			}

			if (isset($this->request->post['custom_field']['address'])) {
				$this->session->data['payment_address']['custom_field'] = $this->request->post['custom_field']['address'];
			} else {
				$this->session->data['payment_address']['custom_field'] = array();
			}

			$this->load->model('localisation/zone');

			$zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

			if ($zone_info) {
				$this->session->data['payment_address']['zone'] = $zone_info['name'];
				$this->session->data['payment_address']['zone_code'] = $zone_info['code'];
			} else {
				$this->session->data['payment_address']['zone'] = '';
				$this->session->data['payment_address']['zone_code'] = '';
			}

			if ($compact_delivery_checkout || !empty($this->request->post['shipping_address'])) {
				$this->session->data['guest']['shipping_address'] = $this->request->post['shipping_address'];
			} else {
				$this->session->data['guest']['shipping_address'] = false;
			}

			if ($this->session->data['guest']['shipping_address']) {
				$this->session->data['shipping_address']['firstname'] = $this->request->post['firstname'];
				$this->session->data['shipping_address']['lastname'] = $this->request->post['lastname'];
				$this->session->data['shipping_address']['company'] = $this->request->post['company'];
				$this->session->data['shipping_address']['address_1'] = $this->request->post['address_1'];
				$this->session->data['shipping_address']['address_2'] = $this->request->post['address_2'];
				$this->session->data['shipping_address']['postcode'] = $this->request->post['postcode'];
				$this->session->data['shipping_address']['city'] = $this->request->post['city'];
				$this->session->data['shipping_address']['country_id'] = $this->request->post['country_id'];
				$this->session->data['shipping_address']['zone_id'] = $this->request->post['zone_id'];

				if ($country_info) {
					$this->session->data['shipping_address']['country'] = $country_info['name'];
					$this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
					$this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
					$this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
				} else {
					$this->session->data['shipping_address']['country'] = '';
					$this->session->data['shipping_address']['iso_code_2'] = '';
					$this->session->data['shipping_address']['iso_code_3'] = '';
					$this->session->data['shipping_address']['address_format'] = '';
				}

				if ($zone_info) {
					$this->session->data['shipping_address']['zone'] = $zone_info['name'];
					$this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
				} else {
					$this->session->data['shipping_address']['zone'] = '';
					$this->session->data['shipping_address']['zone_code'] = '';
				}

				if (isset($this->request->post['custom_field']['address'])) {
					$this->session->data['shipping_address']['custom_field'] = $this->request->post['custom_field']['address'];
				} else {
					$this->session->data['shipping_address']['custom_field'] = array();
				}
			}

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			if ((string)$this->config->get('config_captcha') === 'basic') { $this->load->controller('extension/captcha/basic/consume'); }
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	private function useCompactCarrierCheckout() {
		if (!codecart_carrier_compact_checkout($this->config)) {
			return false;
		}

		// Address-less compact mode is safe only while Carrier Choice is the sole
		// enabled shipping extension. If any standard/third-party OpenCart shipping
		// module is enabled, restore normal address fields so its getQuote() receives
		// the address data expected by OpenCart/ocStore.
		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getExtensions('shipping');

		foreach ($extensions as $extension) {
			$code = isset($extension['code']) ? (string)$extension['code'] : '';
			if ($code === '' || !$this->config->get('shipping_' . $code . '_status')) {
				continue;
			}
			if ($code !== 'carrier_choice') {
				return false;
			}
		}

		return true;
	}

	private function checkoutFieldEnabled($field) {
		if ($this->isDigitalCheckout()) {
			if ($field === 'email') { return true; }
			$key = 'config_digital_checkout_field_' . $field;
			return $this->config->has($key) ? (bool)$this->config->get($key) : false;
		}

		$this->load->helper('codecart_checkout');
		return codecart_checkout_field_visible($this->config, $field);
	}

	private function isDigitalCheckout() {
		return (bool)$this->config->get('config_digital_checkout_status') && $this->cart->hasDownload() && !$this->cart->hasShipping();
	}

}
