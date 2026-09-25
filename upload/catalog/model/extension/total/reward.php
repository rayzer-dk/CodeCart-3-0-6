<?php
class ModelExtensionTotalReward extends Model {
	public function getTotal($total) {
        if (!$this->config->get('total_reward_status')) { return; }

		if (isset($this->session->data['reward'])) {
			$this->load->language('extension/total/reward', 'reward');

			$points = $this->customer->getRewardPoints();

			if ($this->session->data['reward'] <= $points) {
				$discount_total = 0;

				$points_total = 0;

				foreach ($this->cart->getProducts() as $product) {
					if ($product['points']) {
						$points_total += $product['points'];
					}
				}

				$points = min($points, $points_total);

				foreach ($this->cart->getProducts() as $product) {
					$discount = 0;

					if ($product['points']) {
						$discount = $product['total'] * ($this->session->data['reward'] / $points_total);

						if ($product['tax_class_id']) {
							$tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);

							foreach ($tax_rates as $tax_rate) {
								if ($tax_rate['type'] == 'P') {
									$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
								}
							}
						}
					}

					$discount_total += $discount;
				}

				$total['totals'][] = array(
					'code'       => 'reward',
					'title'      => sprintf($this->language->get('reward')->get('text_reward'), $this->session->data['reward']),
					'value'      => -$discount_total,
					'sort_order' => $this->config->get('total_reward_sort_order')
				);

				$total['total'] -= $discount_total;
			}
		}
	}

	public function confirm($order_info, $order_total) {
		$this->load->language('extension/total/reward');
		$points = 0;
		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$points = (int)substr($order_total['title'], $start, $end - $start);
		}

		$customer_id = (int)$order_info['customer_id'];
		if ($customer_id <= 0 || $points <= 0) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		// Lock the customer row so two concurrent orders cannot spend the same points.
		$customer_lock = $this->db->query("SELECT customer_id FROM `" . DB_PREFIX . "customer` WHERE customer_id = '" . $customer_id . "' FOR UPDATE");
		if (!$customer_lock->num_rows) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		$reward_query = $this->db->query("SELECT COALESCE(SUM(points), 0) AS total FROM `" . DB_PREFIX . "customer_reward` WHERE customer_id = '" . $customer_id . "'");
		$available_points = (int)$reward_query->row['total'];

		if ($available_points < $points) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_reward` SET customer_id = '" . $customer_id . "', order_id = '" . (int)$order_info['order_id'] . "', description = '" . $this->db->escape(sprintf($this->language->get('text_order_id'), (int)$order_info['order_id'])) . "', points = '" . (float)-$points . "', date_added = NOW()");
	}

	public function unconfirm($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_reward WHERE order_id = '" . (int)$order_id . "' AND points < 0");
	}
}
