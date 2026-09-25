<?php
class ControllerMailTransaction extends Controller {
	public function index($route, $args, $output) {
		if (isset($args[0])) {
			$customer_id = $args[0];
		} else {
			$customer_id = '';
		}
		
		if (isset($args[1])) {
			$description = $args[1];
		} else {
			$description = '';
		}		
		
		if (isset($args[2])) {
			$amount = $args[2];
		} else {
			$amount = '';
		}
		
		if (isset($args[3])) {
			$order_id = $args[3];
		} else {
			$order_id = '';
		}
			
		$this->load->model('customer/customer');
						
		$customer_info = $this->model_customer_customer->getCustomer($customer_id);

		if ($customer_info) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

			if ($store_info) {
				$store_name = $store_info['name'];
				$store_url = $store_info['url'];
			} else {
				$store_name = $this->config->get('config_name');
				$store_url = HTTP_CATALOG;
			}

			$this->load->model('localisation/language');
			$language_info = $this->model_localisation_language->getLanguage($customer_info['language_id']);
			$language_code = $language_info ? $language_info['code'] : $this->config->get('config_language');
			$language = new Language($language_code);
			$language->load($language_code);
			$language->load('mail/transaction');

			$data['text_received'] = sprintf($language->get('text_received'), $this->currency->format($amount, $this->config->get('config_currency')));
			$data['text_total'] = sprintf($language->get('text_total'), $this->currency->format($this->model_customer_customer->getTransactionTotal($customer_id), $this->config->get('config_currency')));
			$data['text_credit'] = $language->get('text_credit');
			$data['store'] = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');
			$data['store_url'] = $store_url;
			$data['title'] = html_entity_decode(sprintf($language->get('text_subject'), $data['store']), ENT_QUOTES, 'UTF-8');
			
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
			\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'customer-transaction-admin');
		}
	}		
}	
