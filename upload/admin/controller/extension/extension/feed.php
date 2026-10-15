<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionExtensionFeed extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/extension/feed');

		$this->load->model('setting/extension');

		$this->getList();
	}

	public function install() {
		$this->load->language('extension/extension/feed');

		$this->load->model('setting/extension');

		if ($this->validate()) {
			$this->model_setting_extension->install('feed', $this->request->get['extension']);

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/feed/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/feed/' . $this->request->get['extension']);

			// Call install method if it exsits
			$this->load->controller('extension/feed/' . $this->request->get['extension'] . '/install');

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	public function uninstall() {
		$this->load->language('extension/extension/feed');

		$this->load->model('setting/extension');

		if ($this->validate()) {
			$this->model_setting_extension->uninstall('feed', $this->request->get['extension']);

			// Call uninstall method if it exsits
			$this->load->controller('extension/feed/' . $this->request->get['extension'] . '/uninstall');

			$this->load->model('user/user_group');
			$this->model_user_user_group->removePermissions('extension/feed/' . $this->request->get['extension']);

			$this->session->data['success'] = $this->language->get('text_success');
		}
		
		$this->getList();
	}

	protected function getList() {
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

		$extensions = $this->model_setting_extension->getInstalled('feed');

		foreach ($extensions as $key => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/feed/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/feed/' . $value . '.php')) {
				// Keep the installed record; the merchant may restore the missing file.

				unset($extensions[$key]);
			}
		}

		$data['extensions'] = array();
		
		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/extension/feed/*.php');

		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');

				$this->load->language('extension/feed/' . $extension, 'extension');

				$data['extensions'][] = array(
					'code'      => $extension,
					'name'      => $this->language->get('extension')->get('heading_title'),
					'status'    => $this->config->get('feed_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'install'   => $this->url->link('extension/extension/feed/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'uninstall' => $this->url->link('extension/extension/feed/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'installed' => in_array($extension, $extensions),
					'edit'      => $this->url->link('extension/feed/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				);
			}
		}

		$data['promotion'] = $this->load->controller('extension/extension/promotion');

		// Track installation dates without fabricating dates for older extensions.
		$install_dates = $this->model_setting_extension->getInstallDates('feed');
		$upload_dates = $this->model_setting_extension->getUploadDates('feed');
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

		$this->response->setOutput($this->load->view('extension/extension/feed', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/extension/feed')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}