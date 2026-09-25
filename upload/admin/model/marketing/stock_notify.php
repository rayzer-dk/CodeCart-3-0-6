<?php
class ModelMarketingStockNotify extends Model {
    private function where(array $data) {
        $where = array();
        if (!empty($data['filter_product'])) { $where[] = "pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%'"; }
        if (!empty($data['filter_email'])) { $where[] = "sn.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'"; }
        if (!empty($data['filter_status']) && in_array($data['filter_status'], array('waiting','processing','sent','failed'), true)) { $where[] = "sn.status='" . $this->db->escape($data['filter_status']) . "'"; }
        if (isset($data['filter_store_id']) && $data['filter_store_id'] !== '' && $data['filter_store_id'] !== null) { $where[] = "sn.store_id='" . (int)$data['filter_store_id'] . "'"; }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    public function getRows($data = array()) {
        $sortWhitelist = array('pd.name'=>'pd.name','sn.email'=>'sn.email','sn.status'=>'sn.status','sn.store_id'=>'sn.store_id','sn.date_added'=>'sn.date_added','sn.date_sent'=>'sn.date_sent');
        $sort = isset($data['sort'], $sortWhitelist[$data['sort']]) ? $sortWhitelist[$data['sort']] : 'sn.date_added';
        $order = !empty($data['order']) && strtoupper($data['order']) === 'ASC' ? 'ASC' : 'DESC';
        $start = max(0, isset($data['start']) ? (int)$data['start'] : 0);
        $limit = max(1, min(200, isset($data['limit']) ? (int)$data['limit'] : 25));
        $sql = "SELECT sn.*, pd.name FROM `" . DB_PREFIX . "stock_notify` sn LEFT JOIN `" . DB_PREFIX . "product_description` pd ON pd.product_id=sn.product_id AND pd.language_id='" . (int)$this->config->get('config_language_id') . "'";
        $sql .= $this->where($data) . " ORDER BY " . $sort . " " . $order . " LIMIT " . $start . "," . $limit;
        return $this->db->query($sql)->rows;
    }

    public function getTotal($data = array()) {
        $sql = "SELECT COUNT(*) total FROM `" . DB_PREFIX . "stock_notify` sn LEFT JOIN `" . DB_PREFIX . "product_description` pd ON pd.product_id=sn.product_id AND pd.language_id='" . (int)$this->config->get('config_language_id') . "'" . $this->where($data);
        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    public function deleteIds(array $ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids) { $this->db->query("DELETE FROM `" . DB_PREFIX . "stock_notify` WHERE stock_notify_id IN (" . implode(',', $ids) . ")"); }
    }

    public function retryIds(array $ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids) { $this->db->query("UPDATE `" . DB_PREFIX . "stock_notify` SET status='waiting', attempts='0', date_modified=NOW() WHERE stock_notify_id IN (" . implode(',', $ids) . ") AND status IN ('failed','sent')"); }
    }
}
