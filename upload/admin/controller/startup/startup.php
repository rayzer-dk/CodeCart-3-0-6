<?php
class ControllerStartupStartup extends Controller {
	public function index() {
		// Settings
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");
		
		foreach ($query->rows as $setting) {
			if (!$setting['serialized']) {
				$this->config->set($setting['key'], $setting['value']);
			} else {
				$this->config->set($setting['key'], json_decode($setting['value'], true));
			}
		}

		// Set time zone
		if ($this->config->get('config_timezone')) {
			date_default_timezone_set($this->config->get('config_timezone'));

			// Sync PHP and DB time zones.
			$this->db->query("SET time_zone = '" . $this->db->escape(date('P')) . "'");
		}

		// Theme
		$this->config->set('template_cache', $this->config->get('developer_theme'));
				
		// Admin interface language: individual preference, never the storefront default.
		// On an incomplete upgrade fall back to the configured language without breaking login.
		$adminCode = (string)$this->config->get('config_admin_language');
		// Keep product/category content language tied to the configured store admin
		// language. Interface-only overrides must not select another content language.
		$baseLanguage = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($adminCode) . "' LIMIT 1");
		if ($baseLanguage->num_rows) {
			$this->config->set('config_language_id', (int)$baseLanguage->row['language_id']);
		}
		try {
			$available = \CodeCart\Core\AdminLanguagePreference::available($this->db);
			$this->registry->set('codecart_admin_languages', $available);
			$userId = isset($this->session->data['user_id']) ? (int)$this->session->data['user_id'] : 0;
			$preferred = \CodeCart\Core\AdminLanguagePreference::read($this->db, $userId);
			if ($preferred !== '' && isset($available[$preferred])) {
				$adminCode = $preferred;
				$this->registry->set('codecart_admin_explicit_language', $preferred);
			}
		} catch (\Throwable $e) {
			// Never lock an administrator out due to an unavailable optional preference.
		}
		$this->config->set('config_admin_language', $adminCode);
		$language = new Language($adminCode);
		$language->load($adminCode);
		$this->registry->set('language', $language);
		
		// Customer
		$this->registry->set('customer', new Cart\Customer($this->registry));

		// Currency
		$this->registry->set('currency', new Cart\Currency($this->registry));
	
		// Tax
		$this->registry->set('tax', new Cart\Tax($this->registry));
		
		if ($this->config->get('config_tax_default') == 'shipping') {
			$this->tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		if ($this->config->get('config_tax_default') == 'payment') {
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
