<?php
class ControllerExtensionModuleSlideshow extends Controller {
	public function index($setting) {
		$width = max(1, min(3840, (int)($setting['width'] ?? 1140)));
		$height = max(1, min(3840, (int)($setting['height'] ?? 380)));
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

		$results = $this->model_design_banner->getBanner($banner_id);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$main_width = $width;
				$main_height = $height;
				$source_size = @getimagesize(DIR_IMAGE . $result['image']);
				$source_width = is_array($source_size) ? max(1, (int)$source_size[0]) : $main_width;
				$effective_main_width = min($main_width, $source_width);
				$srcset = array();
				foreach (array(480, 640, 720, 960) as $candidate_width) {
					if ($candidate_width < $effective_main_width) {
						$candidate_height = max(1, (int)round($main_height * $candidate_width / $main_width));
						$srcset[] = $this->model_tool_image->display($result['image'], $candidate_width, $candidate_height) . ' ' . $candidate_width . 'w';
					}
				}
				$main_image = $this->model_tool_image->display($result['image'], $main_width, $main_height);
				$srcset[] = $main_image . ' ' . $effective_main_width . 'w';
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $main_image,
					'srcset' => implode(', ', array_values(array_unique($srcset)))
				);
			}
		}

		if (!$data['banners']) {
			return '';
		}

		// Load slider assets only when this module actually renders content.
		$this->document->addStyle('catalog/view/javascript/codecart/slider/codecart-slider.css?v=3.0.6.0-3');
		$this->document->addScript('catalog/view/javascript/codecart/slider/codecart-slider.js?v=3.0.6.0-3', 'footer');

		// Compatibility bridge for third-party OpenCart 3 themes that override these templates.
		if (!in_array((string)$this->config->get('config_theme'), array('default', 'codecart'), true)) {
			$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
			$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
			$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.min.js');
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/slideshow', $data);
	}
}