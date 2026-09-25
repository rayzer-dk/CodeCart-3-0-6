<?php
class ControllerMarketplaceMarketplace extends Controller {
	public function index() {
		$this->load->language('marketplace/marketplace');
		$this->session->data['warning'] = $this->language->get('text_remote_disabled');
		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function info() {
		$this->index();
	}

	public function purchase() {
		$this->remoteDisabledJson();
	}

	public function download() {
		$this->remoteDisabledJson();
	}

	public function addComment() {
		$this->remoteDisabledJson();
	}

	public function comment() {
		$this->remoteDisabledJson();
	}

	public function reply() {
		$this->remoteDisabledJson();
	}

	private function remoteDisabledJson() {
		$this->load->language('marketplace/marketplace');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array(
			'error' => $this->language->get('text_remote_disabled')
		)));
	}
}
