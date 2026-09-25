<?php
class ModelExtensionShippingFree extends Model {
	function getQuote($address) {
        if (!$this->config->get('shipping_free_status')) { return array(); }

		$this->load->language('extension/shipping/free');

		$country_id = isset($address['country_id']) ? (int)$address['country_id'] : 0;
		$zone_id = isset($address['zone_id']) ? (int)$address['zone_id'] : 0;
		$geo_zone_id = (int)$this->config->get('shipping_free_geo_zone_id');

		if (!$geo_zone_id) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . $geo_zone_id . "' AND country_id = '" . $country_id . "' AND (zone_id = '" . $zone_id . "' OR zone_id = '0') LIMIT 1");
			$status = (bool)$query->num_rows;
		}

		if ($this->cart->getSubTotal() < $this->config->get('shipping_free_total')) {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$quote_data = array();

			$quote_data['free'] = array(
				'code'         => 'free.free',
				'title'        => $this->language->get('text_description'),
				'cost'         => 0.00,
				'tax_class_id' => 0,
				'text'         => $this->currency->format(0.00, (isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency')))
			);

			$method_data = array(
				'code'       => 'free',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_free_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}