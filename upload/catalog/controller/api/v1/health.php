<?php
class ControllerApiV1Health extends Controller {
    public function index() {
        $api = $this->registry->get('codecart_api_v1');
        if (!$api->enabled()) { $api->disabled($this->response); return; }
        $api->respond($this->response, array('ok'=>true,'api'=>'v1','status'=>'ready'));
    }
}
