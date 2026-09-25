<?php
class ModelExtensionPaymentBankTransfer extends Model {
	public function getMethod($address, $total) {
        if (!$this->config->get('payment_bank_transfer_status')) { return array(); }

		$this->load->language('extension/payment/bank_transfer');

		$country_id = isset($address['country_id']) ? (int)$address['country_id'] : 0;
		$zone_id = isset($address['zone_id']) ? (int)$address['zone_id'] : 0;
		$geo_zone_id = (int)$this->config->get('payment_bank_transfer_geo_zone_id');
		$query = null;

		if ($geo_zone_id) {
			$query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . $geo_zone_id . "' AND country_id = '" . $country_id . "' AND (zone_id = '" . $zone_id . "' OR zone_id = '0') LIMIT 1");
		}

		if ($this->config->get('payment_bank_transfer_total') > 0 && $this->config->get('payment_bank_transfer_total') > $total) {
			$status = false;
		} elseif (!$geo_zone_id) {
			$status = true;
		} elseif ($query && $query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'bank_transfer',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_bank_transfer_sort_order')
			);
		}

		return $method_data;
	}
}
