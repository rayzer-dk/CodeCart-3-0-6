<?php
class ControllerExtensionPaymentLiqPay extends Controller {
    public function index() {
        if (!$this->config->get('payment_liqpay_status')) { return ''; }
        $this->load->language('extension/payment/liqpay');
        $this->load->model('checkout/order');

        if (empty($this->session->data['order_id'])) { return false; }
        $order_info = $this->model_checkout_order->getOrder((int)$this->session->data['order_id']);
        if (!$order_info) { return false; }

        $public_key = trim((string)$this->config->get('payment_liqpay_public_key'));
        $private_key = trim((string)$this->config->get('payment_liqpay_private_key'));
        if ($public_key === '' || $private_key === '') { return false; }

        $amount = (float)\CodeCart\Core\Money::normalize($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false), 2);
        $payload = array(
            'version' => 7,
            'public_key' => $public_key,
            'action' => 'pay',
            'amount' => $amount,
            'currency' => strtoupper((string)$order_info['currency_code']),
            'description' => substr((string)$this->config->get('config_name') . ' - order #' . (int)$order_info['order_id'], 0, 255),
            'order_id' => (string)(int)$order_info['order_id'],
            'result_url' => $this->url->link('checkout/success', '', true),
            'server_url' => $this->url->link('extension/payment/liqpay/callback', '', true),
            'language' => (isset($this->session->data['language']) && $this->session->data['language'] === 'uk-ua') ? 'uk' : 'en'
        );
        if ($this->config->get('payment_liqpay_sandbox')) { $payload['sandbox'] = 1; }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data = base64_encode($json);
        $signature = base64_encode(hash('sha3-256', $private_key . $data . $private_key, true));

        return $this->load->view('extension/payment/liqpay', array(
            'action' => 'https://www.liqpay.ua/api/3/checkout',
            'data' => $data,
            'signature' => $signature,
            'button_confirm' => $this->language->get('button_confirm')
        ));
    }

    public function callback() {
        if (!$this->config->get('payment_liqpay_status')) { $this->response->setStatusCode(404); return; }
        if (!isset($this->request->server['REQUEST_METHOD']) || strtoupper((string)$this->request->server['REQUEST_METHOD']) !== 'POST') { $this->response->setStatusCode(405); return; }
        $data = isset($this->request->post['data']) ? (string)$this->request->post['data'] : '';
        $received = isset($this->request->post['signature']) ? (string)$this->request->post['signature'] : '';
        $private_key = trim((string)$this->config->get('payment_liqpay_private_key'));
        if ($data === '' || $received === '' || $private_key === '') { $this->response->setStatusCode(400); return; }

        $expected = base64_encode(hash('sha3-256', $private_key . $data . $private_key, true));
        if (!hash_equals($expected, $received)) { $this->log->write('LiqPay callback rejected: invalid signature'); $this->response->setStatusCode(403); return; }

        $decoded = base64_decode($data, true);
        $payload = $decoded !== false ? json_decode($decoded, true) : null;
        if (!is_array($payload) || empty($payload['order_id']) || !ctype_digit((string)$payload['order_id'])) { $this->response->setStatusCode(400); return; }

        $order_id = (int)$payload['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info || (string)$order_info['payment_code'] !== 'liqpay' || (int)$order_info['store_id'] !== (int)$this->config->get('config_store_id') || (string)($payload['public_key'] ?? '') !== trim((string)$this->config->get('payment_liqpay_public_key'))) { $this->response->setStatusCode(404); return; }

        $expected_amount = \CodeCart\Core\Money::normalize($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false), 2);
        $received_amount = isset($payload['amount']) ? \CodeCart\Core\Money::normalize($payload['amount'], 2) : '-1.00';
        $received_currency = isset($payload['currency']) ? strtoupper((string)$payload['currency']) : '';
        if (\CodeCart\Core\Money::compare($expected_amount, $received_amount, 2) !== 0 || $received_currency !== strtoupper((string)$order_info['currency_code'])) {
            $this->log->write('LiqPay callback rejected for order ' . $order_id . ': amount/currency mismatch');
            $this->response->setStatusCode(400); return;
        }

        $status = isset($payload['status']) ? (string)$payload['status'] : '';
        if ($status === 'success' || ($status === 'sandbox' && $this->config->get('payment_liqpay_sandbox'))) {
            if ((int)$order_info['order_status_id'] !== (int)$this->config->get('payment_liqpay_order_status_id')) {
                $event_key = !empty($payload['payment_id']) ? 'payment:' . (string)$payload['payment_id'] : hash('sha256', $data);
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    (int)$this->config->get('payment_liqpay_order_status_id'),
                    'LiqPay: ' . $status,
                    true,
                    false,
                    array(
                        'scope' => 'payment.liqpay.callback',
                        'key' => $event_key,
                        'fingerprint' => $order_id . '|' . $status . '|' . $received_amount . '|' . $received_currency,
                        'ttl' => 604800
                    )
                );
            }
        } elseif (in_array($status, array('failure','error','reversed'), true)) {
            $this->log->write('LiqPay payment order ' . $order_id . ' status: ' . $status);
        }

        $this->response->addHeader('Content-Type: text/plain; charset=utf-8');
        $this->response->setOutput('OK');
    }
}
