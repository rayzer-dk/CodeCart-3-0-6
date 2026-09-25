<?php
class ModelExtensionFeedGoogleBase extends Model {
    private $googleCategoryCache = array();
    private $productTypeCache = array();
    private $legacyMappingAvailable = null;

    public function getCategories() {
        $query = $this->db->query("SELECT google_base_category_id, (SELECT name FROM `" . DB_PREFIX . "google_base_category` gbc WHERE gbc.google_base_category_id = gbc2c.google_base_category_id) AS google_base_category, category_id, (SELECT name FROM `" . DB_PREFIX . "category_description` cd WHERE cd.category_id = gbc2c.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS category FROM `" . DB_PREFIX . "google_base_category_to_category` gbc2c ORDER BY google_base_category ASC");
        return $query->rows;
    }

    public function getMerchantProducts($start = 0, $limit = 500) {
        $start = max(0, (int)$start);
        $limit = max(1, min(1000, (int)$limit));
        $language_id = (int)$this->config->get('config_language_id');
        $store_id = (int)$this->config->get('config_store_id');
        $customer_group_id = (int)$this->config->get('config_customer_group_id');

        $sql = "SELECT p.product_id, p.model, p.sku, p.upc, p.ean, p.jan, p.isbn, p.mpn, p.google_product_category_id, p.quantity, p.image, p.manufacturer_id, p.tax_class_id, p.price, p.weight, p.weight_class_id, p.date_modified, pd.name, pd.description, m.name AS manufacturer, " .
            "(SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . $customer_group_id . "' AND pd2.quantity = '1' AND ((pd2.date_start < NOW()) AND (pd2.date_end < '1000-01-01' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, " .
            "(SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . $customer_group_id . "' AND ((ps.date_start < NOW()) AND (ps.date_end < '1000-01-01' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, " .
            "(SELECT p2c.category_id FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.product_id = p.product_id ORDER BY p2c.main_category DESC, p2c.category_id ASC LIMIT 1) AS category_id " .
            "FROM " . DB_PREFIX . "product p " .
            "INNER JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "') " .
            "INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . $store_id . "') " .
            "LEFT JOIN " . DB_PREFIX . "manufacturer m ON (m.manufacturer_id = p.manufacturer_id) " .
            "WHERE p.status = '1' AND p.date_available <= NOW() ORDER BY p.product_id ASC LIMIT " . $start . "," . $limit;

        return $this->db->query($sql)->rows;
    }


    public function getAdditionalImages(array $product_ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $product_ids), function($id){ return $id > 0; })));
        if (!$ids) return array();
        $out = array();
        foreach (array_chunk($ids, 500) as $chunk) {
            $query = $this->db->query("SELECT product_id, image FROM `" . DB_PREFIX . "product_image` WHERE product_id IN (" . implode(',', $chunk) . ") AND image <> '' ORDER BY product_id ASC, sort_order ASC, product_image_id ASC");
            foreach ($query->rows as $row) {
                $product_id = (int)$row['product_id'];
                if (!isset($out[$product_id])) $out[$product_id] = array();
                if (count($out[$product_id]) < 10) $out[$product_id][] = (string)$row['image'];
            }
        }
        return $out;
    }

    public function getGoogleProductCategoryId($category_id) {
        $category_id = (int)$category_id;
        if ($category_id <= 0) return '';
        if (array_key_exists($category_id, $this->googleCategoryCache)) return $this->googleCategoryCache[$category_id];

        $query = $this->db->query("SELECT c.google_product_category_id FROM `" . DB_PREFIX . "category_path` cp INNER JOIN `" . DB_PREFIX . "category` c ON (c.category_id = cp.path_id) WHERE cp.category_id = '" . $category_id . "' AND c.google_product_category_id <> '' ORDER BY cp.level DESC LIMIT 1");
        $value = $query->num_rows ? preg_replace('/[^0-9]/', '', (string)$query->row['google_product_category_id']) : '';

        if ($value === '' && $this->hasLegacyMappingTable()) {
            $legacy = $this->db->query("SELECT google_base_category_id FROM `" . DB_PREFIX . "google_base_category_to_category` WHERE category_id = '" . $category_id . "' LIMIT 1");
            if ($legacy->num_rows) $value = (string)(int)$legacy->row['google_base_category_id'];
        }

        $this->googleCategoryCache[$category_id] = $value;
        return $value;
    }

    private function hasLegacyMappingTable() {
        if ($this->legacyMappingAvailable !== null) return $this->legacyMappingAvailable;
        $table = DB_PREFIX . 'google_base_category_to_category';
        $query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
        $this->legacyMappingAvailable = (bool)$query->num_rows;
        return $this->legacyMappingAvailable;
    }

    public function getProductType($category_id) {
        $category_id = (int)$category_id;
        if ($category_id <= 0) return '';
        if (array_key_exists($category_id, $this->productTypeCache)) return $this->productTypeCache[$category_id];
        $language_id = (int)$this->config->get('config_language_id');
        $query = $this->db->query("SELECT cd.name FROM `" . DB_PREFIX . "category_path` cp INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = cp.path_id AND cd.language_id = '" . $language_id . "') WHERE cp.category_id = '" . $category_id . "' ORDER BY cp.level ASC");
        $parts = array();
        foreach ($query->rows as $row) {
            $name = trim(strip_tags(html_entity_decode((string)$row['name'], ENT_QUOTES, 'UTF-8')));
            if ($name !== '') $parts[] = $name;
        }
        $value = implode(' > ', $parts);
        $this->productTypeCache[$category_id] = $value;
        return $value;
    }
}
