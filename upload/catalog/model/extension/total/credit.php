<?php
class ModelExtensionTotalCredit extends Model {
	public function getTotal($total) {
        if (!$this->config->get('total_credit_status')) { return; }

		$this->load->language('extension/total/credit');

		$balance = $this->customer->getBalance();

		if ((float)$balance) {
			$credit = min($balance, $total['total']);

			if ((float)$credit > 0) {
				$total['totals'][] = array(
					'code'       => 'credit',
					'title'      => $this->language->get('text_credit'),
					'value'      => -$credit,
					'sort_order' => $this->config->get('total_credit_sort_order')
				);

				$total['total'] -= $credit;
			}
		}
	}

	public function confirm($order_info, $order_total) {
		$this->load->language('extension/total/credit');
		$customer_id = (int)$order_info['customer_id'];

		if ($customer_id <= 0) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		// Serialize store-credit spending for this customer and re-check the balance
		// at finalisation time rather than trusting the earlier checkout calculation.
		$customer_lock = $this->db->query("SELECT customer_id FROM `" . DB_PREFIX . "customer` WHERE customer_id = '" . $customer_id . "' FOR UPDATE");
		if (!$customer_lock->num_rows) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		$balance_query = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS total FROM `" . DB_PREFIX . "customer_transaction` WHERE customer_id = '" . $customer_id . "'");
		$balance = (float)$balance_query->row['total'];

		$required = abs((float)$order_total['value']);

		if ($balance + 0.0000001 < $required) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_transaction` SET customer_id = '" . $customer_id . "', order_id = '" . (int)$order_info['order_id'] . "', description = '" . $this->db->escape(sprintf($this->language->get('text_order_id'), (int)$order_info['order_id'])) . "', amount = '" . (float)$order_total['value'] . "', date_added = NOW()");
	}

	public function unconfirm($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_transaction WHERE order_id = '" . (int)$order_id . "'");
	}
}
