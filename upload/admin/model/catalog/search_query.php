<?php
class ModelCatalogSearchQuery extends Model {
	public function getQueries($days, $zero_only, $start, $limit) {
		$sql = "SELECT MAX(query_text) AS query_text, SUM(searches) AS searches, SUM(zero_results) AS zero_results, SUM(corrected) AS corrected, MAX(corrected_to) AS corrected_to, MAX(day) AS last_day FROM `" . DB_PREFIX . "codecart_search_query` WHERE day >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY) GROUP BY query_hash";

		if ($zero_only) {
			$sql .= " HAVING SUM(zero_results) > 0";
		}

		$sql .= " ORDER BY " . ($zero_only ? "SUM(zero_results)" : "SUM(searches)") . " DESC, query_text ASC LIMIT " . (int)$start . ", " . (int)$limit;

		return $this->db->query($sql)->rows;
	}

	public function getTotalQueries($days, $zero_only) {
		$sql = "SELECT COUNT(*) AS total FROM (SELECT query_hash FROM `" . DB_PREFIX . "codecart_search_query` WHERE day >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY) GROUP BY query_hash" . ($zero_only ? " HAVING SUM(zero_results) > 0" : "") . ") grouped";

		return (int)$this->db->query($sql)->row['total'];
	}

	public function clear() {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_search_query`");
	}
}
