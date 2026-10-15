<?php
class ControllerCatalogDownload extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/download');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/download');

		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/download');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/download');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_download->addDownload($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/download');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/download');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_download->editDownload($this->request->get['download_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/download');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/download');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $download_id) {
				$this->model_catalog_download->deleteDownload($download_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'dd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('catalog/download/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/download/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['downloads'] = array();
        $data['public_documents'] = $this->url->link('catalog/public_document','user_token='.$this->session->data['user_token'],true);
        $data['column_download_count']=$this->language->get('column_download_count');
        $data['column_download_30d']=$this->language->get('column_download_30d');
        $data['column_last_download']=$this->language->get('column_last_download');
        $data['text_public_documents']=$this->language->get('text_public_documents');

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$download_total = $this->model_catalog_download->getTotalDownloads();

		$results = $this->model_catalog_download->getDownloads($filter_data);

		foreach ($results as $result) {
			$data['downloads'][] = array(
				'download_id' => $result['download_id'],
				'name'        => $result['name'],
                'stats_count' => (int)$result['stats_download_count'],
                'stats_30d' => (int)$result['stats_30d'],
                'stats_last' => !empty($result['stats_last_download']) ? $result['stats_last_download'] : '—',
				'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'edit'        => $this->url->link('catalog/download/edit', 'user_token=' . $this->session->data['user_token'] . '&download_id=' . $result['download_id'] . $url, true)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . '&sort=dd.name' . $url, true);
		$data['sort_date_added'] = $this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . '&sort=d.date_added' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $download_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($download_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($download_total - $this->config->get('config_limit_admin'))) ? $download_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $download_total, ceil($download_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/download_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['download_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['filename'])) {
			$data['error_filename'] = $this->error['filename'];
		} else {
			$data['error_filename'] = '';
		}

		if (isset($this->error['mask'])) {
			$data['error_mask'] = $this->error['mask'];
		} else {
			$data['error_mask'] = '';
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['download_id'])) {
			$data['action'] = $this->url->link('catalog/download/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/download/edit', 'user_token=' . $this->session->data['user_token'] . '&download_id=' . $this->request->get['download_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/download', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->get['download_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$download_info = $this->model_catalog_download->getDownload($this->request->get['download_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['download_id'])) {
			$data['download_id'] = (int)$this->request->get['download_id'];
		} else {
			$data['download_id'] = 0;
		}

		if (isset($this->request->post['download_description'])) {
			$data['download_description'] = $this->request->post['download_description'];
		} elseif (isset($this->request->get['download_id'])) {
			$data['download_description'] = $this->model_catalog_download->getDownloadDescriptions($this->request->get['download_id']);
		} else {
			$data['download_description'] = array();
		}

		if (isset($this->request->post['filename'])) {
			$data['filename'] = $this->request->post['filename'];
		} elseif (!empty($download_info)) {
			$data['filename'] = $download_info['filename'];
		} else {
			$data['filename'] = '';
		}

		if (isset($this->request->post['mask'])) {
			$data['mask'] = $this->request->post['mask'];
		} elseif (!empty($download_info)) {
			$data['mask'] = $download_info['mask'];
		} else {
			$data['mask'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/download_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/download')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['download_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 3) || (utf8_strlen($value['name']) > 64)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		$filename = isset($this->request->post['filename']) ? (string)$this->request->post['filename'] : '';
		if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 128) || strpos($filename, "\0") !== false || basename($filename) !== $filename) {
			$this->error['filename'] = $this->language->get('error_filename');
		} else {
			$base = realpath(DIR_DOWNLOAD);
			$file = realpath(rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $filename);
			$prefix = $base !== false ? rtrim($base, '/\\') . DIRECTORY_SEPARATOR : '';
			if ($base === false || $file === false || strpos($file, $prefix) !== 0 || !is_file($file)) {
				$this->error['filename'] = $this->language->get('error_exists');
			}
		}

		if ((utf8_strlen($this->request->post['mask']) < 3) || (utf8_strlen($this->request->post['mask']) > 128)) {
			$this->error['mask'] = $this->language->get('error_mask');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/download')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('catalog/product');

		foreach ($this->request->post['selected'] as $download_id) {
			$product_total = $this->model_catalog_product->getTotalProductsByDownloadId($download_id);

			if ($product_total) {
				$this->error['warning'] = sprintf($this->language->get('error_product'), $product_total);
			}
		}

		return !$this->error;
	}

	public function upload() {
		$this->load->language('catalog/download');
		$json = array();
		if (!$this->user->hasPermission('modify', 'catalog/download')) { $json['error'] = $this->language->get('error_permission'); }
		if ($this->request->server['REQUEST_METHOD'] !== 'POST') { $json['error'] = $this->language->get('error_upload'); }
		if (!$json) {
			$file = isset($this->request->files['file']) && is_array($this->request->files['file']) ? $this->request->files['file'] : array();
			$max_size = (int)$this->config->get('config_file_max_size'); if ($max_size <= 0) { $max_size = \CodeCart\Core\UploadGuard::GENERIC_MAX_BYTES; }
			$check = \CodeCart\Core\UploadGuard::validateGeneric($file, $this->config->get('config_file_ext_allowed'), $this->config->get('config_file_mime_allowed'), $max_size, 128);
			if (!$check['ok']) {
				$code = isset($check['code']) ? (string)$check['code'] : 'upload';
				if (strpos($code, 'upload_') === 0) { $json['error'] = $this->language->get('error_' . $code); }
				elseif ($code === 'filesize') { $json['error'] = $this->language->get('error_filesize'); }
				elseif ($code === 'filename') { $json['error'] = $this->language->get('error_filename'); }
				elseif ($code === 'filetype') { $json['error'] = $this->language->get('error_filetype'); }
				else { $json['error'] = $this->language->get('error_upload'); }
			} else { $filename = $check['filename']; }
		}
		if (!$json) {
			$stored = $filename . '.' . token(32); $target = rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $stored;
			if (!move_uploaded_file($this->request->files['file']['tmp_name'], $target)) { $json['error'] = $this->language->get('error_upload'); }
			else { $json['filename'] = $stored; $json['mask'] = $filename; $json['success'] = $this->language->get('text_upload'); }
		}
		$this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json));
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('catalog/download');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => $this->config->get('config_limit_autocomplete')
			);

			$results = $this->model_catalog_download->getDownloads($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'download_id' => $result['download_id'],
					'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}