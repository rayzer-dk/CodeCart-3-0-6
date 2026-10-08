<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		$this->load->language('common/header');

		$data['title'] = $this->document->getTitle();
		$data['description'] = $this->document->getDescription();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts();
		$data['base'] = HTTP_SERVER;
		$data['version'] = defined('VERSION') ? VERSION : '3.0.6.0';
		$data['package_build'] = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : '2.0.4';

		$code = isset($this->session->data['language']) ? strtolower((string)$this->session->data['language']) : 'uk-ua';
		$data['lang'] = substr($code, 0, 2);
		$data['direction'] = 'ltr';

		return $this->load->view('common/header', $data);
	}
}
