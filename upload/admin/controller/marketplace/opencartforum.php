<?php
class ControllerMarketplaceOpencartforum extends Controller {
	public function index() {
		$this->load->language('marketplace/opencartforum');
		$this->session->data['warning'] = $this->language->get('text_remote_disabled');
		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function info() {
		$this->index();
	}
}
