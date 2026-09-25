<?php
class ControllerExtensionModulePopular extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/popular');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');
		$this->load->model('localisation/language');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->request->post['limit'] = max(1, min(30, (int)($this->request->post['limit'] ?? 5)));
			$headings = isset($this->request->post['heading']) && is_array($this->request->post['heading']) ? $this->request->post['heading'] : array();
			$this->request->post['heading'] = array();
			foreach ($headings as $language_id => $heading) {
				$language_id = (int)$language_id;
				if ($language_id > 0) {
					$this->request->post['heading'][$language_id] = utf8_substr(trim(strip_tags((string)$heading)), 0, 120);
				}
			}
			$this->request->post['display_mode'] = (isset($this->request->post['display_mode']) && $this->request->post['display_mode'] === 'grid') ? 'grid' : 'carousel';
			$this->request->post['show_heading'] = !isset($this->request->post['show_heading']) || !empty($this->request->post['show_heading']) ? 1 : 0;
			$this->request->post['columns_desktop'] = max(1, min(6, (int)($this->request->post['columns_desktop'] ?? 4)));
			$this->request->post['columns_tablet'] = max(1, min(4, (int)($this->request->post['columns_tablet'] ?? 3)));
			$this->request->post['columns_mobile'] = max(1, min(2, (int)($this->request->post['columns_mobile'] ?? 2)));
			$this->request->post['autoplay'] = !empty($this->request->post['autoplay']) ? 1 : 0;
			$this->request->post['autoplay_delay'] = max(1500, min(20000, (int)($this->request->post['autoplay_delay'] ?? 5000)));
			$this->request->post['show_arrows'] = !empty($this->request->post['show_arrows']) ? 1 : 0;
			$this->request->post['show_dots'] = !empty($this->request->post['show_dots']) ? 1 : 0;
			$this->request->post['loop'] = !empty($this->request->post['loop']) ? 1 : 0;
			$this->request->post['carousel_step'] = (isset($this->request->post['carousel_step']) && $this->request->post['carousel_step'] === 'page') ? 'page' : 'item';
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('popular', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->cache->delete('product');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['width'])) {
			$data['error_width'] = $this->error['width'];
		} else {
			$data['error_width'] = '';
		}

		if (isset($this->error['height'])) {
			$data['error_height'] = $this->error['height'];
		} else {
			$data['error_height'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/popular', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/popular', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/popular', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/popular', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}


		if (isset($this->request->post['heading'])) {
			$data['heading'] = $this->request->post['heading'];
		} elseif (!empty($module_info) && isset($module_info['heading']) && is_array($module_info['heading'])) {
			$data['heading'] = $module_info['heading'];
		} else {
			$data['heading'] = array();
		}
		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['limit'])) {
			$data['limit'] = max(1, min(30, (int)$this->request->post['limit']));
		} elseif (!empty($module_info)) {
			$data['limit'] = max(1, min(30, (int)$module_info['limit']));
		} else {
			$data['limit'] = 5;
		}

		if (isset($this->request->post['width'])) {
			$data['width'] = $this->request->post['width'];
		} elseif (!empty($module_info)) {
			$data['width'] = $module_info['width'];
		} else {
			$data['width'] = 200;
		}

		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($module_info)) {
			$data['height'] = $module_info['height'];
		} else {
			$data['height'] = 200;
		}

		$display_defaults = array(
			'display_mode' => 'carousel',
			'show_heading' => 1,
			'columns_desktop' => 4,
			'columns_tablet' => 3,
			'columns_mobile' => 2,
			'autoplay' => 0,
			'autoplay_delay' => 5000,
			'show_arrows' => 1,
			'show_dots' => 1,
			'loop' => 1,
			'carousel_step' => 'item'
		);
		foreach ($display_defaults as $display_key => $display_default) {
			if (isset($this->request->post[$display_key])) {
				$data[$display_key] = $this->request->post[$display_key];
			} elseif (!empty($module_info) && isset($module_info[$display_key])) {
				$data[$display_key] = $module_info[$display_key];
			} else {
				$data[$display_key] = $display_default;
			}
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/popular', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/popular')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!$this->request->post['width']) {
			$this->error['width'] = $this->language->get('error_width');
		}

		if (!$this->request->post['height']) {
			$this->error['height'] = $this->language->get('error_height');
		}

		return !$this->error;
	}
}