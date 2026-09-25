<?php
class ControllerMailForgotten extends Controller {
	public function index(&$route, &$args, &$output) {			            
		$this->load->language('mail/forgotten');

		$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$data['text_change'] = $this->language->get('text_change');
		$data['text_ip'] = $this->language->get('text_ip');
		
		$data['reset'] = str_replace('&amp;', '&', $this->url->link('account/reset', 'code=' . $args[1], true));
		$data['ip'] = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : '';
		$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$data['store_url'] = $this->url->link('common/home', '', true);

		$email_logo = trim((string)$this->config->get('config_email_logo'));
		if ($email_logo === '' || !is_file(DIR_IMAGE . $email_logo)) {
			$email_logo = trim((string)$this->config->get('config_logo'));
		}
		$email_logo_asset = class_exists('CodeCart\Core\EmailLogo')
			? \CodeCart\Core\EmailLogo::prepare($email_logo, $data['store_url'])
			: array('url' => rtrim($data['store_url'], '/') . '/image/' . str_replace(' ', '%20', str_replace('\\', '/', $email_logo)), 'width' => 300, 'height' => 80, 'format' => '');
		$data['logo'] = $email_logo_asset['url'];
		$data['logo_width'] = (int)$email_logo_asset['width'];
		$data['logo_height'] = (int)$email_logo_asset['height'];
		$data['title'] = html_entity_decode(sprintf($this->language->get('text_subject'), $data['store']), ENT_QUOTES, 'UTF-8');
		
		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($args[0]);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$mail->setSubject($data['title']);
		$mail->setText($this->load->view('mail/forgotten', $data));
		$mail->setHtml($this->load->view('mail/forgotten_html', $data));
		\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'customer-forgotten:' . (string)$args[0]);
	}

}
