<?php
class ModelExtensionShippingFlat extends Model {
	function getQuote($address) {
        if (!$this->config->get('shipping_flat_status')) { return array(); }

		$this->load->language('extension/shipping/flat');

		$country_id = isset($address['country_id']) ? (int)$address['country_id'] : 0;
		$zone_id = isset($address['zone_id']) ? (int)$address['zone_id'] : 0;
		$geo_zone_id = (int)$this->config->get('shipping_flat_geo_zone_id');

		if (!$geo_zone_id) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . $geo_zone_id . "' AND country_id = '" . $country_id . "' AND (zone_id = '" . $zone_id . "' OR zone_id = '0') LIMIT 1");
			$status = (bool)$query->num_rows;
		}

		$method_data = array();

		if ($status) {
			$quote_data = array();

			$quote_data['flat'] = array(
				'code'         => 'flat.flat',
				'title'        => $this->language->get('text_description'),
				'cost'         => max(0.0, (float)$this->config->get('shipping_flat_cost')),
				'tax_class_id' => $this->config->get('shipping_flat_tax_class_id'),
				'text'         => $this->currency->format($this->tax->calculate(max(0.0, (float)$this->config->get('shipping_flat_cost')), $this->config->get('shipping_flat_tax_class_id'), $this->config->get('config_tax')), (isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency')))
			);

			$method_data = array(
				'code'       => 'flat',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_flat_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}