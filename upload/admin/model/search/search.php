<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ModelSearchSearch extends Model {
    public function getProducts($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);

        $sql = "SELECT p.product_id, pd.name, p.model, p.sku, p.image
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND (pd.name LIKE '%" . $like . "%'
                    OR p.model LIKE '%" . $like . "%'
                    OR p.sku LIKE '%" . $like . "%'
                    OR p.upc LIKE '%" . $like . "%'
                    OR p.ean LIKE '%" . $like . "%'
                    OR p.jan LIKE '%" . $like . "%'
                    OR p.isbn LIKE '%" . $like . "%'
                    OR p.mpn LIKE '%" . $like . "%')
                GROUP BY p.product_id
                ORDER BY CASE WHEN pd.name LIKE '" . $like . "%' THEN 0 ELSE 1 END, pd.name ASC
                LIMIT 8";

        return $this->db->query($sql)->rows;
    }

    public function getCategories($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);

        $sql = "SELECT cp.category_id AS category_id,
                       GROUP_CONCAT(cdpath.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name,
                       cmain.image
                FROM " . DB_PREFIX . "category_path cp
                LEFT JOIN " . DB_PREFIX . "category cmain ON (cp.category_id = cmain.category_id)
                LEFT JOIN " . DB_PREFIX . "category_description cdmain ON (cp.category_id = cdmain.category_id)
                LEFT JOIN " . DB_PREFIX . "category_description cdpath ON (cp.path_id = cdpath.category_id)
                WHERE cdpath.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND cdmain.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND (cdmain.name LIKE '%" . $like . "%' OR cdmain.meta_h1 LIKE '%" . $like . "%')
                GROUP BY cp.category_id
                ORDER BY CASE WHEN cdmain.name LIKE '" . $like . "%' THEN 0 ELSE 1 END, name ASC
                LIMIT 8";

        return $this->db->query($sql)->rows;
    }

    public function getManufacturers($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);

        $sql = "SELECT DISTINCT m.manufacturer_id, m.name, m.image
                FROM " . DB_PREFIX . "manufacturer m
                WHERE m.name LIKE '%" . $like . "%'
                ORDER BY CASE WHEN m.name LIKE '" . $like . "%' THEN 0 ELSE 1 END, m.name ASC
                LIMIT 8";

        return $this->db->query($sql)->rows;
    }

    public function getCustomers($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);

        $sql = "SELECT customer_id, email, telephone, CONCAT(c.firstname, ' ', c.lastname) AS name
                FROM " . DB_PREFIX . "customer c
                WHERE c.firstname LIKE '%" . $like . "%'
                   OR c.lastname LIKE '%" . $like . "%'
                   OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $like . "%'
                   OR c.email LIKE '%" . $like . "%'
                   OR c.telephone LIKE '%" . $like . "%'
                ORDER BY CASE
                    WHEN c.firstname LIKE '" . $like . "%' OR c.lastname LIKE '" . $like . "%' OR c.email LIKE '" . $like . "%' THEN 0
                    ELSE 1 END, name ASC
                LIMIT 8";

        return $this->db->query($sql)->rows;
    }

    public function getOrders($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);
        $numeric = ctype_digit($query) ? (int)$query : 0;

        $sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, o.total,
                       o.currency_code, o.currency_value, o.date_added, o.email, o.telephone
                FROM `" . DB_PREFIX . "order` o
                WHERE o.order_status_id > '0'
                  AND (" . ($numeric > 0 ? "o.order_id = '" . $numeric . "' OR " : "") . "
                       o.firstname LIKE '%" . $like . "%'
                    OR o.lastname LIKE '%" . $like . "%'
                    OR CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $like . "%'
                    OR o.email LIKE '%" . $like . "%'
                    OR o.telephone LIKE '%" . $like . "%'
                    OR CONCAT(o.invoice_prefix, o.invoice_no) LIKE '%" . $like . "%')
                ORDER BY o.order_id DESC
                LIMIT 8";

        return $this->db->query($sql)->rows;
    }

    public function getInformation($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);
        $sql = "SELECT i.information_id, id.title
                FROM " . DB_PREFIX . "information i
                LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id)
                WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND (id.title LIKE '%" . $like . "%' OR id.meta_title LIKE '%" . $like . "%')
                ORDER BY CASE WHEN id.title LIKE '" . $like . "%' THEN 0 ELSE 1 END, id.title ASC
                LIMIT 8";
        return $this->db->query($sql)->rows;
    }

    public function getArticles($data = array()) {
        $query = isset($data['query']) ? trim((string)$data['query']) : '';
        $like = $this->db->escape($query);
        $sql = "SELECT a.article_id, ad.name
                FROM " . DB_PREFIX . "article a
                LEFT JOIN " . DB_PREFIX . "article_description ad ON (a.article_id = ad.article_id)
                WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND (ad.name LIKE '%" . $like . "%' OR ad.meta_title LIKE '%" . $like . "%')
                ORDER BY CASE WHEN ad.name LIKE '" . $like . "%' THEN 0 ELSE 1 END, ad.name ASC
                LIMIT 8";
        return $this->db->query($sql)->rows;
    }

    /**
     * Return a bounded list of installed module extensions and their configured
     * instances. Localized extension titles are resolved by the controller so a
     * user can search by the visible module name instead of only its code.
     */
    public function getModuleCandidates() {
        $sql = "SELECT e.code, m.module_id, m.name
                FROM `" . DB_PREFIX . "extension` e
                LEFT JOIN `" . DB_PREFIX . "module` m ON (m.code = e.code)
                WHERE e.type = 'module'
                ORDER BY e.code ASC, m.name ASC
                LIMIT 160";
        return $this->db->query($sql)->rows;
    }
}
