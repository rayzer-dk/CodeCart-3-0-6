<?php
class ModelSettingMailAudit extends Model {
    public function getRows($filter, $page = 1, $limit = 25) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit));
        $offset = ($page - 1) * $limit;
        $where = array();
        if (!empty($filter['email'])) {
            $search = $this->db->escape(substr((string)$filter['email'], 0, 120));
            $where[] = "(sender_email LIKE '%" . $search . "%' OR recipient_email LIKE '%" . $search . "%')";
        }
        if (!empty($filter['status']) && in_array($filter['status'], array('accepted', 'failed', 'blocked'), true)) {
            $where[] = "status = '" . $this->db->escape($filter['status']) . "'";
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "codecart_mail_audit" . $sqlWhere);
        $rows = $this->db->query("SELECT audit_id, store_id, date_added, engine, sender_email, recipient_email, subject, source, status, error_type, remote_ip FROM " . DB_PREFIX . "codecart_mail_audit" . $sqlWhere . " ORDER BY audit_id DESC LIMIT " . $offset . ", " . $limit);
        return array('total' => (int)$count->row['total'], 'rows' => $rows->rows);
    }

    public function purgeExpired() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "codecart_mail_audit WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }

    public function clear() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "codecart_mail_audit");
    }
}
