<?php
class ModelExtensionFeedGoogleBase extends Model {
	public function install() {
		$this->db->query("
			CREATE TABLE `" . DB_PREFIX . "google_base_category` (
				`google_base_category_id` INT(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				PRIMARY KEY (`google_base_category_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
		");

		$this->db->query("
			CREATE TABLE `" . DB_PREFIX . "google_base_category_to_category` (
				`google_base_category_id` INT(11) NOT NULL,
				`category_id` INT(11) NOT NULL,
				PRIMARY KEY (`google_base_category_id`, `category_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
		");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "google_base_category`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "google_base_category_to_category`");
	}

    public function import($string) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "google_base_category");

        $lines = explode("\n", $string);

        foreach ($lines as $line) {
			if (substr($line, 0, 1) != '#') {
	            $part = explode(' - ', $line, 2);

	            if (isset($part[1])) {
	                $this->db->query("INSERT INTO " . DB_PREFIX . "google_base_category SET google_base_category_id = '" . (int)$part[0] . "', name = '" . $this->db->escape($part[1]) . "'");
	            }
			}
        }
    }

    public function getGoogleBaseCategories($data = array()) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "google_base_category` WHERE name LIKE '%" . $this->db->escape($data['filter_name']) . "%' ORDER BY name ASC";

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

	public function addCategory($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "google_base_category_to_category WHERE category_id = '" . (int)$data['category_id'] . "'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "google_base_category_to_category SET google_base_category_id = '" . (int)$data['google_base_category_id'] . "', category_id = '" . (int)$data['category_id'] . "'");
	}

	public function deleteCategory($category_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "google_base_category_to_category WHERE category_id = '" . (int)$category_id . "'");
	}

    public function getCategories($data = array()) {
        $sql = "SELECT google_base_category_id, (SELECT name FROM `" . DB_PREFIX . "google_base_category` gbc WHERE gbc.google_base_category_id = gbc2c.google_base_category_id) AS google_base_category, category_id, (SELECT name FROM `" . DB_PREFIX . "category_description` cd WHERE cd.category_id = gbc2c.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS category FROM `" . DB_PREFIX . "google_base_category_to_category` gbc2c ORDER BY google_base_category ASC";

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

	public function diagnose($store_id, $language_id) {
		$store_id = (int)$store_id;
		$language_id = (int)$language_id;
		$query = $this->db->query("SELECT p.product_id, pd.name, pd.description, p.image, p.manufacturer_id, p.upc, p.ean, p.jan, p.isbn, p.mpn FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "') INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . $store_id . "') WHERE p.status = '1' AND p.date_available <= NOW() ORDER BY p.product_id ASC");

		$result = array('total' => count($query->rows), 'description_over_5000' => 0, 'missing_image' => 0, 'missing_brand' => 0, 'missing_identifier' => 0, 'description_samples' => array());
		foreach ($query->rows as $row) {
			$plain = strip_tags(html_entity_decode((string)$row['description'], ENT_QUOTES, 'UTF-8'));
			$plain = trim((string)preg_replace('/\s+/u', ' ', $plain));
			if (utf8_strlen($plain) > 5000) {
				$result['description_over_5000']++;
				if (count($result['description_samples']) < 5) $result['description_samples'][] = (int)$row['product_id'] . ' — ' . (string)$row['name'];
			}
			if (trim((string)$row['image']) === '') $result['missing_image']++;
			if ((int)$row['manufacturer_id'] <= 0) $result['missing_brand']++;
			if (trim((string)$row['upc']) === '' && trim((string)$row['ean']) === '' && trim((string)$row['jan']) === '' && trim((string)$row['isbn']) === '' && trim((string)$row['mpn']) === '') $result['missing_identifier']++;
		}

		return $result;
	}

	public function getTotalCategories() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "google_base_category_to_category`");

		return $query->row['total'];
    }
}
