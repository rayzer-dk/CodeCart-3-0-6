<?php
class ControllerExtensionPaymentBankTransfer extends Controller {
	public function index() {
        if (!$this->config->get('payment_bank_transfer_status')) { return ''; }
        $data['codecart_payment_token'] = (new \CodeCart\Core\OfflinePayment($this->registry))->token();
		$this->load->language('extension/payment/bank_transfer');

		$data['bank'] = nl2br(htmlspecialchars((string)$this->config->get('payment_bank_transfer_bank' . $this->config->get('config_language_id')), ENT_QUOTES, 'UTF-8'));

		return $this->load->view('extension/payment/bank_transfer', $data);
	}

	public function confirm() {
        (new \CodeCart\Core\OfflinePayment($this->registry))->confirm('bank_transfer');
    }
}
