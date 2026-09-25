<?php
class ModelExtensionCurrencyNbu extends Model {
    public function refresh() {
        $result = $this->refreshResult();
        return !empty($result['success']);
    }

    public function refreshResult() {
        if (!function_exists('curl_init')) {
            return $this->failure('NBU currency refresh failed: PHP cURL extension is unavailable.');
        }

        $source = strtolower(trim((string)$this->config->get('currency_nbu_source')));
        if (!in_array($source, array('auto', 'statdirectory', 'exchange'), true)) {
            $source = 'auto';
        }

        $timeout = (int)$this->config->get('currency_nbu_timeout');
        if ($timeout < 5 || $timeout > 60) {
            $timeout = 15;
        }

        $sources = array(
            'statdirectory' => 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchangenew?json',
            'exchange' => 'https://bank.gov.ua/NBU_Exchange/exchange?json'
        );
        $order = $source === 'auto' ? array('statdirectory', 'exchange') : array($source);
        $rates = array();
        $usedSource = '';
        $errors = array();

        foreach ($order as $candidate) {
            $fetch = $this->fetchJson($sources[$candidate], $timeout);
            if (!$fetch['success']) {
                $errors[] = $candidate . ': ' . $fetch['message'];
                continue;
            }

            $parsed = $candidate === 'exchange'
                ? $this->parseExchange($fetch['data'])
                : $this->parseStatDirectory($fetch['data']);

            if (!$parsed['success']) {
                $errors[] = $candidate . ': ' . $parsed['message'];
                continue;
            }

            $rates = $parsed['rates'];
            $usedSource = $candidate;
            break;
        }

        if (!$rates) {
            $detail = $errors ? implode(' | ', $errors) : 'no usable response';
            return $this->failure('NBU currency refresh failed: ' . $detail . '.');
        }

        $default = strtoupper(trim((string)$this->config->get('config_currency')));
        if ($default === '') {
            $default = 'UAH';
        }

        if (!isset($rates[$default]) || !is_finite((float)$rates[$default]) || (float)$rates[$default] <= 0) {
            return $this->failure('NBU currency refresh failed: base currency ' . $default . ' is not available from source ' . $usedSource . '.');
        }

        $includeDisabled = (bool)$this->config->get('currency_nbu_include_disabled');
        $where = $includeDisabled ? '' : " WHERE status = '1'";
        $currencies = $this->db->query("SELECT code FROM `" . DB_PREFIX . "currency`" . $where . " ORDER BY code ASC");
        $missing = array();
        $updates = array();

        foreach ($currencies->rows as $currency) {
            $code = strtoupper(trim((string)$currency['code']));
            if ($code === '' || !preg_match('/^[A-Z]{3}$/', $code)) {
                continue;
            }
            if (!isset($rates[$code]) || !is_finite((float)$rates[$code]) || (float)$rates[$code] <= 0) {
                $missing[] = $code;
                continue;
            }
            $updates[$code] = (float)$rates[$default] / (float)$rates[$code];
        }

        $missingPolicy = strtolower(trim((string)$this->config->get('currency_nbu_missing_policy')));
        if (!in_array($missingPolicy, array('skip', 'error'), true)) {
            $missingPolicy = 'skip';
        }
        if ($missing && $missingPolicy === 'error') {
            return $this->failure('NBU currency refresh stopped: no rate for ' . implode(', ', $missing) . '. Change missing-rate policy to Skip if these currencies are intentionally unsupported.');
        }

        if (!isset($updates[$default])) {
            $updates[$default] = 1.0;
        }

        try {
            $this->db->beginTransaction();
            foreach ($updates as $code => $value) {
                $this->db->query("UPDATE `" . DB_PREFIX . "currency` SET value = '" . (float)$value . "', date_modified = NOW() WHERE code = '" . $this->db->escape($code) . "'");
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "currency` SET value = '1.00000000', date_modified = NOW() WHERE code = '" . $this->db->escape($default) . "'");
            $this->db->commit();
        } catch (\Throwable $e) {
            try { $this->db->rollback(); } catch (\Throwable $ignored) {}
            return $this->failure('NBU currency refresh failed while saving rates: ' . $e->getMessage());
        }

        $this->cache->delete('currency');

        $message = 'NBU currency refresh completed via ' . $usedSource . ': updated ' . count($updates) . ' currency rate(s)';
        if ($missing) {
            $message .= ', skipped ' . count($missing) . ' unsupported code(s): ' . implode(', ', $missing);
        }
        $message .= '.';

        return array(
            'success' => true,
            'message' => $message,
            'source' => $usedSource,
            'updated' => count($updates),
            'missing' => $missing
        );
    }

    private function fetchJson($url, $timeout) {
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => min(8, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : VERSION)
            )
        ));
        \CodeCart\Core\TlsPolicy::applyCurl($curl);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = trim((string)curl_error($curl));

        if ($response === false) {
            return array('success' => false, 'message' => $error !== '' ? $error : 'cURL request failed');
        }
        if ($status !== 200) {
            return array('success' => false, 'message' => 'HTTP ' . $status . ($error !== '' ? ' - ' . $error : ''));
        }

        $rows = json_decode((string)$response, true);
        if (!is_array($rows)) {
            return array('success' => false, 'message' => 'invalid JSON response');
        }

        return array('success' => true, 'data' => $rows, 'message' => 'OK');
    }

    private function parseStatDirectory(array $rows) {
        $rates = array('UAH' => 1.0);
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $code = strtoupper(trim((string)($row['cc'] ?? '')));
            $rate = isset($row['rate']) ? (float)$row['rate'] : 0.0;
            if (preg_match('/^[A-Z]{3}$/', $code) && is_finite($rate) && $rate > 0) {
                $rates[$code] = $rate;
            }
        }
        if (count($rates) < 2) {
            return array('success' => false, 'message' => 'response contains no currency rates', 'rates' => array());
        }
        return array('success' => true, 'message' => 'OK', 'rates' => $rates);
    }

    private function parseExchange(array $rows) {
        $rates = array('UAH' => 1.0);
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $code = strtoupper(trim((string)($row['CurrencyCodeL'] ?? '')));
            $amount = isset($row['Amount']) ? (float)$row['Amount'] : 0.0;
            $units = isset($row['Units']) ? (float)$row['Units'] : 1.0;
            if ($units <= 0) { $units = 1.0; }
            $rate = $amount / $units;
            if (preg_match('/^[A-Z]{3}$/', $code) && is_finite($rate) && $rate > 0) {
                $rates[$code] = $rate;
            }
        }
        if (count($rates) < 2) {
            return array('success' => false, 'message' => 'response contains no currency rates', 'rates' => array());
        }
        return array('success' => true, 'message' => 'OK', 'rates' => $rates);
    }

    private function failure($message) {
        $message = trim((string)$message);
        if ($message !== '') {
            $this->log->write($message);
        }
        return array('success' => false, 'message' => $message !== '' ? $message : 'NBU currency refresh failed.');
    }
}
