<?php
class ControllerCronQueueWorker extends Controller {
    public function index($args = array()) {
        $limit = isset($args['limit']) ? (int)$args['limit'] : 25;
        return (new \CodeCart\Core\Queue($this->registry))->run(max(1, min(100, $limit)));
    }
}
