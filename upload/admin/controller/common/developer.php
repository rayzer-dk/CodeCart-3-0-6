<?php
class ControllerCommonDeveloper extends Controller {
	public function index() {
		$this->load->language('common/developer');

		$data['user_token'] = $this->session->data['user_token'];

		$data['developer_theme'] = $this->config->get('developer_theme');

		$data['eval'] = true;

		$this->response->setOutput($this->load->view('common/developer', $data));
	}

	public function edit() {
		$this->load->language('common/developer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'common/developer')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('developer', $this->request->post, 0);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function theme() {
		$this->load->language('common/developer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'common/developer')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$directories = glob(DIR_CACHE . '/template/*', GLOB_ONLYDIR);

			if ($directories) {
				foreach ($directories as $directory) {
					$files = glob($directory . '/*');

					foreach ($files as $file) { 
						if (is_file($file)) {
							unlink($file);
						}
					}

					if (is_dir($directory)) {
						rmdir($directory);
					}
				}
			}

			$json['success'] = sprintf($this->language->get('text_cache'), $this->language->get('text_theme'));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function sass() {
		$this->load->language('common/developer');
		$json = array();
		if (!$this->user->hasPermission('modify', 'common/developer')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$compiler = new \CodeCart\Core\StyleCompiler();
			$result = $compiler->rebuild();
			if (!empty($result['errors'])) {
				$json['error'] = implode("\n", $result['errors']);
			} else {
				$json['success'] = $this->language->get('text_sass_rebuilt');
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function systemcache() {
		$this->load->language('common/developer');
		$json = array();
		if (!$this->user->hasPermission('modify', 'common/developer')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			try { $this->cache->delete('*'); } catch (\Throwable $e) { $this->log->write('Developer cache reset: ' . $e->getMessage()); }
			foreach ((array)glob(DIR_CACHE . 'cache.*') as $file) { if (is_file($file)) { @unlink($file); } }
			$json['success'] = sprintf($this->language->get('text_cache'), $this->language->get('text_systemcache'));
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function imgcache() {
		$this->load->language('common/developer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'common/developer')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
		$imgfiles = glob(DIR_IMAGE . 'cache/*');

		if (!empty($imgfiles)) {
			foreach($imgfiles as $imgfile){
				$this->deldir($imgfile);
			}
		}

			$json['success'] = sprintf($this->language->get('text_img_cache'), $this->language->get('text_imgcache'));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function allcache() {
		$this->load->language('common/developer');
		$json = array();
		if (!$this->user->hasPermission('modify', 'common/developer')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			try { $this->cache->delete('*'); } catch (\Throwable $e) { $this->log->write('Developer cache reset: ' . $e->getMessage()); }
			foreach ((array)glob(DIR_CACHE . 'cache.*') as $file) { if (is_file($file)) { @unlink($file); } }
			$template = rtrim(DIR_CACHE, '/\\') . DIRECTORY_SEPARATOR . 'template';
			if (is_dir($template)) { foreach ((array)glob($template . DIRECTORY_SEPARATOR . '*') as $path) { $this->deldir($path); } }
			foreach ((array)glob(DIR_IMAGE . 'cache/*') as $imgfile) { $this->deldir($imgfile); }
			// Do not recursively delete arbitrary DIR_CACHE subdirectories: only known,
			// rebuildable core caches are targeted.
			$json['success'] = sprintf($this->language->get('text_cache'), $this->language->get('text_allcache'));
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function deldir($dirname){
		if(file_exists($dirname)) {
			if(is_dir($dirname)){
				$dir=opendir($dirname);
				while(($filename=readdir($dir)) !== false){
					if($filename!="." && $filename!=".."){
						$file=$dirname."/".$filename;
						$this->deldir($file);
					}
				}
				closedir($dir);
				rmdir($dirname);
			} else {
				if (!unlink($dirname) && $this->log) {
					$this->log->write('Developer cache cleanup: unable to delete ' . $dirname);
				}
			}
		}
	}
}
