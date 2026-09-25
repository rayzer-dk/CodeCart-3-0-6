<?php
class ModelCheckoutOrder extends Model {
	public function addOrder($data) {
		$idempotency = null;
		$idempotency_scope = '';
		$idempotency_key = '';
		$idempotency_owner = '';

		if (!empty($data['_idempotency']) && is_array($data['_idempotency'])) {
			$context = $data['_idempotency'];
			$idempotency_scope = isset($context['scope']) ? (string)$context['scope'] : 'checkout.order';
			$idempotency_key = isset($context['key']) ? (string)$context['key'] : '';
			$fingerprint = isset($context['fingerprint']) ? (string)$context['fingerprint'] : '';
			$ttl = isset($context['ttl']) ? (int)$context['ttl'] : 86400;

			if ($idempotency_key !== '') {
				$idempotency = new \CodeCart\Core\Idempotency($this->registry);
				$state = $idempotency->begin($idempotency_scope, $idempotency_key, $fingerprint, $ttl, 300);
				if (!$state['acquired']) {
					if ($state['status'] === 'completed' && is_array($state['result']) && !empty($state['result']['order_id'])) {
						return (int)$state['result']['order_id'];
					}
					throw new \RuntimeException('Order creation is already being processed.');
				}
				$idempotency_owner = isset($state['owner_token']) ? (string)$state['owner_token'] : '';
			}
		}

		$this->db->beginTransaction();
		try {

			$this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape(isset($data['fax']) ? $data['fax'] : '') . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . \CodeCart\Core\Money::normalize($data['total']) . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . \CodeCart\Core\Money::normalize($data['commission']) . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . \CodeCart\Core\Money::rate($data['currency_value']) . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" .  $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

			$order_id = $this->db->getLastId();

			if (!empty($this->session->data['codecart_delivery']) && is_array($this->session->data['codecart_delivery']) && $this->tableExists('codecart_order_delivery')) {
				$delivery = $this->session->data['codecart_delivery'];
				$this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_order_delivery` SET order_id='" . (int)$order_id . "', carrier_id='" . $this->db->escape(substr((string)(isset($delivery['carrier_id']) ? $delivery['carrier_id'] : ''), 0, 16)) . "', provider='" . $this->db->escape(substr((string)(isset($delivery['provider']) ? $delivery['provider'] : ''), 0, 24)) . "', carrier_name='" . $this->db->escape(substr((string)(isset($delivery['carrier_name']) ? $delivery['carrier_name'] : ''), 0, 128)) . "', city_external_id='" . $this->db->escape(substr((string)(isset($delivery['city_external_id']) ? $delivery['city_external_id'] : ''), 0, 128)) . "', city_name='" . $this->db->escape(substr((string)(isset($delivery['city_name']) ? $delivery['city_name'] : ''), 0, 191)) . "', branch_external_id='" . $this->db->escape(substr((string)(isset($delivery['branch_external_id']) ? $delivery['branch_external_id'] : ''), 0, 128)) . "', branch_name='" . $this->db->escape(substr((string)(isset($delivery['branch_name']) ? $delivery['branch_name'] : ''), 0, 255)) . "', branch_address='" . $this->db->escape(substr((string)(isset($delivery['branch_address']) ? $delivery['branch_address'] : ''), 0, 255)) . "', postcode='" . $this->db->escape(substr((string)(isset($delivery['postcode']) ? $delivery['postcode'] : ''), 0, 32)) . "', date_added=NOW()");
			}

			// Products
			if (isset($data['products'])) {
				foreach ($data['products'] as $product) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', sku = '" . $this->db->escape(isset($product['sku']) ? $product['sku'] : '') . "', quantity = '" . (int)$product['quantity'] . "', price = '" . number_format((float)$product['price'], 4, '.', '') . "', total = '" . number_format((float)$product['total'], 4, '.', '') . "', tax = '" . number_format((float)$product['tax'], 4, '.', '') . "', reward = '" . (int)$product['reward'] . "'");

					$order_product_id = $this->db->getLastId();

					foreach ($product['option'] as $option) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
					}

					$this->snapshotOrderDownloads($order_id, $order_product_id, (int)$product['product_id'], isset($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id'));
				}
			}

			// Gift Voucher
			$this->load->model('extension/total/voucher');

			// Vouchers
			if (isset($data['vouchers'])) {
				foreach ($data['vouchers'] as $voucher) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . \CodeCart\Core\Money::normalize($voucher['amount']) . "'");

					$order_voucher_id = $this->db->getLastId();

					$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

					$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
				}
			}

			// Totals
			if (isset($data['totals'])) {
				foreach ($data['totals'] as $total) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . \CodeCart\Core\Money::normalize($total['value']) . "', sort_order = '" . (int)$total['sort_order'] . "'");
				}
			}

	
			if ($idempotency) {
				$idempotency->complete($idempotency_scope, $idempotency_key, array('order_id' => (int)$order_id), $idempotency_owner);
			}
			$this->db->commit();
			return (int)$order_id;
		} catch (\Throwable $e) {
			$this->db->rollback();
			if ($idempotency) {
				try {
					$idempotency->fail($idempotency_scope, $idempotency_key, array('error' => 'order_create_failed'), $idempotency_owner);
				} catch (\Throwable $ignored) {
					// Preserve the primary commerce exception.
				}
			}
			throw $e;
		}
	}

	public function editOrder($order_id, $data) {
		// Editing an existing processed order must be atomic with reversing its old
		// stock/totals state. Do not call addOrderHistory() before this transaction.
		$this->db->query("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
		$this->db->beginTransaction();
		try {
			$this->voidOrderForEdit($order_id);

			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . \CodeCart\Core\Money::normalize($data['total']) . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . \CodeCart\Core\Money::normalize($data['commission']) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

			$this->db->query("DELETE FROM " . DB_PREFIX . "order_download WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

			// Products
			if (isset($data['products'])) {
				foreach ($data['products'] as $product) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', sku = '" . $this->db->escape(isset($product['sku']) ? $product['sku'] : '') . "', quantity = '" . (int)$product['quantity'] . "', price = '" . number_format((float)$product['price'], 4, '.', '') . "', total = '" . number_format((float)$product['total'], 4, '.', '') . "', tax = '" . number_format((float)$product['tax'], 4, '.', '') . "', reward = '" . (int)$product['reward'] . "'");

					$order_product_id = $this->db->getLastId();

					foreach ($product['option'] as $option) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
					}

					$this->snapshotOrderDownloads($order_id, $order_product_id, (int)$product['product_id'], isset($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id'));
				}
			}

			// Gift Voucher
			$this->load->model('extension/total/voucher');

			$this->model_extension_total_voucher->disableVoucher($order_id);

			// Vouchers
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

			if (isset($data['vouchers'])) {
				foreach ($data['vouchers'] as $voucher) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . \CodeCart\Core\Money::normalize($voucher['amount']) . "'");

					$order_voucher_id = $this->db->getLastId();

					$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

					$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
				}
			}

			// Totals
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

			if (isset($data['totals'])) {
				foreach ($data['totals'] as $total) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . \CodeCart\Core\Money::normalize($total['value']) . "', sort_order = '" . (int)$total['sort_order'] . "'");
				}
			}
	
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollback();
			throw $e;
		}
	}


	private function voidOrderForEdit($order_id) {
		$order_id = (int)$order_id;
		$lock = $this->db->query("SELECT order_status_id, affiliate_id FROM `" . DB_PREFIX . "order` WHERE order_id='" . $order_id . "' FOR UPDATE");
		if (!$lock->num_rows) {
			throw new \RuntimeException('Order not found: ' . $order_id);
		}

		$old_status = (int)$lock->row['order_status_id'];
		$processing = array_values(array_unique(array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')))));
		if (in_array($old_status, $processing, true)) {
			$order_products = $this->getOrderProducts($order_id);
			foreach ($order_products as $order_product) {
				$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity=(quantity + " . (int)$order_product['quantity'] . ") WHERE product_id='" . (int)$order_product['product_id'] . "' AND subtract='1'");
				foreach ($this->getOrderOptions($order_id, $order_product['order_product_id']) as $order_option) {
					$this->db->query("UPDATE `" . DB_PREFIX . "product_option_value` SET quantity=(quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id='" . (int)$order_option['product_option_value_id'] . "' AND subtract='1'");
				}
			}
			$this->unconfirmOrderTotals($order_id);
			if ((int)$lock->row['affiliate_id']) {
				$this->load->model('account/customer');
				$this->model_account_customer->deleteTransactionByOrderId($order_id);
			}
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id='0', date_modified=NOW() WHERE order_id='" . $order_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET order_id='" . $order_id . "', order_status_id='0', notify='0', comment='', date_added=NOW()");
	}

	public function deleteOrder($order_id) {
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_download` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order_id . "' AND ort.order_recurring_id = `or`.order_recurring_id");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE order_id = '" . (int)$order_id . "'");
		if ($this->tableExists('codecart_order_delivery')) { $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_order_delivery` WHERE order_id='" . (int)$order_id . "'"); }

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);
	}


	private function tableExists($table) {
		$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
		if ($table === '') { return false; }
		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
		return (bool)$query->num_rows;
	}

	private function snapshotOrderDownloads($order_id, $order_product_id, $product_id, $language_id) {
		$query = $this->db->query("SELECT d.download_id, d.filename, d.mask, dd.name FROM `" . DB_PREFIX . "product_to_download` p2d INNER JOIN `" . DB_PREFIX . "download` d ON (d.download_id = p2d.download_id) LEFT JOIN `" . DB_PREFIX . "download_description` dd ON (dd.download_id = d.download_id AND dd.language_id = '" . (int)$language_id . "') WHERE p2d.product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $download) {
			$name = isset($download['name']) && $download['name'] !== '' ? $download['name'] : basename((string)$download['mask']);
			$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "order_download` SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_id = '" . (int)$product_id . "', download_id = '" . (int)$download['download_id'] . "', name = '" . $this->db->escape($name) . "', filename = '" . $this->db->escape($download['filename']) . "', mask = '" . $this->db->escape($download['mask']) . "', date_added = NOW()");
		}
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT *, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'custom_field'            => json_decode($order_query->row['custom_field'], true),
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
				'payment_method'          => $order_query->row['payment_method'],
				'payment_code'            => $order_query->row['payment_code'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
				'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'order_status'            => $order_query->row['order_status'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_modified'           => $order_query->row['date_modified']
			);
		} else {
			return false;
		}
	}
	
	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		
		return $query->rows;
	}
	
	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");
		
		return $query->rows;
	}
	
	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");
	
		return $query->rows;
	}
	
	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");
		
		return $query->rows;
	}	
			
	public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false, $idempotency_context = array()) {
		$idempotency = null;
		$idempotency_scope = '';
		$idempotency_key = '';
		$idempotency_owner = '';
		if (is_array($idempotency_context) && !empty($idempotency_context['key'])) {
			$idempotency_scope = !empty($idempotency_context['scope']) ? (string)$idempotency_context['scope'] : 'order.history';
			$idempotency_key = (string)$idempotency_context['key'];
			$fingerprint = isset($idempotency_context['fingerprint']) ? (string)$idempotency_context['fingerprint'] : ((int)$order_id . '|' . (int)$order_status_id . '|' . (string)$comment . '|' . (int)$notify);
			$idempotency = new \CodeCart\Core\Idempotency($this->registry);
			$state = $idempotency->begin($idempotency_scope, $idempotency_key, $fingerprint, isset($idempotency_context['ttl']) ? (int)$idempotency_context['ttl'] : 604800, 300);
			if (!$state['acquired']) {
				// Completed or concurrently processing callbacks are safe no-ops.
				return;
			}
			$idempotency_owner = isset($state['owner_token']) ? (string)$state['owner_token'] : '';
		}

		$order_info = $this->getOrder($order_id);

		if (!$order_info) {
			if ($idempotency) {
				$idempotency->fail($idempotency_scope, $idempotency_key, array('error' => 'order_not_found'), $idempotency_owner);
			}
			return;
		}

		// Fraud detection can involve extension logic and external services. Keep it
		// outside the DB transaction, then re-check the order status under a row lock.
		$this->load->model('account/customer');
		$customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);
		$safe = $customer_info && $customer_info['safe'];
		$processing_statuses = array_values(array_unique(array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')))));

		if (!$safe && !$override && in_array((int)$order_status_id, $processing_statuses, true)) {
			$this->load->model('setting/extension');
			$extensions = $this->model_setting_extension->getExtensions('fraud');

			foreach ($extensions as $extension) {
				if ($this->config->get('fraud_' . $extension['code'] . '_status')) {
					$this->load->model('extension/fraud/' . $extension['code']);

					if (property_exists($this->{'model_extension_fraud_' . $extension['code']}, 'check')) {
						$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);

						if ($fraud_status_id) {
							$order_status_id = (int)$fraud_status_id;
						}
					}
				}
			}
		}

		// READ COMMITTED is intentional for order finalisation: after waiting for a
		// parent row lock, resource balance/count queries must see the latest commit.
		$this->db->query("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
		$this->db->beginTransaction();

		try {
			$lock_query = $this->db->query("SELECT order_status_id FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' FOR UPDATE");

			if (!$lock_query->num_rows) {
				$this->db->rollback();
				if ($idempotency) {
					$idempotency->fail($idempotency_scope, $idempotency_key, array('error' => 'order_not_found'), $idempotency_owner);
				}
				return;
			}

			// A concurrent callback may have completed a transition while this request
			// was waiting for the row lock. Always use the status protected by the lock.
			$old_order_status_id = (int)$lock_query->row['order_status_id'];
			$order_info['order_status_id'] = $old_order_status_id;
			$order_status_id = (int)$order_status_id;
			$was_processing = in_array($old_order_status_id, $processing_statuses, true);
			$will_process = in_array($order_status_id, $processing_statuses, true);

			if (!$was_processing && $will_process) {
				if (!$this->config->get('config_stock_checkout') && !$this->lockAndCheckOrderStock($order_id)) {
					$stock_failure_status = (int)$this->config->get('config_fraud_status_id');
					$order_status_id = $stock_failure_status > 0 ? $stock_failure_status : $old_order_status_id;
					$will_process = false;

					if ($this->log) {
						$this->log->write('Order #' . (int)$order_id . ': stock finalisation rejected because available quantity changed before commit.');
					}
				}

				if ($will_process) {
					$order_totals = $this->getOrderTotals($order_id);
					$confirmed_totals = array();

					foreach ($order_totals as $order_total) {
						$this->load->model('extension/total/' . $order_total['code']);
						$model = $this->{'model_extension_total_' . $order_total['code']};

						if (property_exists($model, 'confirm')) {
							$confirmed_totals[] = $order_total['code'];
							$fraud_status_id = $model->confirm($order_info, $order_total);

							// Core total extensions return null/false on success and the fraud
							// status on rejection. Status 0 is still a rejection when no
							// explicit fraud status has been configured.
							if ($fraud_status_id !== null && $fraud_status_id !== false) {
								$order_status_id = (int)$fraud_status_id;
								break;
							}
						}
					}

					$will_process = in_array($order_status_id, $processing_statuses, true);

					if (!$will_process) {
						$this->unconfirmOrderTotals($order_id, $confirmed_totals);
					} else {
						$order_products = $this->getOrderProducts($order_id);

						foreach ($order_products as $order_product) {
							$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");
							$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

							foreach ($order_options as $order_option) {
								$this->db->query("UPDATE `" . DB_PREFIX . "product_option_value` SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
							}
						}

						if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
							$this->load->model('account/customer');

							if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
								$this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
							}
						}
					}
				}
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

			$will_process = in_array((int)$order_status_id, $processing_statuses, true);

			if ($was_processing && !$will_process) {
				$order_products = $this->getOrderProducts($order_id);

				foreach ($order_products as $order_product) {
					$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");
					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE `" . DB_PREFIX . "product_option_value` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}

				$this->unconfirmOrderTotals($order_id);

				if ($order_info['affiliate_id']) {
					$this->load->model('account/customer');
					$this->model_account_customer->deleteTransactionByOrderId($order_id);
				}
			}

			if ($idempotency) {
				$idempotency->complete($idempotency_scope, $idempotency_key, array('order_id' => (int)$order_id, 'order_status_id' => (int)$order_status_id), $idempotency_owner);
			}
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollback();
			if ($idempotency) {
				try {
					$idempotency->fail($idempotency_scope, $idempotency_key, array('error' => 'order_history_failed'), $idempotency_owner);
				} catch (\Throwable $ignored) {
					// Preserve the primary exception.
				}
			}
			throw $e;
		}

		$this->cache->delete('product');

		// Preserve OpenCart's historical void/null return contract for third-party
		// callers and events. The Loader exposes this metadata only during the
		// synchronous AFTER-event dispatch and clears it immediately afterwards.
		$this->registry->set('codecart_order_transition', array(
			'order_id'            => (int)$order_id,
			'old_order_status_id' => (int)$old_order_status_id,
			'new_order_status_id' => (int)$order_status_id,
			'comment'             => (string)$comment,
			'notify'              => (bool)$notify,
			'override'            => (bool)$override,
			'was_processing'      => (bool)$was_processing,
			'is_processing'       => (bool)$will_process
		));
	}

	private function unconfirmOrderTotals($order_id, array $codes = array()) {
		$allowed = $codes ? array_fill_keys($codes, true) : null;
		$order_totals = $this->getOrderTotals($order_id);

		foreach ($order_totals as $order_total) {
			if ($allowed !== null && !isset($allowed[$order_total['code']])) {
				continue;
			}

			$this->load->model('extension/total/' . $order_total['code']);
			$model = $this->{'model_extension_total_' . $order_total['code']};

			if (property_exists($model, 'unconfirm')) {
				$model->unconfirm($order_id);
			}
		}
	}

	private function lockAndCheckOrderStock($order_id) {
		$product_requirements = array();
		$option_requirements = array();
		$order_products = $this->getOrderProducts($order_id);

		foreach ($order_products as $order_product) {
			$product_id = (int)$order_product['product_id'];
			$quantity = (int)$order_product['quantity'];
			$product_requirements[$product_id] = isset($product_requirements[$product_id]) ? $product_requirements[$product_id] + $quantity : $quantity;

			$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);
			foreach ($order_options as $order_option) {
				$option_value_id = (int)$order_option['product_option_value_id'];
				if ($option_value_id > 0) {
					$option_requirements[$option_value_id] = isset($option_requirements[$option_value_id]) ? $option_requirements[$option_value_id] + $quantity : $quantity;
				}
			}
		}

		ksort($product_requirements);
		ksort($option_requirements);

		foreach ($product_requirements as $product_id => $required) {
			$query = $this->db->query("SELECT quantity, subtract FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "' FOR UPDATE");
			if (!$query->num_rows || ((int)$query->row['subtract'] === 1 && (int)$query->row['quantity'] < (int)$required)) {
				return false;
			}
		}

		foreach ($option_requirements as $option_value_id => $required) {
			$query = $this->db->query("SELECT quantity, subtract FROM `" . DB_PREFIX . "product_option_value` WHERE product_option_value_id = '" . (int)$option_value_id . "' FOR UPDATE");
			if (!$query->num_rows || ((int)$query->row['subtract'] === 1 && (int)$query->row['quantity'] < (int)$required)) {
				return false;
			}
		}

		return true;
	}

}