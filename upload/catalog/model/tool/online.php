<?php
class ModelToolOnline extends Model {
	public function addOnline($visitor_key, $ip, $customer_id, $url, $referer, $user_agent = '') {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_online` WHERE date_added < '" . date('Y-m-d H:i:s', strtotime('-1 hour')) . "'");
		$visitor_key = preg_match('/^[a-f0-9]{64}$/', (string)$visitor_key) ? (string)$visitor_key : hash('sha256', (string)$ip . '|' . (string)$user_agent);
		try {
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "customer_online` SET `visitor_key`='" . $this->db->escape($visitor_key) . "', `ip`='" . $this->db->escape($ip) . "', `customer_id`='" . (int)$customer_id . "', `url`='" . $this->db->escape($url) . "', `referer`='" . $this->db->escape($referer) . "', `user_agent`='" . $this->db->escape(utf8_substr((string)$user_agent, 0, 512)) . "', `date_added`='" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
		} catch (\Throwable $e) {
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "customer_online` SET `ip`='" . $this->db->escape($ip) . "', `customer_id`='" . (int)$customer_id . "', `url`='" . $this->db->escape($url) . "', `referer`='" . $this->db->escape($referer) . "', `date_added`='" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
		}
	}
}
