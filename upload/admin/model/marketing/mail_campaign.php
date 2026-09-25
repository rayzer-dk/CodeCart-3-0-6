<?php
class ModelMarketingMailCampaign extends Model {
    public function createCampaign(array $data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "mail_campaign` SET store_id='" . (int)$data['store_id'] . "', audience='" . $this->db->escape((string)$data['audience']) . "', subject='" . $this->db->escape((string)$data['subject']) . "', message='" . $this->db->escape((string)$data['message']) . "', status='queued', user_id='" . (int)$this->user->getId() . "', date_added=NOW(), date_modified=NOW()");
        $campaign_id = (int)$this->db->getLastId();
        $this->populateQueue($campaign_id, $data);
        $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "mail_campaign_queue` WHERE campaign_id='" . $campaign_id . "'");
        $total = (int)$q->row['total'];
        $this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign` SET total='" . $total . "', status='" . ($total ? 'queued' : 'empty') . "', date_modified=NOW() WHERE campaign_id='" . $campaign_id . "'");
        return array('campaign_id'=>$campaign_id,'total'=>$total);
    }

    private function populateQueue($campaign_id, array $data) {
        $audience = (string)$data['audience'];
        $whereSuppression = " LEFT JOIN `" . DB_PREFIX . "mail_suppression` ms ON (LOWER(ms.email)=LOWER(c.email)) WHERE ms.email IS NULL AND c.email<>''";
        if ($audience === 'newsletter') {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "mail_campaign_queue` (campaign_id,customer_id,email,name,status,date_added,date_modified) SELECT '" . (int)$campaign_id . "',c.customer_id,LOWER(c.email),TRIM(CONCAT(c.firstname,' ',c.lastname)),'waiting',NOW(),NOW() FROM `" . DB_PREFIX . "customer` c" . $whereSuppression . " AND c.newsletter='1' AND c.status='1'");
        } elseif ($audience === 'customer_all') {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "mail_campaign_queue` (campaign_id,customer_id,email,name,status,date_added,date_modified) SELECT '" . (int)$campaign_id . "',c.customer_id,LOWER(c.email),TRIM(CONCAT(c.firstname,' ',c.lastname)),'waiting',NOW(),NOW() FROM `" . DB_PREFIX . "customer` c" . $whereSuppression . " AND c.status='1'");
        } elseif ($audience === 'customer_group') {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "mail_campaign_queue` (campaign_id,customer_id,email,name,status,date_added,date_modified) SELECT '" . (int)$campaign_id . "',c.customer_id,LOWER(c.email),TRIM(CONCAT(c.firstname,' ',c.lastname)),'waiting',NOW(),NOW() FROM `" . DB_PREFIX . "customer` c" . $whereSuppression . " AND c.status='1' AND c.customer_group_id='" . (int)$data['customer_group_id'] . "'");
        } elseif ($audience === 'customer' || $audience === 'affiliate') {
            $ids = isset($data[$audience]) ? array_values(array_unique(array_filter(array_map('intval',(array)$data[$audience])))) : array();
            if ($ids) {
                $join = $audience === 'affiliate' ? " INNER JOIN `" . DB_PREFIX . "customer_affiliate` ca ON (ca.customer_id=c.customer_id AND ca.status='1')" : '';
                $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "mail_campaign_queue` (campaign_id,customer_id,email,name,status,date_added,date_modified) SELECT '" . (int)$campaign_id . "',c.customer_id,LOWER(c.email),TRIM(CONCAT(c.firstname,' ',c.lastname)),'waiting',NOW(),NOW() FROM `" . DB_PREFIX . "customer` c" . $join . " LEFT JOIN `" . DB_PREFIX . "mail_suppression` ms ON (LOWER(ms.email)=LOWER(c.email)) WHERE ms.email IS NULL AND c.customer_id IN (" . implode(',',$ids) . ") AND c.email<>''");
            }
        } elseif ($audience === 'affiliate_all') {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "mail_campaign_queue` (campaign_id,customer_id,email,name,status,date_added,date_modified) SELECT '" . (int)$campaign_id . "',c.customer_id,LOWER(c.email),TRIM(CONCAT(c.firstname,' ',c.lastname)),'waiting',NOW(),NOW() FROM `" . DB_PREFIX . "customer` c INNER JOIN `" . DB_PREFIX . "customer_affiliate` ca ON (ca.customer_id=c.customer_id AND ca.status='1') LEFT JOIN `" . DB_PREFIX . "mail_suppression` ms ON (LOWER(ms.email)=LOWER(c.email)) WHERE ms.email IS NULL AND c.email<>''");
        } elseif ($audience === 'product') {
            $ids = isset($data['product']) ? array_values(array_unique(array_filter(array_map('intval',(array)$data['product'])))) : array();
            if ($ids) {
                $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "mail_campaign_queue` (campaign_id,customer_id,email,name,status,date_added,date_modified) SELECT '" . (int)$campaign_id . "',MAX(o.customer_id),LOWER(o.email),MAX(TRIM(CONCAT(o.firstname,' ',o.lastname))),'waiting',NOW(),NOW() FROM `" . DB_PREFIX . "order` o INNER JOIN `" . DB_PREFIX . "order_product` op ON (op.order_id=o.order_id) LEFT JOIN `" . DB_PREFIX . "mail_suppression` ms ON (LOWER(ms.email)=LOWER(o.email)) WHERE ms.email IS NULL AND o.email<>'' AND op.product_id IN (" . implode(',',$ids) . ") GROUP BY LOWER(o.email)");
            }
        }
    }

    public function getRecentCampaigns($limit = 20) {
        $limit=max(1,min(100,(int)$limit));
        return $this->db->query("SELECT campaign_id,store_id,audience,subject,status,total,sent,failed,suppressed,date_added,date_started,date_finished FROM `" . DB_PREFIX . "mail_campaign` ORDER BY campaign_id DESC LIMIT " . $limit)->rows;
    }
}
