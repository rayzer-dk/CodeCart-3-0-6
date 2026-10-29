<?php
class ControllerCronCart extends Controller {
    public function index($args = array()) {
        // API sessions are temporary. Historical guest carts may be kept longer
        // only when CodeCart's own abandoned-cart analytics is enabled.
        // OFF / external source retains the original seven-day cleanup policy.
        $historyDays = 7;
        if ($this->config->get('codecart_abandoned_cart_status')
            && (string)$this->config->get('codecart_abandoned_cart_source') !== 'external') {
            $historyDays = max(1, min(30, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE api_id > '0' AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $apiDeleted = (int)$this->db->countAffected();
        $this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE api_id = '0' AND customer_id = '0' AND date_added < DATE_SUB(NOW(), INTERVAL " . $historyDays . " DAY)");
        $guestDeleted = (int)$this->db->countAffected();
        if ($this->config->get('codecart_abandoned_cart_status') && $this->config->get('codecart_abandoned_cart_guest_capture')) {
            try { (new \CodeCart\Core\GuestCartContact($this->registry))->purge(); } catch (\Throwable $e) { $this->log->write('Guest cart cleanup: ' . get_class($e)); }
        }
        return array('success'=>true,'message'=>'Cart cleanup: API '.$apiDeleted.', guest '.$guestDeleted,'api_deleted' => $apiDeleted, 'guest_deleted' => $guestDeleted);
    }
}
