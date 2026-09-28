<?php
class ModelDesignTranslation extends Model {
	private static $rows = array();

	public function getTranslations($route) {
		$language_code = !empty($this->session->data['language']) ? $this->session->data['language'] : $this->config->get('config_language');
		$store_id = (int)$this->config->get('config_store_id');
		$language_id = (int)$this->config->get('config_language_id');
		$key = $store_id . '|' . $language_id;

		// OpenCart fires this event for every loaded language file. Load the store /
		// language translation set once per request instead of one query per file.
		if (!isset(self::$rows[$key])) {
			self::$rows[$key] = array();

			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "translation WHERE store_id = '" . $store_id . "' AND language_id = '" . $language_id . "'");

			foreach ($query->rows as $row) {
				self::$rows[$key][(string)$row['route']][] = $row;
			}
		}

		$results = array();

		if (isset(self::$rows[$key][(string)$route])) {
			$results = self::$rows[$key][(string)$route];
		}

		if ((string)$language_code !== (string)$route && isset(self::$rows[$key][(string)$language_code])) {
			$results = array_merge($results, self::$rows[$key][(string)$language_code]);
		}

		return $results;
	}
}
