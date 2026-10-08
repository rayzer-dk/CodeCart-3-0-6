<?php
class ControllerSettingSetting extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// Single built-in editor: local Summernote with its native plain Code View. Keep config_editor for legacy compatibility.
			$this->request->post['config_editor'] = 'summernote';
			$this->request->post['codecart_internal_linking_status'] = !empty($this->request->post['codecart_internal_linking_status']) ? 1 : 0;
			foreach (array(
				'config_admin_accent_color' => '#0b6fd3',
				'config_admin_sidebar_color' => '#1f2937',
				'config_admin_submenu_color' => '#293141',
				'config_admin_surface_color' => '#f5f7fa',
				'theme_default_accent_color' => '#0b6fd3',
				'theme_default_accent_hover' => '#095eb4',
				'theme_default_button_color' => '#0b6fd3',
				'theme_default_button_hover' => '#095eb4',
				'theme_default_button_text_color' => '#ffffff',
				'theme_default_buy_button_color' => '#0b6fd3',
				'theme_default_buy_button_hover' => '#095eb4',
				'theme_default_buy_button_text_color' => '#ffffff',
				'theme_default_sale_price_color' => '#d92d20',
				'theme_default_cart_button_color' => '#0b6fd3',
				'theme_default_cart_button_hover' => '#095eb4',
				'theme_default_cart_button_text_color' => '#ffffff',
				'theme_default_text_color' => '#3d4652',
				'theme_default_heading_color' => '#273142',
				'theme_default_background_color' => '#ffffff',
				'theme_default_surface_color' => '#f7f9fb',
				'theme_default_border_color' => '#e5e9ef',
				'theme_default_footer_background_color' => '#303030',
				'theme_default_footer_text_color' => '#e2e2e2',
				'theme_default_footer_link_color' => '#cccccc',
				'theme_default_footer_heading_color' => '#ffffff'
			) as $color_key => $color_default) {
				if (!isset($this->request->post[$color_key]) || !preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->request->post[$color_key])) {
					$this->request->post[$color_key] = $color_default;
				}
			}

			$checkout_fields = array('firstname', 'lastname', 'email', 'telephone', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'zone');
			$checkout_modes = array('required', 'optional', 'hidden');
			foreach ($checkout_fields as $checkout_field) {
				$mode_key = 'config_checkout_field_' . $checkout_field . '_mode';
				$mode = isset($this->request->post[$mode_key]) ? strtolower(trim((string)$this->request->post[$mode_key])) : '';
				if (!in_array($mode, $checkout_modes, true)) {
					$mode = in_array($checkout_field, array('company', 'address_2'), true) ? 'optional' : 'required';
				}
				$this->request->post[$mode_key] = $mode;

				// Keep legacy OpenCart/third-party checks working: hidden = disabled, all visible modes = enabled.
				$this->request->post['config_checkout_field_' . $checkout_field] = $mode === 'hidden' ? 0 : 1;
			}

			$this->request->post['config_quick_checkout_status'] = !empty($this->request->post['config_quick_checkout_status']) ? 1 : 0;
			$fallback_email = trim((string)($this->request->post['config_checkout_email_fallback'] ?? ''));
			$this->request->post['config_checkout_email_fallback'] = filter_var($fallback_email, FILTER_VALIDATE_EMAIL) ? utf8_substr($fallback_email, 0, 96) : 'checkout@invalid.local';

			$checkout_labels = isset($this->request->post['config_checkout_field_labels']) && is_array($this->request->post['config_checkout_field_labels']) ? $this->request->post['config_checkout_field_labels'] : array();
			foreach ($checkout_labels as $language_id => &$language_labels) {
				if (!is_array($language_labels)) { $language_labels = array(); continue; }
				foreach ($checkout_fields as $checkout_field) {
					$language_labels[$checkout_field] = utf8_substr(trim(strip_tags((string)($language_labels[$checkout_field] ?? ''))), 0, 80);
				}
			}
			unset($language_labels);
			$this->request->post['config_checkout_field_labels'] = $checkout_labels;

			$this->request->post['config_digital_checkout_status'] = !empty($this->request->post['config_digital_checkout_status']) ? 1 : 0;
			// E-mail is mandatory for digital orders: it identifies the customer and is used for order/download notices.
			$this->request->post['config_digital_checkout_field_email'] = 1;
			foreach (array('lastname', 'telephone', 'company', 'address_1', 'address_2', 'city', 'postcode') as $digital_field) {
				$key = 'config_digital_checkout_field_' . $digital_field;
				$this->request->post[$key] = !empty($this->request->post[$key]) ? 1 : 0;
			}

			// SeoPro is an advanced SEO URL mode and requires the master SEO URL switch.
			// Never persist the contradictory state SeoPro=ON + SEO URL=OFF.
			if (!empty($this->request->post['config_seo_pro'])) {
				$this->request->post['config_seo_url'] = 1;
			}

			$this->request->post['config_codecart_structured_data_status'] = !empty($this->request->post['config_codecart_structured_data_status']) ? 1 : 0;
			$this->request->post['config_cookie_consent_status'] = !empty($this->request->post['config_cookie_consent_status']) ? 1 : 0;
			$this->request->post['config_cookie_consent_days'] = max(30, min(365, (int)($this->request->post['config_cookie_consent_days'] ?? 180)));
			$this->request->post['config_cookie_consent_information_id'] = (int)($this->request->post['config_cookie_consent_information_id'] ?? 0);
			$this->request->post['config_compression'] = max(0, min(9, (int)($this->request->post['config_compression'] ?? 0)));
			$this->request->post['config_cookie_consent_privacy_information_id'] = (int)($this->request->post['config_cookie_consent_privacy_information_id'] ?? 0);
			$cookie_accent = strtolower(trim((string)($this->request->post['config_cookie_consent_accent_color'] ?? '#0b6fd3')));
			$this->request->post['config_cookie_consent_accent_color'] = preg_match('/^#[0-9a-f]{6}$/', $cookie_accent) ? $cookie_accent : '#0b6fd3';
			$cookie_icon = strtolower(trim((string)($this->request->post['config_cookie_consent_icon'] ?? 'shield_cookie_check')));
			$cookie_icon_allowed = array('cookie_orbit','cookie_document','shield_lock_check','lock_circle_check','shield_cookie_check','hand_shield_check','shield_lock','custom');
			$this->request->post['config_cookie_consent_icon'] = in_array($cookie_icon, $cookie_icon_allowed, true) ? $cookie_icon : 'shield_cookie_check';
			$cookie_custom_icon = trim((string)($this->request->post['config_cookie_consent_custom_icon'] ?? ''));
			$cookie_custom_icon = str_replace(array('..\\','../','\\'), array('','','/'), $cookie_custom_icon);
			$this->request->post['config_cookie_consent_custom_icon'] = ($cookie_custom_icon !== '' && is_file(DIR_IMAGE . $cookie_custom_icon)) ? $cookie_custom_icon : '';

			$this->model_setting_setting->editSetting('config', $this->request->post);

			// codecart_* values intentionally use a shared code namespace. Persist them one-by-one: editSetting()
			// is destructive and only keeps keys beginning with the code itself.
			$this->model_setting_setting->setSettingValue('codecart_core', 'codecart_internal_linking_status', (int)$this->request->post['codecart_internal_linking_status'], 0);
			$cache_engine = strtolower(trim((string)($this->request->post['codecart_cache_engine'] ?? 'file')));
			if (!in_array($cache_engine, array('file','apcu','memcached','redis'), true)) { $cache_engine = 'file'; }
			$this->model_setting_setting->setSettingValue('codecart_core', 'codecart_cache_engine', $cache_engine, 0);

			$theme_default = $this->model_setting_setting->getSetting('theme_default', 0);
			$theme_default['theme_default_accent_color'] = $this->request->post['theme_default_accent_color'];
			$theme_default['theme_default_accent_hover'] = $this->request->post['theme_default_accent_hover'];
			$theme_default['theme_default_button_color'] = $this->request->post['theme_default_button_color'];
			$theme_default['theme_default_button_hover'] = $this->request->post['theme_default_button_hover'];
			$theme_default['theme_default_button_text_color'] = $this->request->post['theme_default_button_text_color'];
			$theme_default['theme_default_buy_button_color'] = $this->request->post['theme_default_buy_button_color'];
			$theme_default['theme_default_buy_button_hover'] = $this->request->post['theme_default_buy_button_hover'];
			$theme_default['theme_default_buy_button_text_color'] = $this->request->post['theme_default_buy_button_text_color'];
			$theme_default['theme_default_sale_price_color'] = $this->request->post['theme_default_sale_price_color'];
			$theme_default['theme_default_cart_button_color'] = $this->request->post['theme_default_cart_button_color'];
			$theme_default['theme_default_cart_button_hover'] = $this->request->post['theme_default_cart_button_hover'];
			$theme_default['theme_default_cart_button_text_color'] = $this->request->post['theme_default_cart_button_text_color'];
			$theme_default['theme_default_text_color'] = $this->request->post['theme_default_text_color'];
			$theme_default['theme_default_heading_color'] = $this->request->post['theme_default_heading_color'];
			$theme_default['theme_default_background_color'] = $this->request->post['theme_default_background_color'];
			$theme_default['theme_default_surface_color'] = $this->request->post['theme_default_surface_color'];
			$theme_default['theme_default_border_color'] = $this->request->post['theme_default_border_color'];
			$theme_default['theme_default_footer_background_color'] = $this->request->post['theme_default_footer_background_color'];
			$theme_default['theme_default_footer_text_color'] = $this->request->post['theme_default_footer_text_color'];
			$theme_default['theme_default_footer_link_color'] = $this->request->post['theme_default_footer_link_color'];
			$theme_default['theme_default_footer_heading_color'] = $this->request->post['theme_default_footer_heading_color'];
			$this->model_setting_setting->editSetting('theme_default', $theme_default, 0);

//			if ($this->config->get('config_currency_auto')) {
//				$this->load->model('localisation/currency');
//
//				$this->model_localisation_currency->refresh();
//			}

			$this->session->data['success'] = $this->language->get('text_success');

			$save_tab = isset($this->request->post['active_tab']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$this->request->post['active_tab']) : 'general';
			if (!in_array($save_tab, array('general','store','local','option','image','mail','appearance','server','seopro'), true)) { $save_tab = 'general'; }
			$this->response->redirect($this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'] . '&tab=' . rawurlencode($save_tab), true) . '#tab-' . $save_tab);
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['owner'])) {
			$data['error_owner'] = $this->error['owner'];
		} else {
			$data['error_owner'] = '';
		}

		if (isset($this->error['address'])) {
			$data['error_address'] = $this->error['address'];
		} else {
			$data['error_address'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = '';
		}

		if (isset($this->error['country'])) {
			$data['error_country'] = $this->error['country'];
		} else {
			$data['error_country'] = '';
		}

		if (isset($this->error['zone'])) {
			$data['error_zone'] = $this->error['zone'];
		} else {
			$data['error_zone'] = '';
		}

		if (isset($this->error['customer_group_display'])) {
			$data['error_customer_group_display'] = $this->error['customer_group_display'];
		} else {
			$data['error_customer_group_display'] = '';
		}

		if (isset($this->error['login_attempts'])) {
			$data['error_login_attempts'] = $this->error['login_attempts'];
		} else {
			$data['error_login_attempts'] = '';
		}

		if (isset($this->error['voucher_min'])) {
			$data['error_voucher_min'] = $this->error['voucher_min'];
		} else {
			$data['error_voucher_min'] = '';
		}

		if (isset($this->error['voucher_max'])) {
			$data['error_voucher_max'] = $this->error['voucher_max'];
		} else {
			$data['error_voucher_max'] = '';
		}

		if (isset($this->error['processing_status'])) {
			$data['error_processing_status'] = $this->error['processing_status'];
		} else {
			$data['error_processing_status'] = '';
		}

		if (isset($this->error['complete_status'])) {
			$data['error_complete_status'] = $this->error['complete_status'];
		} else {
			$data['error_complete_status'] = '';
		}

		if (isset($this->error['log'])) {
			$data['error_log'] = $this->error['log'];
		} else {
			$data['error_log'] = '';
		}

		if (isset($this->error['limit_admin'])) {
			$data['error_limit_admin'] = $this->error['limit_admin'];
		} else {
			$data['error_limit_admin'] = '';
		}

        if (isset($this->error['limit_autocomplete'])) {
            $data['error_limit_autocomplete'] = $this->error['limit_autocomplete'];
        } else {
            $data['error_limit_autocomplete'] = '';
        }

		if (isset($this->error['encryption'])) {
			$data['error_encryption'] = $this->error['encryption'];
		} else {
			$data['error_encryption'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_stores'),
			'href' => $this->url->link('setting/store', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true)
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['action'] = $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true);
		$allowed_tabs = array('general','store','local','option','image','mail','appearance','server','seopro');
		$data['active_tab'] = isset($this->request->post['active_tab']) && in_array((string)$this->request->post['active_tab'], $allowed_tabs, true) ? (string)$this->request->post['active_tab'] : (isset($this->request->get['tab']) && in_array((string)$this->request->get['tab'], $allowed_tabs, true) ? (string)$this->request->get['tab'] : 'general');

		$data['cancel'] = $this->url->link('setting/store', 'user_token=' . $this->session->data['user_token'], true);

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['config_meta_title'])) {
			$data['config_meta_title'] = $this->request->post['config_meta_title'];
		} else {
			$data['config_meta_title'] = $this->config->get('config_meta_title');
		}

		if (isset($this->request->post['config_meta_description'])) {
			$data['config_meta_description'] = $this->request->post['config_meta_description'];
		} else {
			$data['config_meta_description'] = $this->config->get('config_meta_description');
		}

		if (isset($this->request->post['config_meta_keyword'])) {
			$data['config_meta_keyword'] = $this->request->post['config_meta_keyword'];
		} else {
			$data['config_meta_keyword'] = $this->config->get('config_meta_keyword');
		}

		if (isset($this->request->post['config_theme'])) {
			$data['config_theme'] = $this->request->post['config_theme'];
		} else {
			$data['config_theme'] = $this->config->get('config_theme');
		}

		if ($this->request->server['HTTPS']) {
			$data['store_url'] = HTTPS_CATALOG;
		} else {
			$data['store_url'] = HTTP_CATALOG;
		}

		$data['themes'] = array();

		$this->load->model('setting/extension');

		$extensions = $this->model_setting_extension->getInstalled('theme');

		$install_origin = strtolower((string)$this->config->get('codecart_install_origin'));
		$native_codecart_install = ($install_origin === 'fresh') || ($install_origin === '' && !in_array('default', $extensions, true));
		foreach ($extensions as $code) {
			if ($native_codecart_install && $code === 'default') { continue; }
			$this->load->language('extension/theme/' . $code, 'extension');
			
			$data['themes'][] = array(
				'text'  => $this->language->get('extension')->get('heading_title'),
				'value' => $code
			);
		}
			
		foreach (array(
			'config_admin_accent_color' => '#0b6fd3',
			'config_admin_sidebar_color' => '#1f2937',
			'config_admin_submenu_color' => '#293141',
			'config_admin_surface_color' => '#f5f7fa'
		) as $color_key => $color_default) {
			if (isset($this->request->post[$color_key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->request->post[$color_key])) {
				$data[$color_key] = $this->request->post[$color_key];
			} elseif (preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->config->get($color_key))) {
				$data[$color_key] = $this->config->get($color_key);
			} else {
				$data[$color_key] = $color_default;
			}
		}


		$theme_default = $this->model_setting_setting->getSetting('theme_default', 0);
		foreach (array(
			'theme_default_accent_color' => '#0b6fd3',
			'theme_default_accent_hover' => '#095eb4',
			'theme_default_button_color' => '#0b6fd3',
			'theme_default_button_hover' => '#095eb4',
			'theme_default_button_text_color' => '#ffffff',
			'theme_default_buy_button_color' => '#0b6fd3',
			'theme_default_buy_button_hover' => '#095eb4',
			'theme_default_buy_button_text_color' => '#ffffff',
				'theme_default_sale_price_color' => '#d92d20',
				'theme_default_cart_button_color' => '#0b6fd3',
				'theme_default_cart_button_hover' => '#095eb4',
				'theme_default_cart_button_text_color' => '#ffffff',
			'theme_default_text_color' => '#3d4652',
			'theme_default_heading_color' => '#273142',
			'theme_default_background_color' => '#ffffff',
			'theme_default_surface_color' => '#f7f9fb',
			'theme_default_border_color' => '#e5e9ef',
			'theme_default_footer_background_color' => '#303030',
			'theme_default_footer_text_color' => '#e2e2e2',
			'theme_default_footer_link_color' => '#cccccc',
			'theme_default_footer_heading_color' => '#ffffff'
		) as $color_key => $color_default) {
			if (isset($this->request->post[$color_key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->request->post[$color_key])) {
				$data[$color_key] = $this->request->post[$color_key];
			} elseif (isset($theme_default[$color_key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$theme_default[$color_key])) {
				$data[$color_key] = $theme_default[$color_key];
			} else {
				$data[$color_key] = $color_default;
			}
		}

		if (isset($this->request->post['config_layout_id'])) {
			$data['config_layout_id'] = $this->request->post['config_layout_id'];
		} else {
			$data['config_layout_id'] = $this->config->get('config_layout_id');
		}

		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		if (isset($this->request->post['config_name'])) {
			$data['config_name'] = $this->request->post['config_name'];
		} else {
			$data['config_name'] = $this->config->get('config_name');
		}

		if (isset($this->request->post['config_owner'])) {
			$data['config_owner'] = $this->request->post['config_owner'];
		} else {
			$data['config_owner'] = $this->config->get('config_owner');
		}

		if (isset($this->request->post['config_address'])) {
			$data['config_address'] = $this->request->post['config_address'];
		} else {
			$data['config_address'] = $this->config->get('config_address');
		}

		if (isset($this->request->post['config_geocode'])) {
			$data['config_geocode'] = $this->request->post['config_geocode'];
		} else {
			$data['config_geocode'] = $this->config->get('config_geocode');
		}

		if (isset($this->request->post['config_map_url'])) {
			$data['config_map_url'] = trim((string)$this->request->post['config_map_url']);
		} else {
			$data['config_map_url'] = (string)$this->config->get('config_map_url');
		}

		if (isset($this->request->post['config_email'])) {
			$data['config_email'] = $this->request->post['config_email'];
		} else {
			$data['config_email'] = $this->config->get('config_email');
		}

		if (isset($this->request->post['config_telephone'])) {
			$data['config_telephone'] = $this->request->post['config_telephone'];
		} else {
			$data['config_telephone'] = $this->config->get('config_telephone');
		}
		
		if (isset($this->request->post['config_fax'])) {
			$data['config_fax'] = $this->request->post['config_fax'];
		} else {
			$data['config_fax'] = $this->config->get('config_fax');
		}
		
		if (isset($this->request->post['config_image'])) {
			$data['config_image'] = $this->request->post['config_image'];
		} else {
			$data['config_image'] = $this->config->get('config_image');
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['config_image']) && is_file(DIR_IMAGE . $this->request->post['config_image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['config_image'], 100, 100);
		} elseif ($this->config->get('config_image') && is_file(DIR_IMAGE . $this->config->get('config_image'))) {
			$data['thumb'] = $this->model_tool_image->resize($this->config->get('config_image'), 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.webp', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('codecart_add_image.webp', 100, 100);


		if (isset($this->request->post['config_open'])) {
			$data['config_open'] = $this->request->post['config_open'];
		} else {
			$data['config_open'] = $this->config->get('config_open');
		}

		if (isset($this->request->post['config_comment'])) {
			$data['config_comment'] = $this->request->post['config_comment'];
		} else {
			$data['config_comment'] = $this->config->get('config_comment');
		}

		$this->load->model('localisation/location');

		$data['locations'] = $this->model_localisation_location->getLocations();

		if (isset($this->request->post['config_location'])) {
			$data['config_location'] = $this->request->post['config_location'];
		} elseif ($this->config->get('config_location')) {
			$data['config_location'] = $this->config->get('config_location');
		} else {
			$data['config_location'] = array();
		}

		if (isset($this->request->post['config_country_id'])) {
			$data['config_country_id'] = $this->request->post['config_country_id'];
		} else {
			$data['config_country_id'] = $this->config->get('config_country_id');
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post['config_zone_id'])) {
			$data['config_zone_id'] = (int)$this->request->post['config_zone_id'];
		} else {
			$data['config_zone_id'] = (int)$this->config->get('config_zone_id');
		}

		if (isset($this->request->post['config_timezone'])) {
			$data['config_timezone'] = $this->request->post['config_timezone'];
		} elseif ($this->config->has('config_timezone')) {
			$data['config_timezone'] = $this->config->get('config_timezone');
		} else {
			$data['config_timezone'] = 'UTC';
		}
		// Set Time Zone
		$data['timezones'] = array();

		$timestamp = time();

		$timezones = timezone_identifiers_list();

		foreach($timezones as $timezone) {
			date_default_timezone_set($timezone);
			$hour = ' (' . date('P', $timestamp) . ')';
			$data['timezones'][] = array(
				'text'  => $timezone . $hour,
				'value' => $timezone
			);
		}

		date_default_timezone_set($this->config->get('config_timezone'));

		if (isset($this->request->post['config_language'])) {
			$data['config_language'] = $this->request->post['config_language'];
		} else {
			$data['config_language'] = $this->config->get('config_language');
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['config_admin_language'])) {
			$data['config_admin_language'] = $this->request->post['config_admin_language'];
		} else {
			$data['config_admin_language'] = $this->config->get('config_admin_language');
		}

		if (isset($this->request->post['config_currency'])) {
			$data['config_currency'] = $this->request->post['config_currency'];
		} else {
			$data['config_currency'] = $this->config->get('config_currency');
		}

		if (isset($this->request->post['config_currency_auto'])) {
			$data['config_currency_auto'] = $this->request->post['config_currency_auto'];
		} else {
			$data['config_currency_auto'] = $this->config->get('config_currency_auto');
		}

		if (isset($this->request->post['config_currency_engine'])) {
			$data['config_currency_engine'] = $this->request->post['config_currency_engine'];
		} else {
			$data['config_currency_engine'] = $this->config->get('config_currency_engine');
		}

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$data['currency_engines'] = array();

		$extension_codes = $this->model_setting_extension->getInstalled('currency');

		foreach ($extension_codes as $extension_code) {
			if ($this->config->get('currency_' . $extension_code . '_status')) {
				$this->load->language('extension/currency/' . $extension_code, 'currency_engine');
				$data['currency_engines'][] = array(
					'text'  => $this->language->get('currency_engine')->get('heading_title'),
					'value' => $extension_code
				);
			}
		}

		if (isset($this->request->post['config_symbol_left_space'])) {
			$data['config_symbol_left_space'] = $this->request->post['config_symbol_left_space'];
		} else {
			$data['config_symbol_left_space'] = $this->config->get('config_symbol_left_space');
		}

		if (isset($this->request->post['config_symbol_right_space'])) {
			$data['config_symbol_right_space'] = $this->request->post['config_symbol_right_space'];
		} else {
			$data['config_symbol_right_space'] = $this->config->get('config_symbol_right_space');
		}

		if (isset($this->request->post['config_length_class_id'])) {
			$data['config_length_class_id'] = $this->request->post['config_length_class_id'];
		} else {
			$data['config_length_class_id'] = $this->config->get('config_length_class_id');
		}

		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		if (isset($this->request->post['config_weight_class_id'])) {
			$data['config_weight_class_id'] = $this->request->post['config_weight_class_id'];
		} else {
			$data['config_weight_class_id'] = $this->config->get('config_weight_class_id');
		}

		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		if (isset($this->request->post['config_limit_admin'])) {
			$data['config_limit_admin'] = $this->request->post['config_limit_admin'];
		} else {
			$data['config_limit_admin'] = $this->config->get('config_limit_admin');
		}

		if (isset($this->request->post['config_limit_autocomplete'])) {
			$data['config_limit_autocomplete'] = $this->request->post['config_limit_autocomplete'];
        } elseif ($this->config->get('config_limit_autocomplete')) {
			$data['config_limit_autocomplete'] = $this->config->get('config_limit_autocomplete');
		} else {
            $data['config_limit_autocomplete'] = 5;
        }

		if (isset($this->request->post['config_product_count'])) {
			$data['config_product_count'] = $this->request->post['config_product_count'];
		} else {
			$data['config_product_count'] = $this->config->get('config_product_count');
		}

		if (isset($this->request->post['config_review_status'])) {
			$data['config_review_status'] = $this->request->post['config_review_status'];
		} else {
			$data['config_review_status'] = $this->config->get('config_review_status');
		}

		if (isset($this->request->post['config_review_guest'])) {
			$data['config_review_guest'] = $this->request->post['config_review_guest'];
		} else {
			$data['config_review_guest'] = $this->config->get('config_review_guest');
		}

		if (isset($this->request->post['config_voucher_min'])) {
			$data['config_voucher_min'] = $this->request->post['config_voucher_min'];
		} else {
			$data['config_voucher_min'] = $this->config->get('config_voucher_min');
		}

		if (isset($this->request->post['config_voucher_max'])) {
			$data['config_voucher_max'] = $this->request->post['config_voucher_max'];
		} else {
			$data['config_voucher_max'] = $this->config->get('config_voucher_max');
		}

		if (isset($this->request->post['config_tax'])) {
			$data['config_tax'] = $this->request->post['config_tax'];
		} else {
			$data['config_tax'] = $this->config->get('config_tax');
		}

		if (isset($this->request->post['config_tax_display'])) {
			$data['config_tax_display'] = in_array($this->request->post['config_tax_display'], array('native', 'none', 'gross_net', 'gross_tax', 'net_gross'), true) ? $this->request->post['config_tax_display'] : 'native';
		} else {
			$config_tax_display = (string)$this->config->get('config_tax_display');
			$data['config_tax_display'] = in_array($config_tax_display, array('native', 'none', 'gross_net', 'gross_tax', 'net_gross', 'ex_tax', 'tax_amount'), true) ? $config_tax_display : 'native';
		}

		if (isset($this->request->post['config_currency_trim_zeros'])) {
			$data['config_currency_trim_zeros'] = !empty($this->request->post['config_currency_trim_zeros']) ? 1 : 0;
		} else {
			$data['config_currency_trim_zeros'] = (bool)$this->config->get('config_currency_trim_zeros');
		}

		if (isset($this->request->post['config_tax_default'])) {
			$data['config_tax_default'] = $this->request->post['config_tax_default'];
		} else {
			$data['config_tax_default'] = $this->config->get('config_tax_default');
		}

		if (isset($this->request->post['config_tax_customer'])) {
			$data['config_tax_customer'] = $this->request->post['config_tax_customer'];
		} else {
			$data['config_tax_customer'] = $this->config->get('config_tax_customer');
		}

		if (isset($this->request->post['config_customer_online'])) {
			$data['config_customer_online'] = $this->request->post['config_customer_online'];
		} else {
			$data['config_customer_online'] = $this->config->get('config_customer_online');
		}

		if (isset($this->request->post['config_customer_activity'])) {
			$data['config_customer_activity'] = $this->request->post['config_customer_activity'];
		} else {
			$data['config_customer_activity'] = $this->config->get('config_customer_activity');
		}

		if (isset($this->request->post['config_customer_search'])) {
			$data['config_customer_search'] = $this->request->post['config_customer_search'];
		} else {
			$data['config_customer_search'] = $this->config->get('config_customer_search');
		}

		if (isset($this->request->post['config_customer_group_id'])) {
			$data['config_customer_group_id'] = $this->request->post['config_customer_group_id'];
		} else {
			$data['config_customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		if (isset($this->request->post['config_customer_group_display'])) {
			$data['config_customer_group_display'] = $this->request->post['config_customer_group_display'];
		} elseif ($this->config->get('config_customer_group_display')) {
			$data['config_customer_group_display'] = $this->config->get('config_customer_group_display');
		} else {
			$data['config_customer_group_display'] = array();
		}

		if (isset($this->request->post['config_customer_price'])) {
			$data['config_customer_price'] = $this->request->post['config_customer_price'];
		} else {
			$data['config_customer_price'] = $this->config->get('config_customer_price');
		}

		if (isset($this->request->post['config_login_attempts'])) {
			$data['config_login_attempts'] = $this->request->post['config_login_attempts'];
		} elseif ($this->config->has('config_login_attempts')) {
			$data['config_login_attempts'] = $this->config->get('config_login_attempts');
		} else {
			$data['config_login_attempts'] = 5;
		}

		if (isset($this->request->post['config_account_id'])) {
			$data['config_account_id'] = $this->request->post['config_account_id'];
		} else {
			$data['config_account_id'] = $this->config->get('config_account_id');
		}

		$this->load->model('catalog/information');

		$data['informations'] = $this->model_catalog_information->getInformations();

        foreach (array('config_cookie_consent_status'=>0,'config_cookie_consent_days'=>180,'config_cookie_consent_information_id'=>0,'config_cookie_consent_privacy_information_id'=>0,'config_cookie_consent_accent_color'=>'#0b6fd3','config_cookie_consent_icon'=>'shield_cookie_check','config_cookie_consent_custom_icon'=>'') as $cookie_key=>$cookie_default) {
            if (isset($this->request->post[$cookie_key])) { $data[$cookie_key]=$this->request->post[$cookie_key]; }
            elseif ($this->config->has($cookie_key)) { $data[$cookie_key]=$this->config->get($cookie_key); }
            else { $data[$cookie_key]=$cookie_default; }
        }


		if ($data['config_cookie_consent_custom_icon'] && is_file(DIR_IMAGE . $data['config_cookie_consent_custom_icon'])) {
			$data['cookie_consent_custom_icon_thumb'] = $this->model_tool_image->resize($data['config_cookie_consent_custom_icon'], 48, 48);
		} else {
			$data['cookie_consent_custom_icon_thumb'] = $data['placeholder'];
		}
		$cookie_icon_base = (isset($this->request->server['HTTPS']) && $this->request->server['HTTPS']) ? HTTPS_CATALOG : HTTP_CATALOG;
		$data['cookie_consent_icons'] = array(
			array('value' => 'shield_cookie_check', 'text' => $this->language->get('text_cookie_icon_shield_cookie_check'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/shield-cookie-check.webp'),
			array('value' => 'shield_lock_check', 'text' => $this->language->get('text_cookie_icon_shield_lock_check'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/shield-lock-check.webp'),
			array('value' => 'lock_circle_check', 'text' => $this->language->get('text_cookie_icon_lock_circle_check'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/lock-circle-check.webp'),
			array('value' => 'hand_shield_check', 'text' => $this->language->get('text_cookie_icon_hand_shield_check'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/hand-shield-check.webp'),
			array('value' => 'shield_lock', 'text' => $this->language->get('text_cookie_icon_shield_lock'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/shield-lock.webp'),
			array('value' => 'cookie_orbit', 'text' => $this->language->get('text_cookie_icon_cookie_orbit'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/cookie-orbit.webp'),
			array('value' => 'cookie_document', 'text' => $this->language->get('text_cookie_icon_cookie_document'), 'image' => $cookie_icon_base . 'catalog/view/javascript/codecart/consent/icons/cookie-document.webp'),
			array('value' => 'custom', 'text' => $this->language->get('text_cookie_icon_custom'), 'image' => '')
		);

		if (isset($this->request->post['config_cart_weight'])) {
			$data['config_cart_weight'] = $this->request->post['config_cart_weight'];
		} else {
			$data['config_cart_weight'] = $this->config->get('config_cart_weight');
		}

		if (isset($this->request->post['config_checkout_guest'])) {
			$data['config_checkout_guest'] = $this->request->post['config_checkout_guest'];
		} else {
			$data['config_checkout_guest'] = $this->config->get('config_checkout_guest');
		}

		$checkout_fields = array('firstname', 'lastname', 'email', 'telephone', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'zone');
		$data['checkout_field_modes'] = array();
		foreach ($checkout_fields as $checkout_field) {
			$mode_key = 'config_checkout_field_' . $checkout_field . '_mode';
			if (isset($this->request->post[$mode_key])) {
				$mode = (string)$this->request->post[$mode_key];
			} elseif ($this->config->has($mode_key)) {
				$mode = (string)$this->config->get($mode_key);
			} else {
				$legacy_key = 'config_checkout_field_' . $checkout_field;
				if ($this->config->has($legacy_key) && !$this->config->get($legacy_key)) {
					$mode = 'hidden';
				} else {
					$mode = in_array($checkout_field, array('company', 'address_2'), true) ? 'optional' : 'required';
				}
			}
			if (!in_array($mode, array('required', 'optional', 'hidden'), true)) { $mode = 'required'; }
			$data[$mode_key] = $mode;
			$data['checkout_field_modes'][$checkout_field] = $mode;
		}

		if (isset($this->request->post['config_quick_checkout_status'])) {
			$data['config_quick_checkout_status'] = (int)$this->request->post['config_quick_checkout_status'];
		} elseif ($this->config->has('config_quick_checkout_status')) {
			$data['config_quick_checkout_status'] = (int)$this->config->get('config_quick_checkout_status');
		} else {
			$data['config_quick_checkout_status'] = 1;
		}

		if (isset($this->request->post['config_checkout_email_fallback'])) {
			$data['config_checkout_email_fallback'] = (string)$this->request->post['config_checkout_email_fallback'];
		} elseif ($this->config->has('config_checkout_email_fallback')) {
			$data['config_checkout_email_fallback'] = (string)$this->config->get('config_checkout_email_fallback');
		} else {
			$data['config_checkout_email_fallback'] = 'checkout@invalid.local';
		}

		if (isset($this->request->post['config_checkout_field_labels']) && is_array($this->request->post['config_checkout_field_labels'])) {
			$data['config_checkout_field_labels'] = $this->request->post['config_checkout_field_labels'];
		} else {
			$data['config_checkout_field_labels'] = $this->config->get('config_checkout_field_labels');
			if (!is_array($data['config_checkout_field_labels'])) { $data['config_checkout_field_labels'] = array(); }
		}

		if (isset($this->request->post['config_digital_checkout_status'])) {
			$data['config_digital_checkout_status'] = (int)$this->request->post['config_digital_checkout_status'];
		} elseif ($this->config->has('config_digital_checkout_status')) {
			$data['config_digital_checkout_status'] = (int)$this->config->get('config_digital_checkout_status');
		} else {
			$data['config_digital_checkout_status'] = 0;
		}

		$data['config_digital_checkout_field_email'] = 1;
		foreach (array('lastname', 'telephone', 'company', 'address_1', 'address_2', 'city', 'postcode') as $digital_field) {
			$key = 'config_digital_checkout_field_' . $digital_field;
			if (isset($this->request->post[$key])) {
				$data[$key] = (int)$this->request->post[$key];
			} elseif ($this->config->has($key)) {
				$data[$key] = (int)$this->config->get($key);
			} else {
				$data[$key] = 0;
			}
		}

		if (isset($this->request->post['config_checkout_id'])) {
			$data['config_checkout_id'] = $this->request->post['config_checkout_id'];
		} else {
			$data['config_checkout_id'] = $this->config->get('config_checkout_id');
		}

		if (isset($this->request->post['config_invoice_prefix'])) {
			$data['config_invoice_prefix'] = $this->request->post['config_invoice_prefix'];
		} elseif ($this->config->get('config_invoice_prefix')) {
			$data['config_invoice_prefix'] = $this->config->get('config_invoice_prefix');
		} else {
			$data['config_invoice_prefix'] = 'INV-' . date('Y') . '-00';
		}

		if (isset($this->request->post['config_order_status_id'])) {
			$data['config_order_status_id'] = $this->request->post['config_order_status_id'];
		} else {
			$data['config_order_status_id'] = $this->config->get('config_order_status_id');
		}

		if (isset($this->request->post['config_processing_status'])) {
			$data['config_processing_status'] = $this->request->post['config_processing_status'];
		} elseif ($this->config->get('config_processing_status')) {
			$data['config_processing_status'] = $this->config->get('config_processing_status');
		} else {
			$data['config_processing_status'] = array();
		}

		if (isset($this->request->post['config_complete_status'])) {
			$data['config_complete_status'] = $this->request->post['config_complete_status'];
		} elseif ($this->config->get('config_complete_status')) {
			$data['config_complete_status'] = $this->config->get('config_complete_status');
		} else {
			$data['config_complete_status'] = array();
		}

		if (isset($this->request->post['config_fraud_status_id'])) {
			$data['config_fraud_status_id'] = $this->request->post['config_fraud_status_id'];
		} else {
			$data['config_fraud_status_id'] = $this->config->get('config_fraud_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['config_api_id'])) {
			$data['config_api_id'] = $this->request->post['config_api_id'];
		} else {
			$data['config_api_id'] = $this->config->get('config_api_id');
		}

		$this->load->model('user/api');

		$data['apis'] = $this->model_user_api->getApis();

		if (isset($this->request->post['config_stock_display'])) {
			$data['config_stock_display'] = $this->request->post['config_stock_display'];
		} else {
			$data['config_stock_display'] = $this->config->get('config_stock_display');
		}

		if (isset($this->request->post['config_stock_warning'])) {
			$data['config_stock_warning'] = $this->request->post['config_stock_warning'];
		} else {
			$data['config_stock_warning'] = $this->config->get('config_stock_warning');
		}

		if (isset($this->request->post['config_stock_checkout'])) {
			$data['config_stock_checkout'] = $this->request->post['config_stock_checkout'];
		} else {
			$data['config_stock_checkout'] = $this->config->get('config_stock_checkout');
		}

		if (isset($this->request->post['config_affiliate_group_id'])) {
			$data['config_affiliate_group_id'] = $this->request->post['config_affiliate_group_id'];
		} else {
			$data['config_affiliate_group_id'] = $this->config->get('config_affiliate_group_id');
		}

		if (isset($this->request->post['config_affiliate_approval'])) {
			$data['config_affiliate_approval'] = $this->request->post['config_affiliate_approval'];
		} elseif ($this->config->has('config_affiliate_approval')) {
			$data['config_affiliate_approval'] = $this->config->get('config_affiliate_approval');
		} else {
			$data['config_affiliate_approval'] = '';
		}

		if (isset($this->request->post['config_affiliate_auto'])) {
			$data['config_affiliate_auto'] = $this->request->post['config_affiliate_auto'];
		} elseif ($this->config->has('config_affiliate_auto')) {
			$data['config_affiliate_auto'] = $this->config->get('config_affiliate_auto');
		} else {
			$data['config_affiliate_auto'] = '';
		}

		if (isset($this->request->post['config_affiliate_commission'])) {
			$data['config_affiliate_commission'] = $this->request->post['config_affiliate_commission'];
		} elseif ($this->config->has('config_affiliate_commission')) {
			$data['config_affiliate_commission'] = $this->config->get('config_affiliate_commission');
		} else {
			$data['config_affiliate_commission'] = '5.00';
		}

		if (isset($this->request->post['config_affiliate_id'])) {
			$data['config_affiliate_id'] = $this->request->post['config_affiliate_id'];
		} else {
			$data['config_affiliate_id'] = $this->config->get('config_affiliate_id');
		}

		if (isset($this->request->post['config_return_id'])) {
			$data['config_return_id'] = $this->request->post['config_return_id'];
		} else {
			$data['config_return_id'] = $this->config->get('config_return_id');
		}

		if (isset($this->request->post['config_return_status_id'])) {
			$data['config_return_status_id'] = $this->request->post['config_return_status_id'];
		} else {
			$data['config_return_status_id'] = $this->config->get('config_return_status_id');
		}

		$this->load->model('localisation/return_status');

		$data['return_statuses'] = $this->model_localisation_return_status->getReturnStatuses();

		if (isset($this->request->post['config_captcha'])) {
			$data['config_captcha'] = $this->request->post['config_captcha'];
		} else {
			$data['config_captcha'] = $this->config->get('config_captcha');
		}
		
		$this->load->model('setting/extension');

		$data['captchas'] = array();

		// Get a list of installed captchas
		$extensions = $this->model_setting_extension->getInstalled('captcha');

		foreach ($extensions as $code) {
			$this->load->language('extension/captcha/' . $code, 'extension');

			if ($this->config->get('captcha_' . $code . '_status')) {
				$data['captchas'][] = array(
					'text'  => $this->language->get('extension')->get('heading_title'),
					'value' => $code
				);
			}
		}		

		if (isset($this->request->post['config_captcha_page'])) {
			$data['config_captcha_page'] = $this->request->post['config_captcha_page'];
		} elseif ($this->config->has('config_captcha_page')) {
		   	$data['config_captcha_page'] = $this->config->get('config_captcha_page');
		} else {
			$data['config_captcha_page'] = array();
		}

		$data['captcha_pages'] = array();

		$data['captcha_pages'][] = array(
			'text'  => $this->language->get('text_register'),
			'value' => 'register'
		);
		
		$data['captcha_pages'][] = array(
			'text'  => $this->language->get('text_guest'),
			'value' => 'guest'
		);
		
		$data['captcha_pages'][] = array(
			'text'  => $this->language->get('text_review'),
			'value' => 'review'
		);

		$data['captcha_pages'][] = array(
			'text'  => $this->language->get('text_return'),
			'value' => 'return'
		);

		$data['captcha_pages'][] = array(
			'text'  => $this->language->get('text_contact'),
			'value' => 'contact'
		);

		if (isset($this->request->post['config_logo'])) {
			$data['config_logo'] = $this->request->post['config_logo'];
		} else {
			$data['config_logo'] = $this->config->get('config_logo');
		}

		if (isset($this->request->post['config_logo']) && is_file(DIR_IMAGE . $this->request->post['config_logo'])) {
			$data['logo'] = $this->model_tool_image->resize($this->request->post['config_logo'], 100, 100);
		} elseif ($this->config->get('config_logo') && is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $this->model_tool_image->resize($this->config->get('config_logo'), 100, 100);
		} else {
			$data['logo'] = $this->model_tool_image->resize('no_image.webp', 100, 100);
		}

		if (isset($this->request->post['config_icon'])) {
			$data['config_icon'] = $this->request->post['config_icon'];
		} else {
			$data['config_icon'] = $this->config->get('config_icon');
		}

		if (isset($this->request->post['config_icon']) && is_file(DIR_IMAGE . $this->request->post['config_icon'])) {
			$data['icon'] = $this->model_tool_image->resize($this->request->post['config_icon'], 100, 100);
		} elseif ($this->config->get('config_icon') && is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$data['icon'] = $this->model_tool_image->resize($this->config->get('config_icon'), 100, 100);
		} else {
			$data['icon'] = $this->model_tool_image->resize('no_image.webp', 100, 100);
		}

		foreach (array('config_email_logo' => 'email_logo', 'config_apple_touch_icon' => 'apple_touch_icon', 'config_social_preview_image' => 'social_preview_image', 'config_catalog_fallback_image' => 'catalog_fallback_image') as $setting_key => $thumb_key) {
			if (isset($this->request->post[$setting_key])) {
				$data[$setting_key] = trim((string)$this->request->post[$setting_key]);
			} else {
				$data[$setting_key] = trim((string)$this->config->get($setting_key));
			}

			if ($data[$setting_key] !== '' && is_file(DIR_IMAGE . $data[$setting_key])) {
				$data[$thumb_key] = $this->model_tool_image->resize($data[$setting_key], 100, 100);
			} else {
				$data[$thumb_key] = $data['placeholder'];
			}
		}



		$effective_email_logo = trim((string)$data['config_email_logo']);
		if ($effective_email_logo === '') {
			$effective_email_logo = trim((string)$data['config_logo']);
		}
		$email_logo_info = class_exists('CodeCart\Core\EmailLogo') ? \CodeCart\Core\EmailLogo::inspect($effective_email_logo) : null;
		if ($email_logo_info) {
			$data['email_logo_current'] = sprintf(
				$this->language->get('text_email_logo_current'),
				$email_logo_info['format'],
				$email_logo_info['width'],
				$email_logo_info['height'],
				$email_logo_info['size_kb']
			);
		} else {
			$data['email_logo_current'] = $this->language->get('text_email_logo_current_none');
		}

		if (isset($this->request->post['config_mail_engine'])) {
			$data['config_mail_engine'] = $this->request->post['config_mail_engine'];
		} else {
			$data['config_mail_engine'] = $this->config->get('config_mail_engine');
		}

		if (isset($this->request->post['config_mail_parameter'])) {
			$data['config_mail_parameter'] = $this->request->post['config_mail_parameter'];
		} else {
			$data['config_mail_parameter'] = $this->config->get('config_mail_parameter');
		}

		if (isset($this->request->post['config_mail_smtp_hostname'])) {
			$data['config_mail_smtp_hostname'] = $this->request->post['config_mail_smtp_hostname'];
		} else {
			$data['config_mail_smtp_hostname'] = $this->config->get('config_mail_smtp_hostname');
		}

		if (isset($this->request->post['config_mail_smtp_username'])) {
			$data['config_mail_smtp_username'] = $this->request->post['config_mail_smtp_username'];
		} else {
			$data['config_mail_smtp_username'] = $this->config->get('config_mail_smtp_username');
		}

		if (isset($this->request->post['config_mail_smtp_password'])) {
			$data['config_mail_smtp_password'] = $this->request->post['config_mail_smtp_password'];
		} else {
			$data['config_mail_smtp_password'] = $this->config->get('config_mail_smtp_password');
		}

		if (isset($this->request->post['config_mail_smtp_port'])) {
			$data['config_mail_smtp_port'] = $this->request->post['config_mail_smtp_port'];
		} elseif ($this->config->has('config_mail_smtp_port')) {
			$data['config_mail_smtp_port'] = $this->config->get('config_mail_smtp_port');
		} else {
			$data['config_mail_smtp_port'] = 25;
		}

		if (isset($this->request->post['config_mail_smtp_timeout'])) {
			$data['config_mail_smtp_timeout'] = $this->request->post['config_mail_smtp_timeout'];
		} elseif ($this->config->has('config_mail_smtp_timeout')) {
			$data['config_mail_smtp_timeout'] = $this->config->get('config_mail_smtp_timeout');
		} else {
			$data['config_mail_smtp_timeout'] = 5;
		}

		if (isset($this->request->post['config_mail_alert'])) {
			$data['config_mail_alert'] = $this->request->post['config_mail_alert'];
		} elseif ($this->config->has('config_mail_alert')) {
		   	$data['config_mail_alert'] = $this->config->get('config_mail_alert');
		} else {
			$data['config_mail_alert'] = array();
		}

		$data['mail_alerts'] = array();

		$data['mail_alerts'][] = array(
			'text'  => $this->language->get('text_mail_account'),
			'value' => 'account'
		);

		$data['mail_alerts'][] = array(
			'text'  => $this->language->get('text_mail_affiliate'),
			'value' => 'affiliate'
		);

		$data['mail_alerts'][] = array(
			'text'  => $this->language->get('text_mail_order'),
			'value' => 'order'
		);

		$data['mail_alerts'][] = array(
			'text'  => $this->language->get('text_mail_review'),
			'value' => 'review'
		);

		if (isset($this->request->post['config_mail_alert_email'])) {
			$data['config_mail_alert_email'] = $this->request->post['config_mail_alert_email'];
		} else {
			$data['config_mail_alert_email'] = $this->config->get('config_mail_alert_email');
		}
		
		if (isset($this->request->post['config_secure'])) {
			$data['config_secure'] = $this->request->post['config_secure'];
		} else {
			$data['config_secure'] = $this->config->get('config_secure');
		}

		if (isset($this->request->post['config_shared'])) {
			$data['config_shared'] = $this->request->post['config_shared'];
		} else {
			$data['config_shared'] = $this->config->get('config_shared');
		}

		if (isset($this->request->post['config_robots'])) {
			$data['config_robots'] = $this->request->post['config_robots'];
		} else {
			$data['config_robots'] = $this->config->get('config_robots');
		}

		if (isset($this->request->post['config_seo_url'])) {
			$data['config_seo_url'] = $this->request->post['config_seo_url'];
		} else {
			$data['config_seo_url'] = $this->config->get('config_seo_url');
		}

        if (isset($this->request->post['config_noindex_status'])) {
            $data['config_noindex_status'] = $this->request->post['config_noindex_status'];
        } else {
            $data['config_noindex_status'] = $this->config->get('config_noindex_status');
        }


        if (isset($this->request->post['config_seo_filter_index_mode'])) {
            $data['config_seo_filter_index_mode'] = in_array($this->request->post['config_seo_filter_index_mode'], array('noindex','allowlist','legacy'), true) ? $this->request->post['config_seo_filter_index_mode'] : 'noindex';
        } elseif ($this->config->has('config_seo_filter_index_mode')) {
            $data['config_seo_filter_index_mode'] = $this->config->get('config_seo_filter_index_mode');
        } else {
            $data['config_seo_filter_index_mode'] = 'noindex';
        }
        if (isset($this->request->post['config_seo_filter_allowlist'])) {
            $data['config_seo_filter_allowlist'] = $this->request->post['config_seo_filter_allowlist'];
        } else {
            $data['config_seo_filter_allowlist'] = (string)$this->config->get('config_seo_filter_allowlist');
        }
        if (isset($this->request->post['codecart_internal_linking_status'])) {
            $data['codecart_internal_linking_status'] = (int)$this->request->post['codecart_internal_linking_status'];
        } elseif ($this->config->has('codecart_internal_linking_status')) {
            $data['codecart_internal_linking_status'] = (int)$this->config->get('codecart_internal_linking_status');
        } else {
            $data['codecart_internal_linking_status'] = 0;
        }

        if (isset($this->request->post['config_seo_presentation_noindex'])) {
            $data['config_seo_presentation_noindex'] = (int)$this->request->post['config_seo_presentation_noindex'];
        } elseif ($this->config->has('config_seo_presentation_noindex')) {
            $data['config_seo_presentation_noindex'] = (int)$this->config->get('config_seo_presentation_noindex');
        } else {
            $data['config_seo_presentation_noindex'] = 1;
        }

        if (isset($this->request->post['config_noindex_disallow_params'])) {
            $data['config_noindex_disallow_params'] = $this->request->post['config_noindex_disallow_params'];
        } elseif ($this->config->get('config_noindex_disallow_params')) {
            $data['config_noindex_disallow_params'] = $this->config->get('config_noindex_disallow_params');
        } else {
            $data['config_noindex_disallow_params'] = "page";
        }

		$data['config_editor'] = 'summernote';

		if (isset($this->request->post['config_image_webp'])) {
			$data['config_image_webp'] = (int)$this->request->post['config_image_webp'];
		} elseif ($this->config->has('config_image_webp')) {
			$data['config_image_webp'] = (int)$this->config->get('config_image_webp');
		} else {
			$data['config_image_webp'] = 1;
		}

		if (isset($this->request->post['config_image_webp_quality'])) {
			$data['config_image_webp_quality'] = (int)$this->request->post['config_image_webp_quality'];
		} elseif ($this->config->get('config_image_webp_quality')) {
			$data['config_image_webp_quality'] = (int)$this->config->get('config_image_webp_quality');
		} else {
			$data['config_image_webp_quality'] = 82;
		}
		$data['config_image_webp_supported'] = extension_loaded('gd') && function_exists('imagewebp');

		if (isset($this->request->post['config_image_avif'])) {
			$data['config_image_avif'] = (int)$this->request->post['config_image_avif'];
		} elseif ($this->config->has('config_image_avif')) {
			$data['config_image_avif'] = (int)$this->config->get('config_image_avif');
		} else {
			$data['config_image_avif'] = 0;
		}
		if (isset($this->request->post['config_image_avif_quality'])) {
			$data['config_image_avif_quality'] = (int)$this->request->post['config_image_avif_quality'];
		} elseif ($this->config->get('config_image_avif_quality')) {
			$data['config_image_avif_quality'] = (int)$this->config->get('config_image_avif_quality');
		} else {
			$data['config_image_avif_quality'] = 72;
		}
		$data['config_image_avif_supported'] = extension_loaded('gd') && function_exists('imageavif') && function_exists('imagecreatefromavif');

		if (isset($this->request->post['config_file_max_size'])) {
			$data['config_file_max_size'] = $this->request->post['config_file_max_size'];
		} elseif ($this->config->get('config_file_max_size')) {
			$data['config_file_max_size'] = $this->config->get('config_file_max_size');
		} else {
			$data['config_file_max_size'] = 10485760;
		}

		if (isset($this->request->post['config_file_ext_allowed'])) {
			$data['config_file_ext_allowed'] = $this->request->post['config_file_ext_allowed'];
		} else {
			$data['config_file_ext_allowed'] = $this->config->get('config_file_ext_allowed');
		}

		if (isset($this->request->post['config_file_mime_allowed'])) {
			$data['config_file_mime_allowed'] = $this->request->post['config_file_mime_allowed'];
		} else {
			$data['config_file_mime_allowed'] = $this->config->get('config_file_mime_allowed');
		}

		if (isset($this->request->post['config_maintenance'])) {
			$data['config_maintenance'] = $this->request->post['config_maintenance'];
		} else {
			$data['config_maintenance'] = $this->config->get('config_maintenance');
		}

		if (isset($this->request->post['config_password'])) {
			$data['config_password'] = $this->request->post['config_password'];
		} else {
			$data['config_password'] = $this->config->get('config_password');
		}

		if (isset($this->request->post['config_encryption'])) {
			$data['config_encryption'] = $this->request->post['config_encryption'];
		} else {
			$data['config_encryption'] = $this->config->get('config_encryption');
		}

        if (isset($this->request->post['codecart_cache_engine'])) {
            $data['codecart_cache_engine'] = strtolower((string)$this->request->post['codecart_cache_engine']);
        } else {
            $data['codecart_cache_engine'] = strtolower((string)$this->config->get('codecart_cache_engine'));
        }
        if (!in_array($data['codecart_cache_engine'], array('file','apcu','memcached','redis'), true)) { $data['codecart_cache_engine'] = 'file'; }
		$apcu_enabled = function_exists('apcu_enabled') ? (bool)apcu_enabled() : null;
		if ($apcu_enabled === null) {
			$apcu_ini = ini_get('apc.enabled');
			if ($apcu_ini === false || $apcu_ini === '') { $apcu_ini = ini_get('apcu.enabled'); }
			$apcu_enabled = !in_array(strtolower(trim((string)$apcu_ini)), array('', '0', 'off', 'false', 'no'), true);
		}
		$data['cache_apcu_available'] = extension_loaded('apcu') && function_exists('apcu_fetch') && $apcu_enabled;
		$data['cache_memcached_available'] = extension_loaded('memcached') && class_exists('Memcached');
		$data['cache_redis_available'] = extension_loaded('redis') && class_exists('Redis');
		$data['text_cache_extension_unavailable'] = $this->language->get('text_cache_extension_unavailable');

		if (isset($this->request->post['config_compression'])) {
			$data['config_compression'] = $this->request->post['config_compression'];
		} else {
			$data['config_compression'] = $this->config->get('config_compression');
		}

		if (isset($this->request->post['config_error_display'])) {
			$data['config_error_display'] = $this->request->post['config_error_display'];
		} else {
			$data['config_error_display'] = $this->config->get('config_error_display');
		}

		if (isset($this->request->post['config_error_log'])) {
			$data['config_error_log'] = $this->request->post['config_error_log'];
		} else {
			$data['config_error_log'] = $this->config->get('config_error_log');
		}

		if (isset($this->request->post['config_error_filename'])) {
			$data['config_error_filename'] = $this->request->post['config_error_filename'];
		} else {
			$data['config_error_filename'] = $this->config->get('config_error_filename');
		}

		if (isset($this->request->post['config_codecart_structured_data_status'])) {
			$data['config_codecart_structured_data_status'] = (int)$this->request->post['config_codecart_structured_data_status'];
		} elseif ($this->config->has('config_codecart_structured_data_status')) {
			$data['config_codecart_structured_data_status'] = (int)$this->config->get('config_codecart_structured_data_status');
		} else {
			$data['config_codecart_structured_data_status'] = 1;
		}

		if (isset($this->request->post['config_seo_pro'])) {
			$data['config_seo_pro'] = $this->request->post['config_seo_pro'];
		} else {
			$data['config_seo_pro'] = $this->config->get('config_seo_pro');
		}

		if (isset($this->request->post['config_auto_seo_url'])) {
			$data['config_auto_seo_url'] = $this->request->post['config_auto_seo_url'];
		} elseif ($this->config->has('config_auto_seo_url')) {
			$data['config_auto_seo_url'] = $this->config->get('config_auto_seo_url');
		} else {
			$data['config_auto_seo_url'] = 1;
		}

		if (isset($this->request->post['config_seo_url_include_path'])) {
			$data['config_seo_url_include_path'] = $this->request->post['config_seo_url_include_path'];
		} else {
			$data['config_seo_url_include_path'] = $this->config->get('config_seo_url_include_path');
		}

		if (isset($this->request->post['config_seo_url_cache'])) {
			$data['config_seo_url_cache'] = $this->request->post['config_seo_url_cache'];
		} else {
			$data['config_seo_url_cache'] = $this->config->get('config_seo_url_cache');
		}

		if (isset($this->request->post['config_page_postfix'])) {
			$data['config_page_postfix'] = $this->request->post['config_page_postfix'];
		} else {
			$data['config_page_postfix'] = $this->config->get('config_page_postfix');
		}

		if (isset($this->request->post['config_seopro_addslash'])) {
			$data['config_seopro_addslash'] = $this->request->post['config_seopro_addslash'];
		} elseif ($this->config->has('config_seopro_addslash')) {
			$data['config_seopro_addslash'] = $this->config->get('config_seopro_addslash');
		}

		if (isset($this->request->post['config_seopro_lowercase'])) {
			$data['config_seopro_lowercase'] = $this->request->post['config_seopro_lowercase'];
		} elseif ($this->config->has('config_seopro_lowercase')) {
			$data['config_seopro_lowercase'] = $this->config->get('config_seopro_lowercase');
		}

		if (isset($this->request->post['config_valide_params'])) {
			$data['config_valide_params'] = $this->request->post['config_valide_params'];
		} elseif ($this->config->get('config_valide_params')) {
			$data['config_valide_params'] = $this->config->get('config_valide_params');
		} else {
			$data['config_valide_params'] = "block\r\nfrommarket\r\ngclid\r\nfbclid\r\nttclid\r\ngad_source\r\nsrsltid\r\nmsclkid\r\nkeyword\r\nlist_type\r\nopenstat\r\nopenstat_service\r\nopenstat_campaign\r\nopenstat_ad\r\nopenstat_source\r\nposition\r\nsource\r\ntracking\r\ntype\r\nyclid\r\nymclid\r\nuri\r\nurltype\r\nutm_source\r\nutm_medium\r\nutm_campaign\r\nutm_term\r\nutm_content\r\nutm_referrer";
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('setting/setting', $data));
	}

	protected function validate() {
		// The core ships one local editor. Keep the setting key only for OpenCart 3 compatibility.
		$this->request->post['config_editor'] = 'summernote';

		if (isset($this->request->post['config_image_webp'])) {
			$this->request->post['config_image_webp'] = (int)(bool)$this->request->post['config_image_webp'];
		}
		if (isset($this->request->post['config_image_webp_quality'])) {
			$quality = (int)$this->request->post['config_image_webp_quality'];
			$this->request->post['config_image_webp_quality'] = max(60, min(95, $quality ?: 82));
		}
		if (isset($this->request->post['config_image_avif'])) {
			$this->request->post['config_image_avif'] = (int)(bool)$this->request->post['config_image_avif'];
		}
		if (isset($this->request->post['config_image_avif_quality'])) {
			$quality = (int)$this->request->post['config_image_avif_quality'];
			$this->request->post['config_image_avif_quality'] = max(45, min(90, $quality ?: 72));
		}

		if (!$this->user->hasPermission('modify', 'setting/setting')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['config_meta_title']) {
			$this->error['meta_title'] = $this->language->get('error_meta_title');
		}

		if (!$this->request->post['config_name']) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if ((utf8_strlen($this->request->post['config_owner']) < 3) || (utf8_strlen($this->request->post['config_owner']) > 64)) {
			$this->error['owner'] = $this->language->get('error_owner');
		}

		if ((utf8_strlen($this->request->post['config_address']) < 3) || (utf8_strlen($this->request->post['config_address']) > 256)) {
			$this->error['address'] = $this->language->get('error_address');
		}

		if ((utf8_strlen($this->request->post['config_email']) > 96) || !filter_var($this->request->post['config_email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['config_telephone']) < 3) || (utf8_strlen($this->request->post['config_telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if (!empty($this->request->post['config_customer_group_display']) && !in_array($this->request->post['config_customer_group_id'], $this->request->post['config_customer_group_display'])) {
			$this->error['customer_group_display'] = $this->language->get('error_customer_group_display');
		}

		if (!$this->request->post['config_limit_admin']) {
			$this->error['limit_admin'] = $this->language->get('error_limit');
		}

		if (!$this->request->post['config_limit_autocomplete']) {
			$this->error['limit_autocomplete'] = $this->language->get('error_limit');
		}

		if ($this->request->post['config_login_attempts'] < 1) {
			$this->error['login_attempts'] = $this->language->get('error_login_attempts');
		}

		if (!$this->request->post['config_voucher_min']) {
			$this->error['voucher_min'] = $this->language->get('error_voucher_min');
		}

		if (!$this->request->post['config_voucher_max']) {
			$this->error['voucher_max'] = $this->language->get('error_voucher_max');
		}

		if (!isset($this->request->post['config_processing_status'])) {
			$this->error['processing_status'] = $this->language->get('error_processing_status');
		}

		if (!isset($this->request->post['config_complete_status'])) {
			$this->error['complete_status'] = $this->language->get('error_complete_status');
		}
		
		if (!$this->request->post['config_error_filename']) {
			$this->error['log'] = $this->language->get('error_log_required');
		} elseif (preg_match('/\.\.[\/\\\]?/', $this->request->post['config_error_filename'])) {
			$this->error['log'] = $this->language->get('error_log_invalid');
		} elseif (substr($this->request->post['config_error_filename'], strrpos($this->request->post['config_error_filename'], '.')) != '.log') {
			$this->error['log'] = $this->language->get('error_log_extension');
		}
		
		if ((utf8_strlen($this->request->post['config_encryption']) < 32) || (utf8_strlen($this->request->post['config_encryption']) > 1024)) {
			$this->error['encryption'] = $this->language->get('error_encryption');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
	
	public function theme() {
		if ($this->request->server['HTTPS']) {
			$server = HTTPS_CATALOG;
		} else {
			$server = HTTP_CATALOG;
		}
		
		// This is only here for compatibility with old themes.
		if ($this->request->get['theme'] == 'theme_default') {
			$theme = $this->config->get('theme_default_directory');
		} else {
			$theme = basename($this->request->get['theme']);
		}
		
		if ($theme === 'codecart' && is_file(DIR_CATALOG . 'view/theme/codecart/image/preview.png')) {
			$this->response->setOutput($server . 'catalog/view/theme/codecart/image/preview.png');
		} elseif ($theme === 'codecart' && is_file(DIR_CATALOG . 'view/theme/codecart/image/preview.webp')) {
			$this->response->setOutput($server . 'catalog/view/theme/codecart/image/preview.webp');
		} elseif (is_file(DIR_CATALOG . 'view/theme/' . $theme . '/image/' . $theme . '.webp')) {
			$this->response->setOutput($server . 'catalog/view/theme/' . $theme . '/image/' . $theme . '.webp');
		} elseif (is_file(DIR_CATALOG . 'view/theme/' . $theme . '/image/' . $theme . '.png')) {
			// Compatibility fallback for third-party OpenCart 3 themes.
			$this->response->setOutput($server . 'catalog/view/theme/' . $theme . '/image/' . $theme . '.png');
		} else {
			$this->response->setOutput($server . 'image/no_image.webp');
		}
	}	
}
