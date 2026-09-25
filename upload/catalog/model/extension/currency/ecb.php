<?php
class ModelExtensionCurrencyEcb extends Model {
    private $lastError = '';

    public function getLastError() { return $this->lastError; }

    private function fail($message) {
        $this->lastError = (string)$message;
        $this->log->write($this->lastError);
        return false;
    }

    public function refresh() {
        $this->lastError = '';
        if (!$this->config->get('currency_ecb_status') || (string)$this->config->get('config_currency_engine') !== 'ecb') {
            return false;
        }
        if (!function_exists('curl_init') || !class_exists('DOMDocument')) {
            return $this->fail('ECB currency refresh failed: required cURL/DOM PHP extensions are unavailable.');
        }

        $curl = curl_init('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => array('Accept: application/xml,text/xml', 'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : VERSION))
        ));
        \CodeCart\Core\TlsPolicy::applyCurl($curl);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        // CurlHandle is released automatically; curl_close() is deprecated in PHP 8.5.

        if ($response === false || $status !== 200) {
            return $this->fail('ECB currency refresh failed: HTTP ' . $status . ($error !== '' ? ' - ' . $error : ''));
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML((string)$response, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            return $this->fail('ECB currency refresh failed: invalid XML response.');
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
            return $this->fail('ECB currency refresh failed: base currency ' . $default . ' is unavailable in ECB data and the NBU EUR/UAH bridge did not return a valid rate.');
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
            return $this->fail('ECB currency refresh failed while saving rates: ' . $e->getMessage());
        }

        $this->cache->delete('currency');
        return true;
    }
    private function fetchNbuUahPerEur() {
        if (!function_exists('curl_init')) { return 0.0; }

        $endpoints = array(
            array('url' => 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchangeNew?json&valcode=EUR', 'format' => 'statdirectory'),
            array('url' => 'https://bank.gov.ua/NBU_Exchange/exchange?json', 'format' => 'exchange')
        );

        foreach ($endpoints as $endpoint) {
            $curl = curl_init($endpoint['url']);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => array('Accept: application/json', 'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : VERSION))
            ));
            \CodeCart\Core\TlsPolicy::applyCurl($curl);
            $response = curl_exec($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($response === false || $status !== 200) { continue; }

            $rows = json_decode((string)$response, true);
            if (!is_array($rows)) { continue; }

            foreach ($rows as $row) {
                if (!is_array($row)) { continue; }
                if ($endpoint['format'] === 'statdirectory') {
                    if (strtoupper((string)($row['cc'] ?? '')) !== 'EUR') { continue; }
                    $rate = (float)($row['rate'] ?? 0);
                } else {
                    $code = strtoupper((string)($row['CurrencyCodeL'] ?? $row['cc'] ?? ''));
                    if ($code !== 'EUR') { continue; }
                    $amount = (float)($row['Amount'] ?? $row['rate'] ?? 0);
                    $units = max(1.0, (float)($row['Units'] ?? 1));
                    $rate = $amount / $units;
                }
                if (is_finite($rate) && $rate > 0) { return $rate; }
            }
        }

        return 0.0;
    }

}
