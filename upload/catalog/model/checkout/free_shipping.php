<?php
class ModelCheckoutFreeShipping extends Model {
	public function getProgress() {
		if (!$this->config->get('shipping_free_status') || !$this->cart->hasProducts() || !$this->cart->hasShipping()) {
			return array();
		}

		if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
			return array();
		}

		$threshold = (float)$this->config->get('shipping_free_total');

		if ($threshold <= 0 || !$this->isInGeoZone((int)$this->config->get('shipping_free_geo_zone_id'))) {
			return array();
		}

		$this->load->language('checkout/free_shipping');

		$currency = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
		$subtotal = (float)$this->cart->getSubTotal();
		$reached = $subtotal >= $threshold;
		$remaining = $this->currency->format($reached ? 0 : $threshold - $subtotal, $currency);

		return array(
			'reached'   => $reached,
			'percent'   => $reached ? 100 : (int)floor($subtotal / $threshold * 100),
			'remaining' => $remaining,
			'message'   => $reached ? $this->language->get('text_free_shipping_reached') : sprintf($this->language->get('text_free_shipping_left'), $remaining)
		);
	}

	private function isInGeoZone($geo_zone_id) {
		if (!$geo_zone_id) {
			return true;
		}

		if (!empty($this->session->data['shipping_address'])) {
			$address = $this->session->data['shipping_address'];
		} elseif ($this->customer->isLogged() && $this->customer->getAddressId()) {
			$this->load->model('account/address');

			$address = $this->model_account_address->getAddress($this->customer->getAddressId());
		}

		if (empty($address)) {
			return false;
		}

		$query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . $geo_zone_id . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0') LIMIT 1");

		return (bool)$query->num_rows;
	}
}
