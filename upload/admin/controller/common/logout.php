<?php
class ControllerCommonLogout extends Controller {
	public function index() {
		unset($this->session->data['codecart_admin_device_bypass']);
		$this->user->logout();

		unset($this->session->data['user_token'], $this->session->data['codecart_totp_verified_user_id'], $this->session->data['codecart_totp_redirect'], $this->session->data['codecart_totp_pending_secret'], $this->session->data['codecart_totp_recovery_codes'], $this->session->data['codecart_totp_enroll']);

		$this->response->redirect($this->url->link('common/login', '', true));
	}
}