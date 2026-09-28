<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$this->load->model('setting/extension');

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		$is_https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');

		if ($is_https) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		$store_icon = trim((string)$this->config->get('config_icon'));
		if ($store_icon !== '' && is_file(DIR_IMAGE . $store_icon)) {
			$icon_path = str_replace('\\', '/', $store_icon);
			$icon_version = (int)@filemtime(DIR_IMAGE . $store_icon);
			$icon_url = $server . 'image/' . str_replace(' ', '%20', $icon_path);
			if ($icon_version > 0) {
				$icon_url .= (strpos($icon_url, '?') === false ? '?' : '&') . 'v=' . $icon_version;
			}
			$this->document->addLink($icon_url, 'icon');
		}

		$apple_touch_icon = trim((string)$this->config->get('config_apple_touch_icon'));
		if ($apple_touch_icon !== '' && is_file(DIR_IMAGE . $apple_touch_icon)) {
			$this->document->addLink($server . 'image/' . str_replace(' ', '%20', str_replace('\\', '/', $apple_touch_icon)), 'apple-touch-icon');
		}

		$data['title'] = $this->document->getTitle();

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['robots'] = $this->document->getRobots();
		$canonical_url = $this->getCanonicalUrl($data['links']);
		$data['alternate_links'] = $this->buildAlternateLinks($canonical_url, $is_https);
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['modern_assets'] = method_exists($this->document, 'getAssets') ? $this->document->getAssets('header') : array();
		try {
			$assetOptimizer = new \CodeCart\Core\StorefrontAssetOptimizer($this->config);
			$data['styles'] = $assetOptimizer->styles($data['styles']);
			$data['scripts'] = $assetOptimizer->scripts($data['scripts']);
			$data['modern_assets'] = $assetOptimizer->assets($data['modern_assets']);
		} catch (\Throwable $e) {
			// Optimization is optional: storefront must always fall back to original assets.
		}
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$storefront_colors = array(
			'theme_accent_color' => array('theme_default_accent_color', '#0b6fd3'),
			'theme_accent_hover' => array('theme_default_accent_hover', '#095eb4'),
			'theme_button_color' => array('theme_default_button_color', '#0b6fd3'),
			'theme_button_hover' => array('theme_default_button_hover', '#095eb4'),
			'theme_button_text_color' => array('theme_default_button_text_color', '#ffffff'),
			'theme_buy_button_color' => array('theme_default_buy_button_color', '#0b6fd3'),
			'theme_buy_button_hover' => array('theme_default_buy_button_hover', '#095eb4'),
			'theme_buy_button_text_color' => array('theme_default_buy_button_text_color', '#ffffff'),
			'theme_sale_price_color' => array('theme_default_sale_price_color', '#d92d20'),
			'theme_cart_button_color' => array('theme_default_cart_button_color', '#0b6fd3'),
			'theme_cart_button_hover' => array('theme_default_cart_button_hover', '#095eb4'),
			'theme_cart_button_text_color' => array('theme_default_cart_button_text_color', '#ffffff'),
			'theme_text_color' => array('theme_default_text_color', '#3d4652'),
			'theme_heading_color' => array('theme_default_heading_color', '#273142'),
			'theme_h1_color' => array('theme_default_h1_color', '#273142'),
			'theme_h2_color' => array('theme_default_h2_color', '#273142'),
			'theme_background_color' => array('theme_default_background_color', '#ffffff'),
			'theme_surface_color' => array('theme_default_surface_color', '#f7f9fb'),
			'theme_border_color' => array('theme_default_border_color', '#e5e9ef'),
			'theme_footer_background_color' => array('theme_default_footer_background_color', '#303030'),
			'theme_footer_text_color' => array('theme_default_footer_text_color', '#e2e2e2'),
			'theme_footer_link_color' => array('theme_default_footer_link_color', '#cccccc'),
			'theme_footer_heading_color' => array('theme_default_footer_heading_color', '#ffffff')
		);

		foreach ($storefront_colors as $data_key => $color_setting) {
			$color = (string)$this->config->get($color_setting[0]);
			$data[$data_key] = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : $color_setting[1];
		}

		$font_stacks = array(
			'system' => "-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif",
			'inter' => "Inter,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif",
			'arial' => "Arial,Helvetica,sans-serif",
			'verdana' => "Verdana,Geneva,sans-serif",
			'tahoma' => "Tahoma,Verdana,sans-serif",
			'trebuchet' => "Trebuchet MS,Arial,sans-serif",
			'georgia' => "Georgia,Times New Roman,serif"
		);
		$font_key = (string)$this->config->get('theme_default_font_family');
		if (!isset($font_stacks[$font_key])) { $font_key = 'system'; }
		$radius_setting = $this->config->get('theme_default_border_radius');
		$radius = ($radius_setting === null || $radius_setting === '') ? 8 : (int)$radius_setting;
		if ($radius < 0 || $radius > 24) { $radius = 8; }
		$data['theme_border_radius'] = $radius;

		$data['theme_font_family'] = $font_stacks[$font_key];
		$data['theme_font_key'] = $font_key;
		$active_theme = strtolower(trim((string)$this->config->get('config_theme')));
		if ($active_theme === '') { $active_theme = 'default'; }
		$theme_directory = trim((string)$this->config->get('theme_' . $active_theme . '_directory'));
		if ($theme_directory === '') { $theme_directory = $active_theme; }
		$is_default_theme = in_array($active_theme, array('default', 'codecart'), true) && in_array($theme_directory, array('default', 'codecart'), true);

		$icon_mode = (string)$this->config->get('theme_default_icon_mode');
		if ($icon_mode === '' || $icon_mode === 'standard') { $icon_mode = $icon_mode === 'standard' ? 'full' : 'auto'; }
		$data['font_awesome_unresolved_css'] = '';
		try {
			$fontAwesome = new \CodeCart\Core\FontAwesomeManager($theme_directory, explode(',', (string)$this->config->get('codecart_fontawesome_extra_icons')));
			$effective_icon_mode = $fontAwesome->resolveCatalogMode($icon_mode);
			if ($is_default_theme) {
				$data['font_awesome_unresolved_css'] = $fontAwesome->unresolvedPlaceholderCss() . ($effective_icon_mode === 'core' ? $fontAwesome->extraCss() : '');
			}
		} catch (\Throwable $e) {
			// Compatibility-first fallback: a scanner failure must never hide extension icons.
			$effective_icon_mode = 'full';
		}
		$data['font_awesome_mode'] = $icon_mode;
		$data['font_awesome_mode_effective'] = $effective_icon_mode;
		if ($effective_icon_mode === 'core') {
			$data['font_awesome_css'] = 'catalog/view/javascript/font-awesome/css/font-awesome-core.min.css?v=6.7.2-cc178';
		} else {
			$data['font_awesome_css'] = 'catalog/view/javascript/font-awesome/css/font-awesome.min.css?v=6.7.2-cc178';
		}
		$data['catalog_ajax_status'] = ($is_default_theme && (bool)$this->config->get('theme_default_catalog_ajax_status'));
		$dark_status = $this->config->get('theme_default_dark_mode_status');
		$data['theme_dark_mode_status'] = $is_default_theme && ($dark_status === null || $dark_status === '' || (int)$dark_status === 1);
		$dark_default = (string)$this->config->get('theme_default_dark_mode_default');
		$data['theme_dark_mode_default'] = in_array($dark_default, array('light','system','dark'), true) ? $dark_default : 'light';
		$picker_theme = (string)$this->config->get('theme_default_datetimepicker_theme');
		$data['theme_datetimepicker_theme'] = in_array($picker_theme, array('light','system','dark'), true) ? $picker_theme : 'light';
		$header_phone_status = $this->config->get('theme_default_header_phone_status');
		$header_email_status = $this->config->get('theme_default_header_email_status');
		$data['theme_header_phone_status'] = ($header_phone_status === null || $header_phone_status === '' || (int)$header_phone_status === 1);
		$data['theme_header_email_status'] = ($header_email_status === null || $header_email_status === '' || (int)$header_email_status === 1);
		$custom_css = $is_default_theme ? (string)$this->config->get('theme_default_custom_css') : '';
		$custom_css = str_replace(chr(0), '', $custom_css);
		$custom_css = preg_replace('#</?style\b[^>]*>#i', '', $custom_css);
		$data['theme_custom_css'] = $custom_css;

		$data['name'] = $this->config->get('config_name');
		// OpenCart stores developer_theme=1 as the normal production default (it
		// controls template caching). It must not force the unminified stylesheet.
		// Serve stylesheet.css only when the minified copy is missing or was not built
		// from the current source (for example after a merchant edited stylesheet.css).
		$stylesheet_theme = (string)$this->config->get('config_theme') === 'default' ? (string)$this->config->get('theme_default_directory') : (string)$this->config->get('config_theme');
		$stylesheet_theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $stylesheet_theme);
		$stylesheet_dir = DIR_APPLICATION . 'view/theme/' . ($stylesheet_theme !== '' ? $stylesheet_theme : 'default') . '/stylesheet/';
		$stylesheet_source = $stylesheet_dir . 'stylesheet.css';
		$stylesheet_min = $stylesheet_dir . 'stylesheet.min.css';
		$data['developer_theme'] = $this->isMinifiedStylesheetStale($stylesheet_source, $stylesheet_min);

		$data['logo_width'] = 0;
		$data['logo_height'] = 0;
		$data['logo_srcset'] = '';
		$logo_name = (string)$this->config->get('config_logo');
		$logo_file = DIR_IMAGE . $logo_name;
		if ($logo_name !== '' && is_file($logo_file)) {
			$logo_size = @getimagesize($logo_file);
			if (is_array($logo_size)) {
				$source_logo_width = max(1, (int)$logo_size[0]);
				$source_logo_height = max(1, (int)$logo_size[1]);
				$max_logo_width = 320;
				if ($source_logo_width > $max_logo_width) {
					$data['logo_width'] = $max_logo_width;
					$data['logo_height'] = max(1, (int)round($source_logo_height * ($max_logo_width / $source_logo_width)));
				} else {
					$data['logo_width'] = $source_logo_width;
					$data['logo_height'] = $source_logo_height;
				}
				$this->load->model('tool/image');
				$data['logo'] = $this->model_tool_image->resize($logo_name, $data['logo_width'], $data['logo_height']);
				// A 320px logo was being downloaded for a ~190px mobile slot.
				// Offer a smaller candidate without changing the desktop logo quality.
				$small_logo_width = min(200, $data['logo_width']);
				if ($small_logo_width > 0 && $small_logo_width < $data['logo_width']) {
					$small_logo_height = max(1, (int)round($data['logo_height'] * ($small_logo_width / $data['logo_width'])));
					$small_logo = $this->model_tool_image->resize($logo_name, $small_logo_width, $small_logo_height);
					$data['logo_srcset'] = $small_logo . ' ' . $small_logo_width . 'w, ' . $data['logo'] . ' ' . $data['logo_width'] . 'w';
				}
			} else {
				$data['logo'] = $server . 'image/' . $logo_name;
			}
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');
		$data['text_theme_toggle'] = $this->language->get('text_theme_toggle');
		if ($data['text_theme_toggle'] === 'text_theme_toggle' || $data['text_theme_toggle'] === '') { $data['text_theme_toggle'] = 'Switch theme'; }
		$data['cart_add_url'] = $this->url->link('checkout/cart/add', '', true);
		$data['cart_edit_url'] = $this->url->link('checkout/cart/edit', '', true);
		$data['cart_remove_url'] = $this->url->link('checkout/cart/remove', '', true);
		$data['cart_info_url'] = $this->url->link('common/cart/info', '', true);
		$data['wishlist_add_url'] = $this->url->link('account/wishlist/add', '', true);
		$data['compare_add_url'] = $this->url->link('product/compare/add', '', true);

		$data['button_in_cart'] = $this->language->get('button_in_cart');
		$data['codecart_ui_i18n'] = array();
		if (strpos(strtolower((string)$data['lang']), 'uk') === 0) {
			foreach (array('ui_select_date','ui_wishlist','ui_compare','ui_share','ui_search','ui_previous','ui_next','ui_close','ui_increase','ui_decrease','ui_upload','ui_action') as $ui_key) {
				$data['codecart_ui_i18n'][$ui_key] = $this->language->get($ui_key);
			}
		}
		if ($data['button_in_cart'] === 'button_in_cart' || $data['button_in_cart'] === '') { $data['button_in_cart'] = 'In Cart'; }
		
		
		$host = $is_https ? HTTPS_SERVER : HTTP_SERVER;
		$request_uri = isset($this->request->server['REQUEST_URI']) ? (string)$this->request->server['REQUEST_URI'] : '/';
		if ($canonical_url !== '') {
			$data['og_url'] = html_entity_decode((string)$canonical_url, ENT_QUOTES, 'UTF-8');
		} elseif ($request_uri == '/') {
			$data['og_url'] = html_entity_decode((string)$this->url->link('common/home'), ENT_QUOTES, 'UTF-8');
		} else {
			$data['og_url'] = rtrim($host, '/') . '/' . ltrim($request_uri, '/');
		}

		$data['og_type'] = method_exists($this->document, 'getOgType') ? $this->document->getOgType() : 'website';
		$data['og_description'] = (string)$this->document->getDescription();
		$data['og_image'] = $this->document->getOgImage();
		if (!$data['og_image']) {
			$social_preview = trim((string)$this->config->get('config_social_preview_image'));
			if ($social_preview !== '' && is_file(DIR_IMAGE . $social_preview)) {
				$data['og_image'] = rtrim($server, '/') . '/image/' . str_replace(' ', '%20', ltrim(str_replace('\\', '/', $social_preview), '/'));
			}
		}
		


		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$quick_cart_link = (string)$this->config->get('theme_default_commerce_style') === 'modern'
			&& (bool)$this->config->get('config_quick_checkout_status');
		$data['quick_checkout_enabled'] = $quick_cart_link;
		$data['shopping_cart'] = $quick_cart_link ? $this->url->link('checkout/checkout', '', true) : $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['email'] = $this->config->get('config_email');
		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		if ($this->config->get('configblog_blog_menu')) {
			$data['blog_menu'] = $this->load->controller('blog/menu');
		} else {
			$data['blog_menu'] = '';
		}
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		$data['menu'] = $this->load->controller('common/menu');

		return $this->load->view('common/header', $data);
	}

	private function getCanonicalUrl(array $links) {
		foreach ($links as $link) {
			if (isset($link['rel'], $link['href']) && strtolower((string)$link['rel']) === 'canonical') {
				return (string)$link['href'];
			}
		}

		return '';
	}

	private function buildAlternateLinks($canonical_url, $secure) {
		if ($canonical_url === '' || stripos((string)$this->document->getRobots(), 'noindex') !== false) {
			return array();
		}

		$route = isset($this->request->get['route']) ? (string)$this->request->get['route'] : 'common/home';
		$supported = array(
			'common/home',
			'product/product',
			'product/category',
			'product/manufacturer',
			'product/manufacturer/info',
			'information/information',
			'information/contact',
			'information/sitemap',
			'blog/article',
			'blog/category',
			'blog/latest'
		);

		if (!in_array($route, $supported, true)) {
			return array();
		}

		$params = array();
		$route_keys = array(
			'product/product' => array('product_id'),
			'product/category' => array('path'),
			'product/manufacturer/info' => array('manufacturer_id'),
			'information/information' => array('information_id'),
			'blog/article' => array('article_id'),
			'blog/category' => array('blog_category_id')
		);

		if (isset($route_keys[$route])) {
			foreach ($route_keys[$route] as $key) {
				if (isset($this->request->get[$key])) {
					$params[$key] = $this->request->get[$key];
				}
			}
		}

		$canonical_parts = parse_url(str_replace('&amp;', '&', (string)$canonical_url));
		$canonical_query = array();
		if (is_array($canonical_parts) && !empty($canonical_parts['query'])) {
			parse_str($canonical_parts['query'], $canonical_query);
		}

		// Only canonicalized content-state parameters may be repeated in alternates.
		// Presentation parameters such as sort/order/limit never belong in hreflang.
		foreach (array('filter', 'page') as $key) {
			if (isset($canonical_query[$key]) && $canonical_query[$key] !== '' && !is_array($canonical_query[$key])) {
				if ($key !== 'page' || (int)$canonical_query[$key] > 1) {
					$params[$key] = $canonical_query[$key];
				}
			}
		}

		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		$active = array();
		foreach ($languages as $code => $language) {
			if (!empty($language['status'])) {
				$active[$code] = $language;
			}
		}

		if (count($active) < 2) {
			return array();
		}

		// Without SEO aliases or a language-directory router, language is stored
		// only in session/cookie and there are no distinct crawlable URLs. Emitting
		// identical hreflang URLs for every language would be incorrect.
		if (!$this->config->get('config_seo_url') && !$this->config->get('codecart_language_prefix_enabled') && !$this->config->get('langdir_status')) {
			return array();
		}

		$saved_language_id = (int)$this->config->get('config_language_id');
		$saved_prefix = (string)$this->config->get('codecart_language_prefix_current');
		$query = $params ? http_build_query($params, '', '&') : '';
		$alternates = array();

		foreach ($active as $code => $language) {
			$this->config->set('config_language_id', (int)$language['language_id']);
			$this->config->set('codecart_language_prefix_current', isset($language['url_prefix']) ? strtolower(trim((string)$language['url_prefix'])) : '');
			$href = html_entity_decode((string)$this->url->link($route, $query, $secure), ENT_QUOTES, 'UTF-8');
			if ($route === 'common/home' && $this->config->get('config_seo_pro') && !$this->config->get('config_seopro_addslash')) {
				$href = rtrim($href, '/');
			}

			$alternates[$code] = array(
				'hreflang' => str_replace('_', '-', strtolower((string)$code)),
				'href' => $href
			);
		}

		$this->config->set('config_language_id', $saved_language_id);
		$this->config->set('codecart_language_prefix_current', $saved_prefix);

		$default_code = (string)$this->config->get('config_language');
		if (isset($alternates[$default_code])) {
			$alternates['x-default'] = array('hreflang' => 'x-default', 'href' => $alternates[$default_code]['href']);
		} else {
			foreach ($active as $code => $language) {
				if (isset($alternates[$code]) && trim((string)$language['url_prefix']) === '') {
					$alternates['x-default'] = array('hreflang' => 'x-default', 'href' => $alternates[$code]['href']);
					break;
				}
			}
		}

		if (!isset($alternates['x-default']) && $alternates) {
			$first = reset($alternates);
			$alternates['x-default'] = array('hreflang' => 'x-default', 'href' => $first['href']);
		}

		return array_values($alternates);
	}


	private function isMinifiedStylesheetStale($source, $minified) {
		if (!is_file($minified)) {
			return true;
		}

		if (!is_file($source)) {
			return false;
		}

		$signature = md5($source . '|' . (int)@filemtime($source) . '|' . (int)@filesize($source) . '|' . (int)@filemtime($minified) . '|' . (int)@filesize($minified));
		$marker = DIR_CACHE . 'codecart.stylesheet.' . $signature;

		if (is_file($marker)) {
			return trim((string)@file_get_contents($marker)) === '1';
		}

		$stale = false;
		$head = (string)@file_get_contents($minified, false, null, 0, 256);

		if (preg_match('/source stylesheet\.css sha256:([a-f0-9]{64})/', $head, $match)) {
			$stale = !hash_equals($match[1], (string)hash_file('sha256', $source));
		} else {
			$stale = (int)@filemtime($source) > (int)@filemtime($minified);
		}

		foreach ((array)glob(DIR_CACHE . 'codecart.stylesheet.*') as $old) {
			if (is_file($old) && filemtime($old) < time() - 86400) {
				@unlink($old);
			}
		}

		@file_put_contents($marker, $stale ? '1' : '0', LOCK_EX);

		return $stale;
	}
}
