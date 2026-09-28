<?php
class ControllerStartupStartup extends Controller {

	public function __isset($key) {
		// To make sure that calls to isset also support dynamic properties from the registry
		// See https://www.php.net/manual/en/language.oop5.overloading.php#object.isset
		if ($this->registry) {
			if ($this->registry->get($key)!==null) {
				return true;
			}
		}
		return false;
	}

	public function index() {
		// Store
		// Do not assume browser-only server variables: catalog startup is also used by CLI/cron.
		$is_https = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
		$host = isset($this->request->server['HTTP_HOST']) ? trim((string)$this->request->server['HTTP_HOST']) : '';

		if ($host !== '') {
			$script_name = isset($this->request->server['PHP_SELF']) ? (string)$this->request->server['PHP_SELF'] : '/index.php';
			$base_path = rtrim(dirname($script_name), '/.\\');
			$current_store_url = ($is_https ? 'https://' : 'http://') . str_replace('www.', '', $host) . ($base_path !== '' ? $base_path : '') . '/';
		} else {
			// CLI/cron has no HTTP_HOST. Use configured base URL only for store lookup;
			// this resolves to store 0 when no matching secondary store exists.
			$current_store_url = $is_https ? HTTPS_SERVER : HTTP_SERVER;
			$current_store_url = preg_replace('#^https?://www\.#i', ($is_https ? 'https://' : 'http://'), (string)$current_store_url);
		}

		$store_column = $is_https ? 'ssl' : 'url';
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`" . $store_column . "`, 'www.', '') = '" . $this->db->escape($current_store_url) . "'");
		
		if (isset($this->request->get['store_id'])) {
			$this->config->set('config_store_id', (int)$this->request->get['store_id']);
		} else if ($query->num_rows) {
			$this->config->set('config_store_id', $query->row['store_id']);
		} else {
			$this->config->set('config_store_id', 0);
		}
		
		if (!$query->num_rows) {
			$this->config->set('config_url', HTTP_SERVER);
			$this->config->set('config_ssl', HTTPS_SERVER);
		}
		
		// Settings
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' OR store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY store_id ASC");
		
		foreach ($query->rows as $result) {
			if (!$result['serialized']) {
				$this->config->set($result['key'], $result['value']);
			} else {
				$this->config->set($result['key'], json_decode($result['value'], true));
			}
		}

		// Storefront image URLs may vary between original format and WebP based on Accept.
		// Tell HTTP/proxy caches about that negotiation; CLI/cron never enters this path.
		if ((int)$this->config->get('config_image_webp') && function_exists('imagewebp')) {
			$this->response->addHeader('Vary: Accept');
		}

		// Set time zone
		if ($this->config->get('config_timezone')) {
			date_default_timezone_set($this->config->get('config_timezone'));

			// Sync PHP and DB time zones.
			$this->db->query("SET time_zone = '" . $this->db->escape(date('P')) . "'");
		}

		// Theme
		$this->config->set('template_cache', $this->config->get('developer_theme'));
		
		// Url
		$this->registry->set('url', new Url($this->config->get('config_url'), $this->config->get('config_ssl')));
		
		// Language
		$code = '';
		
		$this->load->model('localisation/language');
		
		$languages = $this->model_localisation_language->getLanguages();

		// Optional language prefixes are URL routing metadata, not physical directories.
		// If every prefix is empty, OpenCart/ocStore keeps its legacy language behaviour.
		$url_language_code = '';
		$url_language_explicit = false;
		$language_prefixes = array();
		$prefix_enabled = false;
		$external_langdir = (bool)$this->config->get('langdir_status');

		if (!$external_langdir) {
			foreach ($languages as $language_code => $language_info) {
				$prefix = isset($language_info['url_prefix']) ? strtolower(trim((string)$language_info['url_prefix'])) : '';
				$language_prefixes[$language_code] = $prefix;
				if ($prefix !== '') {
					$prefix_enabled = true;
				}
			}
		}

		$this->config->set('codecart_language_prefix_enabled', $prefix_enabled ? 1 : 0);
		$this->config->set('codecart_language_prefix_explicit', 0);
		$this->config->set('codecart_language_prefix_missing_required', 0);
		$this->config->set('codecart_language_prefix_current', '');

		if ($prefix_enabled) {
			$prefix_to_code = array();
			foreach ($language_prefixes as $language_code => $prefix) {
				if ($prefix !== '') {
					$prefix_to_code[$prefix] = $language_code;
				}
			}

			$route = isset($this->request->get['_route_']) ? trim((string)$this->request->get['_route_'], '/') : '';
			$parts = $route !== '' ? explode('/', $route) : array();
			$first = $parts ? strtolower(rawurldecode((string)$parts[0])) : '';

			if ($first !== '' && isset($prefix_to_code[$first])) {
				$url_language_code = $prefix_to_code[$first];
				$url_language_explicit = true;
				array_shift($parts);

				// /en/index.php?route=... remains compatible with non-SEO links.
				if ($parts && strtolower((string)$parts[0]) === 'index.php') {
					array_shift($parts);
				}

				if ($parts) {
					$this->request->get['_route_'] = implode('/', $parts);
				} else {
					unset($this->request->get['_route_']);
				}
			} else {
				// Normalize an unprefixed route too. Prefixed routes are rebuilt above
				// from a trimmed route, but the primary language used to keep a trailing
				// empty segment (for example /laptop-notebook/). SeoPro interpreted
				// that empty segment as another keyword and returned a false 404.
				if ($route !== '') {
					$this->request->get['_route_'] = $route;
				}

				// In prefix mode an unprefixed URL belongs to the language whose prefix is empty.
				// Prefer the configured default language. If every language has a prefix, the
				// configured default is selected and SeoPro can canonicalize to its prefixed URL.
				$default_code = (string)$this->config->get('config_language');
				if (isset($language_prefixes[$default_code]) && $language_prefixes[$default_code] === '') {
					$url_language_code = $default_code;
				} else {
					foreach ($language_prefixes as $language_code => $prefix) {
						if ($prefix === '') {
							$url_language_code = $language_code;
							break;
						}
					}
					if ($url_language_code === '') {
						$url_language_code = $default_code;
						if (isset($languages[$url_language_code])) {
							// Every active language has a prefix, but this request has none.
							// Keep the configured default language for this request and let the
							// SEO startup canonicalize GET/HEAD to /<prefix>/... exactly once.
							$this->config->set('codecart_language_prefix_missing_required', 1);
						}
					}
				}
				$url_language_explicit = isset($languages[$url_language_code]);
			}
		}
		
		if (isset($this->session->data['language'])) {
			$code = $this->session->data['language'];
		}
				
		if (isset($this->request->cookie['language']) && !array_key_exists($code, $languages)) {
			$code = $this->request->cookie['language'];
		}
		
		// Language Detection
		if (!empty($this->request->server['HTTP_ACCEPT_LANGUAGE']) && !array_key_exists($code, $languages)) {
			$detect = '';
			
			$browser_languages = explode(',', $this->request->server['HTTP_ACCEPT_LANGUAGE']);
			
			// Try using local to detect the language
			foreach ($browser_languages as $browser_language) {
				foreach ($languages as $key => $value) {
					if ($value['status']) {
						$locale = explode(',', $value['locale']);
						
						if (in_array($browser_language, $locale)) {
							$detect = $key;
							break 2;
						}
					}
				}	
			}			
			
			if (!$detect) { 
				// Try using language folder to detect the language
				foreach ($browser_languages as $browser_language) {
					if (array_key_exists(strtolower($browser_language), $languages)) {
						$detect = strtolower($browser_language);
						
						break;
					}
				}
			}
			
			$code = $detect ? $detect : '';
		}

		if ($url_language_explicit && isset($languages[$url_language_code])) {
			$code = $url_language_code;
			$this->config->set('codecart_language_prefix_explicit', 1);
		}
		
		if (!array_key_exists($code, $languages)) {
			$code = $this->config->get('config_language');
		}
		
		if (!isset($this->session->data['language']) || $this->session->data['language'] != $code) {
			$this->session->data['language'] = $code;
		}
				
		if (!isset($this->request->cookie['language']) || $this->request->cookie['language'] != $code) {
			setcookie('language', $code, time() + 60 * 60 * 24 * 30, '/');
		}
				
		// Overwrite the default language object
		$language = new Language($code);
		$language->load($code);
		
		$this->registry->set('language', $language);
		
		// Set the config language_id
		$this->config->set('config_language_id', $languages[$code]['language_id']);
		$this->config->set('codecart_language_prefix_current', ($prefix_enabled && isset($language_prefixes[$code])) ? $language_prefixes[$code] : '');

		// Customer
		$customer = new Cart\Customer($this->registry);
		$this->registry->set('customer', $customer);
		
		// Customer Group
		if (isset($this->session->data['customer']) && isset($this->session->data['customer']['customer_group_id'])) {
			// For API calls
			$this->config->set('config_customer_group_id', $this->session->data['customer']['customer_group_id']);
		} elseif ($this->customer->isLogged()) {
			// Logged in customers
			$this->config->set('config_customer_group_id', $this->customer->getGroupId());
		} elseif (isset($this->session->data['guest']) && isset($this->session->data['guest']['customer_group_id'])) {
			$this->config->set('config_customer_group_id', $this->session->data['guest']['customer_group_id']);
		} else {
			$this->config->set('config_customer_group_id', $this->config->get('config_customer_group_id'));
		}
		
		// Tracking Code
		if (isset($this->request->get['tracking'])) {
			setcookie('tracking', $this->request->get['tracking'], time() + 3600 * 24 * 1000, '/');
		
			$this->db->query("UPDATE `" . DB_PREFIX . "marketing` SET clicks = (clicks + 1) WHERE code = '" . $this->db->escape($this->request->get['tracking']) . "'");
		}		
		
		// Currency
		$code = '';
		
		$this->load->model('localisation/currency');
		
		$currencies = $this->model_localisation_currency->getCurrencies();
		
		if (isset($this->session->data['currency'])) {
			$code = $this->session->data['currency'];
		}
		
		if (isset($this->request->cookie['currency']) && !array_key_exists($code, $currencies)) {
			$code = $this->request->cookie['currency'];
		}
		
		if (!array_key_exists($code, $currencies)) {
			$code = $this->config->get('config_currency');
		}
		
		if (!isset($this->session->data['currency']) || $this->session->data['currency'] != $code) {
			$this->session->data['currency'] = $code;
		}
		
		if (!isset($this->request->cookie['currency']) || $this->request->cookie['currency'] != $code) {
			setcookie('currency', $code, time() + 60 * 60 * 24 * 30, '/');
		}		
		
		$this->registry->set('currency', new Cart\Currency($this->registry));
		
		// Tax
		$this->registry->set('tax', new Cart\Tax($this->registry));
		
		// PHP v7.4+ validation compatibility.
		if (isset($this->session->data['shipping_address']['country_id']) && isset($this->session->data['shipping_address']['zone_id'])) {
			$this->tax->setShippingAddress($this->session->data['shipping_address']['country_id'], $this->session->data['shipping_address']['zone_id']);
		} elseif ($this->config->get('config_tax_default') == 'shipping') {
			$this->tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		if (isset($this->session->data['payment_address']['country_id']) && isset($this->session->data['payment_address']['zone_id'])) {
			$this->tax->setPaymentAddress($this->session->data['payment_address']['country_id'], $this->session->data['payment_address']['zone_id']);
		} elseif ($this->config->get('config_tax_default') == 'payment') {
			$this->tax->setPaymentAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		$this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		
		// Weight
		$this->registry->set('weight', new Cart\Weight($this->registry));
		
		// Length
		$this->registry->set('length', new Cart\Length($this->registry));
		
		// Cart
		$this->registry->set('cart', new Cart\Cart($this->registry));
		
		// Encryption
		$this->registry->set('encryption', new Encryption());
	}
}
