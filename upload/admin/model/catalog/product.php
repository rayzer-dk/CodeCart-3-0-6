<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelCatalogProduct extends Model {
	public function addProduct($data) {
		$google_product_category_id = preg_replace('/[^0-9]/', '', (string)(isset($data['google_product_category_id']) ? $data['google_product_category_id'] : ''));
		$tax_display_mode = isset($data['tax_display_mode']) ? (string)$data['tax_display_mode'] : 'inherit';
		if (!in_array($tax_display_mode, array('inherit','native','none','gross_net','gross_tax','net_gross'), true)) { $tax_display_mode = 'inherit'; }
		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', google_product_category_id = '" . $this->db->escape($google_product_category_id) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape(codecart_normalize_date($data['date_available'], '1970-01-01')) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', tax_display_mode = '" . $this->db->escape($tax_display_mode) . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

		$product_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape(\CodeCart\Core\SafeRichHtml::sanitize(isset($value['description']) ? $value['description'] : '')) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if (isset($data['product_store'])) {
			$this->insertProductLinks('product_to_store', 'store_id', $product_id, $data['product_store']);
		}

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $recurring) {

				$query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_recurring` WHERE `product_id` = '" . (int)$product_id . "' AND `customer_group_id` = '" . (int)$recurring['customer_group_id'] . "' AND `recurring_id` = '" . (int)$recurring['recurring_id'] . "'");

				if (!$query->num_rows) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = '" . (int)$product_id . "', customer_group_id = '" . (int)$recurring['customer_group_id'] . "', `recurring_id` = '" . (int)$recurring['recurring_id'] . "'");
				}
			}
		}
		
		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape(codecart_normalize_date($product_discount['date_start'], '1970-01-01')) . "', date_end = '" . $this->db->escape(codecart_normalize_date($product_discount['date_end'], '9999-12-31')) . "'");
			}
		}

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape(codecart_normalize_date($product_special['date_start'], '1970-01-01')) . "', date_end = '" . $this->db->escape(codecart_normalize_date($product_special['date_end'], '9999-12-31')) . "'");
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				if (empty($product_image['image'])) { continue; }
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}

		if (isset($data['product_download'])) {
			$this->insertProductLinks('product_to_download', 'download_id', $product_id, $data['product_download']);
		}

		if (isset($data['product_category'])) {
			$this->insertProductLinks('product_to_category', 'category_id', $product_id, $data['product_category']);
		}
		
		$this->saveProductMainCategory($product_id, $data);

		if (isset($data['product_filter'])) {
			$this->insertProductLinks('product_filter', 'filter_id', $product_id, $data['product_filter']);
		}

		if (isset($data['product_related'])) {
			$this->saveProductRelated($product_id, $data['product_related']);
		}
		
		if (isset($data['product_related_article'])) {
			$article_ids = array_values(array_unique(array_filter(array_map('intval', (array)$data['product_related_article']))));
			if ($article_ids) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "' AND article_id IN (" . implode(',', $article_ids) . ")");
				$this->insertProductLinks('product_related_article', 'article_id', $product_id, $article_ids);
			}
		}

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
				if ((int)$product_reward['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
				}
			}
		}
		
				if ($this->config->get('config_auto_seo_url')) {
			$seo_names = array();
			foreach ($data['product_description'] as $language_id => $value) {
				if (!empty($value['name'])) { $seo_names[(int)$language_id] = $value['name']; }
			}
			$data['product_seo_url'] = codecart_fill_new_seo_urls($this->db, isset($data['product_seo_url']) ? $data['product_seo_url'] : array(), $seo_names, isset($data['product_store']) ? $data['product_store'] : array(0));
		}

// SEO URL
		if (isset($data['product_seo_url'])) {
			foreach ($data['product_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}
		
		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}


		$this->saveProductExtraTab($product_id, $data);
		$this->savePurchaseBlocks('product', $product_id, $data);

		$this->cache->delete('product');
		$this->cache->delete('admin.product_sidebar_stats');
		$this->cache->deleteTag('catalog.category_counts', 'category_product_counts');
		
		if($this->config->get('config_seo_pro')){		
		$this->cache->delete('seopro');
		}

		return $product_id;
	}

	public function editProduct($product_id, $data) {
		$google_product_category_id = preg_replace('/[^0-9]/', '', (string)(isset($data['google_product_category_id']) ? $data['google_product_category_id'] : ''));
		if (isset($data['tax_display_mode'])) {
			$tax_display_mode = (string)$data['tax_display_mode'];
		} else {
			$existing_tax_display = $this->db->query("SELECT tax_display_mode FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "' LIMIT 1");
			$tax_display_mode = $existing_tax_display->num_rows ? (string)$existing_tax_display->row['tax_display_mode'] : 'inherit';
		}
		if (!in_array($tax_display_mode, array('inherit','native','none','gross_net','gross_tax','net_gross'), true)) { $tax_display_mode = 'inherit'; }
		$this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', google_product_category_id = '" . $this->db->escape($google_product_category_id) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape(codecart_normalize_date($data['date_available'], '1970-01-01')) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', tax_display_mode = '" . $this->db->escape($tax_display_mode) . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_extra_tab WHERE product_id = '" . (int)$product_id . "'");

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape(\CodeCart\Core\SafeRichHtml::sanitize(isset($value['description']) ? $value['description'] : '')) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_store'])) {
			$this->insertProductLinks('product_to_store', 'store_id', $product_id, $data['product_store']);
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

		if (!empty($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $product_recurring) {
				$query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_recurring` WHERE `product_id` = '" . (int)$product_id . "' AND `customer_group_id` = '" . (int)$product_recurring['customer_group_id'] . "' AND `recurring_id` = '" . (int)$product_recurring['recurring_id'] . "'");

				if (!$query->num_rows) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = '" . (int)$product_id . "', `customer_group_id` = '" . (int)$product_recurring['customer_group_id'] . "', `recurring_id` = '" . (int)$product_recurring['recurring_id'] . "'");
				}				
			}
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape(codecart_normalize_date($product_discount['date_start'], '1970-01-01')) . "', date_end = '" . $this->db->escape(codecart_normalize_date($product_discount['date_end'], '9999-12-31')) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape(codecart_normalize_date($product_special['date_start'], '1970-01-01')) . "', date_end = '" . $this->db->escape(codecart_normalize_date($product_special['date_end'], '9999-12-31')) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				if (empty($product_image['image'])) { continue; }
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_download'])) {
			$this->insertProductLinks('product_to_download', 'download_id', $product_id, $data['product_download']);
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_category'])) {
			$this->insertProductLinks('product_to_category', 'category_id', $product_id, $data['product_category']);
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

		$this->saveProductMainCategory($product_id, $data);
		
		if (isset($data['product_filter'])) {
			$this->insertProductLinks('product_filter', 'filter_id', $product_id, $data['product_filter']);
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

		if (isset($data['product_related'])) {
			$this->saveProductRelated($product_id, $data['product_related']);
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "'");
		
		if (isset($data['product_related_article'])) {
			$article_ids = array_values(array_unique(array_filter(array_map('intval', (array)$data['product_related_article']))));
			if ($article_ids) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "' AND article_id IN (" . implode(',', $article_ids) . ")");
				$this->insertProductLinks('product_related_article', 'article_id', $product_id, $article_ids);
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $value) {
				if ((int)$value['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
				}
			}
		}
		
		// SEO URL
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
		
		if (isset($data['product_seo_url'])) {
			foreach ($data['product_seo_url']as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->saveProductExtraTab($product_id, $data);
		$this->savePurchaseBlocks('product', $product_id, $data);

		$this->cache->delete('product');
		$this->cache->delete('admin.product_sidebar_stats');
		$this->cache->deleteTag('catalog.category_counts', 'category_product_counts');
		
		if($this->config->get('config_seo_pro')){		
		$this->cache->delete('seopro');
		}
	}
	
	public function editProductStatus($product_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET status = '" . (int)$status . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
        
		$this->cache->delete('product');
		$this->cache->delete('admin.product_sidebar_stats');
		$this->cache->deleteTag('catalog.category_counts', 'category_product_counts');
		
		if($this->config->get('config_seo_pro')){		
		$this->cache->delete('seopro');
		}
		
		return $product_id;
    }

	public function copyProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int)$product_id . "'");

		if ($query->num_rows) {
			$data = $query->row;

			$data['sku'] = '';
			$data['upc'] = '';
			$data['viewed'] = '0';
			$data['keyword'] = '';
			$data['status'] = '0';
			$data['noindex'] = isset($data['noindex']) ? (int)$data['noindex'] : 1;

			$data['product_attribute'] = $this->getProductAttributes($product_id);
			$data['product_description'] = $this->getProductDescriptions($product_id);
			$data['product_discount'] = $this->getProductDiscounts($product_id);
			$data['product_filter'] = $this->getProductFilters($product_id);
			$data['product_image'] = $this->getProductImages($product_id);
			$data['product_option'] = $this->getProductOptions($product_id);
			$data['product_related'] = $this->getProductRelated($product_id);
			$data['product_related_article'] = $this->getArticleRelated($product_id);
			$data['product_reward'] = $this->getProductRewards($product_id);
			$data['product_special'] = $this->getProductSpecials($product_id);
			$data['product_category'] = $this->getProductCategories($product_id);
			$data['product_download'] = $this->getProductDownloads($product_id);
			$data['product_layout'] = $this->getProductLayouts($product_id);
			$data['product_store'] = $this->getProductStores($product_id);
			$data['product_recurrings'] = $this->getRecurrings($product_id);
			$data['product_extra_tab'] = $this->getProductExtraTab($product_id);
			$data['product_extra_tab_mode'] = $data['product_extra_tab'] ? (int)reset($data['product_extra_tab'])['mode'] : 0;
			$purchase_blocks = $this->getPurchaseBlocks('product', $product_id);
			$data['purchase_block_mode'] = $purchase_blocks['mode'];
			$data['purchase_blocks_json'] = $purchase_blocks['languages'];

			$this->addProduct($data);
		}
	}

	public function deleteProduct($product_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "stock_notify` WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_extra_tab WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "codecart_purchase_block WHERE owner_type = 'product' AND owner_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");
        if ((string)$this->config->get('codecart_public_documents_schema_version') === '2.0.8') {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_document_to_product` WHERE product_id=" . (int)$product_id);
        }
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int)$product_id);
		$this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int)$product_id . "'");
		try { (new \CodeCart\Core\RelationLayer($this->registry))->purgeEntity('product', (int)$product_id); } catch (\Throwable $e) { $this->log->write('Relation purge product: ' . $e->getMessage()); }

		$this->cache->delete('product');
		$this->cache->delete('admin.product_sidebar_stats');
		$this->cache->deleteTag('catalog.category_counts', 'category_product_counts');
		
		if($this->config->get('config_seo_pro')){		
		$this->cache->delete('seopro');
		}
	}


    private function savePurchaseBlocks($owner_type, $owner_id, $data) {
        if (!array_key_exists('purchase_block_mode', $data)) { return; }
        $owner_type = $owner_type === 'category' ? 'category' : 'product';
        $owner_id = (int)$owner_id;
        $mode = isset($data['purchase_block_mode']) ? (string)$data['purchase_block_mode'] : 'inherit';
        if (!in_array($mode, array('inherit','custom','disabled'), true)) { $mode = 'inherit'; }
        $this->db->query("DELETE FROM " . DB_PREFIX . "codecart_purchase_block WHERE owner_type = '" . $this->db->escape($owner_type) . "' AND owner_id = '" . $owner_id . "'");
        if ($mode === 'inherit') { return; }
        if ($mode === 'disabled') {
            $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_purchase_block SET owner_type = '" . $this->db->escape($owner_type) . "', owner_id = '" . $owner_id . "', language_id = '0', mode = '2', data = '[]'");
            return;
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_purchase_block SET owner_type = '" . $this->db->escape($owner_type) . "', owner_id = '" . $owner_id . "', language_id = '0', mode = '1', data = '[]'");
        $blocks = isset($data['purchase_blocks_json']) && is_array($data['purchase_blocks_json']) ? $data['purchase_blocks_json'] : array();
        foreach ($blocks as $language_id => $json) {
            $language_id = (int)$language_id;
            if ($language_id < 1) { continue; }
            $encoded = \CodeCart\Core\PurchaseBlocks::encode($json);
            $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_purchase_block SET owner_type = '" . $this->db->escape($owner_type) . "', owner_id = '" . $owner_id . "', language_id = '" . $language_id . "', mode = '1', data = '" . $this->db->escape($encoded) . "'");
        }
    }

    public function getPurchaseBlocks($owner_type, $owner_id) {
        $owner_type = $owner_type === 'category' ? 'category' : 'product';
        $owner_id = (int)$owner_id;
        $result = array('mode'=>'inherit','languages'=>array());
        $query = $this->db->query("SELECT language_id, mode, data FROM " . DB_PREFIX . "codecart_purchase_block WHERE owner_type = '" . $this->db->escape($owner_type) . "' AND owner_id = '" . $owner_id . "' ORDER BY language_id ASC");
        foreach ($query->rows as $row) {
            if ((int)$row['mode'] === 2) { $result['mode'] = 'disabled'; $result['languages'] = array(); return $result; }
            $result['mode'] = 'custom';
            if ((int)$row['language_id'] > 0) { $result['languages'][(int)$row['language_id']] = (string)$row['data']; }
        }
        return $result;
    }

	/**
	 * One multi-row INSERT for a product link table instead of one query per linked id.
	 */
	private function insertProductLinks($table, $column, $product_id, $ids) {
		$ids = array_values(array_unique(array_map('intval', (array)$ids)));
		if (!$ids) {
			return;
		}

		$values = array();
		foreach ($ids as $id) {
			$values[] = "('" . (int)$product_id . "', '" . $id . "')";
		}

		$this->db->query("INSERT INTO " . DB_PREFIX . $table . " (product_id, " . $column . ") VALUES " . implode(', ', $values));
	}

	/**
	 * Stores related products in both directions with one DELETE and one INSERT.
	 */
	private function saveProductRelated($product_id, $related_ids) {
		$product_id = (int)$product_id;
		$related_ids = array_values(array_unique(array_map('intval', (array)$related_ids)));
		if (!$related_ids) {
			return;
		}

		$id_list = implode(',', $related_ids);
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE (product_id = '" . $product_id . "' AND related_id IN (" . $id_list . ")) OR (related_id = '" . $product_id . "' AND product_id IN (" . $id_list . "))");

		$pairs = array();
		foreach ($related_ids as $related_id) {
			$pairs[$product_id . ':' . $related_id] = "('" . $product_id . "', '" . $related_id . "')";
			$pairs[$related_id . ':' . $product_id] = "('" . $related_id . "', '" . $product_id . "')";
		}

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_related (product_id, related_id) VALUES " . implode(', ', $pairs));
	}

	public function getProduct($product_id) {
		$products = $this->getProductsByIds(array((int)$product_id));

		return isset($products[(int)$product_id]) ? $products[(int)$product_id] : array();
	}

	/**
	 * Unique non-empty SKU hints only. The product list itself may contain many
	 * products with the same SKU; returning products makes the dropdown repeat.
	 */
	/** Quick price editor: only product price and modification timestamp are changed. */
	public function setQuickPrice($product_id, $price) {
		$product_id = (int)$product_id;
		if ($product_id < 1) { return false; }
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE product_id = '" . $product_id . "' LIMIT 1");
		if (!$query->num_rows) { return false; }
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET price = '" . $this->db->escape((string)$price) . "', date_modified = NOW() WHERE product_id = '" . $product_id . "'");
		$this->cache->delete('product');
		return true;
	}

	/**
	 * Read a product's editable attributes without loading descriptions or options.
	 */
	public function getQuickProductFields($product_id) {
		$product_id = (int)$product_id;
		if ($product_id < 1) { return array(); }
		$query = $this->db->query("SELECT product_id, price, quantity, status, noindex FROM `" . DB_PREFIX . "product` WHERE product_id = '" . $product_id . "' LIMIT 1");
		return $query->num_rows ? $query->row : array();
	}

	/**
	 * Update exactly one allowlisted field. The expected value guards stale AJAX
	 * requests so concurrent edits cannot overwrite a newer admin change.
	 */
	public function saveQuickProductField($product_id, $field, $value, $expected) {
		$product_id = (int)$product_id;
		$columns = array('price' => true, 'quantity' => true, 'status' => true, 'noindex' => true);
		if ($product_id < 1 || !isset($columns[$field])) { return false; }
		if ($field === 'price') {
			$value = $this->db->escape((string)$value);
			$expected = $this->db->escape((string)$expected);
		} else {
			$value = (int)$value;
			$expected = (int)$expected;
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `" . $field . "` = '" . $value . "', date_modified = NOW() WHERE product_id = '" . $product_id . "' AND `" . $field . "` = '" . $expected . "'");
		$updated = (int)$this->db->countAffected();
		if ($updated) {
			$this->cache->delete('product');
			$this->cache->delete('admin.product_sidebar_stats');
		}
		return $updated > 0;
	}

	/**
	 * Applies one bulk change to the given products and returns what is needed to undo it.
	 * $change keys: price_mode (set|percent|amount), price, quantity_mode (set|add), quantity,
	 * manufacturer_id (int, 0 clears), category_id (int, added to the product's categories).
	 */
	public function bulkEditProducts(array $product_ids, array $change) {
		$product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
		$undo = array();

		if (!$product_ids) {
			return $undo;
		}

		$query = $this->db->query("SELECT product_id, price, quantity, manufacturer_id FROM `" . DB_PREFIX . "product` WHERE product_id IN (" . implode(',', $product_ids) . ")");

		foreach ($query->rows as $row) {
			$product_id = (int)$row['product_id'];
			$set = array();
			$entry = array();

			if (!empty($change['price_mode'])) {
				$old = (float)$row['price'];
				$value = (float)$change['price'];

				if ($change['price_mode'] === 'set') {
					$new = $value;
				} elseif ($change['price_mode'] === 'percent') {
					$new = $old * (1 + $value / 100);
				} else {
					$new = $old + $value;
				}

				$new = number_format(max(0, round($new, 4)), 4, '.', '');

				if ((float)$new !== $old) {
					$set[] = "price = '" . $this->db->escape($new) . "'";
					$entry['price'] = array((string)$row['price'], $new);
				}
			}

			if (!empty($change['quantity_mode'])) {
				$old = (int)$row['quantity'];
				$new = $change['quantity_mode'] === 'set' ? (int)$change['quantity'] : $old + (int)$change['quantity'];
				$new = max(0, min(2147483647, $new));

				if ($new !== $old) {
					$set[] = "quantity = '" . $new . "'";
					$entry['quantity'] = array($old, $new);
				}
			}

			if (isset($change['manufacturer_id']) && $change['manufacturer_id'] !== null) {
				$old = (int)$row['manufacturer_id'];
				$new = (int)$change['manufacturer_id'];

				if ($new !== $old) {
					$set[] = "manufacturer_id = '" . $new . "'";
					$entry['manufacturer_id'] = array($old, $new);
				}
			}

			if ($set) {
				$this->db->query("UPDATE `" . DB_PREFIX . "product` SET " . implode(', ', $set) . ", date_modified = NOW() WHERE product_id = '" . $product_id . "'");
			}

			if (!empty($change['category_id'])) {
				$category_id = (int)$change['category_id'];
				$exists = $this->db->query("SELECT 1 FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . $product_id . "' AND category_id = '" . $category_id . "' LIMIT 1");

				if (!$exists->num_rows) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . $product_id . "', category_id = '" . $category_id . "'");
					$entry['category_id'] = $category_id;
				}
			}

			if ($entry) {
				$undo[$product_id] = $entry;
			}
		}

		$this->clearBulkCaches();

		return $undo;
	}

	/**
	 * Reverts a bulk change. A field is restored only while it still holds the value the bulk
	 * change wrote, so later manual edits are never overwritten.
	 */
	public function bulkRestoreProducts(array $undo) {
		$restored = 0;

		foreach ($undo as $product_id => $entry) {
			$product_id = (int)$product_id;

			foreach (array('price', 'quantity', 'manufacturer_id') as $field) {
				if (!isset($entry[$field])) {
					continue;
				}

				list($old, $new) = $entry[$field];

				$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `" . $field . "` = '" . $this->db->escape((string)$old) . "', date_modified = NOW() WHERE product_id = '" . $product_id . "' AND `" . $field . "` = '" . $this->db->escape((string)$new) . "'");
				$restored += (int)$this->db->countAffected();
			}

			if (!empty($entry['category_id'])) {
				$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . $product_id . "' AND category_id = '" . (int)$entry['category_id'] . "'");
				$restored += (int)$this->db->countAffected();
			}
		}

		$this->clearBulkCaches();

		return $restored;
	}

	private function clearBulkCaches() {
		$this->cache->delete('product');
		$this->cache->delete('admin.product_sidebar_stats');
		$this->cache->deleteTag('catalog.category_counts', 'category_product_counts');

		if ($this->config->get('config_seo_pro')) {
			$this->cache->delete('seopro');
		}
	}

	public function getQuickPrice($product_id) {
		$query = $this->db->query("SELECT price FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "' LIMIT 1");
		return $query->num_rows ? (string)$query->row['price'] : null;
	}

	public function searchDistinctSkus($term, $limit = 15) {
		$term = trim((string)$term);
		if (utf8_strlen($term) < 2) { return array(); }
		$term = utf8_substr($term, 0, 64);
		$limit = max(1, min(20, (int)$limit));
		$sql = "SELECT MIN(p.product_id) AS product_id, TRIM(p.sku) AS sku FROM `" . DB_PREFIX . "product` p WHERE TRIM(p.sku) <> '' AND p.sku LIKE '%" . $this->db->escape($term) . "%' GROUP BY TRIM(p.sku) ORDER BY sku ASC LIMIT " . $limit;
		return $this->db->query($sql)->rows;
	}

	public function getProducts($data = array()) {
		$sql = "SELECT p.*, pd.* FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		$sql .= $this->buildProductFilterWhere($data);

		$sort_data = array('pd.name', 'p.model', 'p.price', 'p.quantity', 'p.status', 'p.noindex', 'p.sort_order');
		if (isset($data['sort']) && in_array($data['sort'], $sort_data, true)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
		}
		$sql .= (isset($data['order']) && strtoupper((string)$data['order']) === 'DESC') ? ' DESC' : ' ASC';

		if (isset($data['start']) || isset($data['limit'])) {
			$start = isset($data['start']) ? max(0, (int)$data['start']) : 0;
			$limit = isset($data['limit']) ? max(1, (int)$data['limit']) : 20;
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		return $this->db->query($sql)->rows;
	}

	public function searchProductSeoUrls($filter_name, $limit = 10) {
		$limit = max(1, min(50, (int)$limit));
		$sql = "SELECT DISTINCT su.keyword FROM " . DB_PREFIX . "seo_url su WHERE su.query LIKE 'product_id=%' AND su.keyword LIKE '%" . $this->db->escape((string)$filter_name) . "%' ORDER BY su.keyword ASC LIMIT " . $limit;
		return $this->db->query($sql)->rows;
	}

	public function getProductsByCategoryId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.category_id = '" . (int)$category_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getProductDescriptions($product_id) {
		$product_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_h1'	       => $result['meta_h1'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword'],
				'tag'              => $result['tag']
			);
		}

		return $product_description_data;
	}

	public function getProductCategories($product_id) {
		$product_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_category_data[] = $result['category_id'];
		}

		return $product_category_data;
	}
	
	private function saveProductMainCategory($product_id, $data) {
		$compat = $this->registry->get('codecart_compat');
		if (!$compat instanceof \CodeCart\Core\CompatibilityLayer) {
			$compat = new \CodeCart\Core\CompatibilityLayer($this->registry);
		}
		$category_id = (int)($data['main_category_id'] ?? 0);
		if ($category_id < 1) { $category_id = (int)($data['product_category'][0] ?? 0); }
		$relation_flag = $compat->columnExists('product_to_category', 'main_category');
		if ($category_id > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . $category_id . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . $category_id . "'" . ($relation_flag ? ', main_category = 1' : ''));
		}
		if ($compat->columnExists('product', 'main_category_id')) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET main_category_id = '" . max(0, $category_id) . "' WHERE product_id = '" . (int)$product_id . "'");
		}
	}

	public function getProductMainCategoryId($product_id) {
		$compat = $this->registry->get('codecart_compat');
		if (!$compat instanceof \CodeCart\Core\CompatibilityLayer) {
			$compat = new \CodeCart\Core\CompatibilityLayer($this->registry);
		}
		return $compat->getMainCategoryId((int)$product_id);
	}

	public function getProductFilters($product_id) {
		$product_filter_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_filter_data[] = $result['filter_id'];
		}

		return $product_filter_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_data = array();

		$product_attribute_query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' GROUP BY attribute_id");

		foreach ($product_attribute_query->rows as $product_attribute) {
			$product_attribute_description_data = array();

			$product_attribute_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

			foreach ($product_attribute_description_query->rows as $product_attribute_description) {
				$product_attribute_description_data[$product_attribute_description['language_id']] = array('text' => $product_attribute_description['text']);
			}

			$product_attribute_data[] = array(
				'attribute_id'                  => $product_attribute['attribute_id'],
				'product_attribute_description' => $product_attribute_description_data
			);
		}

		return $product_attribute_data;
	}

	public function getProductOptions($product_id) {
		$product_options = $this->getProductOptionsByProductIds(array((int)$product_id));

		return isset($product_options[(int)$product_id]) ? $product_options[(int)$product_id] : array();
	}

	/**
	 * getProductOptions() for several products in two queries, keyed by product id.
	 * Values also carry the option value 'name' in the admin language (null when untranslated).
	 */
	public function getProductOptionsByProductIds(array $product_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
		if (!$ids) {
			return array();
		}

		$language_id = (int)$this->config->get('config_language_id');

		$product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id IN (" . implode(',', $ids) . ") AND od.language_id = '" . $language_id . "' ORDER BY o.sort_order ASC");

		$values = array();
		$product_option_ids = array_map('intval', array_column($product_option_query->rows, 'product_option_id'));
		if ($product_option_ids) {
			$product_option_value_query = $this->db->query("SELECT pov.*, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id AND ovd.language_id = '" . $language_id . "') WHERE pov.product_option_id IN (" . implode(',', $product_option_ids) . ") ORDER BY ov.sort_order ASC");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$values[(int)$product_option_value['product_option_id']][] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'points'                  => $product_option_value['points'],
					'points_prefix'           => $product_option_value['points_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}
		}

		$product_option_data = array();
		foreach ($product_option_query->rows as $product_option) {
			$product_option_data[(int)$product_option['product_id']][] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => isset($values[(int)$product_option['product_option_id']]) ? $values[(int)$product_option['product_option_id']] : array(),
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

	/**
	 * getProduct() rows for several products in one query, keyed by product id.
	 */
	public function getProductsByIds(array $product_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
		if (!$ids) {
			return array();
		}

		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id IN (" . implode(',', $ids) . ") AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		$products = array();
		foreach ($query->rows as $row) {
			$products[(int)$row['product_id']] = $row;
		}

		return $products;
	}

	/**
	 * getProductOptionValue() rows for several product option values, keyed by product_option_value_id.
	 */
	public function getProductOptionValuesByIds(array $product_option_value_ids) {
		$ids = array_values(array_unique(array_filter(array_map('intval', $product_option_value_ids))));
		if (!$ids) {
			return array();
		}

		$query = $this->db->query("SELECT pov.product_option_value_id, pov.product_id, pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id IN (" . implode(',', $ids) . ") AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		$values = array();
		foreach ($query->rows as $row) {
			$values[(int)$row['product_option_value_id']] = $row;
		}

		return $values;
	}

	public function getProductOptionValue($product_id, $product_option_value_id) {
		$query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductDiscounts($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' ORDER BY quantity, priority, price");

		return $query->rows;
	}


	public function getProductSpecialsByProductIds($product_ids) {
		$product_ids = array_values(array_unique(array_filter(array_map('intval', (array)$product_ids))));
		if (!$product_ids) {
			return array();
		}
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(',', $product_ids) . ") ORDER BY product_id, priority, price");
		$result = array();
		foreach ($query->rows as $row) {
			$result[(int)$row['product_id']][] = $row;
		}
		return $result;
	}

	public function getProductSpecials($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' ORDER BY priority, price");

		return $query->rows;
	}

	public function getProductRewards($product_id) {
		$product_reward_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_reward_data[$result['customer_group_id']] = array('points' => $result['points']);
		}

		return $product_reward_data;
	}

	public function getProductDownloads($product_id) {
		$product_download_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_download_data[] = $result['download_id'];
		}

		return $product_download_data;
	}

	public function getProductStores($product_id) {
		$product_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_store_data[] = $result['store_id'];
		}

		return $product_store_data;
	}
	
	public function getProductSeoUrls($product_id) {
		$product_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $product_seo_url_data;
	}
	
	private function saveProductExtraTab($product_id, $data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_extra_tab WHERE product_id = '" . (int)$product_id . "'");
		$mode = isset($data['product_extra_tab_mode']) ? (int)$data['product_extra_tab_mode'] : 0;
		if (!in_array($mode, array(0, 1, 2), true) || $mode === 0) { return; }
		if (empty($data['product_extra_tab']) || !is_array($data['product_extra_tab'])) { return; }
		foreach ($data['product_extra_tab'] as $language_id => $value) {
			$title = ($mode === 1 && isset($value['title'])) ? utf8_substr(trim((string)$value['title']), 0, 128) : '';
			$content = ($mode === 1 && isset($value['content'])) ? \CodeCart\Core\SafeRichHtml::sanitize((string)$value['content']) : '';
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_extra_tab SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', mode = '" . (int)$mode . "', title = '" . $this->db->escape($title) . "', content = '" . $this->db->escape($content) . "'");
		}
	}

	public function setProductExtraTabMode($product_id, $mode) {
		$product_id = (int)$product_id;
		$mode = (int)$mode;
		if (!in_array($mode, array(0, 2), true)) { return false; }

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_extra_tab WHERE product_id = '" . $product_id . "'");

		if ($mode === 2) {
			// One set-based INSERT per product keeps the bulk action cheap even on multilingual stores.
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_extra_tab (product_id, language_id, mode, title, content) SELECT '" . $product_id . "', language_id, '2', '', '' FROM " . DB_PREFIX . "language");
		}

		$this->cache->delete('product');
		$this->cache->delete('admin.product_sidebar_stats');
		$this->cache->deleteTag('catalog.category_counts', 'category_product_counts');
		return true;
	}

	public function getProductExtraTab($product_id) {
		$data = array();
		$query = $this->db->query("SELECT language_id, mode, title, content FROM " . DB_PREFIX . "product_extra_tab WHERE product_id = '" . (int)$product_id . "'");
		foreach ($query->rows as $row) {
			$data[(int)$row['language_id']] = array('mode'=>(int)$row['mode'], 'title'=>(string)$row['title'], 'content'=>(string)$row['content']);
		}
		return $data;
	}

	public function getProductLayouts($product_id) {
		$product_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $product_layout_data;
	}

	public function getProductRelated($product_id) {
		$product_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_related_data[] = $result['related_id'];
		}

		return $product_related_data;
	}
	
	public function getArticleRelated($product_id) {
		$article_related_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related_article WHERE product_id = '" . (int)$product_id . "'");
		
		foreach ($query->rows as $result) {
			$article_related_data[] = $result['article_id'];
		}
		
		return $article_related_data;
	}

	public function getRecurrings($product_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}

	public function getSidebarProductStats() {
		$cache_key = 'admin.product_sidebar_stats';
		$cached = $this->cache->get($cache_key);
		if (is_array($cached) && isset($cached['total'], $cached['enabled'], $cached['disabled'], $cached['out_of_stock'])) { return $cached; }
		$query = $this->db->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS enabled, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS disabled, SUM(CASE WHEN status = 1 AND quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock FROM `" . DB_PREFIX . "product`");
		$stats = array(
			'total' => (int)$query->row['total'],
			'enabled' => (int)$query->row['enabled'],
			'disabled' => (int)$query->row['disabled'],
			'out_of_stock' => (int)$query->row['out_of_stock']
		);
		$this->cache->set($cache_key, $stats);
		return $stats;
	}

	public function getTotalProducts($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		$sql .= $this->buildProductFilterWhere($data);
		$query = $this->db->query($sql);
		return isset($query->row['total']) ? (int)$query->row['total'] : 0;
	}

	private function buildProductFilterWhere($data) {
		$where = '';
		if (!empty($data['filter_name'])) {
			$where .= " AND pd.name LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}
		if (!empty($data['filter_model'])) {
			$where .= " AND p.model LIKE '%" . $this->db->escape((string)$data['filter_model']) . "%'";
		}
		if (!empty($data['filter_sku'])) {
			$where .= " AND p.sku LIKE '%" . $this->db->escape((string)$data['filter_sku']) . "%'";
		}
		if (array_key_exists('filter_category', $data) && $data['filter_category'] !== null && $data['filter_category'] !== '') {
			$category_id = (int)$data['filter_category'];
			if ($category_id > 0) {
				$category_ids = array($category_id);
				if (!empty($data['filter_sub_category'])) {
					$this->load->model('catalog/category');
					foreach ($this->model_catalog_category->getCategoriesChildren($category_id) as $category) {
						$category_ids[] = (int)$category['category_id'];
					}
				}
				$category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids))));
				$where .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.product_id = p.product_id AND p2c.category_id IN (" . implode(',', $category_ids) . "))";
			} else {
				$where .= " AND NOT EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.product_id = p.product_id)";
			}
		}
		if (array_key_exists('filter_manufacturer_id', $data) && $data['filter_manufacturer_id'] !== null && $data['filter_manufacturer_id'] !== '') {
			$where .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}
		if (isset($data['filter_price']) && $data['filter_price'] !== '') {
			$where .= " AND p.price LIKE '" . $this->db->escape((string)$data['filter_price']) . "%'";
		}
		if (isset($data['filter_price_min']) && $data['filter_price_min'] !== '') {
			$where .= " AND p.price >= '" . (float)$data['filter_price_min'] . "'";
		}
		if (isset($data['filter_price_max']) && $data['filter_price_max'] !== '') {
			$where .= " AND p.price <= '" . (float)$data['filter_price_max'] . "'";
		}
		if (isset($data['filter_quantity']) && $data['filter_quantity'] !== '') {
			$where .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
		}
		if (isset($data['filter_quantity_min']) && $data['filter_quantity_min'] !== '') {
			$where .= " AND p.quantity >= '" . (int)$data['filter_quantity_min'] . "'";
		}
		if (isset($data['filter_quantity_max']) && $data['filter_quantity_max'] !== '') {
			$where .= " AND p.quantity <= '" . (int)$data['filter_quantity_max'] . "'";
		}
		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$where .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		if (isset($data['filter_noindex']) && $data['filter_noindex'] !== '') {
			$where .= " AND p.noindex = '" . (int)$data['filter_noindex'] . "'";
		}
		if (!empty($data['filter_attribute_id'])) {
			$where .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = '" . (int)$data['filter_attribute_id'] . "')";
		}
		if (!empty($data['filter_option_id'])) {
			$where .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_option po WHERE po.product_id = p.product_id AND po.option_id = '" . (int)$data['filter_option_id'] . "')";
		}
		if (!empty($data['filter_download_id'])) {
			$where .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_to_download ptd WHERE ptd.product_id = p.product_id AND ptd.download_id = '" . (int)$data['filter_download_id'] . "')";
		}
		if (!empty($data['filter_seo_url'])) {
			$where .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "seo_url su WHERE su.query = CONCAT('product_id=', p.product_id) AND su.keyword LIKE '%" . $this->db->escape((string)$data['filter_seo_url']) . "%')";
		}
		return $where;
	}

	public function getTotalProductsByTaxClassId($tax_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE tax_class_id = '" . (int)$tax_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByStockStatusId($stock_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE stock_status_id = '" . (int)$stock_status_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByWeightClassId($weight_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE weight_class_id = '" . (int)$weight_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByLengthClassId($length_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE length_class_id = '" . (int)$length_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByDownloadId($download_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_to_download WHERE download_id = '" . (int)$download_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByManufacturerId($manufacturer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByAttributeId($attribute_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = '" . (int)$attribute_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByOptionId($option_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_option WHERE option_id = '" . (int)$option_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByProfileId($recurring_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_recurring WHERE recurring_id = '" . (int)$recurring_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

		return $query->row['total'];
	}
	public function getStockNotifyCountsByProductIds($product_ids) {
		$ids=array_values(array_filter(array_unique(array_map('intval',(array)$product_ids)))); if(!$ids){return array();}
		$q=$this->db->query("SELECT product_id, COUNT(*) total FROM `".DB_PREFIX."stock_notify` WHERE status='waiting' AND product_id IN (".implode(',',$ids).") GROUP BY product_id"); $out=array(); foreach($q->rows as $r){$out[(int)$r['product_id']]=(int)$r['total'];} return $out;
	}

}
