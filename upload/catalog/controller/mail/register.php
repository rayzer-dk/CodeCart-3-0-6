<?php
class ControllerMailRegister extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->language('mail/register');

		$data['text_welcome'] = sprintf($this->language->get('text_welcome'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$data['text_login'] = $this->language->get('text_login');
		$data['text_approval'] = $this->language->get('text_approval');
		$data['text_service'] = $this->language->get('text_service');
		$data['text_thanks'] = $this->language->get('text_thanks');

		$this->load->model('account/customer_group');
			
		if (isset($args[0]['customer_group_id'])) {
			$customer_group_id = $args[0]['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}
					
		$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);
		
		if ($customer_group_info) {
			$data['approval'] = $customer_group_info['approval'];
		} else {
			$data['approval'] = '';
		}
			
		$data['login'] = $this->url->link('account/login', '', true);		
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

		$subject = sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$data['title'] = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($args[0]['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$mail->setSubject($data['title']);
		$mail->setText($this->load->view('mail/register', $data));
		$mail->setHtml($this->load->view('mail/register_html', $data));
		\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'customer-register:' . (isset($args[0]['email']) ? (string)$args[0]['email'] : ''));
	}
	
	public function alert(&$route, &$args, &$output) {
		// Send to main admin email if new account email is enabled
		if (in_array('account', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/register');
			
			$data['text_signup'] = $this->language->get('text_signup');
			$data['text_firstname'] = $this->language->get('text_firstname');
			$data['text_lastname'] = $this->language->get('text_lastname');
			$data['text_customer_group'] = $this->language->get('text_customer_group');
			$data['text_email'] = $this->language->get('text_email');
			$data['text_telephone'] = $this->language->get('text_telephone');
			
			$data['firstname'] = $args[0]['firstname'];
			$data['lastname'] = $args[0]['lastname'];
			
			$this->load->model('account/customer_group');
			
			if (isset($args[0]['customer_group_id'])) {
				$customer_group_id = $args[0]['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}
			
			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);
			
			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}
			
			$data['email'] = $args[0]['email'];
			$data['telephone'] = $args[0]['telephone'];
			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			$data['store_url'] = $this->url->link('common/home', '', true);
			$email_logo = trim((string)$this->config->get('config_email_logo'));
			if ($email_logo === '' || !is_file(DIR_IMAGE . $email_logo)) { $email_logo = trim((string)$this->config->get('config_logo')); }
			$email_logo_asset = class_exists('CodeCart\Core\EmailLogo') ? \CodeCart\Core\EmailLogo::prepare($email_logo, $data['store_url']) : array('url' => rtrim($data['store_url'], '/') . '/image/' . str_replace(' ', '%20', str_replace('\\', '/', $email_logo)), 'width' => 300, 'height' => 80, 'format' => '');
			$data['logo'] = $email_logo_asset['url']; $data['logo_width'] = (int)$email_logo_asset['width']; $data['logo_height'] = (int)$email_logo_asset['height'];

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($this->language->get('text_new_customer'), ENT_QUOTES, 'UTF-8'));
			$mail->setText($this->load->view('mail/register_alert', $data));
			$mail->setHtml($this->load->view('mail/register_alert_html', $data));
			\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'customer-register-alert:main');

			// Send to additional alert emails if new account email is enabled, once per mailbox.
			$emails = \CodeCart\Core\MailDelivery::additionalRecipients($this->config->get('config_email'), $this->config->get('config_mail_alert_email'));
			foreach ($emails as $email) {
				$mail->setTo($email);
				\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'customer-register-alert:extra');
			}
		}	
	}

}
