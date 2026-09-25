<?php
class ControllerMailAffiliate extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->language('mail/affiliate');
        
		$data['text_welcome'] = sprintf($this->language->get('text_welcome'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$data['text_login'] = $this->language->get('text_login');
		$data['text_approval'] = $this->language->get('text_approval');
		$data['text_service'] = $this->language->get('text_service');
		$data['text_thanks'] = $this->language->get('text_thanks');

		$this->load->model('account/customer_group');
		
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $args[1]['customer_group_id'];
		}
		
		$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);
		
		if ($customer_group_info) {
			$data['approval'] = ($this->config->get('config_affiliate_approval') || $customer_group_info['approval']);
		} else {
			$data['approval'] = '';
		}		
		
		$data['login'] = $this->url->link('affiliate/login', '', true);
		$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$data['store_url'] = $this->url->link('common/home', '', true);
		$data['title'] = html_entity_decode(sprintf($this->language->get('text_subject'), $data['store']), ENT_QUOTES, 'UTF-8');
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

		if ($this->customer->isLogged()) {
			$mail->setTo($this->customer->getEmail());
		} else {
			$mail->setTo($args[1]['email']);
		}
		
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
		$mail->setText($this->load->view('mail/affiliate', $data));
		$mail->setHtml($this->load->view('mail/affiliate_html', $data));
		\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'affiliate-register');
 	}
	
	public function alert(&$route, &$args, &$output) {
		// Send to main admin email if new affiliate email is enabled
		if (in_array('affiliate', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/affiliate');
			
			$data['text_signup'] = $this->language->get('text_signup');
			$data['text_website'] = $this->language->get('text_website');
			$data['text_firstname'] = $this->language->get('text_firstname');
			$data['text_lastname'] = $this->language->get('text_lastname');
			$data['text_customer_group'] = $this->language->get('text_customer_group');
			$data['text_email'] = $this->language->get('text_email');
			$data['text_telephone'] = $this->language->get('text_telephone');
			
			if ($this->customer->isLogged()) {
				$customer_group_id = $this->customer->getGroupId();
			
				$data['firstname'] = $this->customer->getFirstName();
				$data['lastname'] = $this->customer->getLastName();
				$data['email'] = $this->customer->getEmail();
				$data['telephone'] = $this->customer->getTelephone();
			} else {	
				$customer_group_id = $args[1]['customer_group_id'];
				
				$data['firstname'] = $args[1]['firstname'];
				$data['lastname'] = $args[1]['lastname'];	
				$data['email'] = $args[1]['email'];
				$data['telephone'] = $args[1]['telephone'];		
			}
			
			$data['website'] = html_entity_decode($args[1]['website'], ENT_QUOTES, 'UTF-8');
			$data['company'] = $args[1]['company'];
							
			$this->load->model('account/customer_group');

			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);
			
			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}
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
			$mail->setSubject(html_entity_decode($this->language->get('text_new_affiliate'), ENT_QUOTES, 'UTF-8'));
			$mail->setText($this->load->view('mail/affiliate_alert', $data));
			$mail->setHtml($this->load->view('mail/affiliate_alert_html', $data));
			\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'affiliate-alert:main');

			// Send to additional alert emails if new affiliate email is enabled, once per mailbox.
			$emails = \CodeCart\Core\MailDelivery::additionalRecipients($this->config->get('config_email'), $this->config->get('config_mail_alert_email'));
			foreach ($emails as $email) {
				$mail->setTo($email);
				\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'affiliate-alert:extra');
			}
		}		
	}

}
