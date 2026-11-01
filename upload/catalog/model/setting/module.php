<?php
class ModelSettingModule extends Model {
	private $modules = array();

	public function getModule($module_id) {
		$module_id = (int)$module_id;

		if (!array_key_exists($module_id, $this->modules)) {
			$this->getModulesByIds(array($module_id));
		}

		return $this->modules[$module_id];
	}

	/**
	 * Loads several module settings in one query; later getModule() calls reuse them.
	 */
	public function getModulesByIds(array $module_ids) {
		$missing = array();
		foreach (array_unique(array_map('intval', $module_ids)) as $module_id) {
			if (!array_key_exists($module_id, $this->modules)) {
				$missing[] = $module_id;
				$this->modules[$module_id] = array();
			}
		}

		if ($missing) {
			$query = $this->db->query("SELECT module_id, setting FROM " . DB_PREFIX . "module WHERE module_id IN (" . implode(',', $missing) . ")");

			foreach ($query->rows as $row) {
				$this->modules[(int)$row['module_id']] = json_decode($row['setting'], true);
			}
		}

		$modules = array();
		foreach ($module_ids as $module_id) {
			$modules[(int)$module_id] = $this->modules[(int)$module_id];
		}

		return $modules;
	}
}
