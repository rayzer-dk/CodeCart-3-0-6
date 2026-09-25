<?php
class ControllerExtensionPaymentCod extends Controller {
	public function index() {
        if (!$this->config->get('payment_cod_status')) { return ''; }
        $data['codecart_payment_token'] = (new \CodeCart\Core\OfflinePayment($this->registry))->token();
		return $this->load->view('extension/payment/cod', $data);
	}

	public function confirm() {
        (new \CodeCart\Core\OfflinePayment($this->registry))->confirm('cod');
    }
}
