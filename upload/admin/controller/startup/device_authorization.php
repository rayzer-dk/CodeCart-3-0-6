<?php
class ControllerStartupDeviceAuthorization extends Controller {
    public function index() {
        if (!$this->registry->has('user') || !$this->user->isLogged()) { return; }

        $route = isset($this->request->get['route']) ? (string)$this->request->get['route'] : '';
        $ignore = array('common/device_authorize','common/totp','common/logout','common/login','common/forgotten','common/reset','error/not_found','error/permission');
        if (in_array($route, $ignore, true)) { return; }

        // Never let an optional post-login security layer lock an administrator out
        // while an explicit Installer UPDATE is still required. The updater creates
        // and validates the authorization tables before this feature is used again.
        if ($this->config->get('codecart_upgrade_required')) { return; }

        try {
            $service = new \CodeCart\Core\DeviceAuthorization($this->registry);
            if (!$service->enabled('admin') || $service->isTrusted('admin', (int)$this->user->getId())) { return; }

            if ($route !== '' && empty($this->session->data['codecart_admin_device_redirect'])) {
                $query = $this->request->get;
                unset($query['route'], $query['user_token']);
                $this->session->data['codecart_admin_device_redirect'] = array('route' => $route, 'query' => $query);
            }

            return new Action('common/device_authorize');
        } catch (\Throwable $e) {
            if ($this->registry->has('log')) {
                $this->log->write('Device authorization startup check failed: ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
            return new Action('common/device_authorize');
        }
    }
}
