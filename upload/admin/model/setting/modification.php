<?php
class ModelSettingModification extends Model {
	/**
	 * Align legacy OpenCart/ocStore modification tables with the CodeCart PRO Installer 2.0 schema.
	 * This is intentionally idempotent so upgraded stores and clean installations behave alike.
	 */
	public function ensureCompatibilitySchema() {
		$table = DB_PREFIX . 'modification';
		$column = $this->db->query("SHOW COLUMNS FROM `" . $table . "` LIKE 'extension_install_id'");

		if (!$column->num_rows) {
			$this->db->query("ALTER TABLE `" . $table . "` ADD `extension_install_id` INT(11) NOT NULL DEFAULT '0' AFTER `modification_id`");
		}

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "modification_backup` (
			`backup_id` INT(11) NOT NULL AUTO_INCREMENT,
			`modification_id` INT(11) NOT NULL,
			`code` VARCHAR(64) NOT NULL,
			`xml` MEDIUMTEXT NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`backup_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	public function addModification($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "modification` SET `extension_install_id` = '" . (int)(isset($data['extension_install_id']) ? $data['extension_install_id'] : 0) . "', `name` = '" . $this->db->escape($data['name']) . "', `code` = '" . $this->db->escape($data['code']) . "', `author` = '" . $this->db->escape($data['author']) . "', `version` = '" . $this->db->escape($data['version']) . "', `link` = '" . $this->db->escape($data['link']) . "', `xml` = '" . $this->db->escape($data['xml']) . "', `status` = '" . (int)$data['status'] . "', `date_added` = NOW()");
        $this->markCompatibilityDirty('modification_added', array('code' => isset($data['code']) ? (string)$data['code'] : ''));
	}

    public function addModificationBackup($modification_id, $data) {
        $xml = html_entity_decode($data['xml']);
        $this->db->query("INSERT INTO " . DB_PREFIX . "modification_backup SET modification_id = '" . (int)$modification_id . "', code = '" . $this->db->escape($data['code']) . "', xml = '" . $this->db->escape($xml) . "', date_added = NOW()");
    }

    public function editModification($modification_id, $data) {
        $xml = html_entity_decode($data['xml']);
        $name = html_entity_decode($data['name']);
        $this->db->query("UPDATE " . DB_PREFIX . "modification SET xml = '" . $this->db->escape($xml) . "', name = '" . $this->db->escape($name) . "' WHERE modification_id = '" . (int)$modification_id . "'");
        $this->markCompatibilityDirty('modification_edited', array('modification_id' => (int)$modification_id));
    }

    public function setModificationRestore($modification_id, $xml) {
        $this->db->query("UPDATE " . DB_PREFIX . "modification SET xml = '" . $this->db->escape($xml) . "' WHERE modification_id = '" . (int)$modification_id . "'");
        $this->markCompatibilityDirty('modification_restored', array('modification_id' => (int)$modification_id));
    }

	public function deleteModification($modification_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "modification` WHERE `modification_id` = '" . (int)$modification_id . "'");
        $this->markCompatibilityDirty('modification_deleted', array('modification_id' => (int)$modification_id));
	}

    public function deleteModificationBackups($modification_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "modification_backup WHERE modification_id = '" . (int)$modification_id . "'");
    }

	public function deleteModificationsByExtensionInstallId($extension_install_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "modification` WHERE `extension_install_id` = '" . (int)$extension_install_id . "'");
        $this->markCompatibilityDirty('extension_modifications_deleted', array('extension_install_id' => (int)$extension_install_id));
	}
	
	public function enableModification($modification_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET `status` = '1' WHERE `modification_id` = '" . (int)$modification_id . "'");
        $this->markCompatibilityDirty('modification_enabled', array('modification_id' => (int)$modification_id));
	}

	public function disableModification($modification_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET `status` = '0' WHERE `modification_id` = '" . (int)$modification_id . "'");
        $this->markCompatibilityDirty('modification_disabled', array('modification_id' => (int)$modification_id));
	}

	public function getModification($modification_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "modification` WHERE `modification_id` = '" . (int)$modification_id . "'");

		return $query->row;
	}

	public function getModifications($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "modification`";

		$sort_data = array(
			'name',
			'author',
			'version',
			'status',
			'date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

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

    public function getModificationBackups($modification_id) {
        $sql = "SELECT * FROM " . DB_PREFIX . "modification_backup  WHERE modification_id = '" . (int)$modification_id . "' ORDER BY date_added DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getModificationBackup($modification_id, $backup_id) {
        $sql = "SELECT * FROM " . DB_PREFIX . "modification_backup  WHERE modification_id = '" . (int)$modification_id . "' AND backup_id = '" . (int)$backup_id . "' ORDER BY date_added DESC";
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getTotalModifications() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "modification`");

        return $query->row['total'];
    }
	
	public function getModificationByCode($code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "modification` WHERE `code` = '" . $this->db->escape($code) . "'");

		return $query->row;
	}	

    private function markCompatibilityDirty($reason, array $context = array()) {
        try {
            if (class_exists('\\CodeCart\\Core\\OcmodState')) {
                \CodeCart\Core\OcmodState::markDirty($reason, $context);
            }
        } catch (\Throwable $e) {
            // OCMOD state tracking is advisory and must never block extension management.
        }
    }

}