<?php
class ControllerCronCart extends Controller {
    public function index($args = array()) {
        // API carts are temporary; normal anonymous carts are retained for seven days.
        $this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE api_id > '0' AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $apiDeleted = (int)$this->db->countAffected();
        $this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE api_id = '0' AND customer_id = '0' AND date_added < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $guestDeleted = (int)$this->db->countAffected();
        return array('api_deleted' => $apiDeleted, 'guest_deleted' => $guestDeleted);
    }
}
