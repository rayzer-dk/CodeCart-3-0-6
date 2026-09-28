<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionModuleBlogLatest extends Controller {
	public function index($setting) {
		$limit = max(1, min(30, (int)($setting['limit'] ?? 4)));
		$width = max(40, min(2000, (int)($setting['width'] ?? 200)));
		$height = max(40, min(2000, (int)($setting['height'] ?? 200)));
		$data['image_width'] = $width;
		$data['image_height'] = $height;

		$data['display_mode'] = (isset($setting['display_mode']) && $setting['display_mode'] === 'grid') ? 'grid' : 'carousel';
		$data['columns_desktop'] = max(1, min(6, (int)($setting['columns_desktop'] ?? 4)));
		$data['columns_tablet'] = max(1, min(4, (int)($setting['columns_tablet'] ?? 3)));
		$data['columns_mobile'] = max(1, min(2, (int)($setting['columns_mobile'] ?? 2)));
		$data['autoplay'] = !empty($setting['autoplay']) ? 1 : 0;
		$data['autoplay_delay'] = max(1500, min(20000, (int)($setting['autoplay_delay'] ?? 5000)));
		$data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
		$data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
		$data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
		$data['carousel_step'] = (isset($setting['carousel_step']) && $setting['carousel_step'] === 'page') ? 'page' : 'item';
		$this->load->language('extension/module/blog_latest');
		$this->load->language('extension/module/codecart_slider');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
		$language_id = (int)$this->config->get('config_language_id');
		$custom_heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
		$data['heading_title'] = $custom_heading !== '' ? $custom_heading : $this->language->get('heading_title');
		if (isset($setting['show_heading']) && !$setting['show_heading']) { $data['heading_title'] = ''; }
		$data['text_views'] = $this->language->get('text_views');
		$data['button_more'] = $this->language->get('button_more');
		$this->load->language('extension/module/codecart_slider');
		$data['text_previous_items'] = $this->language->get('text_previous');
		$data['text_next_items'] = $this->language->get('text_next');

		$this->load->model('blog/article');

		$this->load->model('tool/image');

		$data['articles'] = array();

		$limit = max(1, min(30, (int)($setting['limit'] ?? 4)));
		$results = $this->model_blog_article->getLatestArticleCards($limit);

		if ($results) {
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $width, $height);
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $width, $height);
				}

				if ($this->config->get('configblog_review_status')) {
					$rating = $result['rating'];
				} else {
					$rating = false;
				}

				$data['articles'][] = array(
					'article_id'  => $result['article_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('configblog_article_description_length')),
					'rating'      => $rating,
					'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'viewed'      => $result['viewed'],
					'href'        => $this->url->link('blog/article', 'article_id=' . $result['article_id'])
				);
			}

			$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
		$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
		return $this->load->view('extension/module/blog_latest', $data);
		}
	}
}
