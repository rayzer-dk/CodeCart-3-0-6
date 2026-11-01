<?php
class ModelCatalogInformation extends Model {
	public function getInformation($information_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE i.information_id = '" . (int)$information_id . "' AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1'");

		return $query->row;
	}

	public function getInformations() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' ORDER BY i.sort_order, LCASE(id.title) ASC");

		return $query->rows;
	}

	// The four layout positions ask for the same entity layout in one request.
	private $layout_ids = array();

	public function getInformationLayoutId($information_id) {
		$key = (int)$this->config->get('config_store_id') . ':' . (int)$information_id;

		if (!array_key_exists($key, $this->layout_ids)) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_layout WHERE information_id = '" . (int)$information_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

			$this->layout_ids[$key] = $query->num_rows ? (int)$query->row['layout_id'] : 0;
		}

		return $this->layout_ids[$key];
	}
	public function getInformationsForSitemap($start = 0, $limit = 10000) {
		$start = max(0, (int)$start);
		$limit = max(1, min(10000, (int)$limit));
		$sql = "SELECT i.information_id, i.noindex FROM " . DB_PREFIX . "information i INNER JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) INNER JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1'";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND i.noindex > '0'";
		}
		$sql .= " ORDER BY i.information_id ASC LIMIT " . $start . "," . $limit;
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalInformationsForSitemap() {
		$sql = "SELECT COUNT(DISTINCT i.information_id) AS total FROM " . DB_PREFIX . "information i INNER JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) INNER JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1'";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND i.noindex > '0'";
		}
		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

}