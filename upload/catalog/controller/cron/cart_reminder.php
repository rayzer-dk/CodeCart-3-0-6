<?php
class ControllerCronCartReminder extends Controller {
    public function index($args = array()) {
        // Fully inactive unless analytics and explicit automatic mail are enabled.
        if (!$this->config->get('codecart_abandoned_cart_status') || !$this->config->get('codecart_abandoned_cart_mail_auto')) {
            return array('success'=>true,'message'=>'Cart reminders disabled.');
        }
        return (new \CodeCart\Core\AbandonedCartReminder($this->registry))->runBatch(isset($args['limit']) ? (int)$args['limit'] : 10);
    }
}
