<?php
class ControllerExtensionCaptchaGoogle extends Controller {
    public function index($error = array()) {
        $this->load->language('extension/captcha/google');

        if (isset($error['captcha'])) {
			$data['error_captcha'] = $error['captcha'];
		} else {
			$data['error_captcha'] = '';
		}

		$data['site_key'] = $this->config->get('captcha_google_key');

        $data['route'] = isset($this->request->get['route']) ? $this->request->get['route'] : ''; 

		return $this->load->view('extension/captcha/google', $data);
    }

    public function validate() {
		if (empty($this->session->data['gcapcha'])) {
			$this->load->language('extension/captcha/google');

			if (!isset($this->request->post['g-recaptcha-response'])) {
				return $this->language->get('error_captcha');
			}

			$curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
				'secret' => $this->config->get('captcha_google_secret'),
				'response' => $this->request->post['g-recaptcha-response'],
				'remoteip' => isset($this->request->server['REMOTE_ADDR']) ? $this->request->server['REMOTE_ADDR'] : ''
			), '', '&'));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			\CodeCart\Core\TlsPolicy::applyCurl($curl);

			$response = curl_exec($curl);
			$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
			unset($curl);

			$recaptcha = is_string($response) && $status === 200 ? json_decode($response, true) : array();

			if (!empty($recaptcha['success'])) {
				$this->session->data['gcapcha']	= true;
			} else {
				return $this->language->get('error_captcha');
			}
		}
    }
}
