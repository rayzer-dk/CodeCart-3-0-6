<?php
/** Authenticated consent bridge: never changes checkout/order data. */
class ControllerExtensionModuleCodecartRegisteredCartConsent extends Controller {
    private function enabled(): bool {
        return (bool)$this->config->get('codecart_abandoned_cart_status')
            && (string)$this->config->get('codecart_abandoned_cart_source') !== 'external'
            && $this->customer->isLogged();
    }

    private function respond(array $json, int $status = 200): void {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: private, no-store');
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
        $this->session->data['ccp_registered_cart_consent_nonce'] = $nonce;
        $this->respond(array(
            'enabled'=>true,
            'nonce'=>$nonce,
            'checked'=>(new \CodeCart\Core\RegisteredCartConsent($this->registry))->hasConsent((int)$this->customer->getId(),(string)$this->session->getId()),
            'label'=>$this->language->get('text_ccp_registered_cart_consent'),
            'save_url'=>$this->url->link('extension/module/codecart_registered_cart_consent/save', '', true)
        ));
    }

    public function save(): void {
        if (!$this->enabled() || ($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respond(array('success'=>false), 403); return;
        }
        $given = (string)($this->request->post['nonce'] ?? '');
        $expected = (string)($this->session->data['ccp_registered_cart_consent_nonce'] ?? '');
        if (!$given || !$expected || !hash_equals($expected, $given)
            || strtolower((string)($this->request->server['HTTP_X_REQUESTED_WITH'] ?? '')) !== 'xmlhttprequest') {
            $this->respond(array('success'=>false), 403); return;
        }
        $origin = (string)($this->request->server['HTTP_ORIGIN'] ?? '');
        if ($origin !== '') {
            $parsed = parse_url($origin);
            $host = strtolower((string)($parsed['host'] ?? ''));
            $site = strtolower(explode(':',(string)($this->request->server['HTTP_HOST'] ?? ''))[0]);
            if (!$host || $host !== $site || strtolower((string)($parsed['scheme'] ?? '')) !== 'https') {
                $this->respond(array('success'=>false), 403); return;
            }
        }
        $agree = (string)($this->request->post['consent'] ?? '') === '1';
        if ($agree && !$this->cart->hasProducts()) {
            $this->respond(array('success'=>false,'error'=>'empty_cart'), 422); return;
        }
        try {
            $consents = new \CodeCart\Core\RegisteredCartConsent($this->registry);
            try {
                $consents->save((int)$this->customer->getId(), (string)$this->session->getId(), $agree);
            } catch (\Throwable $storageError) {
                // Compatible with file-only upgrades: provision persistent storage on
                // the first explicit checkout consent, then retry just once.
                $consents->install();
                $consents->save((int)$this->customer->getId(), (string)$this->session->getId(), $agree);
            }
            $this->respond(array('success'=>true));
        } catch (\Throwable $e) {
            $this->log->write('Registered cart consent failed: ' . get_class($e));
            $this->respond(array('success'=>false, 'error'=>'save_failed'), 500);
        }
    }
}
