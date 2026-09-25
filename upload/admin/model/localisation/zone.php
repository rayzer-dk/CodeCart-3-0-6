<?php
class ModelLocalisationZone extends Model {
	public function addZone($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "zone SET status = '" . (int)$data['status'] . "', name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', country_id = '" . (int)$data['country_id'] . "'");

		$this->cache->delete('zone');
		$this->cache->delete('zone.' . (int)$data['country_id']);
		
		return $this->db->getLastId();
	}

	public function editZone($zone_id, $data) {
		$old = $this->getZone($zone_id);
		$this->db->query("UPDATE " . DB_PREFIX . "zone SET status = '" . (int)$data['status'] . "', name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', country_id = '" . (int)$data['country_id'] . "' WHERE zone_id = '" . (int)$zone_id . "'");
		$this->cache->delete('zone');
		if (!empty($old['country_id'])) { $this->cache->delete('zone.' . (int)$old['country_id']); }
		$this->cache->delete('zone.' . (int)$data['country_id']);
	}

	public function deleteZone($zone_id) {
		$old = $this->getZone($zone_id);
		$this->db->query("DELETE FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "'");
		$this->cache->delete('zone');
		if (!empty($old['country_id'])) { $this->cache->delete('zone.' . (int)$old['country_id']); }
	}

	public function setZoneStatus($zone_ids, $status) {
		$ids = array_values(array_unique(array_filter(array_map('intval', (array)$zone_ids))));
		if (!$ids) { return; }
		$country_ids = array();
		$query = $this->db->query("SELECT DISTINCT country_id FROM " . DB_PREFIX . "zone WHERE zone_id IN (" . implode(',', $ids) . ")");
		foreach ($query->rows as $row) { $country_ids[] = (int)$row['country_id']; }
		$this->db->query("UPDATE " . DB_PREFIX . "zone SET status = '" . ((int)$status ? 1 : 0) . "' WHERE zone_id IN (" . implode(',', $ids) . ")");
		$this->cache->delete('zone');
		foreach (array_unique($country_ids) as $country_id) { $this->cache->delete('zone.' . (int)$country_id); }
	}

	public function getZone($zone_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "'");

		return $query->row;
	}

	public function getZones($data = array()) {
		$sql = "SELECT *, z.name, c.name AS country FROM " . DB_PREFIX . "zone z LEFT JOIN " . DB_PREFIX . "country c ON (z.country_id = c.country_id)";

		$sort_data = array(
			'c.name',
			'z.name',
			'z.code'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.name";
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

	public function getZonesByCountryId($country_id) {
		$zone_data = $this->cache->get('zone.' . (int)$country_id);

		if ($zone_data === false || $zone_data === null) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '" . (int)$country_id . "' AND status = '1' ORDER BY name");

			$zone_data = $query->rows;

			$this->cache->set('zone.' . (int)$country_id, $zone_data);
		}

		return $zone_data;
	}

	public function getTotalZones() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "zone");

		return $query->row['total'];
	}

	public function getTotalZonesByCountryId($country_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "zone WHERE country_id = '" . (int)$country_id . "'");

		return $query->row['total'];
	}
}