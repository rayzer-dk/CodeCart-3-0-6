<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionModuleFeaturedArticle extends Controller {
    public function index($setting) {
        $this->load->language('extension/module/featured_article');
		$this->load->language('extension/module/codecart_slider');
		$language_id = (int)$this->config->get('config_language_id');
		$custom_heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
		$data['heading_title'] = $custom_heading !== '' ? $custom_heading : $this->language->get('heading_title');
		if (isset($setting['show_heading']) && !$setting['show_heading']) { $data['heading_title'] = ''; }
		$data['text_views'] = $this->language->get('text_views');
		$data['button_more'] = $this->language->get('button_more');
        $this->load->language('extension/module/codecart_slider');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
        $data['text_previous_items'] = $this->language->get('text_previous');
        $data['text_next_items'] = $this->language->get('text_next');
        $this->load->model('blog/article');
        $this->load->model('tool/image');

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
        $data['articles'] = array();
        $results = array();

        if (isset($this->request->get['product_id'])) {
            $productId = (int)$this->request->get['product_id'];
            $results = $this->model_blog_article->getArticleRelatedByProduct(array(
                'product_id' => $productId,
                'limit' => $limit
            ));
            $seen = array();
            foreach ($results as $row) { if (!empty($row['article_id'])) { $seen[(int)$row['article_id']] = true; } }
            $remaining = max(0, $limit - count($seen));
            if ($remaining > 0 && (bool)$this->config->get('codecart_relation_status') && (bool)$this->config->get('codecart_relation_storefront_status')) {
                $autoIds = (new \CodeCart\Core\RelationLayer($this->registry))->getAutoArticleIds($productId, $remaining);
                $autoIds = array_values(array_filter($autoIds, static function($id) use ($seen) { return !isset($seen[(int)$id]); }));
                if ($autoIds) {
                    foreach ($this->model_blog_article->getArticleCardsByIds($autoIds) as $autoArticle) {
                        $id = (int)$autoArticle['article_id'];
                        if (!isset($seen[$id])) { $results[] = $autoArticle; $seen[$id] = true; }
                        if (count($seen) >= $limit) { break; }
                    }
                }
            }
        } elseif (isset($this->request->get['manufacturer_id'])) {
            $results = $this->model_blog_article->getArticleRelatedByManufacturer(array(
                'manufacturer_id' => (int)$this->request->get['manufacturer_id'],
                'limit' => $limit
            ));
        } elseif (isset($this->request->get['path'])) {
            $parts = array_filter(array_map('intval', explode('_', (string)$this->request->get['path'])));
            if ($parts) {
                $results = $this->model_blog_article->getArticleRelatedByCategory(array(
                    'category_id' => (int)array_pop($parts),
                    'limit' => $limit
                ));
            }
        }

        if (!$results) { return ''; }
        $descriptionLength = (int)$this->config->get('configblog_article_description_length');
        if ($descriptionLength < 1) { $descriptionLength = 100; }
        $reviewStatus = (bool)$this->config->get('configblog_review_status');
        $data['configblog_review_status'] = $reviewStatus;

        foreach ($results as $result) {
            if (!$result || empty($result['article_id'])) { continue; }
            $image = !empty($result['image'])
                ? $this->model_tool_image->resize($result['image'], $width, $height)
                : $this->model_tool_image->resize('no_image.webp', $width, $height);
            $data['articles'][] = array(
                'article_id' => (int)$result['article_id'],
                'thumb' => $image,
                'name' => $result['name'],
                'description' => \CodeCart\Core\CardText::excerpt($result['description'], $descriptionLength),
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'viewed' => (int)$result['viewed'],
                'reviews' => sprintf($this->language->get('text_reviews'), (int)$result['reviews']),
                'rating' => $reviewStatus ? $result['rating'] : false,
                'href' => $this->url->link('blog/article', 'article_id=' . (int)$result['article_id'])
            );
        }

        if (!$data['articles']) {
            return '';
        }

        $this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
        $this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');

        return $this->load->view('extension/module/featured_article', $data);
    }
}
