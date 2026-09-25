<?php
class ControllerStartupTotp extends Controller {
    public function index() {
        if (!isset($this->user) || !$this->user->isLogged()) {
            return;
        }

        $route = isset($this->request->get['route']) ? (string)$this->request->get['route'] : '';
        $ignore = array('common/login','common/logout','common/forgotten','common/reset','common/totp','error/not_found','error/permission');
        if (in_array($route, $ignore, true)) {
            return;
        }

        try {
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            $userId = (int)$this->user->getId();
            if (!$totp->isEnabled($userId)) {
                unset($this->session->data['codecart_totp_verified_user_id']);
                return;
            }

            if (isset($this->session->data['codecart_totp_verified_user_id']) && (int)$this->session->data['codecart_totp_verified_user_id'] === $userId) {
                return;
            }

            return new Action('common/totp');
        } catch (\Throwable $e) {
            // Do not create a migration/configuration lockout. Preflight and audit can surface the issue.
            if ($this->registry->has('log')) {
                $this->log->write('TOTP startup check failed: ' . $e->getMessage());
            }
        }
    }
}
