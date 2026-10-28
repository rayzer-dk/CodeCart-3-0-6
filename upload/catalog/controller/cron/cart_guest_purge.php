<?php
class ControllerCronCartGuestPurge extends Controller {
    public function index($args = array()) {
        if (!$this->config->get('codecart_abandoned_cart_status') || (string)$this->config->get('codecart_abandoned_cart_source') === 'external') {
            return array('success'=>true, 'message'=>'Guest cart contact cleanup disabled.');
        }
        (new \CodeCart\Core\RegisteredCartConsent($this->registry))->purge();
        if ($this->config->get('codecart_abandoned_cart_guest_capture')) { (new \CodeCart\Core\GuestCartContact($this->registry))->purge(); }
        return array('success'=>true,'message'=>'Guest cart contacts older than seven days removed.');
    }
}
