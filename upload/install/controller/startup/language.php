<?php
class ControllerStartupLanguage extends Controller {
	public function index() {
		$available = array();
		foreach (glob(DIR_LANGUAGE . '*', GLOB_ONLYDIR) as $directory) {
			$code = basename($directory);
			if (preg_match('/^[a-z]{2}-[a-z]{2}$/i', $code)) {
				$available[strtolower($code)] = $code;
			}
		}

		$default = strtolower((string)$this->config->get('language_default'));
		if (!isset($available[$default])) {
			$default = isset($available['uk-ua']) ? 'uk-ua' : (string)array_key_first($available);
		}

		$selected = $default;
		// A language switch is intentionally accepted before the requested route
		// is dispatched.  This keeps the selector reliable on every installer
		// page, including upgrade pages where the old POST-only flow could be
		// immediately replaced by the pre-action default.
		$requested = '';
		if (isset($this->request->get['language'])) {
			$requested = strtolower(basename((string)$this->request->get['language']));
		} elseif (isset($this->request->get['code'])) {
			$requested = strtolower(basename((string)$this->request->get['code']));
		} elseif (isset($this->request->post['language'])) {
			$requested = strtolower(basename((string)$this->request->post['language']));
		} elseif (isset($this->request->post['code'])) {
			$requested = strtolower(basename((string)$this->request->post['code']));
		}
		if ($requested !== '' && isset($available[$requested])) {
			$selected = $requested;
		} elseif (isset($this->session->data['language'])) {
			$session_code = strtolower(basename((string)$this->session->data['language']));
			if (isset($available[$session_code])) {
				$selected = $session_code;
			}
		} elseif (!empty($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
			$candidates = array();
			foreach (explode(',', (string)$this->request->server['HTTP_ACCEPT_LANGUAGE']) as $item) {
				$parts = array_map('trim', explode(';', $item));
				$tag = strtolower(str_replace('_', '-', $parts[0]));
				$q = 1.0;
				if (isset($parts[1]) && preg_match('/^q=([0-9.]+)$/i', $parts[1], $match)) {
					$q = max(0.0, min(1.0, (float)$match[1]));
				}
				if ($tag !== '') { $candidates[] = array('tag' => $tag, 'q' => $q); }
			}
			usort($candidates, function($a, $b) { return $a['q'] < $b['q'] ? 1 : ($a['q'] > $b['q'] ? -1 : 0); });
			foreach ($candidates as $candidate) {
				$tag = $candidate['tag'];
				if (isset($available[$tag])) { $selected = $tag; break; }
				$primary = substr($tag, 0, 2);
				foreach ($available as $code => $original) {
					if (substr($code, 0, 2) === $primary) { $selected = $code; break 2; }
				}
			}
		}

		$this->session->data['language'] = $available[$selected];
		$language = new Language($available[$selected]);
		$language->load($available[$selected]);
		$this->registry->set('language', $language);
	}
}
