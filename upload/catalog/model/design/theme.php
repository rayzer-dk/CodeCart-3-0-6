<?php
class ModelDesignTheme extends Model {
	private static $routes = array();

	public function getTheme($route, $theme) {
		$store_id = (int)$this->config->get('config_store_id');
		$key = $store_id . '|' . (string)$theme;

		// Theme Editor overrides are rare, but OpenCart checks them for every rendered
		// template. Resolve the list of overridden routes once per request and only
		// fetch the template body when an override for this route really exists.
		if (!isset(self::$routes[$key])) {
			self::$routes[$key] = array();

			$query = $this->db->query("SELECT route FROM " . DB_PREFIX . "theme WHERE store_id = '" . $store_id . "' AND theme = '" . $this->db->escape($theme) . "'");

			foreach ($query->rows as $row) {
				self::$routes[$key][(string)$row['route']] = true;
			}
		}

		if (!isset(self::$routes[$key][(string)$route])) {
			return array();
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "theme WHERE store_id = '" . $store_id . "' AND theme = '" . $this->db->escape($theme) . "' AND route = '" . $this->db->escape($route) . "'");

		return $query->row;
	}
}
