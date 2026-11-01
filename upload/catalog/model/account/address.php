<?php
class ModelAccountAddress extends Model {
	public function addAddress($customer_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "'");

		$address_id = $this->db->getLastId();

		if (!empty($data['default'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
		}

		return $address_id;
	}

	public function editAddress($address_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "address SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "' WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

		if (!empty($data['default'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		}
	}

	public function deleteAddress($address_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		$default_query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "customer WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		if ($default_query->num_rows) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = 0 WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		}
	}

	public function getAddress($address_id) {
		$address_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

		if ($address_query->num_rows) {
			$addresses = $this->buildAddresses($address_query->rows);

			return $addresses[(int)$address_query->row['address_id']];
		} else {
			return false;
		}
	}

	public function getAddresses() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $this->buildAddresses($query->rows);
	}

	/**
	 * Address rows with country and zone data, loaded with one query per table.
	 */
	private function buildAddresses(array $rows) {
		$countries = array();
		$zones = array();

		$country_ids = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'country_id')))));
		if ($country_ids) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id IN (" . implode(',', $country_ids) . ")");
			foreach ($country_query->rows as $country) {
				$countries[(int)$country['country_id']] = $country;
			}
		}

		$zone_ids = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'zone_id')))));
		if ($zone_ids) {
			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id IN (" . implode(',', $zone_ids) . ")");
			foreach ($zone_query->rows as $zone) {
				$zones[(int)$zone['zone_id']] = $zone;
			}
		}

		$address_data = array();
		foreach ($rows as $row) {
			$country = isset($countries[(int)$row['country_id']]) ? $countries[(int)$row['country_id']] : array();
			$zone = isset($zones[(int)$row['zone_id']]) ? $zones[(int)$row['zone_id']] : array();

			$address_data[$row['address_id']] = array(
				'address_id'     => $row['address_id'],
				'firstname'      => $row['firstname'],
				'lastname'       => $row['lastname'],
				'company'        => $row['company'],
				'address_1'      => $row['address_1'],
				'address_2'      => $row['address_2'],
				'postcode'       => $row['postcode'],
				'city'           => $row['city'],
				'zone_id'        => $row['zone_id'],
				'zone'           => $zone ? $zone['name'] : '',
				'zone_code'      => $zone ? $zone['code'] : '',
				'country_id'     => $row['country_id'],
				'country'        => $country ? $country['name'] : '',
				'iso_code_2'     => $country ? $country['iso_code_2'] : '',
				'iso_code_3'     => $country ? $country['iso_code_3'] : '',
				'address_format' => $country ? $country['address_format'] : '',
				'custom_field'   => json_decode($row['custom_field'], true)
			);
		}

		return $address_data;
	}

	public function getTotalAddresses() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}
}
