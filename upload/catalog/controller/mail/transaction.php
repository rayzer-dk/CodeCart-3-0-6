<?php
class ControllerMailTransaction extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->language('mail/transaction');

		$this->load->model('account/customer');
		
		$customer_info = $this->model_account_customer->getCustomer($args[0]);

		if ($customer_info) {
			$data['text_received'] = sprintf($this->language->get('text_received'), $this->config->get('config_name'));
			$data['text_amount'] = $this->language->get('text_amount');
			$data['text_total'] = $this->language->get('text_total');
			
			$data['amount'] = $this->currency->format($args[2], $this->config->get('config_currency'));
			$data['total'] = $this->currency->format($this->model_account_customer->getTransactionTotal($args[0]), $this->config->get('config_currency'));
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
	
			$mail->setTo($customer_info['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($data['store']);
			$mail->setSubject($data['title']);
			$mail->setText($this->load->view('mail/transaction', $data));
			$mail->setHtml($this->load->view('mail/transaction_html', $data));
			\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'customer-transaction');
		}
	}
}



