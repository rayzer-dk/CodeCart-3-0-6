<?php
namespace Session;

final class DB {
	public $maxlifetime;
	public $db;

	public function __construct($registry) {
		$this->db = $registry->get('db');

		$configured_lifetime = $registry->get('config')->get('session_maxlifetime');
		$this->maxlifetime = $configured_lifetime !== null ? max(60, (int)$configured_lifetime) : (ini_get('session.gc_maxlifetime') !== null ? max(60, (int)ini_get('session.gc_maxlifetime')) : 1440);

		$this->gc();
	}

	public function read($session_id) {
		$query = $this->db->query("SELECT `data` FROM `" . DB_PREFIX . "session` WHERE `session_id` = '" . $this->db->escape($session_id) . "' AND `expire` > '" . $this->db->escape(gmdate('Y-m-d H:i:s', time())) . "'");

		if ($query->num_rows) {
			$data = json_decode($query->row['data'], true);
			return is_array($data) ? $data : array();
		}

		return array();
	}

	public function write($session_id, $data) {
		if ($session_id) {
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "session` SET `session_id` = '" . $this->db->escape($session_id) . "', `data` = '" . $this->db->escape(json_encode($data)) . "', `expire` = '" . $this->db->escape(gmdate('Y-m-d H:i:s', time() + (int)$this->maxlifetime)) . "'");
		}

		return true;
	}

	public function destroy($session_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `session_id` = '" . $this->db->escape($session_id) . "'");

		return true;
	}

	public function gc() {
		if (ini_get('session.gc_divisor') && $gc_divisor = (int)ini_get('session.gc_divisor')) {
			$gc_divisor = $gc_divisor === 0 ? 100 : $gc_divisor;
		} else {
			$gc_divisor = 100;
		}

		if (ini_get('session.gc_probability')) {
			$gc_probability = (int)ini_get('session.gc_probability');
		} else {
			$gc_probability = 1;
		}

		if ($gc_probability > 0 && random_int(1, $gc_divisor) <= $gc_probability) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `expire` < '" . $this->db->escape(gmdate('Y-m-d H:i:s', time())) . "'");

			return true;
		}
	}
}
