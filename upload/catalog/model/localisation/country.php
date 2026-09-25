<?php
class ModelLocalisationCountry extends Model {
	public function getCountry($country_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = '" . (int)$country_id . "' AND status = '1'");

		return $query->row;
	}

	public function getCountries() {
		$preferred_country_id = (int)$this->config->get('config_country_id');
		$cache_key = 'country.catalog.' . $preferred_country_id;
		$country_data = $this->cache->get($cache_key);

		if ($country_data === false || $country_data === null) {
			// Keep the store's configured country at the top of long native country
			// selects. This improves checkout usability without introducing Select2,
			// bootstrap-select or another storefront dependency.
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE status = '1' ORDER BY (country_id = '" . $preferred_country_id . "') DESC, name ASC");

			$country_data = $query->rows;

			$this->cache->set($cache_key, $country_data);
		}

		return $country_data;
	}
}
