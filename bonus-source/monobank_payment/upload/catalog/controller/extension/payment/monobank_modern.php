<?php
class ControllerExtensionPaymentMonobankModern extends Controller {
    public function index() {
        if (!$this->config->get('payment_monobank_modern_status')) { return ''; }
        $this->load->language('extension/payment/monobank_modern');
        $data['button_confirm']=$this->language->get('button_confirm');
        $data['confirm_url']=$this->url->link('extension/payment/monobank_modern/confirm','',true);
        return $this->load->view('extension/payment/monobank_modern',$data);
    }

    public function confirm() {
        $json=array();
        if (!$this->config->get('payment_monobank_modern_status')) { $json['error']='Payment method is disabled.'; return $this->json($json); }
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') { $json['error']='Invalid request method.'; return $this->json($json); }
        $orderId=isset($this->session->data['order_id'])?(int)$this->session->data['order_id']:0;
        $this->load->model('checkout/order'); $order=$this->model_checkout_order->getOrder($orderId);
        if (!$order) { $json['error']='Order not found.'; return $this->json($json); }
        try {
            $this->bootstrapModern();
            $service=new \CodeCart\Extension\MonobankPayment\PaymentService($this->registry);
            $result=$service->createInvoice($order,$this->url->link('checkout/success','',true),$this->url->link('extension/payment/monobank_modern/webhook','',true));
            $json['redirect']=(string)$result['pageUrl'];
        } catch (\Throwable $e) {
            try { (new \CodeCart\Extension\MonobankPayment\PaymentService($this->registry))->safeLog('create_invoice',$e->getMessage()); } catch (\Throwable $ignored) {}
            $json['error']='Unable to create Monobank payment. Please try again.';
        }
        return $this->json($json);
    }

    public function webhook() {
        if (!$this->config->get('payment_monobank_modern_status')) { $this->response->addHeader($this->request->server['SERVER_PROTOCOL'].' 404 Not Found'); return; }
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') { $this->response->addHeader($this->request->server['SERVER_PROTOCOL'].' 405 Method Not Allowed'); return; }
        $body=file_get_contents('php://input');
        $signature='';
        foreach ($this->request->server as $key=>$value) { if (strtolower(str_replace('_','-',$key))==='http-x-sign') { $signature=(string)$value; break; } }
        try {
            $this->bootstrapModern();
            $service=new \CodeCart\Extension\MonobankPayment\PaymentService($this->registry);
            if (!$service->verifyWebhook($body,$signature)) { $this->response->addHeader($this->request->server['SERVER_PROTOCOL'].' 401 Unauthorized'); return; }
            $payload=json_decode((string)$body,true); if (!is_array($payload)) { throw new \RuntimeException('Invalid JSON webhook.'); }
            $result=$service->processWebhook($payload);
            if (!empty($result['handled']) && empty($result['stale']) && !empty($result['order_id'])) {
                $status=isset($result['status'])?(string)$result['status']:''; $statusId=0;
                if ($status==='success') { $statusId=(int)$this->config->get('payment_monobank_modern_success_status_id'); }
                elseif ($status==='processing' || $status==='hold') { $statusId=(int)$this->config->get('payment_monobank_modern_processing_status_id'); }
                elseif ($status==='failure' || $status==='reversed') { $statusId=(int)$this->config->get('payment_monobank_modern_failure_status_id'); }
                if ($statusId > 0) {
                    $this->load->model('checkout/order');
                    $this->model_checkout_order->addOrderHistory((int)$result['order_id'],$statusId,'Monobank: '.$status,false,false,array('scope'=>'payment.monobank','key'=>(string)$result['invoice_id'].'|'.$status.'|'.(string)$result['modified_date'],'ttl'=>2592000));
                }
            }
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'].' 200 OK');
            $this->response->setOutput('OK');
        } catch (\Throwable $e) {
            if (isset($service)) { $service->safeLog('webhook',$e->getMessage()); }
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'].' 500 Internal Server Error');
        }
    }

    private function bootstrapModern() { if (!class_exists('\\CodeCart\\Extension\\MonobankPayment\\PaymentService')) { (new \CodeCart\Core\ModernExtensionRegistry($this->registry))->refresh(); } }
    private function json(array $data) { $this->response->addHeader('Content-Type: application/json; charset=utf-8'); $this->response->setOutput(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); }
}
