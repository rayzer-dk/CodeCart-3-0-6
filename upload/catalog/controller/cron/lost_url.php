<?php
class ControllerCronLostUrl extends Controller {
    public function index($args = array()) {
        if (!$this->config->get('codecart_lost_url_status')) { return array('success'=>true,'message'=>'Disabled'); }
        return (new \CodeCart\Core\LostUrlMonitor($this->registry))->prune();
    }
}
