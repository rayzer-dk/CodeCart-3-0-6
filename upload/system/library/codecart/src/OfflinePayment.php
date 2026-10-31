<?php
namespace CodeCart\Core;

/** Confirmation boundary for bundled offline payment methods. */
final class OfflinePayment {
    private $registry;
    private $config;
    private $session;
    private $request;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->config = $registry->get('config');
        $this->session = $registry->get('session');
        $this->request = $registry->get('request');
    }

    public function token() {
        if (empty($this->session->data['codecart_payment_token'])) {
            $this->session->data['codecart_payment_token'] = bin2hex(random_bytes(32));
        }
        return $this->session->data['codecart_payment_token'];
    }

    public function confirm($code) {
        $load = $this->registry->get('load');
        $response = $this->registry->get('response');
        $load->language('extension/payment/codecart_confirm');
        $language = $this->registry->get('language');
        $response->addHeader('Content-Type: application/json');
        $response->addHeader('Cache-Control: no-store');
        try {
            if (!in_array($code, array('cod', 'bank_transfer', 'free_checkout'), true) || !$this->config->get('payment_' . $code . '_status')) {
                throw new \RuntimeException('disabled');
            }
            $method = strtoupper((string)($this->request->server['REQUEST_METHOD'] ?? 'GET'));
            $provided = $this->request->post['codecart_payment_token'] ?? '';
            $expected = $this->session->data['codecart_payment_token'] ?? '';
            $tokenValid = $method === 'POST' && is_string($provided) && is_string($expected) && $expected !== '' && hash_equals($expected, $provided);
            // Old OC3 theme overrides use jQuery GET without a token. Retain that
            // transport only for same-origin XMLHttpRequest; cross-site requests
            // cannot set this header without a successful CORS preflight.
            $legacyAjax = in_array($method, array('GET', 'POST'), true) && strtolower((string)($this->request->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
            if (strtolower((string)($this->request->server['HTTP_SEC_FETCH_SITE'] ?? '')) === 'cross-site') { $legacyAjax = false; }
            foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $header) {
                if (!empty($this->request->server[$header])) {
                    $host = parse_url((string)$this->request->server[$header], PHP_URL_HOST);
                    $allowed = array_filter(array(parse_url((string)$this->config->get('config_url'), PHP_URL_HOST), parse_url((string)$this->config->get('config_ssl'), PHP_URL_HOST)));
                    if (!is_string($host) || !in_array(strtolower($host), array_map('strtolower', $allowed), true)) { $legacyAjax = false; }
                }
            }
            if (!$tokenValid && !$legacyAjax) { throw new \RuntimeException('request'); }
            $orderId = (int)($this->session->data['order_id'] ?? 0);
            if (!$orderId || ($this->session->data['payment_method']['code'] ?? '') !== $code) { throw new \RuntimeException('session'); }
            $load->model('checkout/order');
            $model = $this->registry->get('model_checkout_order');
            $order = $model->getOrder($orderId);
            $customer = $this->registry->get('customer');
            $customerId = $customer && $customer->isLogged() ? (int)$customer->getId() : 0;
            if (!$order || (int)$order['store_id'] !== (int)$this->config->get('config_store_id') || (int)$order['customer_id'] !== $customerId || (string)$order['payment_code'] !== $code) { throw new \RuntimeException('order'); }
            $status = (int)$this->config->get('payment_' . $code . '_order_status_id');
            if ($status < 1) { throw new \RuntimeException('status'); }
            if ($code === 'free_checkout') {
                // A previously selected payment method is not proof of the current total.
                // Recheck the pending order against the cart and enabled Total extensions.
                // Keep COD, bank transfers and their existing confirmation flow untouched.
                if (Money::compare((string)$order['total'], '0') !== 0) {
                    throw new \RuntimeException('nonzero_total');
                }
                if ((int)$order['order_status_id'] === 0) {
                    (new FreeCheckoutGuard($this->registry))->assertCurrentOrder($order, $model);
                } elseif ((int)$order['order_status_id'] !== $status) {
                    // A completed order can have advanced to a processing/completed status.
                    // A rejected or fraud-flagged order must never redirect to success.
                    $accepted = array_map('intval', array_merge(
                        (array)$this->config->get('config_processing_status'),
                        (array)$this->config->get('config_complete_status')
                    ));
                    if (!in_array((int)$order['order_status_id'], $accepted, true)) {
                        throw new \RuntimeException('order_not_confirmed');
                    }
                }
            }
            if ((int)$order['order_status_id'] === 0) {
                $comment = '';
                if ($code === 'bank_transfer') {
                    $load->language('extension/payment/bank_transfer');
                    $comment = $language->get('text_instruction') . "\n\n" . (string)$this->config->get('payment_bank_transfer_bank' . (int)$order['language_id']) . "\n\n" . $language->get('text_payment');
                }
                $model->addOrderHistory($orderId, $status, $comment, $code === 'bank_transfer', false, array('scope' => 'payment.' . $code . '.confirm', 'key' => (string)$orderId, 'fingerprint' => $orderId . '|' . $code . '|' . $status, 'ttl' => 604800));
                $confirmed = $model->getOrder($orderId);
                if (!$confirmed || !(int)$confirmed['order_status_id']) { throw new \RuntimeException('processing'); }
                if ($code === 'free_checkout' && (int)$confirmed['order_status_id'] !== $status) {
                    // Fraud/Total extensions can override the requested status.
                    throw new \RuntimeException('free_checkout_not_approved');
                }
            }
            $response->setOutput(json_encode(array('redirect' => $this->registry->get('url')->link('checkout/success', '', true))));
        } catch (\Throwable $e) {
            $this->registry->get('log')->write('Offline payment confirmation rejected: ' . $code . ' [' . get_class($e) . ']');
            $response->setStatusCode(409);
            $response->setOutput(json_encode(array('error' => $language->get('error_payment_confirmation'))));
        }
    }
}
