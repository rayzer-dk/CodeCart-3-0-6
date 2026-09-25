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

        $totp = new \CodeCart\Core\TotpManager($this->registry);
        $userId = (int)$this->user->getId();
        if (!$totp->isEnabled($userId)) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $code = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';
            if ($code === '') {
                $this->error['warning'] = $this->language->get('error_code_required');
            } else {
                try {
                    if ($totp->verify($userId, $code, true)) {
                        $this->session->data['codecart_totp_verified_user_id'] = $userId;
                        (new \CodeCart\Core\SecurityAudit($this->registry))->add('admin.totp.verify', 'success', 'info', 'admin', $userId, $this->username());
                        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
                        return;
                    }
                    (new \CodeCart\Core\SecurityAudit($this->registry))->add('admin.totp.verify', 'failed', 'warning', 'admin', $userId, $this->username());
                    $this->error['warning'] = $this->language->get('error_code');
                } catch (\Throwable $e) {
                    (new \CodeCart\Core\SecurityAudit($this->registry))->add('admin.totp.verify', 'error', 'error', 'admin', $userId, $this->username(), array('reason' => get_class($e)));
                    $this->error['warning'] = $this->language->get('error_code');
                }
            }
        }

        $data = array();
        foreach (array('heading_title','text_instruction','text_code','text_recovery','button_verify','button_logout') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['action'] = $this->url->link('common/totp', 'user_token=' . $this->session->data['user_token'], true);
        $data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('common/totp', $data));
    }

    private function username(): string {
        try {
            $query = $this->db->query("SELECT username FROM `" . DB_PREFIX . "user` WHERE user_id='" . (int)$this->user->getId() . "' LIMIT 1");
            return $query->num_rows ? (string)$query->row['username'] : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
