<?php
class ModelCatalogCategory extends Model {
	public function getCategory($category_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row;
	}


	public function getCategoriesByIds($category_ids) {
		$category_ids = array_values(array_unique(array_filter(array_map('intval', (array)$category_ids))));
		if (!$category_ids) {
			return array();
		}
		$ids = implode(',', $category_ids);
		$query = $this->db->query("SELECT c.*, cd.* FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) INNER JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id IN (" . $ids . ") AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1' ORDER BY FIELD(c.category_id," . $ids . ")");
		return $query->rows;
	}

	public function getAllCategories() {
		$query = $this->db->query("SELECT c.category_id, c.parent_id, c.top, c.column, c.sort_order, c.image, cd.name FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) INNER JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name), c.category_id");
		return $query->rows;
	}


	public function getCategoriesByParentIds($parent_ids) {
		$parent_ids = array_values(array_unique(array_filter(array_map('intval', (array)$parent_ids))));
		if (!$parent_ids) { return array(); }
		$query = $this->db->query("SELECT c.*, cd.* FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) INNER JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id IN (" . implode(',', $parent_ids) . ") AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1' ORDER BY c.parent_id, c.sort_order, LCASE(cd.name)");
		$out = array();
		foreach ($query->rows as $row) { $out[(int)$row['parent_id']][] = $row; }
		return $out;
	}

	public function getCategories($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");

		return $query->rows;
	}

	public function getCategoriesForSitemap($start = 0, $limit = 10000) {
		$start = max(0, (int)$start);
		$limit = max(1, min(10000, (int)$limit));
		$sql = "SELECT c.category_id, c.date_modified, c.noindex FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) INNER JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND c.noindex > '0'";
		}
		$sql .= " ORDER BY c.category_id ASC LIMIT " . $start . "," . $limit;
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalCategoriesForSitemap() {
		$sql = "SELECT COUNT(DISTINCT c.category_id) AS total FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) INNER JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND c.noindex > '0'";
		}
		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	public function getCategoryFilters($category_id) {
		$implode = array();

		$query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name']
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $filter_data
					);
				}
			}
		}

		return $filter_group_data;
	}

	public function getCategoryLayoutId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}


	public function getResolvedPurchaseBlocks($category_id, $language_id = 0) {
		$category_id = (int)$category_id;
		$language_id = $language_id ? (int)$language_id : (int)$this->config->get('config_language_id');
		if ($category_id < 1 || $language_id < 1) { return array(); }

		$query = $this->db->query("SELECT marker.mode, lang.data FROM " . DB_PREFIX . "category_path cp INNER JOIN " . DB_PREFIX . "codecart_purchase_block marker ON (marker.owner_type='category' AND marker.owner_id=cp.path_id AND marker.language_id='0') LEFT JOIN " . DB_PREFIX . "codecart_purchase_block lang ON (lang.owner_type='category' AND lang.owner_id=cp.path_id AND lang.language_id='" . $language_id . "') WHERE cp.category_id='" . $category_id . "' ORDER BY cp.level DESC LIMIT 1");
		if (!$query->num_rows || (int)$query->row['mode'] === 2 || !isset($query->row['data']) || $query->row['data'] === null) { return array(); }
		return \CodeCart\Core\PurchaseBlocks::decode($query->row['data']);
	}

}
