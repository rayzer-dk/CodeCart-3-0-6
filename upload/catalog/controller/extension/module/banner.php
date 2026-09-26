<?php
class ControllerExtensionModuleBanner extends Controller {
	public function index($setting) {
		$width = max(1, min(3840, (int)($setting['width'] ?? 300)));
		$height = max(1, min(3840, (int)($setting['height'] ?? 200)));
		$banner_id = max(0, (int)($setting['banner_id'] ?? 0));
		$data['image_width'] = $width;
		$data['image_height'] = $height;
		$effect = isset($setting['effect']) ? (string)$setting['effect'] : 'slide';
		$data['effect'] = in_array($effect, array('slide', 'fade', 'zoom'), true) ? $effect : 'slide';
		$data['autoplay'] = !isset($setting['autoplay']) || !empty($setting['autoplay']) ? 1 : 0;
		$data['autoplay_delay'] = max(1500, min(20000, (int)($setting['autoplay_delay'] ?? 4000)));
		$data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
		$data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
		$data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
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
		$compatibility = $this->registry->get('codecart_compatibility_framework');

		$results = $this->model_design_banner->getBanner($banner_id);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$bannerItem = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize($result['image'], $width, $height)
				);
				$bannerContract = $compatibility ? $compatibility->apply('catalog.banner.item', array('item' => $bannerItem, 'width' => $width, 'height' => $height, 'banner_id' => $banner_id)) : array('item' => $bannerItem);
				$data['banners'][] = isset($bannerContract['item']) && is_array($bannerContract['item']) ? $bannerContract['item'] : $bannerItem;
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

		return $this->load->view('extension/module/banner', $data);
	}
}
