<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerInformationInformation extends Controller {
	public function index() {
		$this->load->language('information/information');

		$this->load->model('catalog/information');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			
			if ($information_info['meta_title']) {
				$this->document->setTitle($information_info['meta_title']);
			} else {
				$this->document->setTitle($information_info['title']);
			}
			
			// ocStore compatibility: historical `noindex` field is inverted: 1 = indexing allowed, 0 = noindex.
			if ($information_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
				$this->document->setRobots('noindex,follow');
			}
			
			if ($information_info['meta_h1']) {
				$data['heading_title'] = $information_info['meta_h1'];
			} else {
				$data['heading_title'] = $information_info['title'];
			}
			
			$this->document->setDescription($information_info['meta_description']);
			$this->document->setKeywords($information_info['meta_keyword']);
			$this->document->addLink($this->url->link('information/information', 'information_id=' . $information_id), 'canonical');

			$data['breadcrumbs'][] = array(
				'text' => $information_info['title'],
				'href' => $this->url->link('information/information', 'information_id=' .  $information_id)
			);

			if ($this->config->get('config_codecart_structured_data_status') && stripos((string)$this->document->getRobots(), 'noindex') === false) {
				$schema_url = $this->url->link('information/information', 'information_id=' . $information_id);
				$is_https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
				$schema_base = $is_https ? $this->config->get('config_ssl') : $this->config->get('config_url');
				$this->document->addStructuredData(array(
					'@type' => 'WebPage',
					'@id' => $schema_url . '#webpage',
					'url' => $schema_url,
					'name' => (string)$information_info['title'],
					'description' => trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$information_info['description'], ENT_QUOTES, 'UTF-8')))),
					'isPartOf' => array('@id' => rtrim((string)$schema_base, '/') . '/#website')
				), 'information');

				$schema_breadcrumbs = array();
				$schema_position = 1;
				foreach ($data['breadcrumbs'] as $breadcrumb) {
					if (!empty($breadcrumb['href'])) {
						$schema_breadcrumbs[] = array('@type' => 'ListItem', 'position' => $schema_position++, 'name' => trim(strip_tags(html_entity_decode((string)$breadcrumb['text'], ENT_QUOTES, 'UTF-8'))), 'item' => (string)$breadcrumb['href']);
					}
				}
				if ($schema_breadcrumbs) {
					$this->document->addStructuredData(array('@type' => 'BreadcrumbList', 'itemListElement' => $schema_breadcrumbs), 'breadcrumbs');
				}
			}

			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
			$data['description'] = $this->load->controller('common/codecart_form/shortcodes', array('html'=>$data['description'],'context'=>array('context_type'=>'information','context_id'=>(int)$information_id,'context_url'=>$this->url->link('information/information','information_id=' . (int)$information_id,true))));

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('information/information', $data));
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('information/information', 'information_id=' . $information_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['continue'] = $this->url->link('common/home');

			$this->load->language('error/not_found');
			$this->document->setRobots('noindex,follow');
			$this->document->addStyle('catalog/view/theme/codecart/stylesheet/error-page.css');
			$data['search'] = $this->url->link('product/search');
			$data['heading_title'] = $this->language->get('heading_title');
			$data['text_error'] = $this->language->get('text_error');
			$this->response->setStatusCode(404);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function agree() {
		$this->load->model('catalog/information');

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$output = '';

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$output .= html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8') . "\n";
		}

		$this->response->addHeader('X-Robots-Tag: noindex');

		$this->response->setOutput($output);
	}
}
