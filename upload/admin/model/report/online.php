<?php
class ModelReportOnline extends Model {
	public function getOnline($data = array()) {
		$sql = "SELECT co.ip, co.customer_id, co.url, co.referer, co.user_agent, co.date_added, CONCAT(c.firstname, ' ', c.lastname) AS customer_name FROM " . DB_PREFIX . "customer_online co LEFT JOIN " . DB_PREFIX . "customer c ON (co.customer_id = c.customer_id)";

		$implode = array();

		if (!empty($data['filter_ip'])) {
			$implode[] = "co.ip LIKE '" . $this->db->escape($data['filter_ip']) . "'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] = "co.customer_id > 0 AND CONCAT(c.firstname, ' ', c.lastname) LIKE '" . $this->db->escape($data['filter_customer']) . "'";
		}

		if (!empty($data['filter_type'])) {
			$bot_sql = "LOWER(co.user_agent) REGEXP '(bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headless|python-requests|curl/|wget/|httpclient|semrush|ahrefs|mj12bot|bytespider|gptbot|chatgpt-user|claudebot|anthropic-ai|perplexitybot|googleother)'";
			if ($data['filter_type'] === 'bot') { $implode[] = $bot_sql; }
			elseif ($data['filter_type'] === 'customer') { $implode[] = "co.customer_id > 0 AND NOT (" . $bot_sql . ")"; }
			elseif ($data['filter_type'] === 'guest') { $implode[] = "co.customer_id = 0 AND NOT (" . $bot_sql . ")"; }
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sql .= " ORDER BY co.date_added DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalOnline($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "customer_online` co LEFT JOIN " . DB_PREFIX . "customer c ON (co.customer_id = c.customer_id)";

		$implode = array();

		if (!empty($data['filter_ip'])) {
			$implode[] = "co.ip LIKE '" . $this->db->escape($data['filter_ip']) . "'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] = "co.customer_id > 0 AND CONCAT(c.firstname, ' ', c.lastname) LIKE '" . $this->db->escape($data['filter_customer']) . "'";
		}

		if (!empty($data['filter_type'])) {
			$bot_sql = "LOWER(co.user_agent) REGEXP '(bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headless|python-requests|curl/|wget/|httpclient|semrush|ahrefs|mj12bot|bytespider|gptbot|chatgpt-user|claudebot|anthropic-ai|perplexitybot|googleother)'";
			if ($data['filter_type'] === 'bot') { $implode[] = $bot_sql; }
			elseif ($data['filter_type'] === 'customer') { $implode[] = "co.customer_id > 0 AND NOT (" . $bot_sql . ")"; }
			elseif ($data['filter_type'] === 'guest') { $implode[] = "co.customer_id = 0 AND NOT (" . $bot_sql . ")"; }
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}
}
