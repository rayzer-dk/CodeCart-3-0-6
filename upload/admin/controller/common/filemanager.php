<?php
class ControllerCommonFileManager extends Controller {
	public function index() {
		$this->load->language('common/filemanager');

		// Find which protocol to use to pass the full image link back
		if ($this->request->server['HTTPS']) {
			$server = HTTPS_CATALOG;
		} else {
			$server = HTTP_CATALOG;
		}

		if (isset($this->request->get['filter_name'])) {
			$filter_name = rtrim(str_replace(array('*', '/', '\\'), '', $this->request->get['filter_name']), '/');
		} else {
			$filter_name = '';
		}

		// Make sure we have the correct directory
		if (isset($this->request->get['directory'])) {
			$directory = rtrim(DIR_IMAGE . 'catalog/' . str_replace('*', '', $this->request->get['directory']), '/');
		} else {
			$directory = DIR_IMAGE . 'catalog';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$select_event = '';
		if (isset($this->request->get['select_event']) && preg_match('/^[a-zA-Z0-9_.:-]{1,64}$/', (string)$this->request->get['select_event'])) {
			$select_event = (string)$this->request->get['select_event'];
		}

		$directories = array();
		$files = array();

		$data['images'] = array();

		$this->load->model('tool/image');

		if (substr(str_replace('\\', '/', realpath($directory) . '/' . $filter_name), 0, strlen(DIR_IMAGE . 'catalog')) == str_replace('\\', '/', DIR_IMAGE . 'catalog')) {
			// Get directories
			$directories = glob($directory . '/' . $filter_name . '*', GLOB_ONLYDIR);

			if (!$directories) {
				$directories = array();
			}

			// Get files
			$files = safe_glob($directory . '/' . $filter_name . '*.{jpg,jpeg,png,gif,webp,avif,svg,JPG,JPEG,PNG,GIF,WEBP,AVIF,SVG}', GLOB_BRACE);

			if (!$files) {
				$files = array();
			}
		}

		// Merge directories and files
		$images = array_merge($directories, $files);

		// Get total number of files and directories
		$image_total = count($images);

		// Split the array based on current page number and max number of items per page of 10
		$images = array_splice($images, ($page - 1) * 16, 16);

		foreach ($images as $image) {
			$name = basename($image);

			if (is_dir($image)) {
				$url = '';

				if (isset($this->request->get['target'])) {
					$url .= '&target=' . $this->request->get['target'];
				}

				if (isset($this->request->get['thumb'])) {
					$url .= '&thumb=' . $this->request->get['thumb'];
				}

				if (!empty($this->request->get['multiple'])) {
					$url .= '&multiple=1';
				}

				if ($select_event !== '') {
					$url .= '&select_event=' . urlencode($select_event);
				}

				$data['images'][] = array(
					'thumb' => '',
					'name'  => $name,
					'type'  => 'directory',
					'path'  => utf8_substr($image, utf8_strlen(DIR_IMAGE)),
					'href'  => $this->url->link('common/filemanager', 'user_token=' . $this->session->data['user_token'] . '&directory=' . urlencode(utf8_substr($image, utf8_strlen(DIR_IMAGE . 'catalog/'))) . $url, true)
				);
			} elseif (is_file($image)) {
				$relative_image = utf8_substr($image, utf8_strlen(DIR_IMAGE));
				$preview = $this->model_tool_image->resize($relative_image, 100, 100);
				if (!$preview) {
					continue;
				}
				$data['images'][] = array(
					'thumb' => $preview,
					'name'  => $name,
					'type'  => 'image',
					'path'  => $relative_image,
					'href'  => $preview
				);
			}
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['directory'])) {
			$data['directory'] = urlencode($this->request->get['directory']);
			$data['current_directory'] = (string)$this->request->get['directory'];
		} else {
			$data['directory'] = '';
			$data['current_directory'] = '';
		}

		if (isset($this->request->get['filter_name'])) {
			$data['filter_name'] = $this->request->get['filter_name'];
		} else {
			$data['filter_name'] = '';
		}

		// Return the target ID for the file manager to set the value
		if (isset($this->request->get['target'])) {
			$data['target'] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$this->request->get['target']);
		} else {
			$data['target'] = '';
		}

		// Return the thumbnail for the file manager to show a thumbnail
		if (isset($this->request->get['thumb'])) {
			$data['thumb'] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$this->request->get['thumb']);
		} else {
			$data['thumb'] = '';
		}

		$data['multiple'] = !empty($this->request->get['multiple']);
		$data['select_event'] = $select_event;
		$data['webp_supported'] = extension_loaded('gd') && function_exists('imagewebp');

		// Parent
		$url = '';

		if (isset($this->request->get['directory'])) {
			$pos = strrpos($this->request->get['directory'], '/');

			if ($pos) {
				$url .= '&directory=' . urlencode(substr($this->request->get['directory'], 0, $pos));
			}
		}

		if (isset($this->request->get['target'])) {
			$url .= '&target=' . $this->request->get['target'];
		}

		if (isset($this->request->get['thumb'])) {
			$url .= '&thumb=' . $this->request->get['thumb'];
		}

		if (!empty($this->request->get['multiple'])) {
			$url .= '&multiple=1';
		}

		if ($select_event !== '') {
			$url .= '&select_event=' . urlencode($select_event);
		}

		$data['parent'] = $this->url->link('common/filemanager', 'user_token=' . $this->session->data['user_token'] . $url, true);

		// Refresh
		$url = '';

		if (isset($this->request->get['directory'])) {
			$url .= '&directory=' . urlencode($this->request->get['directory']);
		}

		if (isset($this->request->get['target'])) {
			$url .= '&target=' . $this->request->get['target'];
		}

		if (isset($this->request->get['thumb'])) {
			$url .= '&thumb=' . $this->request->get['thumb'];
		}

		if (!empty($this->request->get['multiple'])) {
			$url .= '&multiple=1';
		}

		if ($select_event !== '') {
			$url .= '&select_event=' . urlencode($select_event);
		}

		$data['refresh'] = $this->url->link('common/filemanager', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$url = '';

		if (isset($this->request->get['directory'])) {
			$url .= '&directory=' . urlencode(html_entity_decode($this->request->get['directory'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['target'])) {
			$url .= '&target=' . $this->request->get['target'];
		}

		if (isset($this->request->get['thumb'])) {
			$url .= '&thumb=' . $this->request->get['thumb'];
		}

		if (!empty($this->request->get['multiple'])) {
			$url .= '&multiple=1';
		}

		if ($select_event !== '') {
			$url .= '&select_event=' . urlencode($select_event);
		}

		$pagination = new Pagination();
		$pagination->total = $image_total;
		$pagination->page = $page;
		$pagination->limit = 16;
		$pagination->url = $this->url->link('common/filemanager', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$this->response->setOutput($this->load->view('common/filemanager', $data));
	}

	public function upload() {
		$this->load->language('common/filemanager');
		$json = array();

		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['error'] = $this->language->get('error_permission');
		}

		$directory = isset($this->request->get['directory']) ? rtrim(DIR_IMAGE . 'catalog/' . $this->request->get['directory'], '/') : DIR_IMAGE . 'catalog';
		$root = rtrim(str_replace('\\', '/', realpath(DIR_IMAGE . 'catalog')), '/') . '/';
		$real_directory = is_dir($directory) ? realpath($directory) : false;

		if (!$real_directory || strpos(rtrim(str_replace('\\', '/', $real_directory), '/') . '/', $root) !== 0) {
			$json['error'] = $this->language->get('error_directory');
		}

		$files = array();
		if (!$json && isset($this->request->files['file'])) {
			$upload = $this->request->files['file'];
			if (is_array($upload['name'])) {
				foreach (array_keys($upload['name']) as $key) {
					$files[] = array('name' => $upload['name'][$key], 'tmp_name' => $upload['tmp_name'][$key], 'error' => $upload['error'][$key], 'size' => $upload['size'][$key]);
				}
			} else {
				$files[] = array('name' => $upload['name'], 'tmp_name' => $upload['tmp_name'], 'error' => $upload['error'], 'size' => $upload['size']);
			}
		}

		if (!$json && !$files) {
			$json['error'] = $this->language->get('error_upload');
		}

		$allowed_types = array(
			'jpg' => array('mime' => 'image/jpeg', 'type' => IMAGETYPE_JPEG),
			'jpeg' => array('mime' => 'image/jpeg', 'type' => IMAGETYPE_JPEG),
			'png' => array('mime' => 'image/png', 'type' => IMAGETYPE_PNG),
			'gif' => array('mime' => 'image/gif', 'type' => IMAGETYPE_GIF),
			'webp' => array('mime' => 'image/webp', 'type' => defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : 18)
		);
		if (defined('IMAGETYPE_AVIF') && function_exists('imagecreatefromavif')) {
			$allowed_types['avif'] = array('mime' => 'image/avif', 'type' => IMAGETYPE_AVIF);
		}
		$allowed_types['svg'] = array('mime' => 'image/svg+xml', 'type' => 0);
		$finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
		$max_pixels = 50000000;
		$validated = array();
		$reserved_names = array();
		$renamed = 0;

		// Validate the entire batch before moving any file. This prevents a mixed
		// result where the first files are stored but a later invalid image aborts.
		foreach ($files as $file) {
			if ($json) {
				break;
			}

			if ($file['error'] != UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
				$json['error'] = $file['error'] != UPLOAD_ERR_OK ? $this->language->get('error_upload_' . $file['error']) : $this->language->get('error_upload');
				break;
			}

			$original = basename(html_entity_decode($file['name'], ENT_QUOTES, 'UTF-8'));
			$extension = utf8_strtolower(pathinfo($original, PATHINFO_EXTENSION));

			if ((utf8_strlen($original) < 3) || (utf8_strlen($original) > 255)) {
				$json['error'] = $this->language->get('error_filename');
			} elseif (!isset($allowed_types[$extension])) {
				$json['error'] = $this->language->get('error_filetype');
			} elseif ($file['size'] > (int)$this->config->get('config_file_max_size')) {
				$json['error'] = $this->language->get('error_filesize');
			} else {
				$mime = $finfo ? $finfo->file($file['tmp_name']) : false;

				if ($extension === 'svg') {
					$svg_error = '';
					$sanitized_svg = \CodeCart\Core\SvgSanitizer::sanitizeFile($file['tmp_name'], $svg_error);
					if ($sanitized_svg === false || ($mime && !in_array(strtolower((string)$mime), array('image/svg+xml', 'application/xml', 'text/xml', 'text/plain'), true))) {
						$json['error'] = $this->language->get('error_svg_unsafe');
					} else {
						$file['sanitized_svg'] = $sanitized_svg;
					}
				} else {
					$image_info = getimagesize($file['tmp_name']);
					if (!$mime || !$image_info || $mime !== $allowed_types[$extension]['mime'] || (int)$image_info[2] !== $allowed_types[$extension]['type']) {
						$json['error'] = $this->language->get('error_filetype');
					} elseif ((int)$image_info[0] <= 0 || (int)$image_info[1] <= 0 || (int)$image_info[0] > 16000 || (int)$image_info[1] > 16000 || ((int)$image_info[0] * (int)$image_info[1]) > $max_pixels) {
						$json['error'] = $this->language->get('error_dimensions');
					}
				}
			}

			if (!$json) {
				$filename = $this->normalizeImageFilename($original, $extension);
				$filename = $this->uniqueImageFilename($real_directory, $filename, $reserved_names);
				$reserved_names[utf8_strtolower($filename)] = true;
				if ($filename !== $original) {
					$renamed++;
				}
				$file['filename'] = $filename;
				$validated[] = $file;
			}
		}

		$moved = array();
		if (!$json) {
			foreach ($validated as $file) {
				$target = $real_directory . DIRECTORY_SEPARATOR . $file['filename'];
				$stored = false;
				if (isset($file['sanitized_svg'])) {
					$tmp_target = $target . '.tmp-' . bin2hex(random_bytes(4));
					if (file_put_contents($tmp_target, $file['sanitized_svg'], LOCK_EX) !== false) {
						$stored = @rename($tmp_target, $target);
					}
					if (is_file($tmp_target)) { @unlink($tmp_target); }
				} else {
					$stored = move_uploaded_file($file['tmp_name'], $target);
				}

				if (!$stored) {
					$json['error'] = $this->language->get('error_upload');
					foreach ($moved as $created) {
						if (is_file($created)) { @unlink($created); }
					}
					break;
				}
				$moved[] = $target;
			}
		}

		if (!$json) {
			$json['success'] = $renamed ? sprintf($this->language->get('text_uploaded_renamed'), count($validated), $renamed) : $this->language->get('text_uploaded');
			$json['files'] = array_map(function($file) { return $file['filename']; }, $validated);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function normalizeImageFilename($original, $extension) {
		$base = pathinfo($original, PATHINFO_FILENAME);
		$base = html_entity_decode($base, ENT_QUOTES, 'UTF-8');

		// Stable Ukrainian/Russian transliteration is used first so filenames do
		// not depend on the host OS locale. Intl/iconv may then normalize accents.
		$map = array(
			'А'=>'A','Б'=>'B','В'=>'V','Г'=>'H','Ґ'=>'G','Д'=>'D','Е'=>'E','Є'=>'Ye','Ж'=>'Zh','З'=>'Z','И'=>'Y','І'=>'I','Ї'=>'Yi','Й'=>'Y','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'Kh','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Shch','Ь'=>'','Ю'=>'Yu','Я'=>'Ya','Ё'=>'Yo','Ъ'=>'','Ы'=>'Y','Э'=>'E',
			'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ie','ж'=>'zh','з'=>'z','и'=>'y','і'=>'i','ї'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ь'=>'','ю'=>'iu','я'=>'ia','ё'=>'yo','ъ'=>'','ы'=>'y','э'=>'e'
		);
		$base = strtr($base, $map);

		if (class_exists('Transliterator')) {
			$transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
			if ($transliterator) {
				$value = $transliterator->transliterate($base);
				if (is_string($value) && $value !== '') {
					$base = $value;
				}
			}
		} elseif (function_exists('iconv')) {
			$value = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
			if (is_string($value) && $value !== '') {
				$base = $value;
			}
		}

		$base = strtolower($base);
		$base = preg_replace('/[^a-z0-9]+/', '-', $base);
		$base = trim((string)$base, '-');
		$base = preg_replace('/-+/', '-', $base);

		if ($base === '') {
			$base = 'image-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
		}

		// Keep uploaded names portable when images are later synced to Windows/SMB.
		if (preg_match('/^(con|prn|aux|nul|com[1-9]|lpt[1-9])$/i', $base)) {
			$base = 'image-' . $base;
		}

		// Keep enough headroom for collision suffixes and the extension.
		$base = substr($base, 0, 180);
		$base = rtrim($base, '-');
		return $base . '.' . strtolower($extension);
	}

	private function uniqueImageFilename($directory, $filename, array $reserved_names) {
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		$base = pathinfo($filename, PATHINFO_FILENAME);
		$candidate = $filename;
		$counter = 2;

		while (file_exists($directory . DIRECTORY_SEPARATOR . $candidate) || isset($reserved_names[utf8_strtolower($candidate)])) {
			$suffix = '-' . $counter++;
			$max = max(1, 180 - strlen($suffix));
			$candidate = rtrim(substr($base, 0, $max), '-') . $suffix . '.' . $extension;
		}

		return $candidate;
	}

	public function folder() {
		$this->load->language('common/filemanager');
		$json = array();

		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$directory = isset($this->request->get['directory']) ? rtrim(DIR_IMAGE . 'catalog/' . $this->request->get['directory'], '/') : DIR_IMAGE . 'catalog';
		$root = rtrim(str_replace('\\', '/', realpath(DIR_IMAGE . 'catalog')), '/') . '/';
		$real_directory = is_dir($directory) ? realpath($directory) : false;

		if (!$real_directory || strpos(rtrim(str_replace('\\', '/', $real_directory), '/') . '/', $root) !== 0) {
			$json['error'] = $this->language->get('error_directory');
		}

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error'] = $this->language->get('error_permission');
		}

		$folder = isset($this->request->post['folder']) ? basename(html_entity_decode($this->request->post['folder'], ENT_QUOTES, 'UTF-8')) : '';
		if (!$json && ((utf8_strlen($folder) < 3) || (utf8_strlen($folder) > 128))) {
			$json['error'] = $this->language->get('error_folder');
		}

		$target = $real_directory ? $real_directory . DIRECTORY_SEPARATOR . $folder : '';
		if (!$json && is_dir($target)) {
			$json['error'] = $this->language->get('error_exists');
		}

		if (!$json) {
			if (!mkdir($target, 0755)) {
				$json['error'] = $this->language->get('error_directory');
			} else {
				$this->safeTouch($target . DIRECTORY_SEPARATOR . 'index.html');
				$json['success'] = $this->language->get('text_directory');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	public function convertWebp() {
		$this->load->language('common/filemanager');
		$json = array('converted' => 0, 'skipped' => 0, 'failed' => 0);

		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!extension_loaded('gd') || !function_exists('imagewebp')) {
			$json['error'] = $this->language->get('error_webp_support');
		}

		$root = realpath(DIR_IMAGE . 'catalog');
		if (!$root) {
			$json['error'] = $this->language->get('error_directory');
		}

		$mode = isset($this->request->post['mode']) ? (string)$this->request->post['mode'] : 'selected';
		$offset = max(0, isset($this->request->post['offset']) ? (int)$this->request->post['offset'] : 0);
		$limit = 25;
		$files = array();

		if (!isset($json['error'])) {
			if ($mode === 'all') {
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
				foreach ($iterator as $file_info) {
					if (!$file_info->isFile() || $file_info->isLink()) {
						continue;
					}
					$extension = strtolower($file_info->getExtension());
					if (in_array($extension, array('jpg', 'jpeg', 'png'), true)) {
						$files[] = $file_info->getPathname();
					}
				}
				sort($files, SORT_NATURAL | SORT_FLAG_CASE);
				$total = count($files);
				$files = array_slice($files, $offset, $limit);
				$json['total'] = $total;
				$json['next_offset'] = min($total, $offset + count($files));
				$json['done'] = $json['next_offset'] >= $total;
			} else {
				$paths = isset($this->request->post['path']) ? $this->request->post['path'] : array();
				if (!is_array($paths)) {
					$paths = array($paths);
				}
				foreach (array_slice($paths, 0, 100) as $path) {
					$target = DIR_IMAGE . ltrim(str_replace('\\', '/', (string)$path), '/');
					$real = realpath($target);
					if ($real && is_file($real) && $this->isPathInside($real, $root) && in_array(strtolower(pathinfo($real, PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'png'), true)) {
						$files[] = $real;
					}
				}
				$json['total'] = count($files);
				$json['done'] = true;
			}

			foreach ($files as $file) {
				$result = $this->convertFileToWebp($file);
				if ($result === true) {
					$json['converted']++;
				} elseif ($result === null) {
					$json['skipped']++;
				} else {
					$json['failed']++;
				}
			}

			$json['success'] = sprintf($this->language->get('text_webp_result'), (int)$json['converted'], (int)$json['skipped'], (int)$json['failed']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function convertFileToWebp($file) {
		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (!in_array($extension, array('jpg', 'jpeg', 'png'), true)) {
			return null;
		}

		$destination = preg_replace('/\.(?:jpe?g|png)$/i', '.webp', $file);
		if (!$destination || $destination === $file) {
			return false;
		}

		if (is_file($destination) && filemtime($destination) >= filemtime($file) && filesize($destination) > 0) {
			return null;
		}

		try {
			$image = new Image($file);
			if (!$image->getImage()) {
				return false;
			}

			$temp = dirname($destination) . DIRECTORY_SEPARATOR . '.codecart-webp-' . bin2hex(random_bytes(8)) . '.webp';
			$image->save($temp, 85);

			if (!is_file($temp) || filesize($temp) < 1) {
				if (is_file($temp)) {
					unlink($temp);
				}
				return false;
			}

			if (is_file($destination) && !unlink($destination)) {
				unlink($temp);
				return false;
			}

			if (!rename($temp, $destination)) {
				unlink($temp);
				return false;
			}

			chmod($destination, 0644);
			return true;
		} catch (Throwable $e) {
			$this->log->write('File Manager WebP conversion failed for ' . basename($file) . ': ' . $e->getMessage());
			return false;
		}
	}

	public function delete() {
		$this->load->language('common/filemanager');

		$json = array();

		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['error'] = $this->language->get('error_permission');
		}

		$paths = array();

		if (isset($this->request->post['path'])) {
			$paths = is_array($this->request->post['path']) ? $this->request->post['path'] : array($this->request->post['path']);
		}

		$root = realpath(DIR_IMAGE . 'catalog');

		if (!$root) {
			$json['error'] = $this->language->get('error_delete');
		}

		if (!$json) {
			foreach ($paths as $path) {
				$path = str_replace('\\\\', '/', (string)$path);
				$target = DIR_IMAGE . ltrim($path, '/');
				$real = realpath($target);

				if (!$real || $real === $root || !$this->isPathInside($real, $root)) {
					$json['error'] = $this->language->get('error_delete');
					break;
				}
			}
		}

		if (!$json) {
			foreach ($paths as $path) {
				$target = DIR_IMAGE . ltrim(str_replace('\\\\', '/', (string)$path), '/');

				if (!$this->deletePath($target, $root)) {
					$json['error'] = $this->language->get('error_delete');
					break;
				}
			}
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function isPathInside($path, $root) {
		$path = rtrim(str_replace('\\\\', '/', $path), '/');
		$root = rtrim(str_replace('\\\\', '/', $root), '/');

		return strpos($path . '/', $root . '/') === 0;
	}

	private function deletePath($path, $root) {
		if (is_link($path)) {
			return $this->safeUnlink($path);
		}

		$real = realpath($path);

		if (!$real || $real === $root || !$this->isPathInside($real, $root)) {
			return false;
		}

		if (is_file($real)) {
			return $this->safeUnlink($real);
		}

		if (!is_dir($real)) {
			return false;
		}

		$items = scandir($real);

		if ($items === false) {
			return false;
		}

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$child = $real . DIRECTORY_SEPARATOR . $item;

			if (is_link($child)) {
				if (!$this->safeUnlink($child)) {
					return false;
				}
			} elseif (!$this->deletePath($child, $root)) {
				return false;
			}
		}

		return $this->safeRmdir($real);
	}

	private function safeTouch($path) {
		if (is_file($path)) {
			return true;
		}

		$error = '';
		set_error_handler(function($severity, $message) use (&$error) {
			$error = $message;
			return true;
		});

		try {
			$result = touch($path);
		} finally {
			restore_error_handler();
		}

		if (!$result && $error) {
			$this->log->write('File Manager touch failed: ' . $error);
		}

		return $result;
	}

	private function safeUnlink($path) {
		$error = '';
		set_error_handler(function($severity, $message) use (&$error) {
			$error = $message;
			return true;
		});

		try {
			$result = unlink($path);
		} finally {
			restore_error_handler();
		}

		if (!$result && $error) {
			$this->log->write('File Manager unlink failed for ' . $path . ': ' . $error);
		}

		return $result;
	}

	private function safeRmdir($path) {
		$error = '';
		set_error_handler(function($severity, $message) use (&$error) {
			$error = $message;
			return true;
		});

		try {
			$result = rmdir($path);
		} finally {
			restore_error_handler();
		}

		if (!$result && $error) {
			$this->log->write('File Manager rmdir failed for ' . $path . ': ' . $error);
		}

		return $result;
	}

}
