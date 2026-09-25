<?php
class ModelExtensionTotalCoupon extends Model {
	public function getCoupon($code) {
		$status = true;
		$product_data = array();

		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start < '1000-01-01' OR date_start <= CURDATE()) AND (date_end < '1000-01-01' OR date_end >= CURDATE())) AND status = '1'");

		if (!$coupon_query->num_rows) {
			return;
		}

		if (\CodeCart\Core\Money::compare($coupon_query->row['total'], $this->cart->getSubTotal()) > 0) {
			$status = false;
		}

		$coupon_total = $this->getTotalCouponHistoriesByCoupon($code);
		if ((int)$coupon_query->row['uses_total'] > 0 && $coupon_total >= (int)$coupon_query->row['uses_total']) {
			$status = false;
		}

		if ($coupon_query->row['logged'] && !$this->customer->getId()) {
			$status = false;
		}

		$group_query = $this->db->query("SELECT customer_group_id FROM `" . DB_PREFIX . "coupon_customer_group` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");
		if ($group_query->num_rows) {
			if (!$this->customer->getId()) {
				$status = false;
			} else {
				$allowed_groups = array_map('intval', array_column($group_query->rows, 'customer_group_id'));
				if (!in_array((int)$this->customer->getGroupId(), $allowed_groups, true)) {
					$status = false;
				}
			}
		}

		if ($this->customer->getId()) {
			$customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $this->customer->getId());
			if ((int)$coupon_query->row['uses_customer'] > 0 && $customer_total >= (int)$coupon_query->row['uses_customer']) {
				$status = false;
			}
		}

		$coupon_product_ids = array();
		$coupon_product_query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "coupon_product` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");
		foreach ($coupon_product_query->rows as $row) {
			$coupon_product_ids[(int)$row['product_id']] = true;
		}

		// Expand configured coupon categories to all descendants in one query.
		$coupon_category_ids = array();
		$coupon_category_query = $this->db->query("SELECT DISTINCT cp.category_id FROM `" . DB_PREFIX . "coupon_category` cc INNER JOIN `" . DB_PREFIX . "category_path` cp ON (cp.path_id = cc.category_id) WHERE cc.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");
		foreach ($coupon_category_query->rows as $row) {
			$coupon_category_ids[(int)$row['category_id']] = true;
		}

		if ($coupon_product_ids || $coupon_category_ids) {
			$cart_product_ids = array();
			foreach ($this->cart->getProducts() as $product) {
				$cart_product_ids[(int)$product['product_id']] = true;
			}

			$category_product_ids = array();
			if ($cart_product_ids && $coupon_category_ids) {
				$product_sql = implode(',', array_map('intval', array_keys($cart_product_ids)));
				$category_sql = implode(',', array_map('intval', array_keys($coupon_category_ids)));
				$match_query = $this->db->query("SELECT DISTINCT product_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id IN (" . $product_sql . ") AND category_id IN (" . $category_sql . ")");
				foreach ($match_query->rows as $row) {
					$category_product_ids[(int)$row['product_id']] = true;
				}
			}

			foreach (array_keys($cart_product_ids) as $product_id) {
				if (isset($coupon_product_ids[$product_id]) || isset($category_product_ids[$product_id])) {
					$product_data[] = (int)$product_id;
				}
			}

			if (!$product_data) {
				$status = false;
			}
		}

		if (!$status) {
			return;
		}

		return array(
			'coupon_id'     => $coupon_query->row['coupon_id'],
			'code'          => $coupon_query->row['code'],
			'name'          => $coupon_query->row['name'],
			'type'          => $coupon_query->row['type'],
			'discount'      => $coupon_query->row['discount'],
			'shipping'      => $coupon_query->row['shipping'],
			'total'         => $coupon_query->row['total'],
			'product'       => $product_data,
			'date_start'    => $coupon_query->row['date_start'],
			'date_end'      => $coupon_query->row['date_end'],
			'uses_total'    => $coupon_query->row['uses_total'],
			'uses_customer' => $coupon_query->row['uses_customer'],
			'status'        => $coupon_query->row['status'],
			'date_added'    => $coupon_query->row['date_added']
		);
	}

	public function getTotal($total) {
        if (!$this->config->get('total_coupon_status')) { return; }

		if (!isset($this->session->data['coupon'])) {
			return;
		}

		$this->load->language('extension/total/coupon', 'coupon');
		$coupon_info = $this->getCoupon($this->session->data['coupon']);
		if (!$coupon_info) {
			return;
		}

		$products = $this->cart->getProducts();
		$eligible = array();
		$weights = array();
		$sub_total = '0.0000';

		foreach ($products as $index => $product) {
			if (!$coupon_info['product'] || in_array((int)$product['product_id'], array_map('intval', $coupon_info['product']), true)) {
				$eligible[$index] = true;
				$weights[$index] = \CodeCart\Core\Money::normalize($product['total']);
				$sub_total = \CodeCart\Core\Money::add($sub_total, $product['total']);
			}
		}

		if (\CodeCart\Core\Money::compare($sub_total, 0) <= 0) {
			return;
		}

		$discounts = array();
		if ($coupon_info['type'] === 'F') {
			$fixed = \CodeCart\Core\Money::min($coupon_info['discount'], $sub_total);
			$discounts = \CodeCart\Core\Money::distribute($fixed, $weights);
		} elseif ($coupon_info['type'] === 'P') {
			$percent = max(0.0, min(100.0, (float)$coupon_info['discount']));
			foreach ($weights as $index => $weight) {
				$discounts[$index] = \CodeCart\Core\Money::percent($weight, $percent);
			}
		}

		$discount_total = '0.0000';
		foreach ($products as $index => $product) {
			if (!isset($eligible[$index])) {
				continue;
			}
			$discount = isset($discounts[$index]) ? $discounts[$index] : '0.0000';
			if (\CodeCart\Core\Money::compare($discount, 0) <= 0) {
				continue;
			}

			if ($product['tax_class_id']) {
				$tax_rates = $this->tax->getRates(\CodeCart\Core\Money::decimal($discount), $product['tax_class_id']);
				foreach ($tax_rates as $tax_rate) {
					if ($tax_rate['type'] === 'P') {
						$current = isset($total['taxes'][$tax_rate['tax_rate_id']]) ? $total['taxes'][$tax_rate['tax_rate_id']] : 0;
						$total['taxes'][$tax_rate['tax_rate_id']] = \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::subtract($current, $tax_rate['amount']));
					}
				}
			}

			$discount_total = \CodeCart\Core\Money::add($discount_total, $discount);
		}

		if ($coupon_info['shipping'] && isset($this->session->data['shipping_method'])) {
			$shipping_cost = \CodeCart\Core\Money::normalize($this->session->data['shipping_method']['cost']);
			if (!empty($this->session->data['shipping_method']['tax_class_id'])) {
				$tax_rates = $this->tax->getRates(\CodeCart\Core\Money::decimal($shipping_cost), $this->session->data['shipping_method']['tax_class_id']);
				foreach ($tax_rates as $tax_rate) {
					if ($tax_rate['type'] === 'P') {
						$current = isset($total['taxes'][$tax_rate['tax_rate_id']]) ? $total['taxes'][$tax_rate['tax_rate_id']] : 0;
						$total['taxes'][$tax_rate['tax_rate_id']] = \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::subtract($current, $tax_rate['amount']));
					}
				}
			}
			$discount_total = \CodeCart\Core\Money::add($discount_total, $shipping_cost);
		}

		$discount_total = \CodeCart\Core\Money::min($discount_total, $total['total']);
		if (\CodeCart\Core\Money::compare($discount_total, 0) > 0) {
			$discount_value = \CodeCart\Core\Money::decimal($discount_total);
			$total['totals'][] = array(
				'code'       => 'coupon',
				'title'      => sprintf($this->language->get('coupon')->get('text_coupon'), $this->session->data['coupon']),
				'value'      => -$discount_value,
				'sort_order' => $this->config->get('total_coupon_sort_order')
			);
			$total['total'] = \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::subtract($total['total'], $discount_total));
		}
	}

	public function confirm($order_info, $order_total) {
		$code = '';
		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');
		if ($start && $end) {
			$code = substr($order_total['title'], $start, $end - $start);
		}
		if (!$code) {
			return;
		}

		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start < '1000-01-01' OR date_start <= CURDATE()) AND (date_end < '1000-01-01' OR date_end >= CURDATE())) AND status = '1' FOR UPDATE");
		$status = (bool)$coupon_query->num_rows;
		if ($status) {
			$coupon_total_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");
			$coupon_total = (int)$coupon_total_query->row['total'];
			$customer_total = 0;
			if (!empty($order_info['customer_id'])) {
				$customer_total_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "' AND customer_id = '" . (int)$order_info['customer_id'] . "'");
				$customer_total = (int)$customer_total_query->row['total'];
			}
			if ((int)$coupon_query->row['uses_total'] > 0 && $coupon_total >= (int)$coupon_query->row['uses_total']) {
				$status = false;
			}
			if ($status && !empty($order_info['customer_id']) && (int)$coupon_query->row['uses_customer'] > 0 && $customer_total >= (int)$coupon_query->row['uses_customer']) {
				$status = false;
			}
			if ($status) {
				$group_query = $this->db->query("SELECT customer_group_id FROM `" . DB_PREFIX . "coupon_customer_group` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");
				if ($group_query->num_rows) {
					$customer_group_id = !empty($order_info['customer_group_id']) ? (int)$order_info['customer_group_id'] : 0;
					$allowed_groups = array_map('intval', array_column($group_query->rows, 'customer_group_id'));
					if ($customer_group_id < 1 || !in_array($customer_group_id, $allowed_groups, true)) {
						$status = false;
					}
				}
			}
		}
		if (!$status) {
			return (int)$this->config->get('config_fraud_status_id');
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_history` SET coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', customer_id = '" . (int)$order_info['customer_id'] . "', amount = '" . \CodeCart\Core\Money::normalize($order_total['value']) . "', date_added = NOW()");
	}

	public function unconfirm($order_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_history` WHERE order_id = '" . (int)$order_id . "'");
	}

	public function getTotalCouponHistoriesByCoupon($coupon) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "'");
		return (int)$query->row['total'];
	}

	public function getTotalCouponHistoriesByCustomerId($coupon, $customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "' AND ch.customer_id = '" . (int)$customer_id . "'");
		return (int)$query->row['total'];
	}
}
