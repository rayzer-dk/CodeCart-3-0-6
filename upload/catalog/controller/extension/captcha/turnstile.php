<?php
class ControllerExtensionCaptchaTurnstile extends Controller {
	public function index($error = array()) {
		$this->load->language('extension/captcha/turnstile');

		$data['error_captcha'] = isset($error['captcha']) ? $error['captcha'] : '';
		$data['site_key'] = htmlspecialchars((string)$this->config->get('captcha_turnstile_key'), ENT_QUOTES, 'UTF-8');
		$data['route'] = isset($this->request->get['route']) ? (string)$this->request->get['route'] : '';

		return $this->load->view('extension/captcha/turnstile', $data);
	}

	public function validate() {
		$this->load->language('extension/captcha/turnstile');

		$token = isset($this->request->post['cf-turnstile-response']) ? (string)$this->request->post['cf-turnstile-response'] : '';

		if ($token === '' || strlen($token) > 2048) {
			return $this->language->get('error_captcha');
		}

		$curl = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
			'secret'   => (string)$this->config->get('captcha_turnstile_secret'),
			'response' => $token,
			'remoteip' => codecart_client_ip()
		), '', '&'));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);
		\CodeCart\Core\TlsPolicy::applyCurl($curl);

		$response = curl_exec($curl);
		$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($curl);
		unset($curl);

		if (!is_string($response) || $status !== 200) {
			$this->log->write('Cloudflare Turnstile siteverify unavailable: HTTP ' . $status . ($curl_error !== '' ? ', ' . $curl_error : ''));

			return $this->language->get('error_unavailable');
		}

		$result = json_decode($response, true);

		if (!is_array($result) || empty($result['success'])) {
			return $this->language->get('error_captcha');
		}
	}
}
