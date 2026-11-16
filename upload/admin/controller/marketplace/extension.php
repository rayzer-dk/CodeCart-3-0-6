<?php
class ControllerMarketplaceExtension extends Controller {

	public function index() {
		$this->load->language('marketplace/extension');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['type'] = '';
		
		$data['categories'] = array();
		
		$files = safe_glob(DIR_APPLICATION . 'controller/extension/extension/*.php', GLOB_BRACE);
		
		foreach ($files as $file) {
			$extension = basename($file, '.php');

			if ($extension=='promotion') {
				continue;
			}

			// Compatibility code for old extension folders
			$this->load->language('extension/extension/' . $extension, 'extension');
		
			if ($this->user->hasPermission('access', 'extension/extension/' . $extension)) {
				$files = safe_glob(DIR_APPLICATION . 'controller/extension/' . $extension . '/*.php', GLOB_BRACE);
				if ($extension === 'theme') {
					$this->load->model('setting/extension');
					$installed_themes = $this->model_setting_extension->getInstalled('theme');
					$install_origin = strtolower((string)$this->config->get('codecart_install_origin'));
					$native_codecart_install = ($install_origin === 'fresh') || ($install_origin === '' && !in_array('default', $installed_themes, true));
					if ($native_codecart_install) {
						$files = array_values(array_filter($files, function($theme_file) { return basename($theme_file, '.php') !== 'default'; }));
					}
				}
		
				$data['categories'][] = array(
					'code' => $extension,
					'text' => $this->language->get('extension')->get('heading_title') . ' (' . count($files) .')',
					'href' => $this->url->link('extension/extension/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				);
			}
		}

		$data['extension_content'] = '';
		$allowed_types = array_column($data['categories'], 'code');
		$requested_type = $this->request->get['type'] ?? '';
		if ($allowed_types) {
			$data['type'] = in_array($requested_type, $allowed_types, true) ? $requested_type : $allowed_types[0];
			$content = $this->load->controller('extension/extension/' . $data['type']);
			if (!is_string($content)) {
				$content = $this->response->getOutput();
			}
			$data['extension_content'] = is_string($content) ? $content : '';
			$this->load->language('marketplace/extension');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketplace/extension', $data));
	}

	public function refreshMenu() {
		$output = $this->load->controller('common/column_left');
		$this->response->setOutput($output);
	}
}
