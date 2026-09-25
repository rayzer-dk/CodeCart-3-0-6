<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountDownload extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/download', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/download');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_downloads'),
			'href' => $this->url->link('account/download', '', true)
		);

		$this->load->model('account/download');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = 10;

		$data['downloads'] = array();

		$download_total = $this->model_account_download->getTotalDownloads();

		$results = $this->model_account_download->getDownloads(($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$file = $this->resolveDownloadFile(isset($result['filename']) ? $result['filename'] : '');
			if ($file === false && !empty($result['snapshot_filename'])) {
				$file = $this->resolveDownloadFile($result['snapshot_filename']);
			}
			if ($file !== false) {
				$size = filesize($file);

				$i = 0;

				$suffix = array(
					'B',
					'KB',
					'MB',
					'GB',
					'TB',
					'PB',
					'EB',
					'ZB',
					'YB'
				);

				while (($size / 1024) > 1) {
					$size = $size / 1024;
					$i++;
				}

				$data['downloads'][] = array(
					'order_id'   => $result['order_id'],
					'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'name'       => $result['name'],
					'size'       => round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i],
					'href'       => $this->url->link('account/download/download', 'download_id=' . $result['download_id'], true)
				);
			}
		}

		$pagination = new Pagination();
		$pagination->total = $download_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('account/download', 'page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($download_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($download_total - $limit)) ? $download_total : ((($page - 1) * $limit) + $limit), $download_total, ceil($download_total / $limit));

		$data['continue'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/download', $data));
	}

	public function download() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/download', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->model('account/download');

		if (isset($this->request->get['download_id'])) {
			$download_id = (int)$this->request->get['download_id'];
		} else {
			$download_id = 0;
		}

		$download_info = $this->model_account_download->getDownload($download_id);

		if ($download_info) {
			$file = $this->resolveDownloadFile(isset($download_info['filename']) ? $download_info['filename'] : '');
			$mask = $this->safeDownloadName(isset($download_info['mask']) ? $download_info['mask'] : '');
			if ($file === false && !empty($download_info['snapshot_filename'])) {
				$file = $this->resolveDownloadFile($download_info['snapshot_filename']);
				$mask = $this->safeDownloadName(isset($download_info['snapshot_mask']) ? $download_info['snapshot_mask'] : $mask);
			}

			if (!headers_sent()) {
				if ($file !== false && is_file($file)) {
					$download_name = $mask !== '' ? $mask : $this->safeDownloadName(basename($file));
					header('Content-Type: application/octet-stream');
					$ascii_name = preg_replace('/[^\x20-\x7E]/', '_', $download_name);
					header("Content-Disposition: attachment; filename=\"" . $ascii_name . "\"; filename*=UTF-8''" . rawurlencode($download_name));
					header('X-Content-Type-Options: nosniff');
					header('Expires: 0');
					header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
					header('Pragma: no-cache');
					header('Content-Length: ' . filesize($file));

					while (ob_get_level() > 0) {
						ob_end_clean();
					}

					readfile($file);

					exit();
				} else {
					http_response_code(404);
					exit('Error: Download file not found.');
				}
			} else {
				exit('Error: Headers already sent out!');
			}
		} else {
			$this->response->redirect($this->url->link('account/download', '', true));
		}
	}

	private function resolveDownloadFile($filename) {
		$filename = (string)$filename;
		if ($filename === '' || strpos($filename, "\0") !== false || basename($filename) !== $filename) {
			return false;
		}

		$base = realpath(DIR_DOWNLOAD);
		$file = realpath(rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $filename);
		if ($base === false || $file === false) {
			return false;
		}

		$prefix = rtrim($base, '/\\') . DIRECTORY_SEPARATOR;
		return strpos($file, $prefix) === 0 && is_file($file) ? $file : false;
	}

	private function safeDownloadName($name) {
		$name = basename((string)$name);
		$name = preg_replace('/[\x00-\x1F\x7F"\\]+/', '_', $name);
		$name = trim((string)$name, " .\t\n\r\0\x0B");

		return $name !== '' ? $name : 'download.bin';
	}

}
