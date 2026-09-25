<?php
class ControllerExtensionModuleCarousel extends Controller {
	public function index($setting) {
		$width = max(1, min(2000, (int)($setting['width'] ?? 130)));
		$height = max(1, min(2000, (int)($setting['height'] ?? 100)));
		$banner_id = max(0, (int)($setting['banner_id'] ?? 0));
		$data['image_width'] = $width;
		$data['image_height'] = $height;
		$data['columns_desktop'] = max(1, min(8, (int)($setting['columns_desktop'] ?? 6)));
		$data['columns_tablet'] = max(1, min(6, (int)($setting['columns_tablet'] ?? 4)));
		$data['columns_mobile'] = max(1, min(3, (int)($setting['columns_mobile'] ?? 3)));
		$data['gap'] = max(0, min(60, (int)($setting['gap'] ?? 15)));
		$data['autoplay'] = !isset($setting['autoplay']) || !empty($setting['autoplay']) ? 1 : 0;
		$data['autoplay_delay'] = max(1500, min(20000, (int)($setting['autoplay_delay'] ?? 4000)));
		$data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
		$data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
		$data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
		$data['carousel_step'] = (isset($setting['carousel_step']) && $setting['carousel_step'] === 'page') ? 'page' : 'item';
		static $module = 0;

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		
		$this->load->language('extension/module/codecart_slider');
		$data['text_previous'] = $this->language->get('text_previous');
		$data['text_next'] = $this->language->get('text_next');
		$data['text_slide'] = $this->language->get('text_slide');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');


		$data['banners'] = array();

		$results = $this->model_design_banner->getBanner($banner_id);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize($result['image'], $width, $height)
				);
			}
		}

		if (!$data['banners']) {
			return '';
		}

		// Load slider assets only when this module actually renders content.
		$this->document->addStyle('catalog/view/javascript/codecart/slider/codecart-slider.css?v=3.0.6.0-2');
		$this->document->addScript('catalog/view/javascript/codecart/slider/codecart-slider.js?v=3.0.6.0-2', 'footer');

		// Compatibility bridge for third-party OpenCart 3 themes that override these templates.
		if (!in_array((string)$this->config->get('config_theme'), array('default', 'codecart'), true)) {
			$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
			$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
			$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.min.js');
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/carousel', $data);
	}
}