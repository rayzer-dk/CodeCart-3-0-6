<?php
class ModelExtensionPaymentMonobankModern extends Model {
    public function getMethod($address, $total) {
        if (!$this->config->get('payment_monobank_modern_status')) { return array(); }
        if (!isset($this->session->data['currency']) || strtoupper((string)$this->session->data['currency']) !== 'UAH') { return array(); }
        if ((float)$this->config->get('payment_monobank_modern_total') > 0 && (float)$this->config->get('payment_monobank_modern_total') > (float)$total) { return array(); }
        $geoZoneId=(int)$this->config->get('payment_monobank_modern_geo_zone_id');
        if ($geoZoneId) {
            $countryId=isset($address['country_id'])?(int)$address['country_id']:0; $zoneId=isset($address['zone_id'])?(int)$address['zone_id']:0;
            $q=$this->db->query("SELECT zone_to_geo_zone_id FROM `".DB_PREFIX."zone_to_geo_zone` WHERE geo_zone_id='".$geoZoneId."' AND country_id='".$countryId."' AND (zone_id='".$zoneId."' OR zone_id='0') LIMIT 1");
            if (!$q->num_rows) { return array(); }
        }
        $this->load->language('extension/payment/monobank_modern');
        return array('code'=>'monobank_modern','title'=>$this->language->get('text_title'),'terms'=>'','sort_order'=>(int)$this->config->get('payment_monobank_modern_sort_order'));
    }
}
