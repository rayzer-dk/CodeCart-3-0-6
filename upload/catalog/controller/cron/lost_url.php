<?php
class ControllerCronLostUrl extends Controller {
    public function index($args = array()) {
        if (PHP_SAPI !== 'cli' && !(defined('CODECART_CLI') && CODECART_CLI)) {
            $expected = (string)$this->config->get('codecart_scheduler_key');
            $provided = isset($this->request->get['key']) && is_string($this->request->get['key']) ? $this->request->get['key'] : '';
            if ($expected === '' || !hash_equals($expected, $provided)) { $this->response->setStatusCode(403); return array('success'=>false,'message'=>'Forbidden'); }
        }
        if (!$this->config->get('codecart_lost_url_status')) { return array('success'=>true,'message'=>'Disabled'); }
        return (new \CodeCart\Core\LostUrlMonitor($this->registry))->prune();
    }
}
