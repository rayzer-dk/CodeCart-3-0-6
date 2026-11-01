<?php
class ModelDesignLayout extends Model {
	// The four layout positions are rendered by separate controllers in the same
	// request; route and module lookups are shared instead of repeated per position.
	private $routes = array();
	private $modules = array();

	public function getLayout($route) {
		$key = (int)$this->config->get('config_store_id') . ':' . (string)$route;

		if (!array_key_exists($key, $this->routes)) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "layout_route WHERE '" . $this->db->escape($route) . "' LIKE route AND store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY route DESC LIMIT 1");

			$this->routes[$key] = $query->num_rows ? (int)$query->row['layout_id'] : 0;
		}

		return $this->routes[$key];
	}
	
	public function getLayoutModules($layout_id, $position) {
		$this->loadLayoutModules($layout_id);

		return isset($this->modules[(int)$layout_id][(string)$position]) ? $this->modules[(int)$layout_id][(string)$position] : array();
	}

	/**
	 * Module ids (the part after the dot in a layout code) used in any position of a layout.
	 */
	public function getLayoutModuleIds($layout_id) {
		$this->loadLayoutModules($layout_id);

		$module_ids = array();
		foreach ($this->modules[(int)$layout_id] as $rows) {
			foreach ($rows as $row) {
				$part = explode('.', $row['code']);
				if (isset($part[1])) {
					$module_ids[] = (int)$part[1];
				}
			}
		}

		return $module_ids;
	}

	private function loadLayoutModules($layout_id) {
		$layout_id = (int)$layout_id;
		if (isset($this->modules[$layout_id])) {
			return;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "layout_module WHERE layout_id = '" . $layout_id . "' ORDER BY sort_order, layout_module_id");

		$this->modules[$layout_id] = array();
		foreach ($query->rows as $row) {
			$this->modules[$layout_id][(string)$row['position']][] = $row;
		}
	}
}
