<?php
class ControllerExtensionTotalVoucher extends Controller {
	public function index() {
		if ($this->config->get('total_voucher_status')) {
			$this->load->language('extension/total/voucher');

			if (isset($this->session->data['voucher'])) {
				$data['voucher'] = $this->session->data['voucher'];
			} else {
				$data['voucher'] = '';
			}

			return $this->load->view('extension/total/voucher', $data);
		}
	}

	public function voucher() {
		$this->load->language('extension/total/voucher');

		$json = array();

		$this->load->model('extension/total/voucher');

		if (isset($this->request->post['voucher'])) {
			$voucher = $this->request->post['voucher'];
		} else {
			$voucher = '';
		}

		$voucher_info = $this->model_extension_total_voucher->getVoucher($voucher);

		if (empty($this->request->post['voucher'])) {
			$json['error'] = $this->language->get('error_empty');
		} elseif ($voucher_info) {
			$this->session->data['voucher'] = $this->request->post['voucher'];

			$this->session->data['success'] = $this->language->get('text_success');

			$json['redirect'] = $this->url->link('checkout/cart');
		} else {
			$json['error'] = $this->language->get('error_voucher');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function send($route, $args, $output) {
		$order_id = isset($args[0]) ? (int)$args[0] : 0;
		$old_status_id = 0;
		$new_status_id = 0;
		$context = $this->registry->get('codecart_order_transition');

		if (is_array($context) && isset($context['order_id'], $context['old_order_status_id'], $context['new_order_status_id']) && (int)$context['order_id'] === $order_id) {
			$order_id = (int)$context['order_id'];
			$old_status_id = (int)$context['old_order_status_id'];
			$new_status_id = (int)$context['new_order_status_id'];
		} elseif (is_array($output) && isset($output['order_id'], $output['old_order_status_id'], $output['new_order_status_id'])) {
			$order_id = (int)$output['order_id'];
			$old_status_id = (int)$output['old_order_status_id'];
			$new_status_id = (int)$output['new_order_status_id'];
		}

		if (!$order_id) {
			return;
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return;
		}

		$complete_statuses = array_map('intval', (array)$this->config->get('config_complete_status'));

		if (!$new_status_id) {
			$new_status_id = (int)$order_info['order_status_id'];
		}

		// Send voucher mail only when the order actually enters a complete status.
		// Repeated complete -> complete history updates must not resend gift vouchers.
		if (!in_array($old_status_id, $complete_statuses, true) && in_array($new_status_id, $complete_statuses, true)) {
			$voucher_query = $this->db->query("SELECT *, vtd.name AS theme FROM `" . DB_PREFIX . "voucher` v LEFT JOIN " . DB_PREFIX . "voucher_theme vt ON (v.voucher_theme_id = vt.voucher_theme_id) LEFT JOIN " . DB_PREFIX . "voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE v.order_id = '" . (int)$order_info['order_id'] . "' AND vtd.language_id = '" . (int)$order_info['language_id'] . "'");

			if ($voucher_query->num_rows) {
				// Send out any gift voucher mails
				$language = new Language($order_info['language_code']);
				$language->load($order_info['language_code']);
				$language->load('mail/voucher');

				foreach ($voucher_query->rows as $voucher) {
					// HTML Mail
					$data = array();

					$data['title'] = sprintf($language->get('text_subject'), $voucher['from_name']);

					$data['text_greeting'] = sprintf($language->get('text_greeting'), $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']));
					$data['text_from'] = sprintf($language->get('text_from'), $voucher['from_name']);
					$data['text_message'] = $language->get('text_message');
					$data['text_redeem'] = sprintf($language->get('text_redeem'), $voucher['code']);
					$data['text_footer'] = $language->get('text_footer');

					if (is_file(DIR_IMAGE . $voucher['image'])) {
						$data['image'] = $this->config->get('config_url') . 'image/' . $voucher['image'];
					} else {
						$data['image'] = '';
					}

					$data['store_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
					$data['store_url'] = $this->url->link('common/home', '', true);
					$email_logo = trim((string)$this->config->get('config_email_logo'));
					if ($email_logo === '' || !is_file(DIR_IMAGE . $email_logo)) { $email_logo = trim((string)$this->config->get('config_logo')); }
					$email_logo_asset = class_exists('CodeCart\Core\EmailLogo') ? \CodeCart\Core\EmailLogo::prepare($email_logo, $data['store_url']) : array('url' => rtrim($data['store_url'], '/') . '/image/' . str_replace(' ', '%20', str_replace('\\', '/', $email_logo)), 'width' => 300, 'height' => 80, 'format' => '');
					$data['logo'] = $email_logo_asset['url']; $data['logo_width'] = (int)$email_logo_asset['width']; $data['logo_height'] = (int)$email_logo_asset['height'];

					$data['store_name'] = $order_info['store_name'];
					$data['store_url'] = $order_info['store_url'];
					$data['message'] = nl2br($voucher['message']);

					$mail = new Mail($this->config->get('config_mail_engine'));
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

					$mail->setTo($voucher['to_email']);
					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
					$mail->setSubject(html_entity_decode(sprintf($language->get('text_subject'), $voucher['from_name']), ENT_QUOTES, 'UTF-8'));
					$mail->setHtml($this->load->view('mail/voucher', $data));
					\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'voucher:' . (isset($voucher['voucher_id']) ? (int)$voucher['voucher_id'] : 0) . ':order:' . (int)$order_info['order_id']);
				}
			}
		}
	}
}
