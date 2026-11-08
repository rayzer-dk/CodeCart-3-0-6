<?php
class ControllerCommonTwoFactor extends Controller {
    public function index() {
        $this->load->language('common/two_factor');
        $this->document->setTitle($this->language->get('heading_title'));

        $userId = (int)$this->user->getId();
        $totp = new \CodeCart\Core\TotpManager($this->registry);

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $action = isset($this->request->post['two_factor_action']) ? (string)$this->request->post['two_factor_action'] : '';
            try {
                $this->handle($totp, $userId, $action);
            } catch (\Throwable $e) {
                if ($this->registry->has('log')) { $this->log->write('Two-factor settings: ' . get_class($e) . ': ' . $e->getMessage()); }
                $this->session->data['codecart_two_factor_error'] = $this->language->get('error_unavailable');
            }
            $this->response->redirect($this->link('common/two_factor'));
            return;
        }

        $enabled = $totp->isEnabled($userId);
        $required = $totp->isRequiredFor((int)$this->user->getGroupId());
        $pending = !$enabled && isset($this->session->data['codecart_totp_pending_secret']) ? (string)$this->session->data['codecart_totp_pending_secret'] : '';

        $data = array();

        $data['enabled'] = $enabled;
        $data['required'] = $required;
        $data['read_only'] = !$enabled && !$required && !$this->maySetUp();
        // Mandatory setup: header, menu and footer stay in signed-out mode until a factor exists.
        $data['forced'] = $this->registry->get('codecart_mfa_pending') === 'setup';
        $data['recovery_left'] = $enabled ? $totp->remainingRecoveryCodes($userId) : 0;
        $data['pending_secret'] = $pending;
        $data['pending_label'] = $pending !== '' ? $totp->provisioningLabel($this->user->getUserName()) : '';
        $data['pending_uri'] = $pending !== '' ? $totp->provisioningUri($pending, $this->user->getUserName()) : '';
        $data['email_code_required'] = $pending !== '' && $data['forced'];
        $data['recovery_codes'] = isset($this->session->data['codecart_totp_recovery_codes']) && is_array($this->session->data['codecart_totp_recovery_codes']) ? $this->session->data['codecart_totp_recovery_codes'] : array();
        unset($this->session->data['codecart_totp_recovery_codes']);

        $data['success'] = isset($this->session->data['codecart_two_factor_success']) ? (string)$this->session->data['codecart_two_factor_success'] : '';
        $data['error_warning'] = isset($this->session->data['codecart_two_factor_error']) ? (string)$this->session->data['codecart_two_factor_error'] : '';
        unset($this->session->data['codecart_two_factor_success'], $this->session->data['codecart_two_factor_error']);

        $data['action'] = $this->link('common/two_factor');
        $data['logout'] = $this->link('common/logout');
        $data['back'] = $this->link('common/dashboard');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/two_factor', $data));
    }

    private function handle(\CodeCart\Core\TotpManager $totp, int $userId, string $action): void {
        $username = $this->user->getUserName();
        $audit = new \CodeCart\Core\SecurityAudit($this->registry);
        $code = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';
        $enabled = $totp->isEnabled($userId);
        // Mandatory setup: the password alone must not let someone enrol their own phone.
        $forced = $this->registry->get('codecart_mfa_pending') === 'setup';

        if (in_array($action, array('start', 'enable'), true) && !$totp->isRequiredFor((int)$this->user->getGroupId()) && !$this->maySetUp()) {
            $this->fail('error_read_only');
            return;
        }

        if ($action === 'start' && !$enabled) {
            $sent = isset($this->session->data['codecart_totp_enroll']['expires']) && (int)$this->session->data['codecart_totp_enroll']['expires'] > time() && isset($this->session->data['codecart_totp_pending_secret']);
            if ($sent) {
                return;
            }
            if ($forced && !$this->sendEnrollmentCode($totp, $userId)) {
                return;
            }
            $this->session->data['codecart_totp_pending_secret'] = $totp->createSecret();
            return;
        }

        if ($action === 'cancel') {
            unset($this->session->data['codecart_totp_pending_secret'], $this->session->data['codecart_totp_enroll']);
            return;
        }

        if (!in_array($action, array('enable', 'disable', 'regenerate'), true) || ($action === 'enable') === $enabled) {
            return;
        }

        if (!$this->confirmPassword($username)) {
            return;
        }

        if ($action === 'enable') {
            $secret = isset($this->session->data['codecart_totp_pending_secret']) ? (string)$this->session->data['codecart_totp_pending_secret'] : '';
            if ($secret === '') {
                $this->fail('error_start');
                return;
            }
            if ($forced && !$this->checkEnrollmentCode($userId)) {
                return;
            }
            try {
                $codes = $totp->enable($userId, $secret, $code);
            } catch (\RuntimeException $e) {
                $this->fail('error_code');
                return;
            }
            unset($this->session->data['codecart_totp_pending_secret'], $this->session->data['codecart_totp_enroll']);
            $totp->elevateSession($userId);
            $this->session->data['codecart_totp_recovery_codes'] = $codes;
            $this->session->data['codecart_two_factor_success'] = $this->language->get('text_success_enabled');
            $audit->add('admin.totp.enable', 'success', 'info', 'admin', $userId, $username);
            $this->notice($totp, $userId, 'mail_enabled');
            return;
        }

        if ($action === 'disable' && $totp->isRequiredFor((int)$this->user->getGroupId())) {
            $this->fail('error_required');
            return;
        }

        // Recovery codes are consumed only when regenerating; disabling wipes them anyway.
        if (!$totp->verify($userId, $code, $action === 'regenerate')) {
            $audit->add('admin.totp.' . $action, 'failed', 'warning', 'admin', $userId, $username);
            $retry = $totp->retryAfter($userId);
            $this->fail($retry > 0 ? 'error_attempts' : 'error_code');
            return;
        }

        if ($action === 'disable') {
            $totp->reset($userId);
            unset($this->session->data['codecart_totp_verified_user_id']);
            $this->session->data['codecart_two_factor_success'] = $this->language->get('text_success_disabled');
            $audit->add('admin.totp.disable', 'success', 'warning', 'admin', $userId, $username);
            $this->notice($totp, $userId, 'mail_disabled');
            return;
        }

        $this->session->data['codecart_totp_recovery_codes'] = $totp->regenerateRecoveryCodes($userId);
        $this->session->data['codecart_two_factor_success'] = $this->language->get('text_success_regenerated');
        $audit->add('admin.totp.recovery', 'success', 'info', 'admin', $userId, $username);
        $this->notice($totp, $userId, 'mail_regenerated');
    }

    private function sendEnrollmentCode(\CodeCart\Core\TotpManager $totp, int $userId): bool {
        $code = sprintf('%06d', random_int(0, 999999));
        if (!$totp->notify($userId, $this->language->get('mail_enroll_subject'), sprintf($this->language->get('mail_enroll_text'), $this->user->getUserName(), $code))) {
            $this->fail('error_mail');
            return false;
        }
        $this->session->data['codecart_totp_enroll'] = array('hash' => hash('sha256', $userId . '|' . $code), 'expires' => time() + 900, 'attempts' => 0);
        return true;
    }

    /** One e-mailed code per setup: 15 minutes, 5 tries, then the setup starts over. */
    private function checkEnrollmentCode(int $userId): bool {
        $enroll = isset($this->session->data['codecart_totp_enroll']) && is_array($this->session->data['codecart_totp_enroll']) ? $this->session->data['codecart_totp_enroll'] : array();
        if (!$enroll || (int)$enroll['expires'] < time() || (int)$enroll['attempts'] >= 5) {
            unset($this->session->data['codecart_totp_pending_secret'], $this->session->data['codecart_totp_enroll']);
            $this->fail('error_email_code_expired');
            return false;
        }
        $this->session->data['codecart_totp_enroll']['attempts'] = (int)$enroll['attempts'] + 1;
        $code = isset($this->request->post['email_code']) ? preg_replace('/\D/', '', (string)$this->request->post['email_code']) : '';
        if ($code === '' || !hash_equals((string)$enroll['hash'], hash('sha256', $userId . '|' . $code))) {
            $this->fail('error_email_code');
            return false;
        }
        return true;
    }

    /** Sensitive changes need the account password; failures share the login brute-force limit. */
    private function confirmPassword(string $username): bool {
        $password = isset($this->request->post['password']) ? (string)$this->request->post['password'] : '';
        $result = (new \CodeCart\Core\LoginProtection($this->registry))->confirmPassword((int)$this->user->getId(), $password);
        if ($result === 'ok') {
            return true;
        }
        (new \CodeCart\Core\SecurityAudit($this->registry))->add('admin.totp.password', 'failed', 'warning', 'admin', (int)$this->user->getId(), $username);
        $this->fail($result === 'blocked' ? 'error_password_attempts' : 'error_password');
        return false;
    }

    /**
     * Accounts without any modify permission (typically a shared view-only demo
     * login) cannot bind the account to one person's phone; a mandatory group
     * policy still makes them set it up.
     */
    private function maySetUp(): bool {
        $query = $this->db->query("SELECT permission FROM `" . DB_PREFIX . "user_group` WHERE user_group_id = '" . (int)$this->user->getGroupId() . "' LIMIT 1");
        $permission = $query->num_rows ? json_decode((string)$query->row['permission'], true) : array();
        return !empty($permission['modify']);
    }

    private function notice(\CodeCart\Core\TotpManager $totp, int $userId, string $key): void {
        $ip = (string)codecart_client_ip($this->request->server);
        $totp->notify($userId, $this->language->get($key . '_subject'), sprintf($this->language->get('mail_text'), $this->user->getUserName(), $this->language->get($key . '_event'), $ip, date('Y-m-d H:i:s')));
    }

    private function fail(string $key): void {
        $this->session->data['codecart_two_factor_error'] = $this->language->get($key);
    }

    private function link(string $route): string {
        return $this->url->link($route, 'user_token=' . $this->session->data['user_token'], true);
    }
}
