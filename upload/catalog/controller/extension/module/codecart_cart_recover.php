<?php
class ControllerExtensionModuleCodecartCartRecover extends Controller {
    public function index() {
        $this->response->addHeader('Cache-Control: no-store, private');
        $this->response->addHeader('Referrer-Policy: no-referrer');
        if ((string)$this->config->get('codecart_abandoned_cart_source') !== 'external'
            && $this->config->get('codecart_abandoned_cart_status')
            && $this->config->get('codecart_abandoned_cart_guest_capture')) {
            try {
                $token = (string)($this->request->get['token'] ?? '');
                (new \CodeCart\Core\GuestCartContact($this->registry))->restore($token, $this->cart);
            } catch (\Throwable $e) {
                $this->log->write('Guest cart recovery failed: ' . get_class($e));
            }
        }
        // Never leave a recovery token in the browser's destination URL.
        $this->response->redirect($this->url->link('checkout/cart', '', true));
    }
}
