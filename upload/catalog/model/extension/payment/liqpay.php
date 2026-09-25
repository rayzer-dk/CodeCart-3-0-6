<?php
class ModelExtensionPaymentLiqPay extends Model {
	public function getMethod($address, $total) {
        if (!$this->config->get('payment_liqpay_status')) { return array(); }

		$this->load->language('extension/payment/liqpay');

		$country_id = isset($address['country_id']) ? (int)$address['country_id'] : 0;
		$zone_id = isset($address['zone_id']) ? (int)$address['zone_id'] : 0;
		$geo_zone_id = (int)$this->config->get('payment_liqpay_geo_zone_id');
		$minimum_total = (float)$this->config->get('payment_liqpay_total');

		if ($minimum_total > 0 && $minimum_total > (float)$total) {
			$status = false;
		} elseif (!$geo_zone_id) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . $geo_zone_id . "' AND country_id = '" . $country_id . "' AND (zone_id = '" . $zone_id . "' OR zone_id = '0') LIMIT 1");
			$status = (bool)$query->num_rows;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'liqpay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_liqpay_sort_order')
			);
		}

		return $method_data;
	}
}