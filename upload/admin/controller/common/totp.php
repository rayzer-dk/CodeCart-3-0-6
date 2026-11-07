<?php
class ControllerCommonTotp extends Controller {
    private $error = array();

    public function index() {
        if (!$this->user->isLogged()) {
            $this->response->redirect($this->url->link('common/login', '', true));
            return;
        }

        $this->load->language('common/totp');
        $this->document->setTitle($this->language->get('heading_title'));

        $userId = (int)$this->user->getId();
        $available = true;

        try {
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            $enabled = $totp->isEnabled($userId);
        } catch (\Throwable $e) {
            $available = false;
            $enabled = false;
            $this->error['warning'] = $this->language->get('error_unavailable');
            $this->response->setStatusCode(503);
        }

        if ($available && !$enabled) {
            $required = $totp->isRequiredFor((int)$this->user->getGroupId());
            $this->response->redirect($this->url->link($required ? 'common/two_factor' : 'common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        if ($available && isset($this->session->data['codecart_totp_verified_user_id']) && (int)$this->session->data['codecart_totp_verified_user_id'] === $userId) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        if ($available && $this->request->server['REQUEST_METHOD'] === 'POST') {
            $this->verify($totp, $userId);
            if (!$this->error) {
                return;
            }
        }

        $data = array();
        foreach (array('heading_title','text_instruction','text_code','text_recovery','button_verify','button_logout') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['available'] = $available;
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['action'] = $this->url->link('common/totp', 'user_token=' . $this->session->data['user_token'], true);
        $data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('common/totp', $data));
    }

    private function verify(\CodeCart\Core\TotpManager $totp, int $userId): void {
        // This route is exempt from the startup token check; without it a foreign page
        // could post wrong codes from the administrator's browser and lock the account.
        $token = isset($this->request->get['user_token']) ? (string)$this->request->get['user_token'] : '';
        if ($token === '' || !hash_equals((string)$this->session->data['user_token'], $token)) {
            $this->error['warning'] = $this->language->get('error_code');
            return;
        }

        $code = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';
        if ($code === '') {
            $this->error['warning'] = $this->language->get('error_code_required');
            return;
        }

        $audit = new \CodeCart\Core\SecurityAudit($this->registry);

        try {
            $verified = $totp->verify($userId, $code, true);
        } catch (\Throwable $e) {
            $audit->add('admin.totp.verify', 'error', 'error', 'admin', $userId, $this->user->getUserName(), array('reason' => get_class($e)));
            $this->error['warning'] = $this->language->get('error_unavailable');
            return;
        }

        if ($verified) {
            $audit->add('admin.totp.verify', 'success', 'info', 'admin', $userId, $this->user->getUserName());
            $this->response->redirect($this->completeSignIn($totp, $userId));
            return;
        }

        $audit->add('admin.totp.verify', 'failed', 'warning', 'admin', $userId, $this->user->getUserName());

        if ($totp->lockTriggered()) {
            $audit->add('admin.totp.locked', 'blocked', 'warning', 'admin', $userId, $this->user->getUserName());
            $totp->notify($userId, $this->language->get('mail_locked_subject'), sprintf($this->language->get('mail_locked_text'), $this->user->getUserName(), $this->clientIp(), date('Y-m-d H:i:s')));
        }

        $retry = $totp->retryAfter($userId);
        if ($retry > 0) {
            $this->response->setStatusCode(429);
            $this->response->addHeader('Retry-After: ' . $retry);
            $this->error['warning'] = sprintf($this->language->get('error_attempts'), $this->formatWait($retry));
        } else {
            $this->error['warning'] = $this->language->get('error_code');
        }
    }

    /** Elevates the session, then returns to the originally requested page. */
    private function completeSignIn(\CodeCart\Core\TotpManager $totp, int $userId): string {
        $token = $totp->elevateSession($userId);

        $target = isset($this->session->data['codecart_totp_redirect']) && is_array($this->session->data['codecart_totp_redirect']) ? $this->session->data['codecart_totp_redirect'] : array();
        unset($this->session->data['codecart_totp_redirect']);

        $route = isset($target['route']) && is_string($target['route']) ? preg_replace('/[^a-zA-Z0-9_\/]/', '', $target['route']) : '';
        if ($route === '' || in_array($route, array('common/totp', 'common/login', 'common/logout'), true)) {
            $route = 'common/dashboard';
        }

        $query = isset($target['query']) && is_array($target['query']) ? $target['query'] : array();
        unset($query['route'], $query['user_token']);
        // Request::clean() stored HTML-encoded values; http_build_query() encodes them for the URL.
        array_walk_recursive($query, function (&$value) { $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'); });

        return $this->url->link($route, 'user_token=' . $token . ($query ? '&' . http_build_query($query) : ''), true);
    }

    private function formatWait(int $seconds): string {
        return $seconds >= 3600 ? sprintf($this->language->get('text_wait_hours'), (int)ceil($seconds / 3600)) : sprintf($this->language->get('text_wait_minutes'), (int)ceil($seconds / 60));
    }

    private function clientIp(): string {
        return function_exists('codecart_client_ip') ? (string)codecart_client_ip($this->request->server) : (string)($this->request->server['REMOTE_ADDR'] ?? '');
    }
}
