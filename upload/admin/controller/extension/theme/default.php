<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionThemeDefault extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/theme/default');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$allowed_fonts = array('system', 'inter', 'arial', 'verdana', 'tahoma', 'trebuchet', 'georgia');
			if (!isset($this->request->post['theme_default_font_family']) || !in_array((string)$this->request->post['theme_default_font_family'], $allowed_fonts, true)) {
				$this->request->post['theme_default_font_family'] = 'system';
			}
			$allowed_icon_modes = array('auto', 'core', 'full');
			if (!isset($this->request->post['theme_default_icon_mode']) || !in_array((string)$this->request->post['theme_default_icon_mode'], $allowed_icon_modes, true)) {
				$this->request->post['theme_default_icon_mode'] = 'auto';
			}
			$allowed_commerce_styles = array('standard', 'modern');
			$allowed_product_card_content = array('none', 'description', 'attributes', 'both');
			if (!isset($this->request->post['theme_default_commerce_style']) || !in_array((string)$this->request->post['theme_default_commerce_style'], $allowed_commerce_styles, true)) {
				$this->request->post['theme_default_commerce_style'] = 'standard';
			}
			if (!isset($this->request->post['theme_default_product_card_content']) || !in_array((string)$this->request->post['theme_default_product_card_content'], $allowed_product_card_content, true)) {
				$this->request->post['theme_default_product_card_content'] = 'description';
			}
			$this->request->post['theme_default_product_card_attribute_limit'] = max(1, min(10, (int)($this->request->post['theme_default_product_card_attribute_limit'] ?? 3)));
			$this->request->post['theme_default_option_image_switch_status'] = !empty($this->request->post['theme_default_option_image_switch_status']) ? 1 : 0;
			$this->request->post['theme_default_purchase_blocks_status'] = !empty($this->request->post['theme_default_purchase_blocks_status']) ? 1 : 0;
			$this->request->post['theme_default_catalog_ajax_status'] = !empty($this->request->post['theme_default_catalog_ajax_status']) ? 1 : 0;
			$this->request->post['theme_default_dark_mode_status'] = !empty($this->request->post['theme_default_dark_mode_status']) ? 1 : 0;
			$this->request->post['theme_default_header_phone_status'] = !empty($this->request->post['theme_default_header_phone_status']) ? 1 : 0;
			$this->request->post['theme_default_header_email_status'] = !empty($this->request->post['theme_default_header_email_status']) ? 1 : 0;
			$allowed_header_menu_modes = array('horizontal', 'vertical');
			$header_menu_mode = isset($this->request->post['theme_default_header_menu_mode']) ? (string)$this->request->post['theme_default_header_menu_mode'] : 'horizontal';
			$this->request->post['theme_default_header_menu_mode'] = in_array($header_menu_mode, $allowed_header_menu_modes, true) ? $header_menu_mode : 'horizontal';
			$header_information_ids = isset($this->request->post['theme_default_header_information_ids']) && is_array($this->request->post['theme_default_header_information_ids']) ? $this->request->post['theme_default_header_information_ids'] : array();
			$this->request->post['theme_default_header_information_ids'] = array_values(array_unique(array_filter(array_map('intval', $header_information_ids), function($id){ return $id > 0; })));
			$header_product_ids = isset($this->request->post['theme_default_header_product_ids']) && is_array($this->request->post['theme_default_header_product_ids']) ? $this->request->post['theme_default_header_product_ids'] : array();
			$this->request->post['theme_default_header_product_ids'] = array_slice(array_values(array_unique(array_filter(array_map('intval', $header_product_ids), function($id){ return $id > 0; }))), 0, 50);
			$clean_custom_links = array();
			foreach ((array)($this->request->post['theme_default_header_custom_links'] ?? array()) as $row) {
				$href = trim((string)($row['href'] ?? ''));
				if ($href === '' || preg_match('/^(?:javascript|data|vbscript):/i', $href)) { continue; }
				$titles = array();
				foreach ((array)($row['title'] ?? array()) as $language_id => $title) { $titles[(int)$language_id] = utf8_substr(trim((string)$title), 0, 128); }
				$clean_custom_links[] = array('title'=>$titles,'href'=>utf8_substr($href,0,2048),'sort_order'=>(int)($row['sort_order'] ?? 0));
				if (count($clean_custom_links) >= 50) { break; }
			}
			$this->request->post['theme_default_header_custom_links'] = $clean_custom_links;
			$this->request->post['theme_default_product_extra_tab_status'] = !empty($this->request->post['theme_default_product_extra_tab_status']) ? 1 : 0;
			foreach (array('theme_default_product_extra_tab_title','theme_default_product_extra_tab_content') as $key) { if (!isset($this->request->post[$key]) || !is_array($this->request->post[$key])) $this->request->post[$key]=array(); }
			foreach ($this->request->post['theme_default_product_extra_tab_title'] as $language_id=>$title) $this->request->post['theme_default_product_extra_tab_title'][(int)$language_id]=utf8_substr(trim((string)$title),0,128);
			foreach ($this->request->post['theme_default_product_extra_tab_content'] as $language_id=>$content) $this->request->post['theme_default_product_extra_tab_content'][(int)$language_id]=\CodeCart\Core\SafeRichHtml::sanitize((string)$content);
			$this->request->post['theme_default_border_radius'] = max(0, min(24, (int)($this->request->post['theme_default_border_radius'] ?? 8)));
			$allowed_dark_defaults = array('light', 'system', 'dark');
			if (!isset($this->request->post['theme_default_dark_mode_default']) || !in_array((string)$this->request->post['theme_default_dark_mode_default'], $allowed_dark_defaults, true)) {
				$this->request->post['theme_default_dark_mode_default'] = 'light';
			}
			$allowed_picker_themes = array('light', 'system', 'dark');
			if (!isset($this->request->post['theme_default_datetimepicker_theme']) || !in_array((string)$this->request->post['theme_default_datetimepicker_theme'], $allowed_picker_themes, true)) {
				$this->request->post['theme_default_datetimepicker_theme'] = 'light';
			}
			$allowed_gallery_engines = array('photoswipe', 'magnific');
			if (!isset($this->request->post['theme_default_gallery_engine']) || !in_array((string)$this->request->post['theme_default_gallery_engine'], $allowed_gallery_engines, true)) {
				$this->request->post['theme_default_gallery_engine'] = 'photoswipe';
			}
			$custom_css = isset($this->request->post['theme_default_custom_css']) ? (string)$this->request->post['theme_default_custom_css'] : '';
			$custom_css = str_replace(chr(0), '', $custom_css);
			$custom_css = preg_replace('#</?style\b[^>]*>#i', '', $custom_css);
			$this->request->post['theme_default_custom_css'] = substr($custom_css, 0, 100000);

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
				'theme_default_h1_color' => '#273142',
				'theme_default_h2_color' => '#273142',
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

			$this->model_setting_setting->editSetting('theme_default', $this->request->post, $this->request->get['store_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['product_limit'])) {
			$data['error_product_limit'] = $this->error['product_limit'];
		} else {
			$data['error_product_limit'] = '';
		}

		if (isset($this->error['product_description_length'])) {
			$data['error_product_description_length'] = $this->error['product_description_length'];
		} else {
			$data['error_product_description_length'] = '';
		}

		if (isset($this->error['image_category'])) {
			$data['error_image_category'] = $this->error['image_category'];
		} else {
			$data['error_image_category'] = '';
		}
		
		if (isset($this->error['image_manufacturer'])) {
			$data['error_image_manufacturer'] = $this->error['image_manufacturer'];
		} else {
			$data['error_image_manufacturer'] = '';
		}

		if (isset($this->error['image_thumb'])) {
			$data['error_image_thumb'] = $this->error['image_thumb'];
		} else {
			$data['error_image_thumb'] = '';
		}

		if (isset($this->error['image_popup'])) {
			$data['error_image_popup'] = $this->error['image_popup'];
		} else {
			$data['error_image_popup'] = '';
		}

		if (isset($this->error['image_product'])) {
			$data['error_image_product'] = $this->error['image_product'];
		} else {
			$data['error_image_product'] = '';
		}

		if (isset($this->error['image_additional'])) {
			$data['error_image_additional'] = $this->error['image_additional'];
		} else {
			$data['error_image_additional'] = '';
		}

		if (isset($this->error['image_related'])) {
			$data['error_image_related'] = $this->error['image_related'];
		} else {
			$data['error_image_related'] = '';
		}

		if (isset($this->error['image_compare'])) {
			$data['error_image_compare'] = $this->error['image_compare'];
		} else {
			$data['error_image_compare'] = '';
		}

		if (isset($this->error['image_wishlist'])) {
			$data['error_image_wishlist'] = $this->error['image_wishlist'];
		} else {
			$data['error_image_wishlist'] = '';
		}

		if (isset($this->error['image_cart'])) {
			$data['error_image_cart'] = $this->error['image_cart'];
		} else {
			$data['error_image_cart'] = '';
		}

		if (isset($this->error['image_location'])) {
			$data['error_image_location'] = $this->error['image_location'];
		} else {
			$data['error_image_location'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/theme/default', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true)
		);

		$data['action'] = $this->url->link('extension/theme/default', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme', true);
		foreach (array('text_theme_modes','entry_dark_mode','help_dark_mode','entry_dark_mode_default','help_dark_mode_default','entry_datetimepicker_theme','help_datetimepicker_theme','entry_gallery_engine','help_gallery_engine','entry_header_phone','help_header_phone','entry_header_email','help_header_email','entry_header_menu_mode','help_header_menu_mode','text_header_menu_horizontal','text_header_menu_vertical','entry_header_information','help_header_information','entry_header_products','help_header_products','entry_header_custom_links','help_header_custom_links','entry_custom_link_title','entry_custom_link_url','entry_custom_link_sort','button_custom_link_add','entry_header_product_search','text_menu_products','entry_custom_css','help_custom_css','text_custom_css_help','entry_border_radius','help_border_radius','entry_h1_color','help_h1_color','entry_h2_color','help_h2_color','entry_product_card_content','help_product_card_content','text_product_card_description','text_product_card_attributes','entry_option_image_switch_status','help_option_image_switch_status','entry_purchase_blocks_status','help_purchase_blocks_status','text_font_preview','text_product_extra_tab','entry_product_extra_tab_status','help_product_extra_tab_status','entry_product_extra_tab_title','entry_product_extra_tab_content') as $language_key) {
			$data[$language_key] = $this->language->get($language_key);
		}
		$data['catalog_ajax_check'] = str_replace('&amp;', '&', $this->url->link('extension/theme/default/catalogAjaxCheck', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . (isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0), true));

		if (isset($this->request->get['store_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$setting_info = $this->model_setting_setting->getSetting('theme_default', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['theme_default_directory'])) {
			$data['theme_default_directory'] = $this->request->post['theme_default_directory'];
		} elseif (isset($setting_info['theme_default_directory'])) {
			$data['theme_default_directory'] = $setting_info['theme_default_directory'];
		} else {
			$data['theme_default_directory'] = 'default';
		}		

		$data['directories'] = array();

		$directories = glob(DIR_CATALOG . 'view/theme/*', GLOB_ONLYDIR);

		foreach ($directories as $directory) {
			$data['directories'][] = basename($directory);
		}

		if (isset($this->request->post['theme_default_product_limit'])) {
			$data['theme_default_product_limit'] = $this->request->post['theme_default_product_limit'];
		} elseif (isset($setting_info['theme_default_product_limit'])) {
			$data['theme_default_product_limit'] = $setting_info['theme_default_product_limit'];
		} else {
			$data['theme_default_product_limit'] = 15;
		}		
		
		if (isset($this->request->post['theme_default_status'])) {
			$data['theme_default_status'] = $this->request->post['theme_default_status'];
		} elseif (isset($setting_info['theme_default_status'])) {
			$data['theme_default_status'] = $setting_info['theme_default_status'];
		} else {
			$data['theme_default_status'] = '';
		}
		
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
				'theme_default_h1_color' => '#273142',
				'theme_default_h2_color' => '#273142',
			'theme_default_background_color' => '#ffffff',
			'theme_default_surface_color' => '#f7f9fb',
			'theme_default_border_color' => '#e5e9ef',
			'theme_default_border_radius' => '8',
			'theme_default_footer_background_color' => '#303030',
			'theme_default_footer_text_color' => '#e2e2e2',
			'theme_default_footer_link_color' => '#cccccc',
			'theme_default_footer_heading_color' => '#ffffff'
		) as $color_key => $color_default) {
			if (isset($this->request->post[$color_key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->request->post[$color_key])) {
				$data[$color_key] = $this->request->post[$color_key];
			} elseif (isset($setting_info[$color_key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$setting_info[$color_key])) {
				$data[$color_key] = $setting_info[$color_key];
			} else {
				$data[$color_key] = $color_default;
			}
		}

		$data['font_families'] = array(
			'system' => 'System UI',
			'inter' => 'Inter',
			'arial' => 'Arial / Helvetica',
			'verdana' => 'Verdana',
			'tahoma' => 'Tahoma',
			'trebuchet' => 'Trebuchet MS',
			'georgia' => 'Georgia'
		);
		if (isset($this->request->post['theme_default_font_family'])) {
			$data['theme_default_font_family'] = (string)$this->request->post['theme_default_font_family'];
		} elseif (isset($setting_info['theme_default_font_family']) && isset($data['font_families'][$setting_info['theme_default_font_family']])) {
			$data['theme_default_font_family'] = (string)$setting_info['theme_default_font_family'];
		} else {
			$data['theme_default_font_family'] = 'system';
		}

		$data['icon_modes'] = array('auto' => $this->language->get('text_icon_auto'), 'core' => $this->language->get('text_icon_core'), 'full' => $this->language->get('text_icon_full'));
		if (isset($this->request->post['theme_default_icon_mode'])) {
			$data['theme_default_icon_mode'] = (string)$this->request->post['theme_default_icon_mode'];
		} elseif (isset($setting_info['theme_default_icon_mode'])) {
			$stored_icon_mode = (string)$setting_info['theme_default_icon_mode'];
			if ($stored_icon_mode === 'standard') {
				$data['theme_default_icon_mode'] = 'full';
			} elseif (isset($data['icon_modes'][$stored_icon_mode])) {
				$data['theme_default_icon_mode'] = $stored_icon_mode;
			} else {
				$data['theme_default_icon_mode'] = 'auto';
			}
		} else {
			$data['theme_default_icon_mode'] = 'auto';
		}

		$data['commerce_styles'] = array('standard' => $this->language->get('text_commerce_standard'), 'modern' => $this->language->get('text_commerce_modern'));
		if (isset($this->request->post['theme_default_commerce_style'])) {
			$data['theme_default_commerce_style'] = (string)$this->request->post['theme_default_commerce_style'];
		} elseif (isset($setting_info['theme_default_commerce_style']) && isset($data['commerce_styles'][$setting_info['theme_default_commerce_style']])) {
			$data['theme_default_commerce_style'] = (string)$setting_info['theme_default_commerce_style'];
		} else {
			$data['theme_default_commerce_style'] = 'standard';
		}

		if (isset($this->request->post['theme_default_catalog_ajax_status'])) {
			$data['theme_default_catalog_ajax_status'] = !empty($this->request->post['theme_default_catalog_ajax_status']) ? 1 : 0;
		} elseif (isset($setting_info['theme_default_catalog_ajax_status'])) {
			$data['theme_default_catalog_ajax_status'] = !empty($setting_info['theme_default_catalog_ajax_status']) ? 1 : 0;
		} else {
			$data['theme_default_catalog_ajax_status'] = 0;
		}

		if (isset($this->request->post['theme_default_border_radius'])) {
			$data['theme_default_border_radius'] = max(0, min(24, (int)$this->request->post['theme_default_border_radius']));
		} elseif (isset($setting_info['theme_default_border_radius'])) {
			$data['theme_default_border_radius'] = max(0, min(24, (int)$setting_info['theme_default_border_radius']));
		} else {
			$data['theme_default_border_radius'] = 8;
		}

		if (isset($this->request->post['theme_default_dark_mode_status'])) {
			$data['theme_default_dark_mode_status'] = !empty($this->request->post['theme_default_dark_mode_status']) ? 1 : 0;
		} elseif (isset($setting_info['theme_default_dark_mode_status'])) {
			$data['theme_default_dark_mode_status'] = !empty($setting_info['theme_default_dark_mode_status']) ? 1 : 0;
		} else {
			$data['theme_default_dark_mode_status'] = 1;
		}

		$data['dark_mode_defaults'] = array('light' => $this->language->get('text_dark_light'), 'system' => $this->language->get('text_dark_system'), 'dark' => $this->language->get('text_dark_dark'));
		$data['datetimepicker_themes'] = array('light' => $this->language->get('text_dark_light'), 'system' => $this->language->get('text_dark_system'), 'dark' => $this->language->get('text_dark_dark'));
		$data['gallery_engines'] = array('photoswipe' => 'PhotoSwipe 5.4.4', 'magnific' => 'Magnific Popup 1.2.0');
		if (isset($this->request->post['theme_default_dark_mode_default']) && isset($data['dark_mode_defaults'][(string)$this->request->post['theme_default_dark_mode_default']])) {
			$data['theme_default_dark_mode_default'] = (string)$this->request->post['theme_default_dark_mode_default'];
		} elseif (isset($setting_info['theme_default_dark_mode_default']) && isset($data['dark_mode_defaults'][(string)$setting_info['theme_default_dark_mode_default']])) {
			$data['theme_default_dark_mode_default'] = (string)$setting_info['theme_default_dark_mode_default'];
		} else {
			$data['theme_default_dark_mode_default'] = 'light';
		}

		if (isset($this->request->post['theme_default_datetimepicker_theme']) && isset($data['datetimepicker_themes'][(string)$this->request->post['theme_default_datetimepicker_theme']])) {
			$data['theme_default_datetimepicker_theme'] = (string)$this->request->post['theme_default_datetimepicker_theme'];
		} elseif (isset($setting_info['theme_default_datetimepicker_theme']) && isset($data['datetimepicker_themes'][(string)$setting_info['theme_default_datetimepicker_theme']])) {
			$data['theme_default_datetimepicker_theme'] = (string)$setting_info['theme_default_datetimepicker_theme'];
		} else {
			$data['theme_default_datetimepicker_theme'] = 'light';
		}

		if (isset($this->request->post['theme_default_gallery_engine']) && isset($data['gallery_engines'][(string)$this->request->post['theme_default_gallery_engine']])) {
			$data['theme_default_gallery_engine'] = (string)$this->request->post['theme_default_gallery_engine'];
		} elseif (isset($setting_info['theme_default_gallery_engine']) && isset($data['gallery_engines'][(string)$setting_info['theme_default_gallery_engine']])) {
			$data['theme_default_gallery_engine'] = (string)$setting_info['theme_default_gallery_engine'];
		} else {
			$data['theme_default_gallery_engine'] = 'photoswipe';
		}

		foreach (array('theme_default_header_phone_status','theme_default_header_email_status') as $contact_key) {
			if (isset($this->request->post[$contact_key])) {
				$data[$contact_key] = !empty($this->request->post[$contact_key]) ? 1 : 0;
			} elseif (isset($setting_info[$contact_key])) {
				$data[$contact_key] = !empty($setting_info[$contact_key]) ? 1 : 0;
			} else {
				$data[$contact_key] = 1;
			}
		}

		$menu_modes = array('horizontal', 'vertical');
		if (isset($this->request->post['theme_default_header_menu_mode']) && in_array((string)$this->request->post['theme_default_header_menu_mode'], $menu_modes, true)) {
			$data['theme_default_header_menu_mode'] = (string)$this->request->post['theme_default_header_menu_mode'];
		} elseif (isset($setting_info['theme_default_header_menu_mode']) && in_array((string)$setting_info['theme_default_header_menu_mode'], $menu_modes, true)) {
			$data['theme_default_header_menu_mode'] = (string)$setting_info['theme_default_header_menu_mode'];
		} else {
			$data['theme_default_header_menu_mode'] = 'horizontal';
		}
		$data['theme_default_header_information_ids'] = isset($this->request->post['theme_default_header_information_ids']) && is_array($this->request->post['theme_default_header_information_ids']) ? array_values(array_unique(array_map('intval', $this->request->post['theme_default_header_information_ids']))) : (isset($setting_info['theme_default_header_information_ids']) && is_array($setting_info['theme_default_header_information_ids']) ? array_values(array_unique(array_map('intval', $setting_info['theme_default_header_information_ids']))) : array());
		$this->load->model('catalog/information');
		$data['header_informations'] = $this->model_catalog_information->getInformations();
		$data['user_token'] = $this->session->data['user_token'];
		$data['theme_default_header_product_ids'] = isset($this->request->post['theme_default_header_product_ids']) && is_array($this->request->post['theme_default_header_product_ids']) ? array_values(array_unique(array_map('intval', $this->request->post['theme_default_header_product_ids']))) : (isset($setting_info['theme_default_header_product_ids']) && is_array($setting_info['theme_default_header_product_ids']) ? array_values(array_unique(array_map('intval', $setting_info['theme_default_header_product_ids']))) : array());
		$this->load->model('catalog/product');
		$data['header_products'] = array();
		foreach ($data['theme_default_header_product_ids'] as $header_product_id) { $header_product = $this->model_catalog_product->getProduct((int)$header_product_id); if ($header_product) { $data['header_products'][] = array('product_id'=>(int)$header_product_id,'name'=>$header_product['name']); } }
		$data['theme_default_header_custom_links'] = isset($this->request->post['theme_default_header_custom_links']) && is_array($this->request->post['theme_default_header_custom_links']) ? $this->request->post['theme_default_header_custom_links'] : (isset($setting_info['theme_default_header_custom_links']) && is_array($setting_info['theme_default_header_custom_links']) ? $setting_info['theme_default_header_custom_links'] : array());

		if (isset($this->request->post['theme_default_custom_css'])) {
			$data['theme_default_custom_css'] = (string)$this->request->post['theme_default_custom_css'];
		} elseif (isset($setting_info['theme_default_custom_css'])) {
			$data['theme_default_custom_css'] = (string)$setting_info['theme_default_custom_css'];
		} else {
			$data['theme_default_custom_css'] = '';
		}

		if (isset($this->request->post['theme_default_product_description_length'])) {
			$data['theme_default_product_description_length'] = $this->request->post['theme_default_product_description_length'];
		} elseif (isset($setting_info['theme_default_product_description_length'])) {
			$data['theme_default_product_description_length'] = $setting_info['theme_default_product_description_length'];
		} else {
			$data['theme_default_product_description_length'] = 100;
		}

		if (isset($this->request->post['theme_default_product_card_content'])) {
			$data['theme_default_product_card_content'] = (string)$this->request->post['theme_default_product_card_content'];
		} elseif (isset($setting_info['theme_default_product_card_content']) && in_array((string)$setting_info['theme_default_product_card_content'], array('none', 'description', 'attributes', 'both'), true)) {
			$data['theme_default_product_card_content'] = (string)$setting_info['theme_default_product_card_content'];
		} else {
			$data['theme_default_product_card_content'] = 'description';
		}

		if (isset($this->request->post['theme_default_product_card_attribute_limit'])) {
			$data['theme_default_product_card_attribute_limit'] = max(1, min(10, (int)$this->request->post['theme_default_product_card_attribute_limit']));
		} elseif (isset($setting_info['theme_default_product_card_attribute_limit'])) {
			$data['theme_default_product_card_attribute_limit'] = max(1, min(10, (int)$setting_info['theme_default_product_card_attribute_limit']));
		} else {
			$data['theme_default_product_card_attribute_limit'] = 3;
		}

		if (isset($this->request->post['theme_default_option_image_switch_status'])) {
			$data['theme_default_option_image_switch_status'] = !empty($this->request->post['theme_default_option_image_switch_status']) ? 1 : 0;
		} elseif (isset($setting_info['theme_default_option_image_switch_status'])) {
			$data['theme_default_option_image_switch_status'] = !empty($setting_info['theme_default_option_image_switch_status']) ? 1 : 0;
		} else {
			$data['theme_default_option_image_switch_status'] = 0;
		}

		if (isset($this->request->post['theme_default_purchase_blocks_status'])) {
			$data['theme_default_purchase_blocks_status'] = !empty($this->request->post['theme_default_purchase_blocks_status']) ? 1 : 0;
		} elseif (isset($setting_info['theme_default_purchase_blocks_status'])) {
			$data['theme_default_purchase_blocks_status'] = !empty($setting_info['theme_default_purchase_blocks_status']) ? 1 : 0;
		} else {
			$data['theme_default_purchase_blocks_status'] = 1;
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['theme_default_product_extra_tab_status'] = isset($this->request->post['theme_default_product_extra_tab_status']) ? (!empty($this->request->post['theme_default_product_extra_tab_status'])?1:0) : (!empty($setting_info['theme_default_product_extra_tab_status'])?1:0);
		$data['theme_default_product_extra_tab_title'] = isset($this->request->post['theme_default_product_extra_tab_title']) && is_array($this->request->post['theme_default_product_extra_tab_title']) ? $this->request->post['theme_default_product_extra_tab_title'] : (isset($setting_info['theme_default_product_extra_tab_title']) && is_array($setting_info['theme_default_product_extra_tab_title']) ? $setting_info['theme_default_product_extra_tab_title'] : array());
		$data['theme_default_product_extra_tab_content'] = isset($this->request->post['theme_default_product_extra_tab_content']) && is_array($this->request->post['theme_default_product_extra_tab_content']) ? $this->request->post['theme_default_product_extra_tab_content'] : (isset($setting_info['theme_default_product_extra_tab_content']) && is_array($setting_info['theme_default_product_extra_tab_content']) ? $setting_info['theme_default_product_extra_tab_content'] : array());
		
		if (isset($this->request->post['theme_default_image_category_width'])) {
			$data['theme_default_image_category_width'] = $this->request->post['theme_default_image_category_width'];
		} elseif (isset($setting_info['theme_default_image_category_width'])) {
			$data['theme_default_image_category_width'] = $setting_info['theme_default_image_category_width'];
		} else {
			$data['theme_default_image_category_width'] = 80;		
		}
		
		if (isset($this->request->post['theme_default_image_category_height'])) {
			$data['theme_default_image_category_height'] = $this->request->post['theme_default_image_category_height'];
		} elseif (isset($setting_info['theme_default_image_category_height'])) {
			$data['theme_default_image_category_height'] = $setting_info['theme_default_image_category_height'];
		} else {
			$data['theme_default_image_category_height'] = 80;
		}
		
		if (isset($this->request->post['theme_default_image_manufacturer_width'])) {
			$data['theme_default_image_manufacturer_width'] = $this->request->post['theme_default_image_manufacturer_width'];
		} elseif (isset($setting_info['theme_default_image_manufacturer_width'])) {
			$data['theme_default_image_manufacturer_width'] = $setting_info['theme_default_image_manufacturer_width'];
		} else {
			$data['theme_default_image_manufacturer_width'] = 160;		
		}
		
		if (isset($this->request->post['theme_default_image_manufacturer_height'])) {
			$data['theme_default_image_manufacturer_height'] = $this->request->post['theme_default_image_manufacturer_height'];
		} elseif (isset($setting_info['theme_default_image_manufacturer_height'])) {
			$data['theme_default_image_manufacturer_height'] = $setting_info['theme_default_image_manufacturer_height'];
		} else {
			$data['theme_default_image_manufacturer_height'] = 160;
		}
		
		if (isset($this->request->post['theme_default_image_thumb_width'])) {
			$data['theme_default_image_thumb_width'] = $this->request->post['theme_default_image_thumb_width'];
		} elseif (isset($setting_info['theme_default_image_thumb_width'])) {
			$data['theme_default_image_thumb_width'] = $setting_info['theme_default_image_thumb_width'];
		} else {
			$data['theme_default_image_thumb_width'] = 640;
		}
		
		if (isset($this->request->post['theme_default_image_thumb_height'])) {
			$data['theme_default_image_thumb_height'] = $this->request->post['theme_default_image_thumb_height'];
		} elseif (isset($setting_info['theme_default_image_thumb_height'])) {
			$data['theme_default_image_thumb_height'] = $setting_info['theme_default_image_thumb_height'];
		} else {
			$data['theme_default_image_thumb_height'] = 480;		
		}
		
		if (isset($this->request->post['theme_default_image_popup_width'])) {
			$data['theme_default_image_popup_width'] = $this->request->post['theme_default_image_popup_width'];
		} elseif (isset($setting_info['theme_default_image_popup_width'])) {
			$data['theme_default_image_popup_width'] = $setting_info['theme_default_image_popup_width'];
		} else {
			$data['theme_default_image_popup_width'] = 1000;
		}
		
		if (isset($this->request->post['theme_default_image_popup_height'])) {
			$data['theme_default_image_popup_height'] = $this->request->post['theme_default_image_popup_height'];
		} elseif (isset($setting_info['theme_default_image_popup_height'])) {
			$data['theme_default_image_popup_height'] = $setting_info['theme_default_image_popup_height'];
		} else {
			$data['theme_default_image_popup_height'] = 750;
		}
		
		if (isset($this->request->post['theme_default_image_product_width'])) {
			$data['theme_default_image_product_width'] = $this->request->post['theme_default_image_product_width'];
		} elseif (isset($setting_info['theme_default_image_product_width'])) {
			$data['theme_default_image_product_width'] = $setting_info['theme_default_image_product_width'];
		} else {
			$data['theme_default_image_product_width'] = 600;
		}
		
		if (isset($this->request->post['theme_default_image_product_height'])) {
			$data['theme_default_image_product_height'] = $this->request->post['theme_default_image_product_height'];
		} elseif (isset($setting_info['theme_default_image_product_height'])) {
			$data['theme_default_image_product_height'] = $setting_info['theme_default_image_product_height'];
		} else {
			$data['theme_default_image_product_height'] = 600;
		}
		
		if (isset($this->request->post['theme_default_image_additional_width'])) {
			$data['theme_default_image_additional_width'] = $this->request->post['theme_default_image_additional_width'];
		} elseif (isset($setting_info['theme_default_image_additional_width'])) {
			$data['theme_default_image_additional_width'] = $setting_info['theme_default_image_additional_width'];
		} else {
			$data['theme_default_image_additional_width'] = 74;
		}
		
		if (isset($this->request->post['theme_default_image_additional_height'])) {
			$data['theme_default_image_additional_height'] = $this->request->post['theme_default_image_additional_height'];
		} elseif (isset($setting_info['theme_default_image_additional_height'])) {
			$data['theme_default_image_additional_height'] = $setting_info['theme_default_image_additional_height'];
		} else {
			$data['theme_default_image_additional_height'] = 74;
		}
		
		if (isset($this->request->post['theme_default_image_related_width'])) {
			$data['theme_default_image_related_width'] = $this->request->post['theme_default_image_related_width'];
		} elseif (isset($setting_info['theme_default_image_related_width'])) {
			$data['theme_default_image_related_width'] = $setting_info['theme_default_image_related_width'];
		} else {
			$data['theme_default_image_related_width'] = 80;
		}
		
		if (isset($this->request->post['theme_default_image_related_height'])) {
			$data['theme_default_image_related_height'] = $this->request->post['theme_default_image_related_height'];
		} elseif (isset($setting_info['theme_default_image_related_height'])) {
			$data['theme_default_image_related_height'] = $setting_info['theme_default_image_related_height'];
		} else {
			$data['theme_default_image_related_height'] = 80;
		}
		
		if (isset($this->request->post['theme_default_image_compare_width'])) {
			$data['theme_default_image_compare_width'] = $this->request->post['theme_default_image_compare_width'];
		} elseif (isset($setting_info['theme_default_image_compare_width'])) {
			$data['theme_default_image_compare_width'] = $setting_info['theme_default_image_compare_width'];
		} else {
			$data['theme_default_image_compare_width'] = 90;
		}
		
		if (isset($this->request->post['theme_default_image_compare_height'])) {
			$data['theme_default_image_compare_height'] = $this->request->post['theme_default_image_compare_height'];
		} elseif (isset($setting_info['theme_default_image_compare_height'])) {
			$data['theme_default_image_compare_height'] = $setting_info['theme_default_image_compare_height'];
		} else {
			$data['theme_default_image_compare_height'] = 90;
		}
		
		if (isset($this->request->post['theme_default_image_wishlist_width'])) {
			$data['theme_default_image_wishlist_width'] = $this->request->post['theme_default_image_wishlist_width'];
		} elseif (isset($setting_info['theme_default_image_wishlist_width'])) {
			$data['theme_default_image_wishlist_width'] = $setting_info['theme_default_image_wishlist_width'];
		} else {
			$data['theme_default_image_wishlist_width'] = 47;
		}
		
		if (isset($this->request->post['theme_default_image_wishlist_height'])) {
			$data['theme_default_image_wishlist_height'] = $this->request->post['theme_default_image_wishlist_height'];
		} elseif (isset($setting_info['theme_default_image_wishlist_height'])) {
			$data['theme_default_image_wishlist_height'] = $setting_info['theme_default_image_wishlist_height'];
		} else {
			$data['theme_default_image_wishlist_height'] = 47;
		}
		
		if (isset($this->request->post['theme_default_image_cart_width'])) {
			$data['theme_default_image_cart_width'] = $this->request->post['theme_default_image_cart_width'];
		} elseif (isset($setting_info['theme_default_image_cart_width'])) {
			$data['theme_default_image_cart_width'] = $setting_info['theme_default_image_cart_width'];
		} else {
			$data['theme_default_image_cart_width'] = 47;
		}
		
		if (isset($this->request->post['theme_default_image_cart_height'])) {
			$data['theme_default_image_cart_height'] = $this->request->post['theme_default_image_cart_height'];
		} elseif (isset($setting_info['theme_default_image_cart_height'])) {
			$data['theme_default_image_cart_height'] = $setting_info['theme_default_image_cart_height'];
		} else {
			$data['theme_default_image_cart_height'] = 47;
		}
		
		if (isset($this->request->post['theme_default_image_location_width'])) {
			$data['theme_default_image_location_width'] = $this->request->post['theme_default_image_location_width'];
		} elseif (isset($setting_info['theme_default_image_location_width'])) {
			$data['theme_default_image_location_width'] = $setting_info['theme_default_image_location_width'];
		} else {
			$data['theme_default_image_location_width'] = 268;
		}
		
		if (isset($this->request->post['theme_default_image_location_height'])) {
			$data['theme_default_image_location_height'] = $this->request->post['theme_default_image_location_height'];
		} elseif (isset($setting_info['theme_default_image_location_height'])) {
			$data['theme_default_image_location_height'] = $setting_info['theme_default_image_location_height'];
		} else {
			$data['theme_default_image_location_height'] = 50;
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/theme/default', $data));
	}


	public function catalogAjaxCheck() {
		$this->load->language('extension/theme/default');
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$json = array('status'=>'pass','message'=>$this->language->get('text_catalog_ajax_check_pass'),'items'=>array());
		$noise = '';
		ob_start();
		try {
			if (!$this->user->hasPermission('access', 'extension/theme/default')) {
				throw new \RuntimeException($this->language->get('error_permission'));
			}
			$store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;
			$this->load->model('setting/setting');
			$settings = $this->model_setting_setting->getSetting('theme_default', $store_id);
			$directory = isset($settings['theme_default_directory']) ? (string)$settings['theme_default_directory'] : 'default';
			if ($directory !== 'default') {
				$json['status']='fail'; $json['message']=$this->language->get('text_catalog_ajax_check_theme'); $json['items'][]=$directory;
			}
			$asset=DIR_CATALOG.'view/javascript/codecart/catalog/catalog-ajax.js';
			if (!is_file($asset) || !is_readable($asset)) { $json['status']='fail'; $json['message']=$this->language->get('text_catalog_ajax_check_asset'); }
			$targets=array('catalog/view/theme/default/template/product/category.twig','catalog/view/theme/default/template/product/manufacturer_info.twig','catalog/view/theme/default/template/product/search.twig','catalog/view/theme/default/template/product/special.twig','catalog/view/theme/default/template/extension/module/filter.twig');
			$touching=array();
			try {
				$q=$this->db->query("SELECT `name`,`code`,`xml` FROM `".DB_PREFIX."modification` WHERE `status`='1' ORDER BY `name` ASC");
				foreach($q->rows as $row){$xml=isset($row['xml'])?(string)$row['xml']:'';foreach($targets as $target){if($xml!==''&&strpos($xml,$target)!==false){$name=trim((string)($row['name']?:$row['code']));if($name!=='')$touching[$name]=true;break;}}}
			} catch (\Throwable $e) {
				$json['status']='warning'; $json['items'][]=$this->language->get('text_catalog_ajax_check_ocmod_unavailable');
			}
			$script=false;
			if(defined('DIR_MODIFICATION')){foreach($targets as $target){$file=rtrim(DIR_MODIFICATION,'/\\').'/'.$target;if(is_file($file)&&is_readable($file)){$content=@file_get_contents($file);if(is_string($content)&&stripos($content,'<script')!==false){$script=true;break;}}}}
			if($json['status']!=='fail'&&($touching||$script)){$json['status']='warning';$json['message']=$this->language->get('text_catalog_ajax_check_warning');}
			if($touching)$json['items']=array_merge($json['items'],array_keys($touching));
			if($script)$json['items'][]=$this->language->get('text_catalog_ajax_check_script');
		} catch (\Throwable $e) {
			$json['status']='fail'; $json['message']=$this->language->get('text_catalog_ajax_check_failed');
			$this->log->write('Catalog AJAX compatibility check failed: '.$e->getMessage());
		}
		$noise=(string)ob_get_clean();
		if(trim($noise)!==''){$this->log->write('Catalog AJAX compatibility check output suppressed: '.substr(strip_tags($noise),0,1000));}
		$this->response->setOutput(json_encode($json,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/theme/default')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['theme_default_product_limit']) {
			$this->error['product_limit'] = $this->language->get('error_limit');
		}

		if (!$this->request->post['theme_default_product_description_length']) {
			$this->error['product_description_length'] = $this->language->get('error_limit');
		}

		if (!$this->request->post['theme_default_image_category_width'] || !$this->request->post['theme_default_image_category_height']) {
			$this->error['image_category'] = $this->language->get('error_image_category');
		}
		
		if (!$this->request->post['theme_default_image_manufacturer_width'] || !$this->request->post['theme_default_image_manufacturer_height']) {
			$this->error['image_manufacturer'] = $this->language->get('error_image_manufacturer');
		}

		if (!$this->request->post['theme_default_image_thumb_width'] || !$this->request->post['theme_default_image_thumb_height']) {
			$this->error['image_thumb'] = $this->language->get('error_image_thumb');
		}

		if (!$this->request->post['theme_default_image_popup_width'] || !$this->request->post['theme_default_image_popup_height']) {
			$this->error['image_popup'] = $this->language->get('error_image_popup');
		}

		if (!$this->request->post['theme_default_image_product_width'] || !$this->request->post['theme_default_image_product_height']) {
			$this->error['image_product'] = $this->language->get('error_image_product');
		}

		if (!$this->request->post['theme_default_image_additional_width'] || !$this->request->post['theme_default_image_additional_height']) {
			$this->error['image_additional'] = $this->language->get('error_image_additional');
		}

		if (!$this->request->post['theme_default_image_related_width'] || !$this->request->post['theme_default_image_related_height']) {
			$this->error['image_related'] = $this->language->get('error_image_related');
		}

		if (!$this->request->post['theme_default_image_compare_width'] || !$this->request->post['theme_default_image_compare_height']) {
			$this->error['image_compare'] = $this->language->get('error_image_compare');
		}

		if (!$this->request->post['theme_default_image_wishlist_width'] || !$this->request->post['theme_default_image_wishlist_height']) {
			$this->error['image_wishlist'] = $this->language->get('error_image_wishlist');
		}

		if (!$this->request->post['theme_default_image_cart_width'] || !$this->request->post['theme_default_image_cart_height']) {
			$this->error['image_cart'] = $this->language->get('error_image_cart');
		}

		if (!$this->request->post['theme_default_image_location_width'] || !$this->request->post['theme_default_image_location_height']) {
			$this->error['image_location'] = $this->language->get('error_image_location');
		}

		return !$this->error;
	}
}
