<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelBlogArticle extends Model {
	public function updateViewed($article_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "article SET viewed = (viewed + 1) WHERE article_id = '" . (int)$article_id . "'");
	}
	
	public function getArticle($article_id) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review_article r1 WHERE r1.article_id = p.article_id AND r1.status = '1' GROUP BY r1.article_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review_article r2 WHERE r2.article_id = p.article_id AND r2.status = '1' GROUP BY r2.article_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_description pd ON (p.article_id = pd.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id)  WHERE p.article_id = '" . (int)$article_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'meta_title'       => $query->row['meta_title'],
				'noindex'          => $query->row['noindex'],
				'meta_h1'          => $query->row['meta_h1'],
				'article_id'       => $query->row['article_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'image'            => $query->row['image'],
				'rating'           => round($query->row['rating'] ?? 0),
				'reviews'          => $query->row['reviews'],
				'sort_order'       => $query->row['sort_order'],
				'article_review'   => $query->row['article_review'],
				'status'           => $query->row['status'],
				'gstatus'          => $query->row['gstatus'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}


	public function getArticleCardsByIds(array $article_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $article_ids))));
		if (!$ids) {
			return array();
		}

		$id_list = implode(',', $ids);
		$sql = "SELECT a.article_id, a.image, a.date_added, a.viewed, ad.name, ad.description, "
			. "(SELECT AVG(r.rating) FROM " . DB_PREFIX . "review_article r WHERE r.article_id = a.article_id AND r.status = '1') AS rating, "
			. "(SELECT COUNT(*) FROM " . DB_PREFIX . "review_article r2 WHERE r2.article_id = a.article_id AND r2.status = '1') AS reviews "
			. "FROM " . DB_PREFIX . "article a INNER JOIN " . DB_PREFIX . "article_description ad ON (a.article_id = ad.article_id) INNER JOIN " . DB_PREFIX . "article_to_store a2s ON (a.article_id = a2s.article_id) "
			. "WHERE a.article_id IN (" . $id_list . ") AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND a.status = '1' AND a.date_available <= NOW() AND a2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY FIELD(a.article_id," . $id_list . ")";
		$query = $this->db->query($sql);
		$data = array();
		foreach ($query->rows as $row) {
			$article_id = (int)$row['article_id'];
			$data[$article_id] = array(
				'article_id' => $article_id,
				'name' => (string)$row['name'],
				'description' => (string)$row['description'],
				'image' => (string)$row['image'],
				'rating' => round($row['rating'] === null ? 0 : (float)$row['rating']),
				'reviews' => (int)$row['reviews'],
				'date_added' => (string)$row['date_added'],
				'viewed' => (int)$row['viewed']
			);
		}
		return $data;
	}

	public function getLatestArticleCards($limit) {
		$limit = max(1, min(100, (int)$limit));
		$query = $this->db->query("SELECT a.article_id FROM " . DB_PREFIX . "article a INNER JOIN " . DB_PREFIX . "article_to_store a2s ON (a.article_id = a2s.article_id) WHERE a.status = '1' AND a.date_available <= NOW() AND a2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY a.date_added DESC, a.article_id DESC LIMIT " . $limit);
		$ids = array();
		foreach ($query->rows as $row) {
			$ids[] = (int)$row['article_id'];
		}
		return $this->getArticleCardsByIds($ids);
	}

	public function getArticles($data = array()) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
		
		$cache = 'article.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . md5(http_build_query($data));
		
		$article_data = $this->cache->get($cache);
		
		if ($article_data === false || $article_data === null) {
			$sql = "SELECT p.article_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review_article r1 WHERE r1.article_id = p.article_id AND r1.status = '1' GROUP BY r1.article_id) AS rating FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_description pd ON (p.article_id = pd.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id)"; 
						
			if (!empty($data['filter_blog_category_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "article_to_blog_category a2c ON (p.article_id = a2c.article_id)";			
			}
			
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'"; 
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				
				if (!empty($data['filter_name'])) {					
					if (!empty($data['filter_description'])) {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%' OR MATCH(pd.description) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "')";
					} else {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$sql .= "MATCH(pd.tag) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "')";
				}
			
				$sql .= ")";
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}	
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}					
			}
			
			if (!empty($data['filter_blog_category_id'])) {
				if (!empty($data['filter_sub_category'])) {
					$implode_data = array();
					
					$implode_data[] = (int)$data['filter_blog_category_id'];
					
					$this->load->model('blog/category');
					
					$categories = $this->model_blog_category->getCategoriesByParentId($data['filter_blog_category_id']);
										
					foreach ($categories as $blog_category_id) {
						$implode_data[] = (int)$blog_category_id;
					}
								
					$sql .= " AND a2c.blog_category_id IN (" . implode(', ', $implode_data) . ")";	
				} else {
					$sql .= " AND a2c.blog_category_id = '" . (int)$data['filter_blog_category_id'] . "'";
				}
			}		
					
			$sql .= " GROUP BY p.article_id";
			
			$sort_data = array(
				'pd.name',
				//OCSTORE.COM
				'p.viewed',
				//OCSTORE.COM
				'rating',
				'p.sort_order',
				'p.date_added'
			);	
			
			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model' || $data['sort'] == 'p.date_added') {
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
			
			$article_data = array();
					
			$query = $this->db->query($sql);
		
			foreach ($query->rows as $result) {
				$article_data[$result['article_id']] = $this->getArticle($result['article_id']);
			}
			
			$this->cache->set($cache, $article_data);
		}
		
		return $article_data;
	}
		
	public function getArticlesForSitemap($start = 0, $limit = 10000) {
		$start = max(0, (int)$start);
		$limit = max(1, min(10000, (int)$limit));
		$sql = "SELECT a.article_id, a.date_modified, a.noindex FROM " . DB_PREFIX . "article a INNER JOIN " . DB_PREFIX . "article_description ad ON (a.article_id = ad.article_id) INNER JOIN " . DB_PREFIX . "article_to_store a2s ON (a.article_id = a2s.article_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND a2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND a.status = '1' AND a.date_available <= NOW()";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND a.noindex > '0'";
		}
		$sql .= " ORDER BY a.article_id ASC LIMIT " . $start . "," . $limit;
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalArticlesForSitemap() {
		$sql = "SELECT COUNT(DISTINCT a.article_id) AS total FROM " . DB_PREFIX . "article a INNER JOIN " . DB_PREFIX . "article_description ad ON (a.article_id = ad.article_id) INNER JOIN " . DB_PREFIX . "article_to_store a2s ON (a.article_id = a2s.article_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND a2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND a.status = '1' AND a.date_available <= NOW()";
		if ($this->config->get('config_noindex_status')) {
			$sql .= " AND a.noindex > '0'";
		}
		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	public function getLatestArticles($limit) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$cache = 'article.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $customer_group_id . '.' . (int)$limit;
		$article_data = $this->cache->get($cache);

		if ($article_data === false || $article_data === null) { 
			$article_data = array();
			$query = $this->db->query("SELECT p.article_id FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);
		 	 
			foreach ($query->rows as $result) {
				$article_data[$result['article_id']] = $this->getArticle($result['article_id']);
			}
			
			$this->cache->set($cache, $article_data);
		}
		
		return $article_data;
	}
	
	public function getPopularArticles($limit) {
		$article_data = array();
		
		$query = $this->db->query("SELECT p.article_id FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
		
		foreach ($query->rows as $result) { 		
			$article_data[$result['article_id']] = $this->getArticle($result['article_id']);
		}
					 	 		
		return $article_data;
	}
		
	public function getArticleImages($article_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_image WHERE article_id = '" . (int)$article_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}
	
	public function getArticleRelated($article_id) {
		$article_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_related pr LEFT JOIN " . DB_PREFIX . "article p ON (pr.related_id = p.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE pr.article_id = '" . (int)$article_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		$ids = array();
		foreach ($query->rows as $result) {
			$ids[] = (int)$result['related_id'];
		}
		return $this->getArticleCardsByIds($ids);
	}
	
	public function getArticleRelatedByProduct($data) {
		
		$article_data = array();
		
		$this->load->model('blog/article');
		
		$product_id = max(0, (int)($data['product_id'] ?? 0));
		$limit = max(1, min(100, (int)($data['limit'] ?? 20)));
		if (!$product_id) { return array(); }
		$sql = "SELECT DISTINCT np.article_id FROM " . DB_PREFIX . "product_related_article np INNER JOIN " . DB_PREFIX . "article p ON (np.article_id = p.article_id) INNER JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE np.product_id = '" . $product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . $limit;

		$query = $this->db->query($sql);
		$ids = array();
		foreach ($query->rows as $result) {
			$ids[] = (int)$result['article_id'];
		}
		return $this->getArticleCardsByIds($ids);
	}
	
	//category manuf
	public function getArticleRelatedByCategory($data) {

		$article_data = array();
				
		$category_id = max(0, (int)($data['category_id'] ?? 0));
		$limit = max(1, min(100, (int)($data['limit'] ?? 20)));
		if (!$category_id) { return array(); }
		$query = $this->db->query("SELECT DISTINCT pr.article_id FROM " . DB_PREFIX . "article_related_wb pr INNER JOIN " . DB_PREFIX . "article p ON (pr.article_id = p.article_id) INNER JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE pr.category_id = '" . $category_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . $limit);

		$ids = array();
		foreach ($query->rows as $result) {
			$ids[] = (int)$result['article_id'];
		}
		return $this->getArticleCardsByIds($ids);

	}
	
	public function getArticleRelatedByManufacturer($data) {

		$article_data = array();

		$manufacturer_id = max(0, (int)($data['manufacturer_id'] ?? 0));
		$limit = max(1, min(100, (int)($data['limit'] ?? 20)));
		if (!$manufacturer_id) { return array(); }
		$query = $this->db->query("SELECT DISTINCT pr.article_id FROM " . DB_PREFIX . "article_related_mn pr INNER JOIN " . DB_PREFIX . "article p ON (pr.article_id = p.article_id) INNER JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id) WHERE pr.manufacturer_id = '" . $manufacturer_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . $limit);

		$ids = array();
		foreach ($query->rows as $result) {
			$ids[] = (int)$result['article_id'];
		}
		return $this->getArticleCardsByIds($ids);

	}
	//category manuf
	
	public function getArticleRelatedProduct($article_id) {
		$product_data = array();
		$this->load->model('catalog/product');
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_related_product np LEFT JOIN " . DB_PREFIX . "product p ON (np.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE np.article_id = '" . (int)$article_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		$ids = array();
		foreach ($query->rows as $result) {
			$ids[] = (int)$result['product_id'];
		}
		$cards = $this->model_catalog_product->getProductCardsByIds($ids);
		$product_data = array();
		foreach ($cards as $card) {
			$product_data[(int)$card['product_id']] = $card;
		}
		return $product_data;
	}
		
	public function getArticleLayoutId($article_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_layout WHERE article_id = '" . (int)$article_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return  $this->config->get('config_layout_article');
		}
	}
	
	public function getCategories($article_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_blog_category WHERE article_id = '" . (int)$article_id . "'");
		
		return $query->rows;
	}

	public function getDownloads($article_id) {

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article_to_download pd LEFT JOIN " . DB_PREFIX . "download d ON(pd.download_id=d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON(pd.download_id=dd.download_id) WHERE article_id = '" . (int)$article_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id')."'");

		return $query->rows;
	}

	public function getDownload($article_id, $download_id) {
		$article_id = (int)$article_id;
		$download_id = (int)$download_id;

		if ($article_id < 1 || $download_id < 1) {
			return array();
		}

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "article_to_download` pd LEFT JOIN `" . DB_PREFIX . "download` d ON (pd.download_id = d.download_id) LEFT JOIN `" . DB_PREFIX . "download_description` dd ON (pd.download_id = dd.download_id) WHERE pd.article_id = '" . $article_id . "' AND d.download_id = '" . $download_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");

		return $query->row;
	}
		

	public function getBlogCategoryArticleCounts(array $category_ids, $include_sub_categories = true) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $category_ids))));
		if (!$ids) {
			return array();
		}

		$counts = array_fill_keys($ids, 0);
		$id_list = implode(',', $ids);
		if ($include_sub_categories) {
			$sql = "SELECT cp.path_id AS blog_category_id, COUNT(DISTINCT a.article_id) AS total FROM " . DB_PREFIX . "blog_category_path cp INNER JOIN " . DB_PREFIX . "article_to_blog_category a2c ON (cp.blog_category_id = a2c.blog_category_id) INNER JOIN " . DB_PREFIX . "article a ON (a2c.article_id = a.article_id) INNER JOIN " . DB_PREFIX . "article_description ad ON (a.article_id = ad.article_id) INNER JOIN " . DB_PREFIX . "article_to_store a2s ON (a.article_id = a2s.article_id) WHERE cp.path_id IN (" . $id_list . ") AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND a.status = '1' AND a.date_available <= NOW() AND a2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY cp.path_id";
		} else {
			$sql = "SELECT a2c.blog_category_id, COUNT(DISTINCT a.article_id) AS total FROM " . DB_PREFIX . "article_to_blog_category a2c INNER JOIN " . DB_PREFIX . "article a ON (a2c.article_id = a.article_id) INNER JOIN " . DB_PREFIX . "article_description ad ON (a.article_id = ad.article_id) INNER JOIN " . DB_PREFIX . "article_to_store a2s ON (a.article_id = a2s.article_id) WHERE a2c.blog_category_id IN (" . $id_list . ") AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND a.status = '1' AND a.date_available <= NOW() AND a2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY a2c.blog_category_id";
		}
		$query = $this->db->query($sql);
		foreach ($query->rows as $row) {
			$blog_category_id = (int)$row['blog_category_id'];
			if (isset($counts[$blog_category_id])) {
				$counts[$blog_category_id] = (int)$row['total'];
			}
		}
		return $counts;
	}

	public function getTotalArticles($data = array()) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$cache = md5(http_build_query($data));
		
		$article_data = $this->cache->get('article.total.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache);
		
		if ($article_data === false || $article_data === null) {
			$sql = "SELECT COUNT(DISTINCT p.article_id) AS total FROM " . DB_PREFIX . "article p LEFT JOIN " . DB_PREFIX . "article_description pd ON (p.article_id = pd.article_id) LEFT JOIN " . DB_PREFIX . "article_to_store p2s ON (p.article_id = p2s.article_id)";
	
			if (!empty($data['filter_blog_category_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "article_to_blog_category a2c ON (p.article_id = a2c.article_id)";		
			}
						
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				
				if (!empty($data['filter_name'])) {					
					if (!empty($data['filter_description'])) {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%' OR MATCH(pd.description) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "')";
					} else {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$sql .= "MATCH(pd.tag) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "')";
				}
			
				$sql .= ")";
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}	
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}				
			}
						
			if (!empty($data['filter_blog_category_id'])) {
				$include_sub_categories = !empty($data['filter_sub_blog_category']) || !empty($data['filter_sub_category']);
				if ($include_sub_categories) {
					$implode_data = array();
					
					$implode_data[] = (int)$data['filter_blog_category_id'];
					
					$this->load->model('blog/category');
					
					$categories = $this->model_blog_category->getCategoriesByParentId((int)$data['filter_blog_category_id']);
										
					foreach ($categories as $blog_category_id) {
						$implode_data[] = (int)$blog_category_id;
					}
								
					$sql .= " AND a2c.blog_category_id IN (" . implode(', ', $implode_data) . ")";		
				} else {
					$sql .= " AND a2c.blog_category_id = '" . (int)$data['filter_blog_category_id'] . "'";
				}
			}		
			
			$query = $this->db->query($sql);
			
			$article_data = $query->row['total']; 
			
			$this->cache->set('article.total.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache, $article_data);
		}
		
		return $article_data;
	}
		
}
