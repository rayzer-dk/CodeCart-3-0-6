<?php
class ControllerExtensionPaymentFreeCheckout extends Controller {
	public function index() {
        if (!$this->config->get('payment_free_checkout_status')) { return ''; }
        $data['codecart_payment_token'] = (new \CodeCart\Core\OfflinePayment($this->registry))->token();
		$data['continue'] = $this->url->link('checkout/success');

		return $this->load->view('extension/payment/free_checkout', $data);
	}

	public function confirm() {
        (new \CodeCart\Core\OfflinePayment($this->registry))->confirm('free_checkout');
    }
}
