<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerBlogArticle extends Controller {
	private $error = array(); 
	
	public function index() { 
		$this->load->language('blog/article');
	
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),			
			'separator' => false
		);
		
		$configblog_name = $this->config->get('configblog_name');
		
		if (!empty($configblog_name)) {
			$name = $this->config->get('configblog_name');
		} else {
			$name = $this->language->get('text_blog');
		}
		
		$data['breadcrumbs'][] = array(
			'text' => $name,
			'href' => $this->url->link('blog/latest')
		);
		
		$this->load->model('blog/category');	
		
		
		if (isset($this->request->get['blog_category_id'])) {
			$blog_category_id = '';
				
			foreach (explode('_', $this->request->get['blog_category_id']) as $path_id) {
				if (!$blog_category_id) {
					$blog_category_id = $path_id;
				} else {
					$blog_category_id .= '_' . $path_id;
				}
				
				$category_info = $this->model_blog_category->getCategory($path_id);
				
				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text'      => $category_info['name'],
						'href'      => $this->url->link('blog/category', 'blog_category_id=' . $blog_category_id)
					);
				}
			}
		}
		
	

	

	if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_tag'])) {
			$url = '';
			
			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . $this->request->get['filter_name'];
			}
						
			if (isset($this->request->get['filter_tag'])) {
				$url .= '&filter_tag=' . $this->request->get['filter_tag'];
			}
						
			if (isset($this->request->get['filter_description'])) {
				$url .= '&filter_description=' . $this->request->get['filter_description'];
			}
			
			if (isset($this->request->get['filter_news_id'])) {
				$url .= '&filter_news_id=' . $this->request->get['filter_news_id'];
			}	
						
		}
		
		$article_id = isset($this->request->get['article_id']) ? (int)$this->request->get['article_id'] : 0;
		if ($article_id < 1) {
			$article_id = 0;
		}
		
		$this->load->model('blog/article');
		
		$article_info = $this->model_blog_article->getArticle($article_id);
		
		if ($article_info) {
			$url = '';
			
			if (isset($this->request->get['blog_category_id'])) {
				$url .= '&blog_category_id=' . $this->request->get['blog_category_id'];
			}	

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . $this->request->get['filter_name'];
			}
						
			if (isset($this->request->get['filter_tag'])) {
				$url .= '&filter_tag=' . $this->request->get['filter_tag'];
			}
			
			if (isset($this->request->get['filter_description'])) {
				$url .= '&filter_description=' . $this->request->get['filter_description'];
			}	
						
			if (isset($this->request->get['filter_news_id'])) {
				$url .= '&filter_news_id=' . $this->request->get['filter_news_id'];
			}
			
			$data['breadcrumbs'][] = array(
				'text' => $article_info['name'],
				'href' => $this->url->link('blog/article', 'article_id=' . $article_id)
			);
			
			if ($article_info['meta_title']) {
				$this->document->setTitle($article_info['meta_title']);
			} else {
				$this->document->setTitle($article_info['name']);
			}
			
			// ocStore compatibility: historical `noindex` field is inverted: 1 = indexing allowed, 0 = noindex.
			if ($article_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
				$this->document->setRobots('noindex,follow');
			}

			$this->document->setDescription($article_info['meta_description']);
			$this->document->setKeywords($article_info['meta_keyword']);
			$this->document->addLink($this->url->link('blog/article', 'article_id=' . $article_id), 'canonical');
			$this->document->setOgType('article');
			if (!empty($article_info['image']) && is_file(DIR_IMAGE . $article_info['image'])) {
				$is_https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
				$og_base = $is_https ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');
				$this->document->setOgImage(rtrim($og_base, '/') . '/image/' . ltrim(str_replace('\\', '/', (string)$article_info['image']), '/'));
			}
			$gallery_engine = in_array((string)$this->config->get('config_theme'), array('default', 'codecart'), true) ? (string)$this->config->get('theme_default_gallery_engine') : 'magnific';
			if (!in_array($gallery_engine, array('photoswipe', 'magnific'), true)) {
				$gallery_engine = 'photoswipe';
			}
			$data['gallery_engine'] = $gallery_engine;
			if ($gallery_engine === 'photoswipe') {
				// Load PhotoSwipe in deterministic dependency order. The previous lazy
				// first-click loader could race when a visitor opened image #2/#3 first.
				$this->document->addStyle('catalog/view/javascript/photoswipe/photoswipe.css?v=' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0'), 'stylesheet', 'screen', 'footer');
				$this->document->addScript('catalog/view/javascript/photoswipe/photoswipe.umd.min.js?v=' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0'), 'footer');
				$this->document->addScript('catalog/view/javascript/photoswipe/photoswipe-lightbox.umd.min.js?v=' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0'), 'footer');
			} else {
				$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
				$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');
			}

			if ($article_info['meta_h1']) {	
				$data['heading_title'] = $article_info['meta_h1'];
				} else {
				$data['heading_title'] = $article_info['name'];
				}
			
			$data['text_select'] = $this->language->get('text_select');
			$data['text_write'] = $this->language->get('text_write');
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
			$data['text_loading'] = $this->language->get('text_loading');
			$data['text_note'] = $this->language->get('text_note');
			$data['text_share'] = $this->language->get('text_share');
			$data['text_copy_link'] = $this->language->get('text_copy_link');
			$data['text_link_copied'] = $this->language->get('text_link_copied');
			$data['share_url'] = $this->url->link('blog/article', 'article_id=' . $article_id, true);
			$data['share_url_encoded'] = rawurlencode($data['share_url']);
			$data['share_title_encoded'] = rawurlencode((string)$data['heading_title']);
			$data['text_wait'] = $this->language->get('text_wait');
			$data['button_cart'] = $this->language->get('button_cart');
			$data['button_wishlist'] = $this->language->get('button_wishlist');
			$data['button_compare'] = $this->language->get('button_compare');
			$data['entry_name'] = $this->language->get('entry_name');
			$data['entry_review'] = $this->language->get('entry_review');
			$data['entry_rating'] = $this->language->get('entry_rating');
			$data['entry_good'] = $this->language->get('entry_good');
			$data['entry_bad'] = $this->language->get('entry_bad');
			$data['entry_captcha'] = $this->language->get('entry_captcha');
			
			$data['button_continue'] = $this->language->get('button_continue');
			
			$this->load->model('blog/review');

			$data['text_related'] = $this->language->get('text_related');
			$data['text_related_product'] = $this->language->get('text_related_product');
			
			$data['article_id'] = $article_id;
			
			$data['review_status'] = $this->config->get('configblog_review_status');
			
			if ($this->config->get('configblog_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}
			
			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}
			
			$data['article_review'] = (int)$article_info['article_review'];
			$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$article_info['reviews']);
			$data['rating'] = (int)$article_info['rating'];
			$data['gstatus'] = (int)$article_info['gstatus'];
			$data['description'] = html_entity_decode($article_info['description'], ENT_QUOTES, 'UTF-8');
			$data['description'] = $this->load->controller('common/codecart_form/shortcodes', array('html'=>$data['description'],'context'=>array('context_type'=>'article','context_id'=>(int)$article_id,'context_url'=>$this->url->link('blog/article','article_id=' . (int)$article_id,true))));


			$data['thumb'] = false;
			$data['popup'] = false;
			$data['images'] = array();

			$this->load->model('tool/image');
			$article_image_width = max(760, (int)$this->config->get('configblog_image_article_width'));
			$article_image_height = max(560, (int)$this->config->get('configblog_image_article_height'));
			$image_base = function_exists('codecart_is_https') && codecart_is_https() ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');

			$data['popup_width'] = $article_image_width;
			$data['popup_height'] = $article_image_height;
			$primary_image = !empty($article_info['image']) ? $this->model_tool_image->resolveFilename($article_info['image']) : '';
			if ($primary_image !== '' && is_file(DIR_IMAGE . $primary_image)) {
				$data['thumb'] = $this->model_tool_image->display($primary_image, $article_image_width, $article_image_height);
				$data['popup'] = rtrim($image_base, '/') . '/image/' . str_replace('%2F', '/', rawurlencode(str_replace('\\', '/', (string)$primary_image)));
				$primary_size = @getimagesize(DIR_IMAGE . $primary_image);
				if (is_array($primary_size)) {
					$data['popup_width'] = max(1, (int)$primary_size[0]);
					$data['popup_height'] = max(1, (int)$primary_size[1]);
				}
			}

			foreach ($this->model_blog_article->getArticleImages($article_id) as $article_image) {
				$resolved_article_image = !empty($article_image['image']) ? $this->model_tool_image->resolveFilename($article_image['image']) : '';
				if ($resolved_article_image !== '' && is_file(DIR_IMAGE . $resolved_article_image)) {
					$image_size = @getimagesize(DIR_IMAGE . $resolved_article_image);
					$data['images'][] = array(
						'thumb' => $this->model_tool_image->display($resolved_article_image, 160, 160),
						'popup' => rtrim($image_base, '/') . '/image/' . str_replace('%2F', '/', rawurlencode(str_replace('\\', '/', (string)$resolved_article_image))),
						'width' => is_array($image_size) ? max(1, (int)$image_size[0]) : 1600,
						'height' => is_array($image_size) ? max(1, (int)$image_size[1]) : 1200
					);
				}
			}
			
			$data['articles'] = array();
			
			$data['button_more'] = $this->language->get('button_more');
			$data['text_views'] = $this->language->get('text_views');
			
			$this->load->model('tool/image');
			
			$results = $this->model_blog_article->getArticleRelated($article_id);
			
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('configblog_image_related_width'), $this->config->get('configblog_image_related_height'));
				} else {
					$image = false;
				}
				
				if ($this->config->get('configblog_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}
							
				$data['articles'][] = array(
					'article_id' => $result['article_id'],
					'thumb'   	 => $image,
					'name'    	 => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('configblog_article_description_length')),
					'rating'     => $rating,
					'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'viewed'      => $result['viewed'],
					'reviews'    => sprintf($this->language->get('text_reviews'), (int)$result['reviews']),
					'href'    	 => $this->url->link('blog/article', 'article_id=' . $result['article_id']),
				);
			}

			$this->load->model('tool/image');
			$this->load->model('catalog/product');
			$data['products'] = array();
			
			$results = $this->model_blog_article->getArticleRelatedProduct($article_id);
			$blog_card_attributes = array();
			$card_mode = (string)$this->config->get('theme_default_product_card_content');
			$data['card_content'] = in_array($card_mode, array('none','description','attributes','both'), true) ? $card_mode : 'attributes';
			$card_attribute_limit = max(1, min(10, (int)$this->config->get('theme_default_product_card_attribute_limit')));
			if (!$this->config->get('theme_default_product_card_attribute_limit')) { $card_attribute_limit = 3; }
			if (in_array($data['card_content'], array('attributes','both'), true) && $results) {
				$blog_product_ids = array();
				foreach ($results as $blog_product) { $blog_product_ids[] = (int)$blog_product['product_id']; }
				$blog_card_attributes = $this->model_catalog_product->getProductCardAttributes($blog_product_ids, $card_attribute_limit);
			}
			
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('configblog_image_related_width'), $this->config->get('configblog_image_related_height'));
				} else {
					$image = false;
				}
				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}
				
				if ($this->config->get('configblog_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}
				
				$data['text_tax'] = \CodeCart\Core\TaxDisplay::label($this->config, $this->language);
							
				$data['products'][] = array(
					'product_id' => $result['product_id'],
					'attributes' => isset($blog_card_attributes[(int)$result['product_id']]) ? $blog_card_attributes[(int)$result['product_id']] : array(),
					'can_buy' => isset($result['quantity']) ? ((int)$result['quantity'] > 0) : true,
					'stock_status' => isset($result['stock_status']) ? (string)$result['stock_status'] : '',
					'thumb'   	 => $image,
					'name'    	 => $result['name'],
					'description' => \CodeCart\Core\CardText::excerpt($result['description'], $this->config->get('configblog_article_description_length')),
					'price'   	 => $price,
					'special' 	 => $special,
					'rating'     => $rating,
					'tax'        => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'reviews'    => sprintf($this->language->get('text_reviews'), (int)$result['reviews']),
					'href'    	 => $this->url->link('product/product', 'product_id=' . $result['product_id']),
				);
			}	
			

			if ($data['products'] || $data['articles']) {
				$this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
				$this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
			}
			$data['text_previous_related'] = $this->language->get('text_previous_related');
			$data['text_next_related'] = $this->language->get('text_next_related');

			$data['date_added'] = !empty($article_info['date_added']) ? date($this->language->get('date_format_short'), strtotime($article_info['date_added'])) : '';

			$data['download_status'] = $this->config->get('configblog_article_download');
			
			$data['downloads'] = array();
			
			$results = $this->model_blog_article->getDownloads($article_id);
 
            foreach ($results as $result) {
                $download_file = $this->resolveDownloadFile(isset($result['filename']) ? $result['filename'] : '');
                if ($download_file !== false) {
                    $size = (float)filesize($download_file);
                    $i = 0;
                    $suffix = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

                    while ($size >= 1024 && $i < count($suffix) - 1) {
                        $size /= 1024;
                        $i++;
                    }

                    $data['downloads'][] = array(
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'name'       => $result['name'],
                        'size'       => round($size, 2) . ' ' . $suffix[$i],
                        'href'       => $this->url->link('blog/article/download', 'article_id=' . $article_id . '&download_id=' . (int)$result['download_id'])
                    );
                }
            }
			

			if ($this->config->get('config_codecart_structured_data_status')) {
				$schema_url = html_entity_decode($this->url->link('blog/article', 'article_id=' . (int)$article_id), ENT_QUOTES, 'UTF-8');
				$modified_ts = strtotime((string)$article_info['date_modified']);
				$published_ts = strtotime((string)$article_info['date_added']);
				if (!$modified_ts || $modified_ts < 315532800) { $modified_ts = $published_ts; }
				$article_schema = array(
					'@type' => 'BlogPosting',
					'@id' => $schema_url . '#article',
					'mainEntityOfPage' => $schema_url,
					'headline' => (string)$article_info['name'],
					'description' => trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$article_info['description'], ENT_QUOTES, 'UTF-8')))),
					'datePublished' => date(DATE_ATOM, $published_ts),
					'dateModified' => date(DATE_ATOM, $modified_ts),
					'author' => array('@type' => 'Organization', 'name' => (string)$this->config->get('config_name'))
				);

				$is_https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
				$schema_base = $is_https ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');
				$article_schema['publisher'] = array('@id' => rtrim($schema_base, '/') . '/#organization');

				if (!empty($article_info['image']) && is_file(DIR_IMAGE . $article_info['image'])) {
					$article_schema['image'] = rtrim($schema_base, '/') . '/image/' . ltrim(str_replace('\\', '/', (string)$article_info['image']), '/');
				}

				$this->document->addStructuredData($article_schema, 'blog_article');

				$schema_breadcrumbs = array();
				$schema_position = 1;
				foreach ($data['breadcrumbs'] as $breadcrumb) {
					if (!empty($breadcrumb['href'])) {
						$schema_breadcrumbs[] = array('@type' => 'ListItem', 'position' => $schema_position++, 'name' => trim(strip_tags(html_entity_decode((string)$breadcrumb['text'], ENT_QUOTES, 'UTF-8'))), 'item' => html_entity_decode((string)$breadcrumb['href'], ENT_QUOTES, 'UTF-8'));
					}
				}
				if ($schema_breadcrumbs) {
					$this->document->addStructuredData(array('@type' => 'BreadcrumbList', 'itemListElement' => $schema_breadcrumbs), 'breadcrumbs');
				}
			}

			$this->load->language('common/internal_links');
			$data['text_internal_links'] = $this->language->get('text_internal_links');
			$data['internal_links'] = (new \CodeCart\Core\InternalLinking($this->registry))->article($article_id, 8);

			$this->model_blog_article->updateViewed($article_id);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			
			$this->response->setOutput($this->load->view('blog/article', $data));
		} else {
			$url = '';
			
			if (isset($this->request->get['blog_category_id'])) {
				$url .= '&blog_category_id=' . $this->request->get['blog_category_id'];
			}		

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . $this->request->get['filter_name'];
			}	
					
			if (isset($this->request->get['filter_tag'])) {
				$url .= '&filter_tag=' . $this->request->get['filter_tag'];
			}
							
			if (isset($this->request->get['filter_description'])) {
				$url .= '&filter_description=' . $this->request->get['filter_description'];
			}
					
			if (isset($this->request->get['filter_news_id'])) {
				$url .= '&filter_news_id=' . $this->request->get['filter_news_id'];
			}
								
				$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('blog/article', 'article_id=' . $article_id . $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('common/home');

			$this->response->setStatusCode(404);
            $this->load->language('error/not_found');
            $this->document->setRobots('noindex,follow');
            $this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css');
            $data['heading_title'] = $this->language->get('heading_title');
            $data['text_error'] = $this->language->get('text_error');
            $data['search'] = $this->url->link('product/search');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
    	}
  	}
	
	public function download() {
		$this->load->model('blog/article');

		$download_id = isset($this->request->get['download_id']) ? (int)$this->request->get['download_id'] : 0;
		$article_id = isset($this->request->get['article_id']) ? (int)$this->request->get['article_id'] : 0;

		if ($download_id < 1 || $article_id < 1) {
			$this->response->redirect($this->url->link('blog/latest'));
			return;
		}

		$download_info = $this->model_blog_article->getDownload($article_id, $download_id);
		if (!$download_info) {
			$this->response->redirect($this->url->link('blog/article', 'article_id=' . $article_id));
			return;
		}

		$file = $this->resolveDownloadFile(isset($download_info['filename']) ? $download_info['filename'] : '');
		if ($file === false) {
			if ($this->log) {
				$this->log->write('Blog download unavailable: article_id=' . $article_id . ', download_id=' . $download_id);
			}
			$this->response->redirect($this->url->link('blog/article', 'article_id=' . $article_id));
			return;
		}

		if (headers_sent()) {
			if ($this->log) {
				$this->log->write('Blog download aborted because response headers were already sent: article_id=' . $article_id . ', download_id=' . $download_id);
			}
			return;
		}

		$mask = isset($download_info['mask']) ? basename(str_replace('\\', '/', (string)$download_info['mask'])) : '';
		$filename = $mask !== '' ? $mask : basename($file);
		$filename = preg_replace('/[\\r\\n\"]+/', '', $filename);
		if ($filename === '') {
			$filename = 'download';
		}

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));

		readfile($file);
		exit;
	}

	public function review() {
    	$this->language->load('blog/article');
		
		$this->load->model('blog/review');

		$data['text_on'] = $this->language->get('text_on');
		$data['text_no_reviews'] = $this->language->get('text_no_reviews');

		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		if ($page < 1) {
			$page = 1;
		}

		$article_id = isset($this->request->get['article_id']) ? (int)$this->request->get['article_id'] : 0;
		if ($article_id < 1) {
			$article_id = 0;
		}
		
		$data['reviews'] = array();
		
		$review_total = $this->model_blog_review->getTotalReviewsByArticleId($article_id);
			
		$results = $this->model_blog_review->getReviewsByArticleId($article_id, ($page - 1) * 5, 5);
      		
		foreach ($results as $result) {
        	$data['reviews'][] = array(
        		'author'     => $result['author'],
				'text'       => $result['text'],
				'rating'     => (int)$result['rating'],
        		'reviews'    => sprintf($this->language->get('text_reviews'), (int)$review_total),
        		'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
        	);
      	}
		
		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('blog/article/review', 'article_id=' . $article_id . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		$this->response->setOutput($this->load->view('blog/review', $data));
		
	}
	
	private function resolveDownloadFile($filename) {
		$filename = (string)$filename;
		if ($filename === '' || strpos($filename, "\0") !== false) {
			return false;
		}

		$filename = ltrim(str_replace('\\', '/', $filename), '/');
		if ($filename === '') {
			return false;
		}

		$base = realpath(DIR_DOWNLOAD);
		$file = realpath(rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $filename);
		if ($base === false || $file === false || !is_file($file)) {
			return false;
		}

		$base = rtrim(str_replace('\\', '/', $base), '/') . '/';
		$file = str_replace('\\', '/', $file);
		if (strncmp($file, $base, strlen($base)) !== 0) {
			return false;
		}

		return $file;
	}

	public function write() {
		$this->load->language('blog/article');
		$json = array();
		$article_id = isset($this->request->get['article_id']) ? (int)$this->request->get['article_id'] : 0;

		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$article_id) {
			$json['error'] = $this->language->get('text_error');
		} else {
			$guard = new \CodeCart\Core\FormGuard($this->registry);
			$rate = $guard->consume('blog_review', 40, 600, 2);
			if (!$rate['allowed']) {
				$json['error'] = $this->language->get('error_rate_limit');
				$this->response->setStatusCode(429);
				$this->response->addHeader('Retry-After: ' . (int)$rate['retry_after']);
			}

			$this->load->model('blog/article');
			if (!isset($json['error']) && !$this->model_blog_article->getArticle($article_id)) {
				$json['error'] = $this->language->get('text_error');
			}

			$name = isset($this->request->post['name']) ? (string)$this->request->post['name'] : '';
			$text = isset($this->request->post['text']) ? (string)$this->request->post['text'] : '';
			$rating = isset($this->request->post['rating']) ? (int)$this->request->post['rating'] : 0;

			if ((utf8_strlen($name) < 3) || (utf8_strlen($name) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}
			if ((utf8_strlen($text) < 25) || (utf8_strlen($text) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}
			if ($rating < 1 || $rating > 5) {
				$json['error'] = $this->language->get('error_rating');
			}

			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');
				if ($captcha) {
					$json['error'] = $captcha;
					$json['captcha_error'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('blog/review');
				$this->model_blog_review->addReview($article_id, $this->request->post);
				if ((string)$this->config->get('config_captcha') === 'basic') {
					$this->load->controller('extension/captcha/basic/consume');
					$json['captcha_reset'] = true;
				}
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($json));
	}

	
}
