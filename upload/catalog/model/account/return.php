<?php
class ModelAccountReturn extends Model {
	/** Only an order number and its actual billing email can identify a return. */
	public function getReturnOrder($order_id, $email) {
		$order_id = (int)$order_id;
		$email = trim((string)$email);
		if ($order_id < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		$query = $this->db->query("SELECT o.order_id, o.customer_id, o.firstname, o.lastname, o.email, o.telephone, o.date_added FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . $order_id . "' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "' AND o.order_status_id > '0' AND LOWER(o.email) = LOWER('" . $this->db->escape($email) . "') LIMIT 1");
		return $query->num_rows ? $query->row : false;
	}

	/** Ordered lines remain available even if a product was later deleted. */
	public function getReturnOrderProducts($order_id) {
		$order_id = (int)$order_id;
		if ($order_id < 1) {
			return array();
		}
		$query = $this->db->query("SELECT order_product_id, product_id, name, model, quantity FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . $order_id . "' AND quantity > '0' ORDER BY order_product_id ASC LIMIT 200");
		return $query->rows;
	}

	public function addReturn($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "return` SET order_id = '" . (int)$data['order_id'] . "', product_id = '" . (int)$data['product_id'] . "', customer_id = '" . (isset($data['customer_id']) ? (int)$data['customer_id'] : (int)$this->customer->getId()) . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', product = '" . $this->db->escape($data['product']) . "', model = '" . $this->db->escape($data['model']) . "', quantity = '" . (int)$data['quantity'] . "', opened = '" . (int)$data['opened'] . "', return_reason_id = '" . (int)$data['return_reason_id'] . "', return_status_id = '" . (int)$this->config->get('config_return_status_id') . "', comment = '" . $this->db->escape($data['comment']) . "', date_ordered = '" . $this->db->escape(codecart_normalize_date($data['date_ordered'], '1970-01-01')) . "', date_added = NOW(), date_modified = NOW()");

		return $this->db->getLastId();
	}

	public function getReturn($return_id) {
		$query = $this->db->query("SELECT r.return_id, r.order_id, r.firstname, r.lastname, r.email, r.telephone, r.product, r.model, r.quantity, r.opened, (SELECT rr.name FROM " . DB_PREFIX . "return_reason rr WHERE rr.return_reason_id = r.return_reason_id AND rr.language_id = '" . (int)$this->config->get('config_language_id') . "') AS reason, (SELECT ra.name FROM " . DB_PREFIX . "return_action ra WHERE ra.return_action_id = r.return_action_id AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "') AS action, (SELECT rs.name FROM " . DB_PREFIX . "return_status rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, r.comment, r.date_ordered, r.date_added, r.date_modified FROM `" . DB_PREFIX . "return` r WHERE r.return_id = '" . (int)$return_id . "' AND r.customer_id = '" . $this->customer->getId() . "'");

		return $query->row;
	}

	public function getReturns($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT r.return_id, r.order_id, r.firstname, r.lastname, rs.name as status, r.date_added FROM `" . DB_PREFIX . "return` r LEFT JOIN " . DB_PREFIX . "return_status rs ON (r.return_status_id = rs.return_status_id) WHERE r.customer_id = '" . (int)$this->customer->getId() . "' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY r.return_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalReturns() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return` WHERE customer_id = '" . $this->customer->getId() . "'");

		return $query->row['total'];
	}

	public function getReturnHistories($return_id) {
		$query = $this->db->query("SELECT rh.date_added, rs.name AS status, rh.comment FROM " . DB_PREFIX . "return_history rh INNER JOIN `" . DB_PREFIX . "return` r ON (r.return_id = rh.return_id) LEFT JOIN " . DB_PREFIX . "return_status rs ON rh.return_status_id = rs.return_status_id WHERE rh.return_id = '" . (int)$return_id . "' AND r.customer_id = '" . (int)$this->customer->getId() . "' AND r.customer_id > '0' AND rh.notify = '1' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY rh.date_added ASC");

		return $query->rows;
	}
}
