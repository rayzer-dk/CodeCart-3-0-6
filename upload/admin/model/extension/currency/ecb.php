<?php
class ModelExtensionCurrencyEcb extends Model {
    public function refresh() {
        if (!$this->config->get('currency_ecb_status') || (string)$this->config->get('config_currency_engine') !== 'ecb') {
            return false;
        }
        if (!function_exists('curl_init') || !class_exists('DOMDocument')) {
            $this->log->write('ECB currency refresh failed: required cURL/DOM PHP extensions are unavailable.');
            return false;
        }

        $curl = curl_init('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => array('Accept: application/xml,text/xml', 'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : VERSION))
        ));
        \CodeCart\Core\TlsPolicy::applyCurl($curl);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        // CurlHandle is released automatically; curl_close() is deprecated in PHP 8.5.

        if ($response === false || $status !== 200) {
            $this->log->write('ECB currency refresh failed: HTTP ' . $status . ($error !== '' ? ' - ' . $error : ''));
            return false;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML((string)$response, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            $this->log->write('ECB currency refresh failed: invalid XML response.');
            return false;
        }

        $rates = array('EUR' => 1.0);
        foreach ($dom->getElementsByTagName('Cube') as $node) {
            $code = strtoupper(trim((string)$node->getAttribute('currency')));
            $rate = (float)$node->getAttribute('rate');
            if (preg_match('/^[A-Z]{3}$/', $code) && is_finite($rate) && $rate > 0) {
                $rates[$code] = $rate;
            }
        }

        $default = strtoupper(trim((string)$this->config->get('config_currency')));
        if ($default === '') { $default = 'EUR'; }
        if ($default === 'UAH' && (!isset($rates['UAH']) || $rates['UAH'] <= 0)) {
            $uahPerEur = $this->fetchNbuUahPerEur();
            if ($uahPerEur > 0) {
                // ECB quotes currencies per EUR; NBU's EUR rate is UAH per EUR, so the
                // value can be used directly as the missing UAH leg of the ECB matrix.
                $rates['UAH'] = $uahPerEur;
            }
        }
        if (!isset($rates[$default]) || $rates[$default] <= 0) {
            $this->log->write('ECB currency refresh skipped: base currency ' . $default . ' is unavailable.');
            return false;
        }

        $currencies = $this->db->query("SELECT code FROM `" . DB_PREFIX . "currency` WHERE status = '1'");
        try {
            $this->db->beginTransaction();
            foreach ($currencies->rows as $currency) {
                $code = strtoupper((string)$currency['code']);
                if (!isset($rates[$code]) || $rates[$code] <= 0) { continue; }
                $value = $rates[$code] / $rates[$default];
                $this->db->query("UPDATE `" . DB_PREFIX . "currency` SET value = '" . (float)$value . "', date_modified = NOW() WHERE code = '" . $this->db->escape($code) . "'");
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "currency` SET value = '1.00000000', date_modified = NOW() WHERE code = '" . $this->db->escape($default) . "'");
            $this->db->commit();
        } catch (\Throwable $e) {
            try { $this->db->rollback(); } catch (\Throwable $ignored) {}
            $this->log->write('ECB currency refresh failed while saving rates: ' . $e->getMessage());
            return false;
        }

        $this->cache->delete('currency');
        return true;
    }
    private function fetchNbuUahPerEur() {
        if (!function_exists('curl_init')) { return 0.0; }

        $curl = curl_init('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode=EUR&json');
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : VERSION))
        ));
        \CodeCart\Core\TlsPolicy::applyCurl($curl);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($response === false || $status !== 200) { return 0.0; }

        $rows = json_decode((string)$response, true);
        if (!is_array($rows)) { return 0.0; }
        foreach ($rows as $row) {
            if (is_array($row) && strtoupper((string)($row['cc'] ?? '')) === 'EUR') {
                $rate = (float)($row['rate'] ?? 0);
                return is_finite($rate) && $rate > 0 ? $rate : 0.0;
            }
        }
        return 0.0;
    }

}
