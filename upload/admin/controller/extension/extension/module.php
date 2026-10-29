<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionExtensionModule extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		$this->getList();
	}

	public function install() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if ($this->validate()) {
			$this->model_setting_extension->install('module', $this->request->get['extension']);

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/' . $this->request->get['extension']);

			// Call install method if it exsits
			$this->load->controller('extension/module/' . $this->request->get['extension'] . '/install');

			$this->session->data['success'] = $this->language->get('text_success');
		} else {
			$this->session->data['error'] = $this->error['warning'];
		}
	
		$this->getList();
	}

	public function uninstall() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if ($this->validate()) {
			$this->model_setting_extension->uninstall('module', $this->request->get['extension']);

			$this->model_setting_module->deleteModulesByCode($this->request->get['extension']);

			// Call uninstall method if it exsits
			$this->load->controller('extension/module/' . $this->request->get['extension'] . '/uninstall');

			$this->load->model('user/user_group');
			$this->model_user_user_group->removePermissions('extension/module/' . $this->request->get['extension']);

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}
	
	public function add() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if ($this->validate()) {
			$this->load->language('module' . '/' . $this->request->get['extension']);
			
			$this->model_setting_module->addModule($this->request->get['extension'], $this->language->get('heading_title'));

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	public function delete() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if (isset($this->request->get['module_id']) && $this->validate()) {
			$this->model_setting_module->deleteModule($this->request->get['module_id']);

			$this->session->data['success'] = $this->language->get('text_success');
		}
		
		$this->getList();
	}

	protected function getList() {
		$data['text_layout'] = sprintf($this->language->get('text_layout'), $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], true));
		$data['text_hide_modules'] = sprintf($this->language->get('text_hide_modules'), $this->url->link('user/user_permission', 'user_token=' . $this->session->data['user_token'], true));

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

		$extensions = $this->model_setting_extension->getInstalled('module');

		foreach ($extensions as $key => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/module/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/module/' . $value . '.php')) {
				// Keep the installed record; the merchant may restore the missing file.

				unset($extensions[$key]);
				
				$this->model_setting_module->deleteModulesByCode($value);
			}
		}

		$data['extensions'] = array();

		// Create a new language container so we don't pollute the current one
		$language = new Language($this->config->get('config_language'));
		
		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/extension/module/*.php');

		$this->load->model('user/user_group');
		
		$user_group_info = $this->model_user_user_group->getUserGroup($this->user->getGroupId());

		if(isset($user_group_info['permission']['hiden'])) {
			$hiden = $user_group_info['permission']['hiden'];
		} else {
			$hiden = array();
		}

		$data['hiden'] = false;

		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');
				
				
				if (!in_array('extension/module/' . $extension, $hiden)) {
					$this->load->language('extension/module/' . $extension, 'extension');
					$module_data = array();
					$modules = $this->model_setting_module->getModulesByCode($extension);
					foreach ($modules as $module) {
						if ($module['setting']) {
							$setting_info = json_decode($module['setting'], true);
						} else {
							$setting_info = array();
						}
						
						$module_data[] = array(
						'module_id' => $module['module_id'],
						'name'      => $module['name'],
						'status_enabled' => !empty($setting_info['status']),
						'status'    => (isset($setting_info['status']) && $setting_info['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
						'edit'      => $this->url->link('extension/module/' . $extension, 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true),
						'delete'    => $this->url->link('extension/extension/module/delete', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true)
					);
					}
					$data['extensions'][] = array(
					'code'      => $extension,
					'name'      => $this->language->get('extension')->get('heading_title'),
					'status_enabled' => (bool)$this->config->get('module_' . $extension . '_status'),
					'status'    => $this->config->get('module_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'module'    => $module_data,
					'install'   => $this->url->link('extension/extension/module/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'uninstall' => $this->url->link('extension/extension/module/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'installed' => in_array($extension, $extensions),
					'edit'      => $this->url->link('extension/module/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				);
				} else {
					$data['hiden'] = true;
				}
			}
		}

		$sort_order = array();

		foreach ($data['extensions'] as $key => $value) {
			if($value['installed']){
				$add = '0';
			}else{
				$add = '1';
			}
				$sort_order[$key] = $add.$value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $data['extensions']);

		$data['promotion'] = $this->load->controller('extension/extension/promotion');

		// Track installation dates without fabricating dates for older extensions.
		$install_dates = $this->model_setting_extension->getInstallDates('module');
		$upload_dates = $this->model_setting_extension->getUploadDates('module');
		foreach ($data['extensions'] as &$extension_row) {
			$installed_at = $extension_row['installed'] && isset($install_dates[$extension_row['code']]) ? $install_dates[$extension_row['code']] : '';
			$uploaded_at = isset($upload_dates[$extension_row['code']]) ? $upload_dates[$extension_row['code']] : '';
			$extension_row['date_uploaded'] = $uploaded_at;
			$extension_row['date_uploaded_label'] = $uploaded_at !== '' ? substr($uploaded_at, 0, 16) : '';
			$extension_row['date_installed'] = $installed_at;
			$extension_row['date_installed_label'] = $installed_at !== '' ? date('Y-m-d H:i', strtotime($installed_at)) : '';
		}
		unset($extension_row);

		$this->load->language('marketplace/extension', 'codecart_sort');
		$sort_language = $this->language->get('codecart_sort');
		$data['column_installed'] = $sort_language->get('column_installed');
		$data['column_install_date'] = $sort_language->get('column_install_date');
		$data['column_upload_date'] = $sort_language->get('column_upload_date');
		$data['text_installed'] = $sort_language->get('text_installed');
		$data['text_not_installed'] = $sort_language->get('text_not_installed');
		$data['text_date_unknown'] = $sort_language->get('text_date_unknown');
		$data['text_upload_date_unknown'] = $sort_language->get('text_upload_date_unknown');
		$data['text_sort_ascending'] = $sort_language->get('text_sort_ascending');
		$data['text_sort_descending'] = $sort_language->get('text_sort_descending');

		$this->response->setOutput($this->load->view('extension/extension/module', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if(!isset($this->error['warning']) && isset($this->request->get['extension']) && ((utf8_strlen($this->request->get['extension']) < 3) || (utf8_strlen($this->request->get['extension']) > 32))) {
			$this->error['warning'] = $this->language->get('error_code_name');
		}

		return !$this->error;
	}
}
