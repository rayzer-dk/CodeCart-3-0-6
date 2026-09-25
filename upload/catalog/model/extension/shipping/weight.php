<?php
class ModelExtensionShippingWeight extends Model {
	public function getQuote($address) {
        if (!$this->config->get('shipping_weight_status')) { return array(); }

		$this->load->language('extension/shipping/weight');

		$quote_data = array();
		$country_id = isset($address['country_id']) ? (int)$address['country_id'] : 0;
		$zone_id = isset($address['zone_id']) ? (int)$address['zone_id'] : 0;

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "geo_zone ORDER BY name");

        $weight = $this->cart->getWeight();

		foreach ($query->rows as $result) {
			if ($this->config->get('shipping_weight_' . $result['geo_zone_id'] . '_status')) {
				$query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$result['geo_zone_id'] . "' AND country_id = '" . $country_id . "' AND (zone_id = '" . $zone_id . "' OR zone_id = '0')");

				if ($query->num_rows) {
					$status = true;
				} else {
					$status = false;
				}
			} else {
				$status = false;
			}

			if ($status) {
				$cost = '';

				$rates = explode(',', (string)$this->config->get('shipping_weight_' . $result['geo_zone_id'] . '_rate'));

                $brackets = array();
                foreach ($rates as $rate) {
                    $data = array_map('trim', explode(':', (string)$rate, 2));
                    if (count($data) !== 2 || !is_numeric($data[0]) || !is_numeric($data[1])) { continue; }
                    $limit = (float)$data[0];
                    $fee = (float)$data[1];
                    if (!is_finite($limit) || !is_finite($fee) || $limit < 0 || $fee < 0) { continue; }
                    $brackets[] = array('limit' => $limit, 'fee' => $fee);
                }
                usort($brackets, function($a, $b) { return $a['limit'] <=> $b['limit']; });
                foreach ($brackets as $bracket) {
                    if ($bracket['limit'] >= (float)$weight) { $cost = $bracket['fee']; break; }
                }

				if ((string)$cost != '') {
					$quote_data['weight_' . $result['geo_zone_id']] = array(
						'code'         => 'weight.weight_' . $result['geo_zone_id'],
						'title'        => $result['name'] . '  (' . $this->language->get('text_weight') . ' ' . $this->weight->format($weight, $this->config->get('config_weight_class_id')) . ')',
						'cost'         => $cost,
						'tax_class_id' => $this->config->get('shipping_weight_tax_class_id'),
						'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_weight_tax_class_id'), $this->config->get('config_tax')), (isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency')))
					);
				}
			}
		}

		$method_data = array();

		if ($quote_data) {
			$method_data = array(
				'code'       => 'weight',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_weight_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}
