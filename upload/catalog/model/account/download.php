<?php
class ModelAccountDownload extends Model {
	public function getDownload($download_id) {
		$where = $this->completeStatusSql('o');
		if ($where === '') {
			return;
		}

		// RC47 digital entitlement: a completed purchase grants the customer the
		// current downloads attached to that purchased product. This means a new
		// version may use a new Download ID and existing buyers still see it.
		if ($this->orderDownloadTableExists()) {
			$query = $this->db->query("SELECT d.filename, d.mask, '' AS snapshot_filename, '' AS snapshot_mask FROM `" . DB_PREFIX . "order_download` od INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = od.order_id) INNER JOIN `" . DB_PREFIX . "product_to_download` p2d ON (p2d.product_id = od.product_id AND p2d.download_id = '" . (int)$download_id . "') INNER JOIN `" . DB_PREFIX . "download` d ON (d.download_id = p2d.download_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") ORDER BY o.date_added DESC LIMIT 1");
			if ($query->num_rows) {
				return $query->row;
			}

			// Purchased snapshot fallback. If the merchant removes/replaces current
			// product relations, the exact originally purchased file is still recoverable.
			$query = $this->db->query("SELECT od.filename AS snapshot_filename, od.mask AS snapshot_mask, d.filename AS current_filename, d.mask AS current_mask FROM `" . DB_PREFIX . "order_download` od INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = od.order_id) LEFT JOIN `" . DB_PREFIX . "download` d ON (d.download_id = od.download_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") AND od.download_id = '" . (int)$download_id . "' ORDER BY od.order_download_id DESC LIMIT 1");
			if ($query->num_rows) {
				return array(
					'filename' => !empty($query->row['current_filename']) ? $query->row['current_filename'] : $query->row['snapshot_filename'],
					'mask' => !empty($query->row['current_mask']) ? $query->row['current_mask'] : $query->row['snapshot_mask'],
					'snapshot_filename' => $query->row['snapshot_filename'],
					'snapshot_mask' => $query->row['snapshot_mask']
				);
			}
		}

		// Compatibility for orders created before RC47 snapshots existed.
		$query = $this->db->query("SELECT d.filename, d.mask FROM `" . DB_PREFIX . "order` o LEFT JOIN `" . DB_PREFIX . "order_product` op ON (o.order_id = op.order_id) LEFT JOIN `" . DB_PREFIX . "product_to_download` p2d ON (op.product_id = p2d.product_id) LEFT JOIN `" . DB_PREFIX . "download` d ON (p2d.download_id = d.download_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") AND d.download_id = '" . (int)$download_id . "'" . $this->legacyOrderPredicate('o') . " LIMIT 1");

		return $query->row;
	}

	public function getDownloads($start = 0, $limit = 20) {
		$start = max(0, (int)$start);
		$limit = max(1, (int)$limit);
		$where = $this->completeStatusSql('o');
		if ($where === '') {
			return array();
		}

		$sql = array();
		if ($this->orderDownloadTableExists()) {
			// Current version channel: one entitlement per purchased product, then
			// expose every download currently attached to that product. New Download
			// IDs therefore become visible to historical buyers of that product.
			$sql[] = "SELECT p2d.download_id AS row_id, p2d.download_id, ent.order_id, ent.date_added, COALESCE(NULLIF(dd.name, ''), d.mask) AS name, d.filename, '' AS snapshot_filename FROM (SELECT od.product_id, MAX(o.order_id) AS order_id, MAX(o.date_added) AS date_added FROM `" . DB_PREFIX . "order_download` od INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = od.order_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") GROUP BY od.product_id) ent INNER JOIN `" . DB_PREFIX . "product_to_download` p2d ON (p2d.product_id = ent.product_id) INNER JOIN `" . DB_PREFIX . "download` d ON (d.download_id = p2d.download_id) LEFT JOIN `" . DB_PREFIX . "download_description` dd ON (dd.download_id = d.download_id AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "')";

			// Snapshot fallback only when the purchased product currently has no
			// download relation at all. This avoids showing obsolete + current versions
			// together, while preserving access if the merchant removes the relation.
			$sql[] = "SELECT od.order_download_id AS row_id, od.download_id, o.order_id, o.date_added, COALESCE(NULLIF(dd.name, ''), od.name) AS name, COALESCE(NULLIF(d.filename, ''), od.filename) AS filename, od.filename AS snapshot_filename FROM `" . DB_PREFIX . "order_download` od INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = od.order_id) LEFT JOIN `" . DB_PREFIX . "download` d ON (d.download_id = od.download_id) LEFT JOIN `" . DB_PREFIX . "download_description` dd ON (dd.download_id = od.download_id AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_download` p2dx WHERE p2dx.product_id = od.product_id)";
		}

		$sql[] = "SELECT op.order_product_id AS row_id, d.download_id, o.order_id, o.date_added, dd.name, d.filename, d.filename AS snapshot_filename FROM `" . DB_PREFIX . "order` o LEFT JOIN `" . DB_PREFIX . "order_product` op ON (o.order_id = op.order_id) LEFT JOIN `" . DB_PREFIX . "product_to_download` p2d ON (op.product_id = p2d.product_id) LEFT JOIN `" . DB_PREFIX . "download` d ON (p2d.download_id = d.download_id) LEFT JOIN `" . DB_PREFIX . "download_description` dd ON (d.download_id = dd.download_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND (" . $where . ")" . $this->legacyOrderPredicate('o');

		$query = $this->db->query("SELECT DISTINCT * FROM (" . implode(" UNION ALL ", $sql) . ") downloads WHERE download_id IS NOT NULL ORDER BY date_added DESC, row_id DESC LIMIT " . $start . "," . $limit);
		return $query->rows;
	}

	public function getTotalDownloads() {
		$where = $this->completeStatusSql('o');
		if ($where === '') {
			return 0;
		}

		$total = 0;
		if ($this->orderDownloadTableExists()) {
			$query = $this->db->query("SELECT COUNT(DISTINCT CONCAT(ent.product_id, ':', p2d.download_id)) AS total FROM (SELECT DISTINCT od.product_id FROM `" . DB_PREFIX . "order_download` od INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = od.order_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ")) ent INNER JOIN `" . DB_PREFIX . "product_to_download` p2d ON (p2d.product_id = ent.product_id)");
			$total += (int)$query->row['total'];

			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_download` od INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = od.order_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_download` p2dx WHERE p2dx.product_id = od.product_id)");
			$total += (int)$query->row['total'];
		}

		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o LEFT JOIN `" . DB_PREFIX . "order_product` op ON (o.order_id = op.order_id) LEFT JOIN `" . DB_PREFIX . "product_to_download` p2d ON (op.product_id = p2d.product_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . $where . ") AND p2d.download_id IS NOT NULL" . $this->legacyOrderPredicate('o'));
		$total += (int)$query->row['total'];

		return $total;
	}

	private function completeStatusSql($alias) {
		$status_ids = array_map('intval', (array)$this->config->get('config_complete_status'));
		$status_ids = array_values(array_filter($status_ids));
		if (!$status_ids) {
			return '';
		}

		return $alias . ".order_status_id IN (" . implode(',', $status_ids) . ")";
	}

	private function legacyOrderPredicate($alias) {
		if (!$this->orderDownloadTableExists()) {
			return '';
		}

		return " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "order_download` odx WHERE odx.order_id = " . $alias . ".order_id)";
	}

	private function orderDownloadTableExists() {
		static $exists = null;
		if ($exists === null) {
			$query = $this->db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $this->db->escape(DB_PREFIX . "order_download") . "' LIMIT 1");
			$exists = (bool)$query->num_rows;
		}

		return $exists;
	}
}
