<?php
class ControllerCommonColumnLeft extends Controller {
	public function index() {
		$this->load->language('common/column_left');

		$data['text_license'] = $this->language->get('text_license');
		$data['text_installation'] = $this->language->get('text_installation');
		$data['text_configuration'] = $this->language->get('text_configuration');
		$data['text_upgrade'] = $this->language->get('text_upgrade');
		$data['text_finished'] = $this->language->get('text_finished');
		$data['text_language'] = $this->language->get('text_language');
		$data['route'] = isset($this->request->get['route']) ? (string)$this->request->get['route'] : 'install/step_1';
		$data['action'] = $this->url->link('common/column_left/language', '', $this->request->server['HTTPS']);
		$data['code'] = isset($this->session->data['language']) ? (string)$this->session->data['language'] : (string)$this->config->get('language_default');
		$data['languages'] = array();

		foreach (glob(DIR_LANGUAGE . '*', GLOB_ONLYDIR) as $directory) {
			$code = basename($directory);
			if (!preg_match('/^[a-z]{2}-[a-z]{2}$/i', $code)) { continue; }
			$name = strtoupper($code);
			try {
				$language = new Language($code);
				$language->load($code);
				$label = $language->get('text_name');
				if ($label !== 'text_name' && $label !== '') { $name = $label; }
			} catch (\Throwable $e) {}
			$data['languages'][] = array(
				'code' => $code,
				'name' => $name,
				'image' => 'language/' . $code . '/' . $code . '.png',
				'href' => $this->url->link('common/column_left/language', array('code' => $code), $this->request->server['HTTPS'])
			);
		}

		if (!isset($this->request->get['route'])) {
			$data['redirect'] = $this->url->link('install/step_1');
		} else {
			$url_data = $this->request->get;
			$route = (string)$url_data['route'];
			unset($url_data['route']);
			$url = $url_data ? '&' . urldecode(http_build_query($url_data, '', '&')) : '';
			$data['redirect'] = $this->url->link($route, $url, $this->request->server['HTTPS']);
		}
		// Keep the same-origin return URL in the installer session.  The
		// language endpoint can then redirect without trusting a URL supplied by
		// a form field and without losing query parameters through HTML escaping.
		$this->session->data['language_return'] = $data['redirect'];

		return $this->load->view('common/column_left', $data);
	}

	public function language() {
		$code = '';
		if (isset($this->request->post['code'])) {
			$code = basename((string)$this->request->post['code']);
		} elseif (isset($this->request->get['code'])) {
			$code = basename((string)$this->request->get['code']);
		} elseif (isset($this->request->get['language'])) {
			$code = basename((string)$this->request->get['language']);
		}
		if ($code !== '' && preg_match('/^[a-z]{2}-[a-z]{2}$/i', $code) && is_dir(DIR_LANGUAGE . $code)) {
			$this->session->data['language'] = $code;
		}
		$redirect = isset($this->session->data['language_return']) ? (string)$this->session->data['language_return'] : '';
		if ($redirect === '' && isset($this->request->post['redirect'])) {
			$redirect = (string)$this->request->post['redirect'];
		}
		if ($redirect === '' && isset($this->request->get['redirect'])) {
			$redirect = (string)$this->request->get['redirect'];
		}
		$redirect = html_entity_decode($redirect, ENT_QUOTES, 'UTF-8');
		if ($redirect === '' || strpos($redirect, HTTP_SERVER) !== 0) {
			$redirect = $this->url->link('install/step_1');
		}
		unset($this->session->data['language_return']);
		$this->response->redirect($redirect);
	}
}
