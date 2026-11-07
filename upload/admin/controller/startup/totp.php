<?php
class ControllerStartupTotp extends Controller {
    // Routes reachable while the second factor is pending; none of them grant admin access.
    private const OPEN_ROUTES = array('common/login', 'common/logout', 'common/forgotten', 'common/reset', 'error/not_found', 'error/permission');

    public function index() {
        // Controller has no __isset(): isset($this->user) is always false.
        if (!$this->registry->has('user') || !$this->user->isLogged()) {
            return;
        }

        $state = $this->state((int)$this->user->getId());
        if ($state === 'off' || $state === 'verified') {
            return;
        }

        // Header, menu and footer treat the session as not signed in until this is resolved.
        $this->registry->set('codecart_mfa_pending', $state);

        $route = isset($this->request->get['route']) ? (string)$this->request->get['route'] : '';
        if (in_array($route, self::OPEN_ROUTES, true)) {
            return;
        }

        if ($state === 'setup') {
            return $route === 'common/two_factor' ? null : new Action('common/two_factor');
        }

        if ($route === 'common/totp') {
            return;
        }

        $this->rememberTarget($route);

        return new Action('common/totp');
    }

    /** off | verified | challenge | setup | unavailable */
    private function state(int $userId): string {
        try {
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            if ($totp->isEnabled($userId)) {
                $verified = isset($this->session->data['codecart_totp_verified_user_id']) && (int)$this->session->data['codecart_totp_verified_user_id'] === $userId;
                return $verified ? 'verified' : 'challenge';
            }

            unset($this->session->data['codecart_totp_verified_user_id']);

            return $totp->isRequiredFor((int)$this->user->getGroupId()) ? 'setup' : 'off';
        } catch (\Throwable $e) {
            if ($this->registry->has('log')) {
                $this->log->write('Two-factor state unavailable: ' . get_class($e) . ': ' . $e->getMessage());
            }

            // Only a store that still needs the Installer UPDATE and has no MFA table
            // yet passes: no second factor can exist there. Any other error blocks access.
            return $this->config->get('codecart_upgrade_required') && \CodeCart\Core\DatabaseError::isMissingTable($e) ? 'off' : 'unavailable';
        }
    }

    private function rememberTarget(string $route): void {
        $method = isset($this->request->server['REQUEST_METHOD']) ? strtoupper((string)$this->request->server['REQUEST_METHOD']) : 'GET';
        $ajax = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower((string)$this->request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($route === '' || $method !== 'GET' || $ajax || !empty($this->session->data['codecart_totp_redirect'])) {
            return;
        }

        $query = $this->request->get;
        unset($query['route'], $query['user_token']);
        $this->session->data['codecart_totp_redirect'] = array('route' => $route, 'query' => $query);
    }
}
