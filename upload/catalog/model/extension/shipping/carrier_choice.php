<?php
class ModelExtensionShippingCarrierChoice extends Model {
    public function getQuote($address) {
        if (!$this->config->get('shipping_carrier_choice_status')) { return array(); }
        $this->load->language('extension/shipping/carrier_choice');
        $country_id = isset($address['country_id']) ? (int)$address['country_id'] : 0;
        $zone_id = isset($address['zone_id']) ? (int)$address['zone_id'] : 0;
        $geo_zone_id = (int)$this->config->get('shipping_carrier_choice_geo_zone_id');
        if ($geo_zone_id) {
            $query = $this->db->query("SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id='" . $geo_zone_id . "' AND country_id='" . $country_id . "' AND (zone_id='" . $zone_id . "' OR zone_id='0') LIMIT 1");
            if (!$query->num_rows) { return array(); }
        }

        $carriers = $this->getCarriers();
        $language_id = (int)$this->config->get('config_language_id');
        $tax_class_id = (int)$this->config->get('shipping_carrier_choice_tax_class_id');
        $currency = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
        $quote_data = array();
        foreach ($carriers as $carrier) {
            if (empty($carrier['status'])) { continue; }
            $title = $this->resolveName($carrier, $language_id);
            if ($title === '') { continue; }
            $id = $this->carrierId($carrier);
            if ($id === '') { continue; }
            $quote_key = 'carrier_' . $id;
            $cost = isset($carrier['cost']) && is_numeric($carrier['cost']) ? max(0, (float)$carrier['cost']) : 0.0;
            $provider = isset($carrier['provider']) ? (string)$carrier['provider'] : 'manual';
            $quote_data[$quote_key] = array(
                'code' => 'carrier_choice.' . $quote_key,
                'title' => $title,
                'cost' => $cost,
                'tax_class_id' => $tax_class_id,
                'text' => $this->currency->format($this->tax->calculate($cost, $tax_class_id, $this->config->get('config_tax')), $currency),
                'carrier_id' => $id,
                'provider' => $provider,
                'requires_location' => in_array($provider, array('nova_poshta','meest','delivery','ukrposhta'), true)
            );
        }
        if (!$quote_data) { return array(); }
        return array('code'=>'carrier_choice','title'=>$this->language->get('text_title'),'quote'=>$quote_data,'sort_order'=>(int)$this->config->get('shipping_carrier_choice_sort_order'),'error'=>false);
    }

    public function getCarrierByQuoteKey($quoteKey) {
        $quoteKey = (string)$quoteKey;
        if (strpos($quoteKey, 'carrier_') === 0) { $quoteKey = substr($quoteKey, 8); }
        $quoteKey = preg_replace('/[^a-z0-9]/', '', strtolower($quoteKey));
        foreach ($this->getCarriers() as $carrier) {
            if ($this->carrierId($carrier) === $quoteKey && !empty($carrier['status'])) { return $carrier; }
        }
        return array();
    }

    public function getCarrierTitle(array $carrier) { return $this->resolveName($carrier, (int)$this->config->get('config_language_id')); }

    public function searchCities(array $carrier, $term, $limit = 20) { return $this->directory()->searchCities($carrier, $term, $limit); }
    public function getBranches(array $carrier, $cityId) { return $this->directory()->getBranches($carrier, $cityId); }
    public function validateSelection(array $carrier, $cityId, $branchId) { return $this->directory()->validateSelection($carrier, $cityId, $branchId); }

    public function isCompactQuickCheckout() {
        return $this->config->get('shipping_carrier_choice_status')
            && $this->config->get('shipping_carrier_choice_compact_checkout')
            && (string)$this->config->get('theme_default_commerce_style') === 'modern'
            && $this->config->get('config_quick_checkout_status');
    }

    private function getCarriers() {
        $carriers = $this->config->get('shipping_carrier_choice_carriers');
        if (!is_array($carriers)) { return array(); }
        usort($carriers, function($a,$b){$as=isset($a['sort_order'])?(int)$a['sort_order']:0;$bs=isset($b['sort_order'])?(int)$b['sort_order']:0;return $as==$bs?0:($as<$bs?-1:1);});
        return $carriers;
    }

    private function carrierId(array $carrier) {
        $id = isset($carrier['id']) ? preg_replace('/[^a-z0-9]/', '', strtolower((string)$carrier['id'])) : '';
        return substr($id, 0, 16);
    }

    private function resolveName(array $carrier, $language_id) {
        if (empty($carrier['name']) || !is_array($carrier['name'])) { return ''; }
        if (isset($carrier['name'][$language_id])) {
            $name = trim(strip_tags((string)$carrier['name'][$language_id]));
            if ($name !== '') { return $name; }
        }
        foreach ($carrier['name'] as $name) {
            $name = trim(str_replace(array('<','>'),'',strip_tags((string)$name)));
            if ($name !== '') { return $name; }
        }
        return '';
    }

    private function directory() {
        require_once(DIR_SYSTEM . 'library/codecart/carrier_directory.php');
        return new CodeCartCarrierDirectory($this->registry);
    }
}
