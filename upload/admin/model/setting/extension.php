<?php
class ModelSettingExtension extends Model {	
	public function getInstalled($type) {
		$extension_data = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `type` = '" . $this->db->escape($type) . "' ORDER BY `code`");

		foreach ($query->rows as $result) {
			$extension_data[] = $result['code'];
		}

		return $extension_data;
	}

	/**
	 * Recorded installation timestamps, keyed by extension code.
	 * No date is backfilled for extensions installed before tracking started.
	 */
	public function getInstallDates($type) {
		$prefix = 'installed_at_' . $type . '_';
		$dates = array();
		$query = $this->db->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0 AND `code` = 'codecart_extension_install'");

		foreach ($query->rows as $row) {
			if (strpos($row['key'], $prefix) !== 0) {
				continue;
			}

			$code = substr($row['key'], strlen($prefix));
			$dates[$code] = $row['value'];
		}

		return $dates;
	}


	/**
	 * Upload date of the last ZIP installer package containing this extension's
	 * actual controller file. Do not infer a date from file mtime or ZIP names.
	 * Returns no date for built-in/FTP modules or packages without a matching
	 * installer path record. Works with the existing OpenCart 3 schema.
	 *
	 * @param string $type Known extension type
	 * @return array Code => upload timestamp
	 */
	public function getUploadDates($type) {
		$allowed = array('advertise', 'analytics', 'captcha', 'currency', 'dashboard', 'feed', 'fraud', 'menu', 'module', 'payment', 'report', 'shipping', 'theme', 'total');
		if (!in_array($type, $allowed, true)) {
			return array();
		}

		$prefixes = array('admin/controller/extension/' . $type . '/');
		if ($type === 'module') {
			$prefixes[] = 'admin/controller/module/';
		}

		$clauses = array();
		foreach ($prefixes as $prefix) {
			$clauses[] = "ep.`path` LIKE '" . $this->db->escape($prefix . '%') . "'";
		}

		$sql = "SELECT ep.`path`, ei.`date_added` FROM `" . DB_PREFIX . "extension_path` ep "
			. "INNER JOIN `" . DB_PREFIX . "extension_install` ei ON ei.`extension_install_id` = ep.`extension_install_id` "
			. "WHERE (" . implode(' OR ', $clauses) . ") "
			. "ORDER BY ei.`date_added` DESC, ei.`extension_install_id` DESC";
		$query = $this->db->query($sql);
		$dates = array();

		foreach ($query->rows as $row) {
			$path = str_replace('\\', '/', $row['path']);
			foreach ($prefixes as $prefix) {
				if (strpos($path, $prefix) !== 0) {
					continue;
				}
				$filename = substr($path, strlen($prefix));
				// Map only direct extension controller files, not helper classes.
				if (strpos($filename, '/') === false && preg_match('/^([a-zA-Z0-9_]+)\.php$/D', $filename, $matches)) {
					$code = $matches[1];
					if (!isset($dates[$code])) {
						$dates[$code] = $row['date_added'];
					}
				}
				break;
			}
		}

		return $dates;
	}

	public function install($type, $code) {
		$extensions = $this->getInstalled($type);

		if (!in_array($code, $extensions)) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "extension` SET `type` = '" . $this->db->escape($type) . "', `code` = '" . $this->db->escape($code) . "'");

			// Installation dates are tracked only for new installations. Existing
			// extensions cannot be assigned a trustworthy historical date.
			$key = 'installed_at_' . $type . '_' . $code;
			$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0 AND `code` = 'codecart_extension_install' AND `key` = '" . $this->db->escape($key) . "'");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0, `code` = 'codecart_extension_install', `key` = '" . $this->db->escape($key) . "', `value` = NOW(), `serialized` = 0");
		}
	}


	/**
	 * Refresh the existing installation date only for extensions already
	 * registered and successfully updated by the package installer.
	 */
	public function refreshInstalledDatesFromPackage($extension_install_id) {
		$types = array('advertise', 'analytics', 'captcha', 'currency', 'dashboard', 'feed', 'fraud', 'menu', 'module', 'payment', 'report', 'shipping', 'theme', 'total');
		$paths = $this->getExtensionPathsByExtensionInstallId((int)$extension_install_id);
		$found = array();

		foreach ($paths as $row) {
			$path = str_replace('\\', '/', $row['path']);
			if (!preg_match('~^admin/controller/extension/([a-z]+)/([a-zA-Z0-9_]+)\\.php$~D', $path, $matches)) {
				continue;
			}
			if (!in_array($matches[1], $types, true)) {
				continue;
			}
			$found[$matches[1]][$matches[2]] = true;
		}

		foreach ($found as $type => $codes) {
			$installed = array_flip($this->getInstalled($type));
			foreach (array_keys($codes) as $code) {
				if (!isset($installed[$code])) {
					continue;
				}
				$key = 'installed_at_' . $type . '_' . $code;
				$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0 AND `code` = 'codecart_extension_install' AND `key` = '" . $this->db->escape($key) . "'");
				$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0, `code` = 'codecart_extension_install', `key` = '" . $this->db->escape($key) . "', `value` = NOW(), `serialized` = 0");
			}
		}
	}

	public function uninstall($type, $code) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "extension` WHERE `type` = '" . $this->db->escape($type) . "' AND `code` = '" . $this->db->escape($code) . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $this->db->escape($type . '_' . $code) . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0 AND `code` = 'codecart_extension_install' AND `key` = '" . $this->db->escape('installed_at_' . $type . '_' . $code) . "'");
	}	

	public function addExtensionInstall($filename, $extension_download_id = 0) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "extension_install` SET `filename` = '" . $this->db->escape($filename) . "', `extension_download_id` = '" . (int)$extension_download_id . "', `date_added` = NOW()");
	
		return $this->db->getLastId();
	}
	
	public function deleteExtensionInstall($extension_install_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "extension_install` WHERE `extension_install_id` = '" . (int)$extension_install_id . "'");
	}

	public function getExtensionInstalls($start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}		
		
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension_install` ORDER BY date_added ASC LIMIT " . (int)$start . "," . (int)$limit);
	
		return $query->rows;
	}

	public function getExtensionInstallByExtensionDownloadId($extension_download_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension_install` WHERE `extension_download_id` = '" . (int)$extension_download_id . "'");

		return $query->row;
	}
		
	public function getTotalExtensionInstalls() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "extension_install`");

		return $query->row['total'];
	}
		
	public function addExtensionPath($extension_install_id, $path) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "extension_path` SET `extension_install_id` = '" . (int)$extension_install_id . "', `path` = '" . $this->db->escape($path) . "', `date_added` = NOW()");
	}
		
	public function deleteExtensionPath($extension_path_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "extension_path` WHERE `extension_path_id` = '" . (int)$extension_path_id . "'");
	}
	
	public function getExtensionPathsByExtensionInstallId($extension_install_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension_path` WHERE `extension_install_id` = '" . (int)$extension_install_id . "' ORDER BY `date_added` ASC");

		return $query->rows;
	}
}