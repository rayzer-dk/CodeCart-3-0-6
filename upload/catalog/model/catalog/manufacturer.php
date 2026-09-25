<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelCatalogManufacturer extends Model {
	
	public function getManufacturerLayoutId($manufacturer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer_to_layout WHERE manufacturer_id = '" . (int)$manufacturer_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return 0;
		}
	}
	
	public function getManufacturer($manufacturer_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id) LEFT JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m.manufacturer_id = '" . (int)$manufacturer_id . "' AND md.language_id = '" . (int)$this->config->get('config_language_id') . "' AND m2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row;
	}

	public function getManufacturersByIds(array $manufacturer_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $manufacturer_ids))));
		if (!$ids) { return array(); }
		$id_list = implode(',', $ids);
		$query = $this->db->query("SELECT m.*, md.* FROM " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id) INNER JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m.manufacturer_id IN (" . $id_list . ") AND md.language_id = '" . (int)$this->config->get('config_language_id') . "' AND m2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY FIELD(m.manufacturer_id," . $id_list . ")");
		return $query->rows;
	}

	public function getManufacturerProductCounts(array $manufacturer_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $manufacturer_ids))));
		if (!$ids) { return array(); }
		$counts = array_fill_keys($ids, 0);
		$query = $this->db->query("SELECT p.manufacturer_id, COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.manufacturer_id IN (" . implode(',', $ids) . ") AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY p.manufacturer_id");
		foreach ($query->rows as $row) {
			$manufacturer_id = (int)$row['manufacturer_id'];
			if (isset($counts[$manufacturer_id])) { $counts[$manufacturer_id] = (int)$row['total']; }
		}
		return $counts;
	}

	public function getManufacturers($data = array()) {
		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id) LEFT JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND md.language_id = '" . (int)$this->config->get('config_language_id') . "'";

			$sort_data = array(
				'name',
				'sort_order'
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
		} else {
			$manufacturer_data = $this->cache->get('manufacturer.' . (int)$this->config->get('config_store_id') . '.' . (int)$this->config->get('config_language_id'));

			if ($manufacturer_data === false || $manufacturer_data === null) {
				
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id) LEFT JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND md.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");	
				
				$manufacturer_data = $query->rows;	
				
				$this->cache->set('manufacturer.' . (int)$this->config->get('config_store_id') . '.' . (int)$this->config->get('config_language_id'), $manufacturer_data);
 			}
			return $manufacturer_data;
		}
	}
	public function getManufacturersForSitemap($start = 0, $limit = 10000) {
		$start = max(0, (int)$start);
		$limit = max(1, min(10000, (int)$limit));
		$sql = "SELECT m.manufacturer_id, m.noindex FROM " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND m.noindex > '0'";
		}
		$sql .= " ORDER BY m.manufacturer_id ASC LIMIT " . $start . "," . $limit;
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalManufacturersForSitemap() {
		$sql = "SELECT COUNT(DISTINCT m.manufacturer_id) AS total FROM " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND m.noindex > '0'";
		}
		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

}