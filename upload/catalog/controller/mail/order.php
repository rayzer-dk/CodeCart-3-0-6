<?php
class ControllerMailOrder extends Controller {
	public function index(&$route, &$args) {
		$transition = $this->resolveCommittedTransition($args, null);

		if (!$transition) {
			return;
		}

		$order_info = $this->model_checkout_order->getOrder($transition['order_id']);

		if (!$order_info || !$transition['new_order_status_id']) {
			return;
		}

		if (!$transition['old_order_status_id']) {
			$this->add($order_info, $transition['new_order_status_id'], $transition['comment'], $transition['notify']);
		} elseif ($transition['notify']) {
			$this->edit($order_info, $transition['new_order_status_id'], $transition['comment']);
		}
	}

	public function add($order_info, $order_status_id, $comment, $notify) {
		$this->load->helper('codecart_checkout');
		if (empty($order_info['customer_id']) && isset($order_info['email']) && (string)$order_info['email'] === codecart_checkout_email_fallback($this->config)) {
			return;
		}

		// Check for any downloadable products
		$download_status = false;

		$order_products = $this->model_checkout_order->getOrderProducts($order_info['order_id']);

		$product_ids = array();
		foreach ($order_products as $order_product) {
			$product_id = isset($order_product['product_id']) ? (int)$order_product['product_id'] : 0;
			if ($product_id > 0) {
				$product_ids[$product_id] = $product_id;
			}
		}

		if ($product_ids) {
			$product_download_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_download` WHERE product_id IN (" . implode(',', array_map('intval', $product_ids)) . ")");
			$download_status = !empty($product_download_query->row['total']);
		}
		
		// Load the language for any mails that might be required to be sent out
		$language = new Language($order_info['language_code']);
		$language->load($order_info['language_code']);
		$language->load('mail/order_add');

		// HTML Mail
		$data['title'] = sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']);

		$data['text_greeting'] = sprintf($language->get('text_greeting'), $order_info['store_name']);
		$data['text_link'] = $language->get('text_link');
		$data['text_download'] = $language->get('text_download');
		$data['text_order_detail'] = $language->get('text_order_detail');
		$data['text_instruction'] = $language->get('text_instruction');
		$data['text_order_id'] = $language->get('text_order_id');
		$data['text_date_added'] = $language->get('text_date_added');
		$data['text_payment_method'] = $language->get('text_payment_method');
		$data['text_shipping_method'] = $language->get('text_shipping_method');
		$data['text_email'] = $language->get('text_email');
		$data['text_telephone'] = $language->get('text_telephone');
		$data['text_ip'] = $language->get('text_ip');
		$data['text_order_status'] = $language->get('text_order_status');
		$data['text_payment_address'] = $language->get('text_payment_address');
		$data['text_shipping_address'] = $language->get('text_shipping_address');
		$data['text_product'] = $language->get('text_product');
		$data['text_model'] = $language->get('text_model');
		$data['text_quantity'] = $language->get('text_quantity');
		$data['text_price'] = $language->get('text_price');
		$data['text_total'] = $language->get('text_total');
		$data['text_footer'] = $language->get('text_footer');

		$email_logo = trim((string)$this->config->get('config_email_logo'));
		if ($email_logo === '' || !is_file(DIR_IMAGE . $email_logo)) {
			$email_logo = trim((string)$this->config->get('config_logo'));
		}

		$email_logo_asset = class_exists('CodeCart\\Core\\EmailLogo')
			? \CodeCart\Core\EmailLogo::prepare($email_logo, $order_info['store_url'])
			: array('url' => $order_info['store_url'] . 'image/' . str_replace(' ', '%20', str_replace('\\', '/', $email_logo)), 'width' => 300, 'height' => 80, 'format' => '');
		$data['logo'] = $email_logo_asset['url'];
		$data['logo_width'] = (int)$email_logo_asset['width'];
		$data['logo_height'] = (int)$email_logo_asset['height'];
		$data['store_name'] = $order_info['store_name'];
		$data['store_url'] = $order_info['store_url'];
		$data['customer_id'] = $order_info['customer_id'];
		$data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];

		if ($download_status) {
			$data['download'] = $order_info['store_url'] . 'index.php?route=account/download';
		} else {
			$data['download'] = '';
		}

		$data['order_id'] = $order_info['order_id'];
		$data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
		$data['payment_method'] = $order_info['payment_method'];
		$data['shipping_method'] = $order_info['shipping_method'];
		$data['email'] = $order_info['email'];
		$data['telephone'] = $order_info['telephone'];
		$data['ip'] = $order_info['ip'];

		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");
	
		if ($order_status_query->num_rows) {
			$data['order_status'] = $order_status_query->row['name'];
		} else {
			$data['order_status'] = '';
		}

		if ($comment && $notify) {
			$data['comment'] = nl2br($comment);
		} else {
			$data['comment'] = '';
		}

		if ($order_info['payment_address_format']) {
			$format = $order_info['payment_address_format'];
		} else {
			$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => $order_info['payment_firstname'],
			'lastname'  => $order_info['payment_lastname'],
			'company'   => $order_info['payment_company'],
			'address_1' => $order_info['payment_address_1'],
			'address_2' => $order_info['payment_address_2'],
			'city'      => $order_info['payment_city'],
			'postcode'  => $order_info['payment_postcode'],
			'zone'      => $order_info['payment_zone'],
			'zone_code' => $order_info['payment_zone_code'],
			'country'   => $order_info['payment_country']
		);

		$data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

		if ($order_info['shipping_address_format']) {
			$format = $order_info['shipping_address_format'];
		} else {
			$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => $order_info['shipping_firstname'],
			'lastname'  => $order_info['shipping_lastname'],
			'company'   => $order_info['shipping_company'],
			'address_1' => $order_info['shipping_address_1'],
			'address_2' => $order_info['shipping_address_2'],
			'city'      => $order_info['shipping_city'],
			'postcode'  => $order_info['shipping_postcode'],
			'zone'      => $order_info['shipping_zone'],
			'zone_code' => $order_info['shipping_zone_code'],
			'country'   => $order_info['shipping_country']
		);

		$data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

		$this->load->model('tool/upload');

		// Products. Resolve product images in one query so the email remains fast for large orders.
		$data['products'] = array();
		$mail_product_map = array();
		if ($product_ids) {
			$product_query = $this->db->query("SELECT product_id, image FROM `" . DB_PREFIX . "product` WHERE product_id IN (" . implode(',', array_map('intval', $product_ids)) . ")");
			foreach ($product_query->rows as $row) {
				$mail_product_map[(int)$row['product_id']] = isset($row['image']) ? (string)$row['image'] : '';
			}
			$this->load->model('tool/image');
		}

		foreach ($order_products as $order_product) {
			$option_data = array();

			$order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);

			foreach ($order_options as $order_option) {
				if ($order_option['type'] != 'file') {
					$value = $order_option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);

					if ($upload_info) {
						$value = $upload_info['name'];
					} else {
						$value = '';
					}
				}

				$option_data[] = array(
					'name'  => $order_option['name'],
					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
				);
			}

			$product_id = isset($order_product['product_id']) ? (int)$order_product['product_id'] : 0;
			$product_image = ($product_id > 0 && isset($mail_product_map[$product_id])) ? $mail_product_map[$product_id] : '';
			$product_thumb = '';
			if ($product_image !== '') {
				$product_thumb = (string)$this->model_tool_image->display($product_image, 96, 96);
			}
			$product_href = $product_id > 0 ? rtrim((string)$order_info['store_url'], '/') . '/index.php?route=product/product&product_id=' . $product_id : '';

			$data['products'][] = array(
				'product_id' => $product_id,
				'name'       => $order_product['name'],
				'model'      => $order_product['model'],
				'image'      => $product_thumb,
				'href'       => $product_href,
				'option'     => $option_data,
				'quantity'   => $order_product['quantity'],
				'price'      => $this->currency->format($order_product['price'] + ($this->config->get('config_tax') ? $order_product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
				'total'      => $this->currency->format($order_product['total'] + ($this->config->get('config_tax') ? ($order_product['tax'] * $order_product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
			);
		}

		// Vouchers
		$data['vouchers'] = array();

		$order_vouchers = $this->model_checkout_order->getOrderVouchers($order_info['order_id']);

		foreach ($order_vouchers as $order_voucher) {
			$data['vouchers'][] = array(
				'description' => $order_voucher['description'],
				'amount'      => $this->currency->format($order_voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
			);
		}

		// Order Totals
		$data['totals'] = array();
		
		$order_totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);

		foreach ($order_totals as $order_total) {
			$data['totals'][] = array(
				'title' => $order_total['title'],
				'text'  => $this->currency->format($order_total['value'], $order_info['currency_code'], $order_info['currency_value']),
			);
		}
	
		$this->load->model('setting/setting');
		
		$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
		
		if (!$from) {
			$from = $this->config->get('config_email');
		}
		
		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($order_info['email']);
		$mail->setFrom($from);
		$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(html_entity_decode(sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), ENT_QUOTES, 'UTF-8'));
		$mail->setHtml($this->load->view('mail/order_add', $data));
		\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'order:' . (isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0));
	}
	
	public function edit($order_info, $order_status_id, $comment) {
		$this->load->helper('codecart_checkout');
		if (empty($order_info['customer_id']) && isset($order_info['email']) && (string)$order_info['email'] === codecart_checkout_email_fallback($this->config)) {
			return;
		}

		$language = new Language($order_info['language_code']);
		$language->load($order_info['language_code']);
		$language->load('mail/order_edit');

		$data['text_order_id'] = $language->get('text_order_id');
		$data['text_date_added'] = $language->get('text_date_added');
		$data['text_order_status'] = $language->get('text_order_status');
		$data['text_link'] = $language->get('text_link');
		$data['text_comment'] = $language->get('text_comment');
		$data['text_footer'] = $language->get('text_footer');

		$data['order_id'] = $order_info['order_id'];
		$data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
		
		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");
	
		if ($order_status_query->num_rows) {
			$data['order_status'] = $order_status_query->row['name'];
		} else {
			$data['order_status'] = '';
		}

		if ($order_info['customer_id']) {
			$data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];
		} else {
			$data['link'] = '';
		}

		$data['comment'] = strip_tags($comment);
		$data['store'] = html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8');
		$data['store_url'] = $order_info['store_url'];

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
		$data['title'] = html_entity_decode(sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), ENT_QUOTES, 'UTF-8');

		$this->load->model('setting/setting');
		
		$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
		
		if (!$from) {
			$from = $this->config->get('config_email');
		}
		
		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($order_info['email']);
		$mail->setFrom($from);
		$mail->setSender($data['store']);
		$mail->setSubject($data['title']);
		$mail->setText($this->load->view('mail/order_edit', $data));
		$mail->setHtml($this->load->view('mail/order_edit_html', $data));
		\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'order:' . (isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0));
	}
	
	// Admin Alert Mail
	public function alert(&$route, &$args) {
		$transition = $this->resolveCommittedTransition($args, null);

		if (!$transition) {
			return;
		}

		$order_id = $transition['order_id'];
		$order_status_id = $transition['new_order_status_id'];
		$comment = $transition['comment'];
		$notify = $transition['notify'];
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info && !$transition['old_order_status_id'] && $order_status_id && in_array('order', (array)$this->config->get('config_mail_alert'), true)) {	
			$this->load->language('mail/order_alert');
			
			// HTML Mail
			$data['text_received'] = $this->language->get('text_received');
			$data['text_order_id'] = $this->language->get('text_order_id');
			$data['text_date_added'] = $this->language->get('text_date_added');
			$data['text_order_status'] = $this->language->get('text_order_status');
			$data['text_product'] = $this->language->get('text_product');
			$data['text_model'] = $this->language->get('text_model');
			$data['text_quantity'] = $this->language->get('text_quantity');
			$data['text_total'] = $this->language->get('text_total');
			$data['text_comment'] = $this->language->get('text_comment');
			
			$data['order_id'] = $order_info['order_id'];
			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

			if ($order_status_query->num_rows) {
				$data['order_status'] = $order_status_query->row['name'];
			} else {
				$data['order_status'] = '';
			}

			$this->load->model('tool/upload');
			
			$data['products'] = array();

			$order_products = $this->model_checkout_order->getOrderProducts($order_id);

			foreach ($order_products as $order_product) {
				$option_data = array();
				
				$order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);
				
				foreach ($order_options as $order_option) {
					if ($order_option['type'] != 'file') {
						$value = $order_option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);
	
						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$option_data[] = array(
						'name'  => $order_option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);					
				}
					
				$data['products'][] = array(
					'name'     => $order_product['name'],
					'model'    => $order_product['model'],
					'quantity' => $order_product['quantity'],
					'option'   => $option_data,
					'total'    => html_entity_decode($this->currency->format($order_product['total'] + ($this->config->get('config_tax') ? ($order_product['tax'] * $order_product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8')
				);
			}
			
			$data['vouchers'] = array();
			
			$order_vouchers = $this->model_checkout_order->getOrderVouchers($order_id);

			foreach ($order_vouchers as $order_voucher) {
				$data['vouchers'][] = array(
					'description' => $order_voucher['description'],
					'amount'      => html_entity_decode($this->currency->format($order_voucher['amount'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8')
				);					
			}

			$data['totals'] = array();
			
			$order_totals = $this->model_checkout_order->getOrderTotals($order_id);

			foreach ($order_totals as $order_total) {
				$data['totals'][] = array(
					'title' => $order_total['title'],
					'value' => html_entity_decode($this->currency->format($order_total['value'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8')
				);
			}

			$data['comment'] = strip_tags($order_info['comment']);

			$data['store'] = html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8');
			$data['store_url'] = $order_info['store_url'];
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

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), $this->config->get('config_name'), $order_info['order_id']), ENT_QUOTES, 'UTF-8'));
			$mail->setText($this->load->view('mail/order_alert', $data));
			$mail->setHtml($this->load->view('mail/order_alert_html', $data));
			\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'order:' . (isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0));

			// Send to additional alert emails, once per mailbox.
			$emails = \CodeCart\Core\MailDelivery::additionalRecipients($this->config->get('config_email'), $this->config->get('config_mail_alert_email'));
			foreach ($emails as $email) {
				$mail->setTo($email);
				\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'order:' . (isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0));
			}
		}
	}

	private function resolveCommittedTransition(array $args, $output) {
		$context = $this->registry->get('codecart_order_transition');

		if (is_array($context) && isset($context['order_id'], $context['old_order_status_id'], $context['new_order_status_id']) && (!isset($args[0]) || (int)$context['order_id'] === (int)$args[0])) {
			return array(
				'order_id'            => (int)$context['order_id'],
				'old_order_status_id' => (int)$context['old_order_status_id'],
				'new_order_status_id' => (int)$context['new_order_status_id'],
				'comment'             => isset($context['comment']) ? (string)$context['comment'] : '',
				'notify'              => !empty($context['notify'])
			);
		}

		if (is_array($output) && isset($output['order_id'], $output['old_order_status_id'], $output['new_order_status_id'])) {
			return array(
				'order_id'            => (int)$output['order_id'],
				'old_order_status_id' => (int)$output['old_order_status_id'],
				'new_order_status_id' => (int)$output['new_order_status_id'],
				'comment'             => isset($output['comment']) ? (string)$output['comment'] : '',
				'notify'              => !empty($output['notify'])
			);
		}

		// Compatibility fallback for an overridden order model that does not yet
		// return transition metadata. AFTER events can reconstruct the committed
		// transition from the two latest order history rows.
		$order_id = isset($args[0]) ? (int)$args[0] : 0;
		if (!$order_id) {
			return false;
		}

		$history = $this->db->query("SELECT order_status_id, notify, comment FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . $order_id . "' ORDER BY order_history_id DESC LIMIT 2");
		if (!$history->num_rows) {
			return false;
		}

		return array(
			'order_id'            => $order_id,
			'old_order_status_id' => isset($history->rows[1]) ? (int)$history->rows[1]['order_status_id'] : 0,
			'new_order_status_id' => (int)$history->rows[0]['order_status_id'],
			'comment'             => (string)$history->rows[0]['comment'],
			'notify'              => !empty($history->rows[0]['notify'])
		);
	}

}
