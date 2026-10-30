<?php
/** Consent-only bridge for the independently implemented UniShop2 guest checkout. */
class ControllerExtensionModuleCodecartGuestCartContact extends Controller {
    private function enabled(): bool {
        return (bool)$this->config->get('codecart_abandoned_cart_status')
            && (bool)$this->config->get('codecart_abandoned_cart_guest_capture')
            && (string)$this->config->get('codecart_abandoned_cart_source') !== 'external'
            && !$this->customer->isLogged();
    }

    private function respond(array $json, int $status = 200): void {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store, private');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        if ($status !== 200) { $this->response->addHeader('HTTP/1.1 ' . $status . ' Error'); }
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    public function settings(): void {
        if (!$this->enabled() || ($this->request->server['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            $this->respond(array('enabled'=>false), 403); return;
        }
        $this->load->language('checkout/checkout');
        $nonce = bin2hex(random_bytes(24));
        $this->session->data['ccp_cart_guest_nonce'] = $nonce;
        $this->respond(array(
            'enabled'=>true, 'nonce'=>$nonce,
            'label'=>$this->language->get('text_ccp_cart_recovery_consent'),
            'error_label'=>$this->language->get('text_ccp_cart_recovery_save_failed'),
            'saved_label'=>$this->language->get('text_ccp_cart_recovery_saved'),
            'selectors'=>array(
                'form'=>(string)$this->config->get('codecart_abandoned_cart_selector_form'),
                'firstname'=>(string)$this->config->get('codecart_abandoned_cart_selector_firstname'),
                'lastname'=>(string)$this->config->get('codecart_abandoned_cart_selector_lastname'),
                'email'=>(string)$this->config->get('codecart_abandoned_cart_selector_email'),
                'telephone'=>(string)$this->config->get('codecart_abandoned_cart_selector_telephone')
            ),
            'save_url'=>$this->url->link('extension/module/codecart_guest_cart_contact/save', '', true)
        ));
    }

    public function save(): void {
        if (!$this->enabled() || ($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respond(array('success'=>false), 403); return;
        }
        $token = (string)($this->request->post['nonce'] ?? '');
        $expected = (string)($this->session->data['ccp_cart_guest_nonce'] ?? '');
        if ($token === '' || $expected === '' || !hash_equals($expected, $token) ||
            strtolower((string)($this->request->server['HTTP_X_REQUESTED_WITH'] ?? '')) !== 'xmlhttprequest') {
            $this->respond(array('success'=>false), 403); return;
        }
        $origin = (string)($this->request->server['HTTP_ORIGIN'] ?? '');
        if ($origin !== '') {
            $url = parse_url($origin);
            $host = strtolower((string)($url['host'] ?? ''));
            $site = strtolower((string)($this->request->server['HTTP_HOST'] ?? ''));
            if (!$host || $host !== strtolower(explode(':', $site)[0]) || strtolower((string)($url['scheme'] ?? '')) !== 'https') {
                $this->respond(array('success'=>false), 403); return;
            }
        }
        $agree = (string)($this->request->post['ccp_cart_recovery_consent'] ?? '') === '1';
        if ($agree && !$this->cart->hasProducts()) {
            $this->respond(array('success'=>false, 'error'=>'empty_cart'), 422); return;
        }
        $data = array('ccp_cart_recovery_consent'=>$agree ? '1' : '0');
        if ($agree) {
            $data['email'] = trim((string)($this->request->post['email'] ?? ''));
            $data['firstname'] = trim((string)($this->request->post['firstname'] ?? ''));
            $data['lastname'] = trim((string)($this->request->post['lastname'] ?? ''));
            $data['telephone'] = trim((string)($this->request->post['telephone'] ?? ''));
            if (strlen($data['email']) > 96 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
                || (function_exists('utf8_strlen') ? utf8_strlen($data['firstname']) > 100 || utf8_strlen($data['lastname']) > 100 : strlen($data['firstname']) > 100 || strlen($data['lastname']) > 100)
                || strlen($data['telephone']) > 40) {
                $this->respond(array('success'=>false,'error'=>'invalid_contact'), 422); return;
            }
        }
        // Avoid duplicate writes, but always persist a changed contact immediately.
        $fingerprint = $agree ? hash('sha256', implode('|', array($data['email'], $data['firstname'], $data['lastname'], $data['telephone']))) : '';
        try {
            $guest = new \CodeCart\Core\GuestCartContact($this->registry);
            // A stale session fingerprint must never masquerade as a saved consent.
            if ($agree && $fingerprint === (string)($this->session->data['ccp_guest_contact_fingerprint'] ?? '')) {
                try {
                    if ($guest->hasPersistedConsent((string)$this->session->getId(), $data['email'])) {
                        $this->respond(array('success'=>true, 'stored'=>true)); return;
                    }
                } catch (\Throwable $lookupError) {
                    // Allow the write below to handle a missing legacy consent table.
                    unset($this->session->data['ccp_guest_contact_fingerprint']);
                }
            }
            try {
                $guest->saveFromCheckout((string)$this->session->getId(), $data);
            } catch (\Throwable $storageError) {
                // File-only upgrades may leave the consent table uninstalled. Repair
                // only a confirmed missing table, after explicit valid opt-in/revocation.
                $missingTable = strpos((string)$storageError->getMessage(), DB_PREFIX . 'codecart_cart_guest_contact') !== false
                    && preg_match('/(?:\b1146\b|does not exist|doesn.t exist)/i', (string)$storageError->getMessage());
                if (!$missingTable) { throw $storageError; }
                $guest->install();
                $guest->saveFromCheckout((string)$this->session->getId(), $data);
            }
            if ($agree && !$guest->hasPersistedConsent((string)$this->session->getId(), $data['email'])) {
                unset($this->session->data['ccp_guest_contact_fingerprint']);
                $this->log->write('Guest cart consent persistence verification failed for store ' . (int)$this->config->get('config_store_id') . '; no personal data logged');
                $this->respond(array('success'=>false, 'error'=>'not_persisted'), 500); return;
            }
            if ($agree) { $this->session->data['ccp_guest_contact_fingerprint'] = $fingerprint; }
            else { unset($this->session->data['ccp_guest_contact_fingerprint']); }
            $this->respond(array('success'=>true,'stored'=>$agree));
        } catch (\Throwable $e) {
            $this->log->write('Guest cart contact bridge failed: ' . get_class($e));
            $this->respond(array('success'=>false, 'error'=>'save_failed'), 500);
        }
    }
}
