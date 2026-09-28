<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelCatalogProduct extends Model {
	public function updateViewed($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");
	}

	public function getProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, p.noindex AS noindex, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start < NOW()) AND (pd2.date_end < '1000-01-01' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_title'       => $query->row['meta_title'],
				'noindex'          => $query->row['noindex'],
				'meta_h1'	       => $query->row['meta_h1'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'tag'              => $query->row['tag'],
				'model'            => $query->row['model'],
				'sku'              => $query->row['sku'],
				'upc'              => $query->row['upc'],
				'ean'              => $query->row['ean'],
				'jan'              => $query->row['jan'],
				'isbn'             => $query->row['isbn'],
				'mpn'              => $query->row['mpn'],
				'location'         => $query->row['location'],
				'quantity'         => $query->row['quantity'],
				'stock_status'     => $query->row['stock_status'],
				'image'            => $query->row['image'],
				'manufacturer_id'  => $query->row['manufacturer_id'],
				'manufacturer'     => $query->row['manufacturer'],
				'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
				'special'          => $query->row['special'],
				'reward'           => $query->row['reward'],
				'points'           => $query->row['points'],
				'tax_class_id'     => $query->row['tax_class_id'],
				'tax_display_mode'  => isset($query->row['tax_display_mode']) ? $query->row['tax_display_mode'] : 'inherit',
				'date_available'   => $query->row['date_available'],
				'weight'           => $query->row['weight'],
				'weight_class_id'  => $query->row['weight_class_id'],
				'length'           => $query->row['length'],
				'width'            => $query->row['width'],
				'height'           => $query->row['height'],
				'length_class_id'  => $query->row['length_class_id'],
				'subtract'         => $query->row['subtract'],
				'rating'           => round(($query->row['rating']===null) ? 0 : $query->row['rating']),
				'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
				'minimum'          => $query->row['minimum'],
				'sort_order'       => $query->row['sort_order'],
				'status'           => $query->row['status'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}


	private function getProductsByIdsFull(array $product_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
		if (!$ids) {
			return array();
		}

		$id_list = implode(',', $ids);
		$query = $this->db->query("SELECT DISTINCT p.*, pd.name AS name, pd.description, pd.meta_title, pd.meta_h1, pd.meta_description, pd.meta_keyword, pd.tag, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start < NOW()) AND (pd2.date_end < '1000-01-01' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT AVG(r.rating) FROM " . DB_PREFIX . "review r WHERE r.product_id = p.product_id AND r.status = '1') AS rating, (SELECT COUNT(*) FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1') AS reviews FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id IN (" . $id_list . ") AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY FIELD(p.product_id," . $id_list . ")");
		$out = array();
		foreach ($query->rows as $row) {
			$out[(int)$row['product_id']] = array(
				'product_id' => $row['product_id'], 'name' => $row['name'], 'description' => $row['description'], 'meta_title' => $row['meta_title'], 'noindex' => $row['noindex'], 'meta_h1' => $row['meta_h1'], 'meta_description' => $row['meta_description'], 'meta_keyword' => $row['meta_keyword'], 'tag' => $row['tag'], 'model' => $row['model'], 'sku' => $row['sku'], 'upc' => $row['upc'], 'ean' => $row['ean'], 'jan' => $row['jan'], 'isbn' => $row['isbn'], 'mpn' => $row['mpn'], 'location' => $row['location'], 'quantity' => $row['quantity'], 'stock_status' => $row['stock_status'], 'image' => $row['image'], 'manufacturer_id' => $row['manufacturer_id'], 'manufacturer' => $row['manufacturer'], 'price' => ($row['discount'] !== null ? $row['discount'] : $row['price']), 'special' => $row['special'], 'reward' => $row['reward'], 'points' => $row['points'], 'tax_class_id' => $row['tax_class_id'], 'tax_display_mode' => isset($row['tax_display_mode']) ? $row['tax_display_mode'] : 'inherit', 'date_available' => $row['date_available'], 'weight' => $row['weight'], 'weight_class_id' => $row['weight_class_id'], 'length' => $row['length'], 'width' => $row['width'], 'height' => $row['height'], 'length_class_id' => $row['length_class_id'], 'subtract' => $row['subtract'], 'rating' => round($row['rating'] === null ? 0 : $row['rating']), 'reviews' => $row['reviews'] ? $row['reviews'] : 0, 'minimum' => $row['minimum'], 'sort_order' => $row['sort_order'], 'status' => $row['status'], 'date_added' => $row['date_added'], 'date_modified' => $row['date_modified'], 'viewed' => $row['viewed']
			);
		}
		return $out;
	}


	public function getProductCardsByIds(array $product_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
		if (!$ids) {
			return array();
		}

		// Product cards keep the historical OpenCart/ocStore getProduct() row contract
		// (ean, isbn, mpn, date_available, manufacturer, weight, ...). Themes such as
		// UniShop2 and third-party OCMOD modules read these keys from $result inside
		// product-list loops. The rows are still hydrated in one batched query.
		$rows = $this->getProductsByIdsFull($ids);
		$products = array();
		foreach ($ids as $id) {
			if (!isset($rows[$id])) {
				continue;
			}
			$row = $rows[$id];
			$row['product_id'] = (int)$row['product_id'];
			$row['name'] = (string)$row['name'];
			$row['model'] = (string)$row['model'];
			$row['description'] = (string)$row['description'];
			$row['image'] = (string)$row['image'];
			$row['quantity'] = (int)$row['quantity'];
			$row['stock_status'] = (string)$row['stock_status'];
			$row['price'] = (float)$row['price'];
			$row['special'] = ($row['special'] !== null ? (float)$row['special'] : null);
			$row['tax_class_id'] = (int)$row['tax_class_id'];
			$row['tax_display_mode'] = (string)$row['tax_display_mode'];
			$row['minimum'] = max(1, (int)$row['minimum']);
			$row['rating'] = round((float)$row['rating']);
			$row['reviews'] = (int)$row['reviews'];
			$products[] = $row;
		}
		return $products;
	}

	public function getProductsForSitemap($start = 0, $limit = 10000) {
		$start = max(0, (int)$start);
		$limit = max(1, min(10000, (int)$limit));

		$sql = "SELECT p.product_id, p.image, p.date_modified, p.noindex, pd.name FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.status = '1' AND p.date_available <= NOW()";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND p.noindex > '0'";
		}
		$sql .= " ORDER BY p.product_id ASC LIMIT " . $start . "," . $limit;
		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalProductsForSitemap() {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.status = '1' AND p.date_available <= NOW()";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND p.noindex > '0'";
		}
		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	public function getProducts($data = array()) {
		$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start < NOW()) AND (pd2.date_end < '1000-01-01' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$filter_id = (int)$filter_id;
					if ($filter_id > 0) {
						$implode[$filter_id] = $filter_id;
					}
				}

				if ($implode) {
					$filter_ids = implode(',', array_values($implode));
					$sql .= " AND pf.filter_id IN (" . $filter_ids . ")";
					// Match OR within one filter group, but require a match from every selected group.
					// This stays in SQL instead of loading matching product IDs into PHP memory.
					$sql .= " AND (SELECT COUNT(DISTINCT f_match.filter_group_id) FROM " . DB_PREFIX . "product_filter pf_match INNER JOIN `" . DB_PREFIX . "filter` f_match ON (f_match.filter_id = pf_match.filter_id) WHERE pf_match.product_id = p.product_id AND pf_match.filter_id IN (" . $filter_ids . ")) = (SELECT COUNT(DISTINCT f_required.filter_group_id) FROM `" . DB_PREFIX . "filter` f_required WHERE f_required.filter_id IN (" . $filter_ids . "))";
				} else {
					$sql .= " AND 1 = 0";
				}
			}
		}

		$search_condition = $this->buildSearchCondition($data);
		if ($search_condition !== '') {
			$sql .= " AND (" . $search_condition . ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
			'rating',
			'p.sort_order',
			'p.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$sql = $this->pruneListingSubqueries($sql);

		$query = $this->db->query($sql);
		$ids = array();
		foreach ($query->rows as $result) { $ids[] = (int)$result['product_id']; }
		return $this->getProductsByIdsFull($ids);
	}

	/**
	 * The listing query only returns product IDs; prices/ratings are hydrated later
	 * in one batch. Correlated rating/discount/special subqueries are therefore only
	 * needed when the ORDER BY/HAVING (including third-party OCMOD additions) uses
	 * them. Otherwise MySQL evaluates them for every matching row before LIMIT,
	 * which dominates category/manufacturer pages on large catalogs.
	 */
	private function pruneListingSubqueries($sql) {
		$group = (int)$this->config->get('config_customer_group_id');
		$columns = array(
			'rating' => ", (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating",
			'discount' => ", (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . $group . "' AND pd2.quantity = '1' AND ((pd2.date_start < NOW()) AND (pd2.date_end < '1000-01-01' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount",
			'special' => ", (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . $group . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special"
		);

		$head = 'SELECT p.product_id' . implode('', $columns);
		if (strpos($sql, $head) !== 0) {
			return $sql; // Modified by a third-party extension: keep it untouched.
		}

		$tail = substr($sql, strlen($head));
		$keep = '';
		foreach ($columns as $alias => $expression) {
			if (preg_match('/\b' . $alias . '\b/', $tail)) {
				$keep .= $expression;
			}
		}

		return 'SELECT p.product_id' . $keep . $tail;
	}

	public function getProductSpecials($data = array()) {
		$sql = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) GROUP BY ps.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'ps.price',
			'rating',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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
		$ids = array();
		foreach ($query->rows as $result) { $ids[] = (int)$result['product_id']; }
		return $this->getProductsByIdsFull($ids);
	}


	public function getLatestProductCards($limit) {
		$limit = max(1, min(100, (int)$limit));
		$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC, p.product_id DESC LIMIT " . $limit);
		$ids = array();
		foreach ($query->rows as $row) { $ids[] = (int)$row['product_id']; }
		return $this->getProductCardsByIds($ids);
	}

	public function getPopularProductCards($limit) {
		$limit = max(1, min(100, (int)$limit));
		$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC, p.product_id DESC LIMIT " . $limit);
		$ids = array();
		foreach ($query->rows as $row) { $ids[] = (int)$row['product_id']; }
		return $this->getProductCardsByIds($ids);
	}

	public function getBestSellerProductCards($limit) {
		$limit = max(1, min(100, (int)$limit));
		$query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) INNER JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY op.product_id ORDER BY total DESC, op.product_id DESC LIMIT " . $limit);
		$ids = array();
		foreach ($query->rows as $row) { $ids[] = (int)$row['product_id']; }
		return $this->getProductCardsByIds($ids);
	}

	public function getSpecialProductCards($limit) {
		$limit = max(1, min(100, (int)$limit));
		$query = $this->db->query("SELECT DISTINCT ps.product_id, MIN(ps.priority) AS min_priority, MIN(ps.price) AS min_price, p.sort_order FROM " . DB_PREFIX . "product_special ps INNER JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) GROUP BY ps.product_id, p.sort_order ORDER BY p.sort_order ASC, min_priority ASC, min_price ASC, ps.product_id ASC LIMIT " . $limit);
		$ids = array();
		foreach ($query->rows as $row) { $ids[] = (int)$row['product_id']; }
		return $this->getProductCardsByIds($ids);
	}


	public function getProductIdsByCategory($category_id, $include_sub_categories = true, $limit = 20) {
		$category_id = (int)$category_id;
		$limit = max(1, min(100, (int)$limit));
		if ($category_id < 1) {
			return array();
		}

		if ($include_sub_categories) {
			$sql = "SELECT DISTINCT p.product_id, p.sort_order FROM " . DB_PREFIX . "category_path cp INNER JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id) INNER JOIN `" . DB_PREFIX . "product` p ON (p2c.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE cp.path_id = '" . $category_id . "'";
		} else {
			$sql = "SELECT DISTINCT p.product_id, p.sort_order FROM " . DB_PREFIX . "product_to_category p2c INNER JOIN `" . DB_PREFIX . "product` p ON (p2c.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p2c.category_id = '" . $category_id . "'";
		}
		$sql .= " AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.sort_order ASC, p.product_id DESC LIMIT " . $limit;
		$query = $this->db->query($sql);
		$ids = array();
		foreach ($query->rows as $row) {
			$ids[] = (int)$row['product_id'];
		}
		return $ids;
	}

	public function getProductIdsByManufacturer($manufacturer_id, $limit = 20) {
		$manufacturer_id = (int)$manufacturer_id;
		$limit = max(1, min(100, (int)$limit));
		if ($manufacturer_id < 1) {
			return array();
		}
		$query = $this->db->query("SELECT p.product_id FROM `" . DB_PREFIX . "product` p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.manufacturer_id = '" . $manufacturer_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.sort_order ASC, p.product_id DESC LIMIT " . $limit);
		$ids = array();
		foreach ($query->rows as $row) {
			$ids[] = (int)$row['product_id'];
		}
		return $ids;
	}

	public function getLatestProducts($limit) {
		$product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if ($product_data === false || $product_data === null) {
			$product_data = array();
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getPopularProducts($limit) {
		$product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);
	
		if ($product_data === false || $product_data === null) {
			$product_data = array();
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
	
			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}
			
			$this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}
		
		return $product_data;
	}

	public function getBestSellerProducts($limit) {
		$product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if ($product_data === false || $product_data === null) {
			$product_data = array();

			$query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getProductCardAttributes(array $product_ids, $limit = 3) {
		$limit = max(1, min(10, (int)$limit));
		$product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));

		if (!$product_ids) {
			return array();
		}

		$product_attribute_data = array();
		$query = $this->db->query("SELECT pa.product_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa INNER JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) INNER JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id IN (" . implode(',', $product_ids) . ") AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND TRIM(pa.text) <> '' ORDER BY pa.product_id, a.sort_order, ad.name");

		foreach ($query->rows as $row) {
			$product_id = (int)$row['product_id'];
			if (!isset($product_attribute_data[$product_id])) {
				$product_attribute_data[$product_id] = array();
			}
			if (count($product_attribute_data[$product_id]) >= $limit) {
				continue;
			}
			$product_attribute_data[$product_id][] = array(
				'name' => (string)$row['name'],
				'text' => trim((string)$row['text'])
			);
		}

		return $product_attribute_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_group_data = array();

		$product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = array();

			$product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = array(
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				);
			}

			$product_attribute_group_data[] = array(
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			);
		}

		return $product_attribute_group_data;
	}

	public function getProductOptions($product_id) {
		$product_id = (int)$product_id;
		$product_option_data = array();
		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . $product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");
		if (!$product_option_query->rows) { return array(); }

		$value_query = $this->db->query("SELECT pov.*, ov.image, ov.sort_order, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . $product_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY pov.product_option_id, ov.sort_order");
		$values_by_option = array();
		foreach ($value_query->rows as $product_option_value) {
			$values_by_option[(int)$product_option_value['product_option_id']][] = array(
				'product_option_value_id' => $product_option_value['product_option_value_id'],
				'option_value_id' => $product_option_value['option_value_id'],
				'name' => $product_option_value['name'],
				'image' => $product_option_value['image'],
				'quantity' => $product_option_value['quantity'],
				'subtract' => $product_option_value['subtract'],
				'price' => $product_option_value['price'],
				'price_prefix' => $product_option_value['price_prefix'],
				'weight' => $product_option_value['weight'],
				'weight_prefix' => $product_option_value['weight_prefix']
			);
		}
		foreach ($product_option_query->rows as $product_option) {
			$id = (int)$product_option['product_option_id'];
			$product_option_data[] = array(
				'product_option_id' => $product_option['product_option_id'],
				'product_option_value' => isset($values_by_option[$id]) ? $values_by_option[$id] : array(),
				'option_id' => $product_option['option_id'],
				'name' => $product_option['name'],
				'type' => $product_option['type'],
				'value' => $product_option['value'],
				'required' => $product_option['required']
			);
		}
		return $product_option_data;
	}

	public function getProductPricePreview($product_id, $quantity = 1, $option = array()) {
		$product_id = (int)$product_id;
		$quantity = max(1, (int)$quantity);
		$option = is_array($option) ? $option : array();

		$product_query = $this->db->query("SELECT p.product_id, p.price, p.tax_class_id, p.tax_display_mode, p.minimum FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.product_id = '" . $product_id . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.status = '1' AND p.date_available <= NOW() LIMIT 1");
		if (!$product_query->num_rows) { return false; }

		$quantity = max($quantity, max(1, (int)$product_query->row['minimum']));
		$regular = (float)$product_query->row['price'];
		$discount = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . $product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity <= '" . $quantity . "' AND ((date_start < NOW()) AND (date_end < '1000-01-01' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
		if ($discount->num_rows) { $regular = (float)$discount->row['price']; }

		$current = $regular;
		$special = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . $product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start < NOW()) AND (date_end < '1000-01-01' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");
		if ($special->num_rows) { $current = (float)$special->row['price']; }

		$delta = 0.0;
		$equal = null;
		foreach ($option as $product_option_id => $value) {
			$product_option_id = (int)$product_option_id;
			$option_query = $this->db->query("SELECT po.product_option_id, o.type FROM " . DB_PREFIX . "product_option po INNER JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) WHERE po.product_option_id = '" . $product_option_id . "' AND po.product_id = '" . $product_id . "' LIMIT 1");
			if (!$option_query->num_rows) { continue; }
			$type = (string)$option_query->row['type'];
			$values = ($type === 'checkbox' && is_array($value)) ? $value : array($value);
			foreach ($values as $selected) {
				if (!in_array($type, array('select','radio','checkbox','image'), true)) { continue; }
				$value_query = $this->db->query("SELECT price, price_prefix FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$selected . "' AND product_option_id = '" . $product_option_id . "' AND product_id = '" . $product_id . "' LIMIT 1");
				if (!$value_query->num_rows) { continue; }
				$prefix = (string)$value_query->row['price_prefix'];
				$value_price = (float)$value_query->row['price'];
				if ($prefix === '+') { $delta += $value_price; }
				elseif ($prefix === '-') { $delta -= $value_price; }
				elseif ($prefix === '=' && ($type === 'select' || $type === 'radio' || $type === 'image')) { $equal = $value_price; }
			}
		}

		if ($equal !== null) {
			$regular = $equal;
			$current = $equal;
		} else {
			$regular += $delta;
			$current += $delta;
		}

		return array(
			'quantity' => $quantity,
			'regular' => $regular,
			'current' => $current,
			'is_special' => ($equal === null && $special->num_rows && abs($regular - $current) > 0.00001),
			'tax_class_id' => (int)$product_query->row['tax_class_id'],
			'tax_display_mode' => isset($product_query->row['tax_display_mode']) ? (string)$product_query->row['tax_display_mode'] : 'inherit'
		);
	}


    public function getResolvedPurchaseBlocks($product_id, $language_id = 0) {
        $product_id = (int)$product_id;
        $language_id = $language_id ? (int)$language_id : (int)$this->config->get('config_language_id');
        if ($product_id < 1) { return array(); }

        // Category blocks are reusable templates for product pages. Walk the
        // main category path from the nearest category upward. "Inherit" skips
        // to the parent, "custom" supplies the category template, and
        // "disabled" stops category inheritance completely.
        $category_blocks = array();
        $category_id = 0;
        try {
            $cat = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id='" . $product_id . "' ORDER BY main_category DESC, category_id ASC LIMIT 1");
            if ($cat->num_rows) { $category_id = (int)$cat->row['category_id']; }
        } catch (\Throwable $e) {
            $cat = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id='" . $product_id . "' ORDER BY category_id ASC LIMIT 1");
            if ($cat->num_rows) { $category_id = (int)$cat->row['category_id']; }
        }
        if ($category_id) {
            $category_query = $this->db->query("SELECT cp.path_id, marker.mode, lang.data FROM " . DB_PREFIX . "category_path cp INNER JOIN " . DB_PREFIX . "codecart_purchase_block marker ON (marker.owner_type='category' AND marker.owner_id=cp.path_id AND marker.language_id='0') LEFT JOIN " . DB_PREFIX . "codecart_purchase_block lang ON (lang.owner_type='category' AND lang.owner_id=cp.path_id AND lang.language_id='" . $language_id . "') WHERE cp.category_id='" . $category_id . "' ORDER BY cp.level DESC");
            foreach ($category_query->rows as $category_row) {
                $mode = (int)$category_row['mode'];
                if ($mode === 0) { continue; }
                if ($mode === 2) {
                    // Category "disabled" suppresses only inherited category content.
                    // An explicit product-level custom selection has higher priority and
                    // is still rendered. This keeps inheritance additive and predictable.
                    $category_blocks = array();
                    break;
                }
                $category_blocks = isset($category_row['data']) && $category_row['data'] !== null ? \CodeCart\Core\PurchaseBlocks::decode($category_row['data']) : array();
                break;
            }
        }


        // Product mode: inherit category template; custom appends product blocks;
        // disabled suppresses all purchase-area blocks for this product.
        $marker = $this->db->query("SELECT mode FROM " . DB_PREFIX . "codecart_purchase_block WHERE owner_type='product' AND owner_id='" . $product_id . "' AND language_id='0' LIMIT 1");
        if (!$marker->num_rows || (int)$marker->row['mode'] === 0) { return $category_blocks; }
        if ((int)$marker->row['mode'] === 2) { return array(); }

        $lang = $this->db->query("SELECT data FROM " . DB_PREFIX . "codecart_purchase_block WHERE owner_type='product' AND owner_id='" . $product_id . "' AND language_id='" . $language_id . "' LIMIT 1");
        if (!$lang->num_rows) { return $category_blocks; }
        $product_blocks = \CodeCart\Core\PurchaseBlocks::decode($lang->row['data']);
        return array_merge($category_blocks, $product_blocks);
    }

	public function getProductDiscounts($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity > 1 AND ((date_start < NOW()) AND (date_end < '1000-01-01' OR date_end > NOW())) ORDER BY quantity ASC, priority ASC, price ASC");

		return $query->rows;
	}

	public function getProductExtraTab($product_id, $language_id = 0) {
		$language_id = $language_id ? (int)$language_id : (int)$this->config->get('config_language_id');
		$query = $this->db->query("SELECT mode, title, content FROM " . DB_PREFIX . "product_extra_tab WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "' LIMIT 1");
		return $query->num_rows ? $query->row : array();
	}

	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductRelated($product_id) {
		$product_id = (int)$product_id;
		if ($product_id < 1) {
			return array();
		}

		$query = $this->db->query("SELECT pr.related_id FROM " . DB_PREFIX . "product_related pr INNER JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.product_id = '" . $product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY pr.related_id ASC");
		$ids = array();
		foreach ($query->rows as $row) {
			$ids[] = (int)$row['related_id'];
		}
		if (!$ids) {
			return array();
		}

		$cards = $this->getProductCardsByIds($ids);
		$product_data = array();
		foreach ($cards as $card) {
			$product_data[(int)$card['product_id']] = $card;
		}
		return $product_data;
	}

	public function getProductLayoutId($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategories($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}


	public function getCategoryProductCounts(array $category_ids, $include_sub_categories = true) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $category_ids))));
		if (!$ids) {
			return array();
		}
		sort($ids, SORT_NUMERIC);
		$store_id = (int)$this->config->get('config_store_id');
		$cache_key = 'category_product_counts.' . $store_id . '.' . ($include_sub_categories ? 'tree' : 'direct') . '.' . sha1(implode(',', $ids));
		$cached = $this->cache->get($cache_key);
		if (is_array($cached)) {
			return $cached;
		}

		$id_list = implode(',', $ids);
		$counts = array_fill_keys($ids, 0);
		if ($include_sub_categories) {
			$sql = "SELECT cp.path_id AS category_id, COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "category_path cp INNER JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id) INNER JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE cp.path_id IN (" . $id_list . ") AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . $store_id . "' GROUP BY cp.path_id";
		} else {
			$sql = "SELECT p2c.category_id, COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product_to_category p2c INNER JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p2c.category_id IN (" . $id_list . ") AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . $store_id . "' GROUP BY p2c.category_id";
		}
		$query = $this->db->query($sql);
		foreach ($query->rows as $row) {
			$category_id = (int)$row['category_id'];
			if (isset($counts[$category_id])) {
				$counts[$category_id] = (int)$row['total'];
			}
		}
		$this->cache->set($cache_key, $counts, array('catalog.category_counts'));
		return $counts;
	}


	public function getFilterProductCounts($category_id, array $filter_ids) {
		$category_id = (int)$category_id;
		$ids = array_values(array_unique(array_filter(array_map('intval', $filter_ids))));
		if ($category_id < 1 || !$ids) {
			return array();
		}

		$counts = array_fill_keys($ids, 0);
		$id_list = implode(',', $ids);
		$query = $this->db->query("SELECT pf.filter_id, COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "category_path cp INNER JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id) INNER JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) INNER JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id) INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE cp.path_id = '" . $category_id . "' AND pf.filter_id IN (" . $id_list . ") AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY pf.filter_id");
		foreach ($query->rows as $row) {
			$filter_id = (int)$row['filter_id'];
			if (isset($counts[$filter_id])) {
				$counts[$filter_id] = (int)$row['total'];
			}
		}
		return $counts;
	}

	public function getSearchSuggestionTerms($limit = 400) {
		$limit = max(50, min(600, (int)$limit));
		$product_limit = min(300, $limit);
		$manufacturer_limit = min(100, max(0, $limit - $product_limit));
		$category_limit = max(0, $limit - $product_limit - $manufacturer_limit);
		$terms = array();
		$query = $this->db->query("SELECT pd.name AS term FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id=p.product_id AND pd.language_id='" . (int)$this->config->get('config_language_id') . "') INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p2s.product_id=p.product_id AND p2s.store_id='" . (int)$this->config->get('config_store_id') . "') WHERE p.status='1' AND p.date_available<=NOW() ORDER BY p.viewed DESC,p.date_added DESC LIMIT " . (int)$product_limit);
		foreach ($query->rows as $row) if (trim((string)$row['term'])!=='') $terms[]=(string)$row['term'];
		if ($manufacturer_limit) {
			$query=$this->db->query("SELECT m.name AS term FROM " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m2s.manufacturer_id=m.manufacturer_id AND m2s.store_id='" . (int)$this->config->get('config_store_id') . "') ORDER BY m.sort_order,m.name LIMIT " . (int)$manufacturer_limit);
			foreach($query->rows as $row) if(trim((string)$row['term'])!=='') $terms[]=(string)$row['term'];
		}
		if ($category_limit) {
			$query=$this->db->query("SELECT cd.name AS term FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id=c.category_id AND cd.language_id='" . (int)$this->config->get('config_language_id') . "') INNER JOIN " . DB_PREFIX . "category_to_store c2s ON (c2s.category_id=c.category_id AND c2s.store_id='" . (int)$this->config->get('config_store_id') . "') WHERE c.status='1' ORDER BY c.sort_order,cd.name LIMIT " . (int)$category_limit);
			foreach($query->rows as $row) if(trim((string)$row['term'])!=='') $terms[]=(string)$row['term'];
		}
		return array_values(array_unique($terms));
	}

	public function getTotalProducts($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$filter_id = (int)$filter_id;
					if ($filter_id > 0) {
						$implode[$filter_id] = $filter_id;
					}
				}

				if ($implode) {
					$filter_ids = implode(',', array_values($implode));
					$sql .= " AND pf.filter_id IN (" . $filter_ids . ")";
					// Match OR within one filter group, but require a match from every selected group.
					// This stays in SQL instead of loading matching product IDs into PHP memory.
					$sql .= " AND (SELECT COUNT(DISTINCT f_match.filter_group_id) FROM " . DB_PREFIX . "product_filter pf_match INNER JOIN `" . DB_PREFIX . "filter` f_match ON (f_match.filter_id = pf_match.filter_id) WHERE pf_match.product_id = p.product_id AND pf_match.filter_id IN (" . $filter_ids . ")) = (SELECT COUNT(DISTINCT f_required.filter_group_id) FROM `" . DB_PREFIX . "filter` f_required WHERE f_required.filter_id IN (" . $filter_ids . "))";
				} else {
					$sql .= " AND 1 = 0";
				}
			}
		}

		$search_condition = $this->buildSearchCondition($data);
		if ($search_condition !== '') {
			$sql .= " AND (" . $search_condition . ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}


	private function buildSearchCondition($data) {
		$clauses = array();
		$name = isset($data['filter_name']) ? trim(preg_replace('/\s+/u', ' ', (string)$data['filter_name'])) : '';
		$tag = isset($data['filter_tag']) ? trim(preg_replace('/\s+/u', ' ', (string)$data['filter_tag'])) : '';

		if ($name !== '') {
			$name_words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
			$name_parts = array();
			foreach ($name_words as $word) {
				if (utf8_strlen($word) >= 2) {
					$name_parts[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}
			}
			if ($name_parts) {
				$clauses[] = '(' . implode(' AND ', $name_parts) . ')';
			}

			// Description search is opt-in and still requires a meaningful query.
			if (!empty($data['filter_description']) && utf8_strlen($name) >= 2) {
				$clauses[] = "pd.description LIKE '%" . $this->db->escape($name) . "%'";
			}

			// Exact identifiers stay useful even for very short stock/model codes.
			$identifier = $this->db->escape(utf8_strtolower($name));
			$clauses[] = "LCASE(p.model) = '" . $identifier . "'";
			$clauses[] = "LCASE(p.sku) = '" . $identifier . "'";
			$clauses[] = "LCASE(p.upc) = '" . $identifier . "'";
			$clauses[] = "LCASE(p.ean) = '" . $identifier . "'";
			$clauses[] = "LCASE(p.jan) = '" . $identifier . "'";
			$clauses[] = "LCASE(p.isbn) = '" . $identifier . "'";
			$clauses[] = "LCASE(p.mpn) = '" . $identifier . "'";

			// Category/manufacturer substring search is useful, but only for non-trivial phrases.
			// This prevents one-letter queries from returning entire categories.
			if (utf8_strlen($name) >= 3) {
				$escaped_name = $this->db->escape($name);
				$clauses[] = "EXISTS (SELECT 1 FROM " . DB_PREFIX . "manufacturer m_search WHERE m_search.manufacturer_id = p.manufacturer_id AND m_search.name LIKE '%" . $escaped_name . "%')";
				$clauses[] = "EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_to_category p2c_search INNER JOIN " . DB_PREFIX . "category_description cd_search ON (cd_search.category_id = p2c_search.category_id AND cd_search.language_id = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN " . DB_PREFIX . "category_to_store c2s_search ON (c2s_search.category_id = p2c_search.category_id AND c2s_search.store_id = '" . (int)$this->config->get('config_store_id') . "') WHERE p2c_search.product_id = p.product_id AND cd_search.name LIKE '%" . $escaped_name . "%')";
			}
		}

		if ($tag !== '') {
			$tag_words = preg_split('/\s+/u', $tag, -1, PREG_SPLIT_NO_EMPTY);
			$tag_parts = array();
			foreach ($tag_words as $word) {
				if (utf8_strlen($word) >= 2) {
					$tag_parts[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}
			}
			if ($tag_parts) {
				$clauses[] = '(' . implode(' AND ', $tag_parts) . ')';
			}
		}

		// A non-empty query with no meaningful searchable clause must never broaden to all products.
		if (!$clauses && ($name !== '' || $tag !== '')) {
			return '1 = 0';
		}

		return implode(' OR ', $clauses);
	}


	public function getProfile($product_id, $recurring_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r JOIN " . DB_PREFIX . "product_recurring pr ON (pr.recurring_id = r.recurring_id AND pr.product_id = '" . (int)$product_id . "') WHERE pr.recurring_id = '" . (int)$recurring_id . "' AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

		return $query->row;
	}

	public function getProfiles($product_id) {
		$query = $this->db->query("SELECT rd.* FROM " . DB_PREFIX . "product_recurring pr JOIN " . DB_PREFIX . "recurring_description rd ON (rd.language_id = " . (int)$this->config->get('config_language_id') . " AND rd.recurring_id = pr.recurring_id) JOIN " . DB_PREFIX . "recurring r ON r.recurring_id = rd.recurring_id WHERE pr.product_id = " . (int)$product_id . " AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getTotalProductSpecials() {
		$query = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW()))");

		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function checkProductCategory($product_id, $category_ids) {
		$implode = array();

		foreach ((array)$category_ids as $category_id) {
			if ((int)$category_id > 0) {
				$implode[(int)$category_id] = (int)$category_id;
			}
		}

		if (!$implode) {
			return array();
		}

		// Category listings include products from subcategories (filter_sub_category),
		// so a product linked as path=20 may belong only to a child of category 20.
		// Accept direct membership and membership in any descendant of the path.
		$query = $this->db->query("SELECT p2c.product_id FROM " . DB_PREFIX . "product_to_category p2c LEFT JOIN " . DB_PREFIX . "category_path cp ON (cp.category_id = p2c.category_id) WHERE p2c.product_id = '" . (int)$product_id . "' AND (p2c.category_id IN (" . implode(',', $implode) . ") OR cp.path_id IN (" . implode(',', $implode) . ")) LIMIT 1");

		return $query->row;
	}
	public function getStockNotificationSelection($product_id, array $options) {
		$ids = array();
		foreach ($options as $value) {
			if (is_array($value)) { foreach ($value as $id) { if ((int)$id > 0) { $ids[(int)$id]=(int)$id; } } }
			elseif ((int)$value > 0) { $ids[(int)$value]=(int)$value; }
		}
		if (!$ids) { return array('tracked'=>array(),'unavailable'=>false,'label'=>''); }
		$q=$this->db->query("SELECT pov.product_option_value_id,pov.quantity,pov.subtract,od.name AS option_name,ovd.name AS value_name FROM `".DB_PREFIX."product_option_value` pov INNER JOIN `".DB_PREFIX."product_option` po ON (po.product_option_id=pov.product_option_id AND po.product_id='".(int)$product_id."') INNER JOIN `".DB_PREFIX."option_description` od ON (od.option_id=po.option_id AND od.language_id='".(int)$this->config->get('config_language_id')."') INNER JOIN `".DB_PREFIX."option_value_description` ovd ON (ovd.option_value_id=pov.option_value_id AND ovd.language_id='".(int)$this->config->get('config_language_id')."') WHERE pov.product_option_value_id IN (".implode(',',array_values($ids)).") AND pov.subtract='1'");
		$tracked=array();$labels=array();$unavailable=false;
		foreach($q->rows as $row){$id=(int)$row['product_option_value_id'];$tracked[$id]=$id;$labels[]=$row['option_name'].': '.$row['value_name'];if((int)$row['quantity']<=0){$unavailable=true;}}
		ksort($tracked);
		return array('tracked'=>array_values($tracked),'unavailable'=>$unavailable,'label'=>implode(', ',$labels));
	}

	public function addStockNotification($product_id, $email, $customer_id = 0, array $selection = array()) {
		$email = utf8_strtolower(trim((string)$email));
		if (!$product_id || !filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }
		$tracked = isset($selection['tracked']) ? array_values(array_unique(array_map('intval',(array)$selection['tracked']))) : array();
		sort($tracked, SORT_NUMERIC);
		$option_data = $tracked ? json_encode($tracked, JSON_UNESCAPED_SLASHES) : '';
		$option_hash = $tracked ? hash('sha256',$option_data) : '';
		$label = isset($selection['label']) ? trim((string)$selection['label']) : '';
		if (utf8_strlen($label) > 255) { $label = utf8_substr($label,0,255); }
		$this->db->query("INSERT INTO `" . DB_PREFIX . "stock_notify` SET product_id='" . (int)$product_id . "', customer_id='" . (int)$customer_id . "', store_id='" . (int)$this->config->get('config_store_id') . "', language_id='" . (int)$this->config->get('config_language_id') . "', email='" . $this->db->escape($email) . "', option_hash='".$this->db->escape($option_hash)."', option_data=".($option_data!==''?"'".$this->db->escape($option_data)."'":"NULL").", option_label='".$this->db->escape($label)."', status='waiting', attempts='0', date_added=NOW(), date_modified=NOW() ON DUPLICATE KEY UPDATE customer_id=VALUES(customer_id), language_id=VALUES(language_id), option_data=VALUES(option_data), option_label=VALUES(option_label), status='waiting', attempts='0', date_sent=NULL, date_modified=NOW()");
		return true;
	}

	public function hasStockNotification($product_id, $email, array $selection = array()) {
		$tracked=isset($selection['tracked'])?array_values(array_unique(array_map('intval',(array)$selection['tracked']))):array();sort($tracked,SORT_NUMERIC);$hash=$tracked?hash('sha256',json_encode($tracked,JSON_UNESCAPED_SLASHES)):'';
		$q=$this->db->query("SELECT stock_notify_id FROM `" . DB_PREFIX . "stock_notify` WHERE product_id='".(int)$product_id."' AND store_id='".(int)$this->config->get('config_store_id')."' AND email='".$this->db->escape(utf8_strtolower(trim((string)$email)))."' AND option_hash='".$this->db->escape($hash)."' AND status='waiting' LIMIT 1");
		return (bool)$q->num_rows;
	}


}
