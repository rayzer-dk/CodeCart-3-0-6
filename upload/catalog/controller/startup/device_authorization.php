<?php
class ControllerStartupDeviceAuthorization extends Controller {
    public function index() {
        if (!$this->registry->has('customer') || !$this->customer->isLogged()) { return; }
        if (!empty($this->session->data['codecart_customer_device_bypass'])) { return; }
        $service=new \CodeCart\Core\DeviceAuthorization($this->registry);
        if (!$service->enabled('customer') || $service->isTrusted('customer',(int)$this->customer->getId())) { return; }
        $route=isset($this->request->get['route'])?(string)$this->request->get['route']:'';
        $ignore=array('account/device_authorize','account/logout','account/login','account/forgotten','account/reset','error/not_found');
        if (in_array($route,$ignore,true)) { return; }
        if ($route!=='' && empty($this->session->data['codecart_customer_device_redirect'])) {
            $query=$this->request->get; unset($query['route']);
            $this->session->data['codecart_customer_device_redirect']=array('route'=>$route,'query'=>$query);
        }
        return new Action('account/device_authorize');
    }
}
