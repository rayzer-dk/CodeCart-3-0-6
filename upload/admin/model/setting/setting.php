<?php
class ModelSettingSetting extends Model {
	public function getSetting($code, $store_id = 0) {
		$setting_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

		foreach ($query->rows as $result) {
			if (!$result['serialized']) {
				$setting_data[$result['key']] = $result['value'];
			} else {
				$setting_data[$result['key']] = json_decode($result['value'], true);
			}
		}

		return $setting_data;
	}

	public function editSetting($code, $data, $store_id = 0) {
		$code = (string)$code;
		$store_id = (int)$store_id;
		$prefix_length = strlen($code);

		$this->db->beginTransaction();
		try {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . $store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

			foreach ($data as $key => $value) {
				$key = (string)$key;
				if (substr($key, 0, $prefix_length) !== $code) {
					continue;
				}

				$serialized = is_array($value) ? 1 : 0;
				$stored_value = $serialized ? json_encode($value) : (string)$value;
				if ($serialized && $stored_value === false) {
					throw new RuntimeException('Unable to encode setting value for key: ' . $key);
				}

				$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '" . $store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($stored_value) . "', serialized = '" . $serialized . "'");
			}

			$this->db->commit();
		} catch (Throwable $e) {
			try { $this->db->rollback(); } catch (Throwable $ignored) {}
			throw $e;
		}
	}

	public function deleteSetting($code, $store_id = 0) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");
	}
	
	public function getSettingValue($key, $store_id = 0) {
		$query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

		if ($query->num_rows) {
			return $query->row['value'];
		} else {
			return null;	
		}
	}
	
	/**
	 * Insert or update one setting without deleting the rest of its namespace.
	 * Unlike editSetting(), the key is not required to start with the code.
	 */
	public function setSettingValue($code, $key, $value, $store_id = 0) {
		$code = (string)$code;
		$key = (string)$key;
		$store_id = (int)$store_id;
		$serialized = is_array($value) ? 1 : 0;
		$stored_value = $serialized ? json_encode($value) : (string)$value;
		if ($serialized && $stored_value === false) {
			throw new RuntimeException('Unable to encode setting value for key: ' . $key);
		}

		$query = $this->db->query("SELECT setting_id FROM " . DB_PREFIX . "setting WHERE store_id = '" . $store_id . "' AND `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' LIMIT 1");
		if ($query->num_rows) {
			$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($stored_value) . "', serialized = '" . $serialized . "' WHERE setting_id = '" . (int)$query->row['setting_id'] . "'");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . $store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($stored_value) . "', serialized = '" . $serialized . "'");
		}
	}

	public function editSettingValue($code = '', $key = '', $value = '', $store_id = 0) {
		if (!is_array($value)) {
			$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($value) . "', serialized = '0'  WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1' WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
		}
	}
}
