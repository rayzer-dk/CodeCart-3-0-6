<?php
class ControllerToolUpload extends Controller {
	public function index() {
		$this->load->language('tool/upload');
		$json = array();

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!$json) {
			$file = isset($this->request->files['file']) && is_array($this->request->files['file']) ? $this->request->files['file'] : array();
			$max_size = (int)$this->config->get('config_file_max_size');
			if ($max_size <= 0) { $max_size = \CodeCart\Core\UploadGuard::GENERIC_MAX_BYTES; }
			$check = \CodeCart\Core\UploadGuard::validateGeneric($file, $this->config->get('config_file_ext_allowed'), $this->config->get('config_file_mime_allowed'), $max_size, 64);

			if (!$check['ok']) {
				$code = isset($check['code']) ? (string)$check['code'] : 'upload';
				if (strpos($code, 'upload_') === 0) {
					$json['error'] = $this->language->get('error_' . $code);
				} elseif ($code === 'filesize') {
					$json['error'] = $this->language->get('error_filesize');
				} elseif ($code === 'filename') {
					$json['error'] = $this->language->get('error_filename');
				} elseif ($code === 'filetype') {
					$json['error'] = $this->language->get('error_filetype');
				} else {
					$json['error'] = $this->language->get('error_upload');
				}
			} else {
				$filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($check['filename'], ENT_QUOTES, 'UTF-8')));
				if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 64)) {
					$json['error'] = $this->language->get('error_filename');
				}
			}
		}

		if (!$json) {
			$stored = $filename . '.' . token(32);
			$target = rtrim(DIR_UPLOAD, '/\\') . DIRECTORY_SEPARATOR . $stored;
			if (!move_uploaded_file($this->request->files['file']['tmp_name'], $target)) {
				$json['error'] = $this->language->get('error_upload');
			} else {
				$this->load->model('tool/upload');
				$json['code'] = $this->model_tool_upload->addUpload($filename, $stored);
				$json['success'] = $this->language->get('text_upload');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

}