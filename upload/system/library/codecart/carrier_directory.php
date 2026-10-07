<?php
/**
 * CodeCart PRO carrier directory adapter.
 * Lightweight address directory support for Nova Poshta, Meest, Delivery and Ukrposhta.
 * No requests are performed while the shipping module is disabled.
 */
class CodeCartCarrierDirectory {
    private $registry;
    private $db;
    private $config;
    private $log;
    private $maxResponseBytes = 16777216;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
    }

    public function requiresLocation(array $carrier) {
        return isset($carrier['provider']) && in_array((string)$carrier['provider'], array('nova_poshta', 'meest', 'delivery', 'ukrposhta'), true);
    }

    public function supportsFullCitySync(array $carrier) {
        return isset($carrier['provider']) && in_array((string)$carrier['provider'], array('nova_poshta', 'delivery'), true);
    }

    public function syncCities(array $carrier) {
        $provider = isset($carrier['provider']) ? (string)$carrier['provider'] : 'manual';
        if (!$this->requiresLocation($carrier)) {
            return array('count' => 0, 'mode' => 'manual');
        }

        if ($provider === 'nova_poshta') {
            return array('count' => $this->syncNovaPoshtaCities($carrier), 'mode' => 'full');
        }
        if ($provider === 'delivery') {
            return array('count' => $this->syncDeliveryCities($carrier), 'mode' => 'full');
        }

        return array('count' => $this->getCachedCityCount($carrier), 'mode' => 'on_demand');
    }

    public function searchCities(array $carrier, $term, $limit = 20) {
        $term = trim((string)$term);
        $limit = max(1, min(50, (int)$limit));
        if (!$this->requiresLocation($carrier) || utf8_strlen($term) < 2) {
            return array();
        }

        $cached = $this->searchCachedCities($carrier, $term, $limit);
        $provider = isset($carrier['provider']) ? (string)$carrier['provider'] : '';

        if (in_array($provider, array('nova_poshta', 'delivery'), true) && count($cached) >= min(8, $limit)) {
            return $cached;
        }

        if (in_array($provider, array('nova_poshta', 'delivery', 'meest', 'ukrposhta'), true)) {
            try {
                if ($provider === 'nova_poshta') {
                    $remote = $this->searchNovaPoshtaCities($carrier, $term, $limit);
                } elseif ($provider === 'delivery') {
                    $remote = $this->searchDeliveryCities($term, $limit);
                } elseif ($provider === 'meest') {
                    $remote = $this->searchMeestCities($carrier, $term, $limit);
                } else {
                    $remote = $this->searchUkrposhtaCities($carrier, $term, $limit);
                }
                if ($remote) {
                    $this->upsertCities($carrier, $remote, false);
                    $cached = $this->searchCachedCities($carrier, $term, $limit);
                }
            } catch (Throwable $e) {
                $this->safeLog('City lookup failed', array('provider' => $provider, 'message' => $e->getMessage()));
                if (!$cached) { throw $e; }
            }
        }

        return $cached;
    }

    public function getBranches(array $carrier, $cityExternalId) {
        $cityExternalId = trim((string)$cityExternalId);
        if (!$this->requiresLocation($carrier) || $cityExternalId === '') {
            return array();
        }

        $provider = isset($carrier['provider']) ? (string)$carrier['provider'] : '';
        $city = $this->getCachedCity($carrier, $cityExternalId);
        if (!$city) {
            throw new Exception('Selected city is not available in the carrier directory.');
        }

        // Branch lists are requested when the buyer picks a city and again when the
        // selection is validated on save/confirm. Cache them briefly so a checkout does
        // not repeat several paged carrier API calls (Kyiv has thousands of NP points).
        $cache = $this->registry->get('cache');
        $cacheKey = 'codecart.carrier_branches.' . $this->carrierId($carrier) . '.' . substr(sha1($provider . '|' . $cityExternalId . '|' . (isset($carrier['api_key']) ? $carrier['api_key'] : '') . '|' . (isset($carrier['api_login']) ? $carrier['api_login'] : '') . '|' . (isset($carrier['api_token']) ? $carrier['api_token'] : '')), 0, 20);
        if ($cache) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached) && $cached) { return $cached; }
        }

        if ($provider === 'nova_poshta') {
            $branches = $this->getNovaPoshtaBranches($carrier, $cityExternalId);
        } elseif ($provider === 'delivery') {
            $branches = $this->getDeliveryBranches($cityExternalId);
        } elseif ($provider === 'meest') {
            $branches = $this->getMeestBranches($carrier, $cityExternalId);
        } elseif ($provider === 'ukrposhta') {
            $branches = $this->getUkrposhtaBranches($carrier, $city);
        } else {
            $branches = array();
        }

        if ($cache && $branches) { $cache->set($cacheKey, $branches); }

        return $branches;
    }

    public function validateSelection(array $carrier, $cityExternalId, $branchExternalId) {
        $cityExternalId = trim((string)$cityExternalId);
        $branchExternalId = trim((string)$branchExternalId);
        $city = $this->getCachedCity($carrier, $cityExternalId);
        if (!$city) {
            throw new Exception('City is not selected or is no longer available.');
        }

        $branches = $this->getBranches($carrier, $cityExternalId);
        foreach ($branches as $branch) {
            if (isset($branch['id']) && hash_equals((string)$branch['id'], $branchExternalId)) {
                return array('city' => $city, 'branch' => $branch);
            }
        }

        throw new Exception('Selected branch is not available for this city.');
    }

    public function getCachedCityCount(array $carrier) {
        if (!$this->tableExists('codecart_carrier_city')) { return 0; }
        $carrierId = $this->carrierId($carrier);
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_carrier_city` WHERE carrier_id='" . $this->db->escape($carrierId) . "'");
        return $query->num_rows ? (int)$query->row['total'] : 0;
    }

    private function syncNovaPoshtaCities(array $carrier) {
        $this->requireSecret($carrier, 'api_key', 'Nova Poshta API key');

        // Nova Poshta's proven import flow returns the complete city directory from
        // Address/getCities without artificial page slicing. This mirrors the stable
        // Nova Poshta PRO adapter and avoids page-dependent partial caches.
        $response = $this->novaRequest($carrier, 'Address', 'getCities', array());
        $rows = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
        $all = array();

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['Ref']) || empty($row['Description'])) { continue; }
            $all[] = array(
                'external_id' => (string)$row['Ref'],
                'name' => (string)$row['Description'],
                'region' => isset($row['AreaDescription']) ? (string)$row['AreaDescription'] : '',
                'district' => isset($row['SettlementTypeDescription']) ? (string)$row['SettlementTypeDescription'] : '',
                'extra' => array('area_ref' => isset($row['Area']) ? (string)$row['Area'] : '')
            );
        }

        if (!$all) { throw new Exception('Nova Poshta returned an empty city directory.'); }
        return $this->replaceCities($carrier, $all);
    }

    private function syncDeliveryCities(array $carrier) {
        $response = $this->deliveryRequest('GetAreasList', array('culture' => 'uk-UA', 'fl_all' => 'true', 'country' => '1'));
        $rows = $this->deliveryRows($response);
        $all = array();

        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $id = $this->arrayPickInsensitive($row, array('id', 'areaId', 'cityId', 'CityId'));
            $name = $this->arrayPickInsensitive($row, array('name', 'areaName', 'cityName', 'Name'));
            if ($id === '' || $name === '') { continue; }
            $all[] = array(
                'external_id' => $id,
                'name' => $name,
                'region' => $this->arrayPickInsensitive($row, array('regionName', 'RegionName', 'region')),
                'district' => $this->arrayPickInsensitive($row, array('districtName', 'DistrictName', 'district')),
                'extra' => array('region_external_id' => $this->arrayPickInsensitive($row, array('RegionId', 'regionId', 'region_id')))
            );
        }

        if (!$all) { throw new Exception('Delivery returned an empty city directory.'); }
        return $this->replaceCities($carrier, $all);
    }


    private function searchNovaPoshtaCities(array $carrier, $term, $limit) {
        $this->requireSecret($carrier, 'api_key', 'Nova Poshta API key');
        $response = $this->novaRequest($carrier, 'Address', 'getCities', array('FindByString' => $term, 'Limit' => max(20, $limit)));
        $rows = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['Ref']) || empty($row['Description'])) { continue; }
            $out[] = array(
                'external_id' => (string)$row['Ref'],
                'name' => (string)$row['Description'],
                'region' => isset($row['AreaDescription']) ? (string)$row['AreaDescription'] : '',
                'district' => isset($row['SettlementTypeDescription']) ? (string)$row['SettlementTypeDescription'] : '',
                'extra' => array('area_ref' => isset($row['Area']) ? (string)$row['Area'] : '')
            );
            if (count($out) >= $limit) { break; }
        }
        return $out;
    }

    private function searchDeliveryCities($term, $limit) {
        $response = $this->deliveryRequest('GetAreasList', array('culture' => 'uk-UA', 'fl_all' => 'true', 'country' => '1', 'cityName' => $term));
        $rows = $this->deliveryRows($response);
        $out = array();

        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $id = $this->arrayPickInsensitive($row, array('id', 'areaId', 'cityId', 'CityId'));
            $name = $this->arrayPickInsensitive($row, array('name', 'areaName', 'cityName', 'Name'));
            if ($id === '' || $name === '') { continue; }
            $out[] = array(
                'external_id' => $id,
                'name' => $name,
                'region' => $this->arrayPickInsensitive($row, array('regionName', 'RegionName', 'region')),
                'district' => $this->arrayPickInsensitive($row, array('districtName', 'DistrictName', 'district')),
                'extra' => array('region_external_id' => $this->arrayPickInsensitive($row, array('RegionId', 'regionId', 'region_id')))
            );
            if (count($out) >= $limit) { break; }
        }
        return $out;
    }
    private function searchMeestCities(array $carrier, $term, $limit) {
        $this->requireSecret($carrier, 'api_login', 'Meest API login');
        $this->requireSecret($carrier, 'api_password', 'Meest API password');
        $safe = str_replace(array("'", '"', '<', '>'), '', $term);
        $xml = $this->meestRequest($carrier, 'City', "DescriptionUA like '" . $safe . "%'", 'DescriptionUA');
        $items = $this->xmlItems($xml);
        $out = array();
        foreach ($items as $row) {
            $id = $this->arrayPick($row, array('uuid', 'UUID'));
            $name = $this->arrayPick($row, array('DescriptionUA', 'descriptionua'));
            if ($id === '' || $name === '') { continue; }
            $out[] = array(
                'external_id' => $id,
                'name' => $name,
                'region' => $this->arrayPick($row, array('RegionDescriptionUA', 'regiondescriptionua')),
                'district' => $this->arrayPick($row, array('DistrictDescriptionUA', 'districtdescriptionua')),
                'extra' => array()
            );
            if (count($out) >= $limit) { break; }
        }
        return $out;
    }

    private function searchUkrposhtaCities(array $carrier, $term, $limit) {
        $this->requireSecret($carrier, 'api_token', 'Ukrposhta bearer');
        $url = 'https://www.ukrposhta.ua/address-classifier-ws/get_city_by_region_id_and_district_id_and_city_ua?city_ua=' . rawurlencode($term);
        $response = $this->httpJson('GET', $url, array('Authorization: Bearer ' . trim((string)$carrier['api_token']), 'Accept: application/json'), null);
        $rows = $this->ukrposhtaEntries($response);
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['CITY_ID']) || empty($row['CITY_UA'])) { continue; }
            $out[] = array(
                'external_id' => (string)$row['CITY_ID'],
                'name' => (string)$row['CITY_UA'],
                'region' => isset($row['REGION_UA']) ? (string)$row['REGION_UA'] : '',
                'district' => isset($row['DISTRICT_UA']) ? (string)$row['DISTRICT_UA'] : '',
                'extra' => array(
                    'region_id' => isset($row['REGION_ID']) ? (string)$row['REGION_ID'] : '',
                    'district_id' => isset($row['DISTRICT_ID']) ? (string)$row['DISTRICT_ID'] : '',
                    'katottg' => isset($row['CITY_KATOTTG']) ? (string)$row['CITY_KATOTTG'] : '',
                    'koatuu' => isset($row['CITY_KOATUU']) ? (string)$row['CITY_KOATUU'] : ''
                )
            );
            if (count($out) >= $limit) { break; }
        }
        return $out;
    }

    private function getNovaPoshtaBranches(array $carrier, $cityRef) {
        $this->requireSecret($carrier, 'api_key', 'Nova Poshta API key');
        $out = array();
        $page = 1;
        $limit = 500;

        while ($page <= 20) {
            $response = $this->novaRequest($carrier, 'AddressGeneral', 'getWarehouses', array('CityRef' => $cityRef, 'Page' => $page, 'Limit' => $limit));
            $rows = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
            foreach ($rows as $row) {
                if (!is_array($row) || empty($row['Ref']) || empty($row['Description'])) { continue; }
                $out[] = array(
                    'id' => (string)$row['Ref'],
                    'name' => (string)$row['Description'],
                    'address' => isset($row['ShortAddress']) ? (string)$row['ShortAddress'] : (isset($row['Description']) ? (string)$row['Description'] : ''),
                    'postcode' => isset($row['PostalCodeUA']) ? (string)$row['PostalCodeUA'] : '',
                    'number' => isset($row['Number']) ? (string)$row['Number'] : ''
                );
            }
            if (count($rows) < $limit) { break; }
            $page++;
        }

        return $out;
    }

    private function getDeliveryBranches($cityId) {
        $response = $this->deliveryRequest('GetWarehousesList', array('CityId' => $cityId, 'includeRegionalCenters' => 'true', 'culture' => 'uk-UA', 'country' => '1'));
        $rows = $this->deliveryRows($response);
        $out = array();

        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $id = $this->arrayPickInsensitive($row, array('id', 'warehouseId', 'WarehouseId', 'Id'));
            if ($id === '') { continue; }
            $name = $this->arrayPickInsensitive($row, array('name', 'warehouseName', 'WarehouseName', 'Name'));
            $address = $this->arrayPickInsensitive($row, array('address', 'Address', 'warehouseAddress'));
            $number = $this->arrayPickInsensitive($row, array('Number', 'number', 'warehouseNumber'));
            $label = trim($name . ($address !== '' ? ' — ' . $address : ''));
            if ($label === '') { $label = $address !== '' ? $address : $id; }
            $out[] = array('id' => $id, 'name' => $label, 'address' => $address, 'postcode' => '', 'number' => $number);
        }
        return $out;
    }

    private function getMeestBranches(array $carrier, $cityUuid) {
        $this->requireSecret($carrier, 'api_login', 'Meest API login');
        $this->requireSecret($carrier, 'api_password', 'Meest API password');
        $safe = preg_replace('/[^A-Za-z0-9\-]/', '', $cityUuid);
        $xml = $this->meestRequest($carrier, 'Branch', "CityUUID='" . $safe . "'", 'DescriptionUA');
        $items = $this->xmlItems($xml);
        $out = array();
        foreach ($items as $row) {
            $id = $this->arrayPick($row, array('UUID', 'uuid'));
            $name = $this->arrayPick($row, array('DescriptionUA', 'descriptionua'));
            if ($id === '' || $name === '') { continue; }
            $out[] = array('id' => $id, 'name' => $name, 'address' => $name, 'postcode' => '', 'number' => $this->arrayPick($row, array('BranchCode', 'branchcode')));
        }
        return $out;
    }

    private function getUkrposhtaBranches(array $carrier, array $city) {
        $this->requireSecret($carrier, 'api_token', 'Ukrposhta bearer');
        $extra = !empty($city['extra_json']) ? json_decode($city['extra_json'], true) : array();
        if (!is_array($extra)) { $extra = array(); }
        $params = array();
        if (!empty($extra['katottg'])) { $params['city_katottg'] = $extra['katottg']; }
        elseif (!empty($extra['koatuu'])) { $params['city_koatuu'] = $extra['koatuu']; }
        else {
            $params['city_id'] = $city['external_id'];
            if (!empty($extra['district_id'])) { $params['district_id'] = $extra['district_id']; }
            if (!empty($extra['region_id'])) { $params['region_id'] = $extra['region_id']; }
        }

        if (isset($params['city_id'])) {
            $endpoint = 'get_postoffices_by_city_id';
        } else {
            $endpoint = 'get_postoffices_by_postcode_cityid_cityvpzid';
        }
        $url = 'https://www.ukrposhta.ua/address-classifier-ws/' . $endpoint . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $response = $this->httpJson('GET', $url, array('Authorization: Bearer ' . trim((string)$carrier['api_token']), 'Accept: application/json'), null);
        $rows = $this->ukrposhtaEntries($response);
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            if (isset($row['LOCK_CODE']) && (string)$row['LOCK_CODE'] !== '0') { continue; }
            if (isset($row['IS_SECURITY']) && (string)$row['IS_SECURITY'] === '1') { continue; }
            $id = isset($row['POSTOFFICE_ID']) ? (string)$row['POSTOFFICE_ID'] : (isset($row['ID']) ? (string)$row['ID'] : (isset($row['POSTINDEX']) ? (string)$row['POSTINDEX'] : ''));
            if ($id === '') { continue; }
            $branchFallback = 'Branch';
            if ($this->registry->has('language')) {
                $localizedBranch = (string)$this->registry->get('language')->get('text_branch');
                if ($localizedBranch !== '' && $localizedBranch !== 'text_branch') { $branchFallback = $localizedBranch; }
            }
            $short = isset($row['PO_SHORT']) ? (string)$row['PO_SHORT'] : (isset($row['POSTOFFICE_UA']) ? (string)$row['POSTOFFICE_UA'] : $branchFallback);
            $address = isset($row['ADDRESS']) ? (string)$row['ADDRESS'] : '';
            $postcode = isset($row['POSTINDEX']) ? (string)$row['POSTINDEX'] : (isset($row['POSTCODE']) ? (string)$row['POSTCODE'] : '');
            $out[] = array('id' => $id, 'name' => trim(($postcode !== '' ? $postcode . ' ' : '') . $short . ($address !== '' ? ' — ' . $address : '')), 'address' => $address, 'postcode' => $postcode, 'number' => $postcode);
        }
        return $out;
    }

    private function novaRequest(array $carrier, $model, $method, array $properties) {
        $key = trim(isset($carrier['api_key']) ? (string)$carrier['api_key'] : '');
        if ($key === '') { throw new Exception('Nova Poshta API key is empty.'); }
        if (!function_exists('curl_init')) { throw new Exception('PHP cURL extension is required.'); }

        $payload = array(
            'apiKey' => $key,
            'modelName' => $model,
            'calledMethod' => $method,
            'methodProperties' => $properties ? $properties : new stdClass()
        );
        $lastError = 'Nova Poshta API request failed.';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $ch = curl_init('https://api.novaposhta.ua/v2.0/json/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_TIMEOUT, 45);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0')));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $raw = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                $lastError = 'Nova Poshta network error #' . $errno . ': ' . $error;
                $this->safeLog('Nova Poshta network error', array('attempt' => $attempt, 'http' => $status, 'errno' => $errno));
                if ($attempt < 3) { usleep(700000); continue; }
                break;
            }

            if ($raw === false || $raw === '') {
                $lastError = 'Nova Poshta returned an empty response. HTTP ' . $status . '.';
                if ($attempt < 3 && in_array($status, array(0, 408, 429, 500, 502, 503, 504), true)) { usleep(700000); continue; }
                break;
            }
            if (strlen($raw) > $this->maxResponseBytes) { throw new Exception('Nova Poshta API response is too large.'); }

            $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)$raw);
            $response = json_decode($raw, true);
            if (!is_array($response) && function_exists('iconv')) {
                $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $raw);
                if ($converted !== false) { $response = json_decode($converted, true); }
            }
            if (!is_array($response)) {
                $lastError = 'Nova Poshta returned invalid JSON. HTTP ' . $status . '.';
                if ($attempt < 3 && in_array($status, array(0, 408, 429, 500, 502, 503, 504), true)) { usleep(700000); continue; }
                break;
            }

            $messages = $this->joinApiMessages($response);
            if ($status >= 400) {
                $lastError = 'Nova Poshta HTTP ' . $status . ($messages !== '' ? ': ' . $messages : '.');
                if ($attempt < 3 && in_array($status, array(408, 429, 500, 502, 503, 504), true)) { usleep(700000); continue; }
                break;
            }
            if (isset($response['success']) && !$response['success']) {
                $lastError = $messages !== '' ? $messages : 'Nova Poshta API returned success=false.';
                $lower = function_exists('mb_strtolower') ? mb_strtolower($lastError, 'UTF-8') : strtolower($lastError);
                $rate = strpos($lower, 'too many requests') !== false || strpos($lower, 'to many requests') !== false;
                if ($rate && $attempt < 3) { usleep(900000); continue; }
                break;
            }

            return $response;
        }

        throw new Exception($lastError);
    }

    private function deliveryRequest($method, array $params) {
        $allowed = array('GetAreasList', 'GetWarehousesList');
        if (!in_array($method, $allowed, true)) { throw new Exception('Unsupported Delivery API method.'); }
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $bases = array(
            'https://www.delivery-auto.com/api/v4/Public/',
            'https://delivery-auto.com/api/v4/Public/'
        );
        $lastError = null;

        foreach ($bases as $base) {
            try {
                $response = $this->httpJson('GET', $base . $method . '?' . $query, array(
                    'Accept: application/json, text/json',
                    'Accept-Language: uk-UA,uk;q=0.9,en;q=0.5'
                ), null);

                if (!$this->deliveryStatusOk($response)) {
                    $message = $this->arrayPickInsensitive($response, array('message', 'Message', 'error', 'Error'));
                    throw new Exception($message !== '' ? $message : 'Delivery API returned an unsuccessful response.');
                }

                return $response;
            } catch (Throwable $e) {
                $lastError = $e;
                $this->safeLog('Delivery API endpoint failed', array('method' => $method, 'host' => parse_url($base, PHP_URL_HOST), 'message' => $e->getMessage()));
            }
        }

        throw $lastError ?: new Exception('Delivery API request failed.');
    }

    private function deliveryStatusOk(array $response) {
        // Delivery's public reference methods are documented to return a JSON
        // collection directly, not necessarily a {status,data} envelope.
        if ($this->isListArray($response)) { return true; }

        foreach (array('status', 'Status', 'success', 'Success') as $key) {
            if (!array_key_exists($key, $response)) { continue; }
            $value = $response[$key];
            if (is_bool($value)) { return $value; }
            if (is_numeric($value)) { return (int)$value !== 0; }
            $value = strtolower(trim((string)$value));
            return in_array($value, array('true', 'ok', 'success', '1'), true);
        }

        // Keep compatibility with older/newer wrappers used by the Delivery API.
        return isset($response['data']) || isset($response['Data']) || isset($response['result']) || isset($response['Result']) || isset($response['items']) || isset($response['Items']);
    }

    private function deliveryRows(array $response) {
        if ($this->isListArray($response)) { return $response; }

        $data = null;
        foreach (array('data', 'Data', 'result', 'Result', 'items', 'Items') as $key) {
            if (isset($response[$key]) && is_array($response[$key])) { $data = $response[$key]; break; }
        }
        if (!is_array($data)) { return array(); }

        if ($this->isListArray($data)) { return $data; }

        foreach (array('areas', 'Areas', 'warehouses', 'Warehouses', 'items', 'Items', 'rows', 'Rows') as $key) {
            if (isset($data[$key]) && is_array($data[$key])) { $data = $data[$key]; break; }
        }

        if (!$data) { return array(); }
        if ($this->isListArray($data)) { return $data; }
        return array($data);
    }

    private function isListArray(array $value) {
        if (!$value) { return true; }
        return array_keys($value) === range(0, count($value) - 1);
    }

    private function arrayPickInsensitive(array $row, array $keys) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && !is_array($row[$key]) && !is_object($row[$key])) { return trim((string)$row[$key]); }
        }
        $lower = array();
        foreach ($row as $key => $value) { $lower[strtolower((string)$key)] = $value; }
        foreach ($keys as $key) {
            $lk = strtolower((string)$key);
            if (array_key_exists($lk, $lower) && !is_array($lower[$lk]) && !is_object($lower[$lk])) { return trim((string)$lower[$lk]); }
        }
        return '';
    }

    private function joinApiMessages(array $response) {
        $messages = array();
        foreach (array('errors', 'warnings', 'info', 'messageCodes') as $keyName) {
            if (empty($response[$keyName]) || !is_array($response[$keyName])) { continue; }
            foreach ($response[$keyName] as $message) {
                if (is_array($message)) { $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
                $message = trim((string)$message);
                if ($message !== '') { $messages[] = $message; }
            }
        }
        return implode('; ', array_unique($messages));
    }

    private function meestRequest(array $carrier, $function, $where, $order) {
        $login = trim((string)$carrier['api_login']);
        $password = trim((string)$carrier['api_password']);
        $sign = md5($login . $password . $function . $where . $order);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><param><login>' . $this->xmlEscape($login) . '</login><function>' . $this->xmlEscape($function) . '</function><where>' . $this->xmlEscape($where) . '</where><order>' . $this->xmlEscape($order) . '</order><sign>' . $sign . '</sign></param>';
        return $this->httpText('POST', 'https://api1c.meest-group.com/services/1C_Query.php', array('Content-Type: text/xml; charset=UTF-8'), $xml);
    }

    private function httpJson($method, $url, array $headers, $body) {
        $raw = $this->httpText($method, $url, $headers, $body);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)$raw);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) && function_exists('iconv')) {
            $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $raw);
            if ($converted !== false) { $decoded = json_decode($converted, true); }
        }
        if (!is_array($decoded)) {
            $excerpt = trim(preg_replace('/\s+/', ' ', substr((string)$raw, 0, 400)));
            throw new Exception('Carrier API returned invalid JSON.' . ($excerpt !== '' ? ' Response: ' . $excerpt : ''));
        }
        return $decoded;
    }

    private function httpText($method, $url, array $headers, $body) {
        if (!preg_match('#^https://#i', $url)) { throw new Exception('Only HTTPS carrier API endpoints are allowed.'); }
        if (!function_exists('curl_init')) { throw new Exception('PHP cURL extension is required.'); }

        $hasAccept = false;
        $hasUserAgent = false;
        foreach ($headers as $header) {
            if (stripos((string)$header, 'Accept:') === 0) { $hasAccept = true; }
            if (stripos((string)$header, 'User-Agent:') === 0) { $hasUserAgent = true; }
        }
        if (!$hasAccept) { $headers[] = 'Accept: application/json, text/plain, */*'; }
        if (!$hasUserAgent) { $headers[] = 'User-Agent: CodeCart/' . (defined('CODECART_BUILD') ? CODECART_BUILD : '3.0.6.0') . ' (+https://codecartpro.com)'; }

        $lastError = 'Carrier API request failed.';
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$body);
            }

            $raw = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                $lastError = 'Carrier API network error #' . $errno . ': ' . $error;
                if ($attempt < 2) { usleep(350000); continue; }
                throw new Exception($lastError);
            }
            if ($raw === false || $raw === '') {
                $lastError = 'Carrier API returned an empty response. HTTP ' . $status . '.';
                if ($attempt < 2 && in_array($status, array(0, 408, 429, 500, 502, 503, 504), true)) { usleep(350000); continue; }
                throw new Exception($lastError);
            }
            if (strlen($raw) > $this->maxResponseBytes) { throw new Exception('Carrier API response is too large.'); }
            if ($status < 200 || $status >= 300) {
                $lastError = 'Carrier API returned HTTP ' . $status . '.';
                if ($attempt < 2 && in_array($status, array(408, 429, 500, 502, 503, 504), true)) { usleep(350000); continue; }
                throw new Exception($lastError);
            }
            return $raw;
        }

        throw new Exception($lastError);
    }

    private function replaceCities(array $carrier, array $rows) {
        $carrierId = $this->carrierId($carrier);
        $this->db->query("START TRANSACTION");
        try {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_carrier_city` WHERE carrier_id='" . $this->db->escape($carrierId) . "'");
            $count = $this->upsertCities($carrier, $rows, true);
            $this->db->query("COMMIT");
            return $count;
        } catch (Throwable $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    private function upsertCities(array $carrier, array $rows, $replace) {
        if (!$this->tableExists('codecart_carrier_city')) { throw new Exception('Carrier city cache table is missing. Run the CodeCart PRO updater.'); }
        $carrierId = $this->carrierId($carrier);
        $provider = isset($carrier['provider']) ? (string)$carrier['provider'] : 'manual';
        $count = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $externalId = substr(trim((string)(isset($row['external_id']) ? $row['external_id'] : '')), 0, 128);
            $name = trim(strip_tags((string)(isset($row['name']) ? $row['name'] : '')));
            if ($externalId === '' || $name === '') { continue; }
            $region = trim(strip_tags((string)(isset($row['region']) ? $row['region'] : '')));
            $district = trim(strip_tags((string)(isset($row['district']) ? $row['district'] : '')));
            $extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
            $search = trim($name . ' ' . $region . ' ' . $district);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_carrier_city` SET carrier_id='" . $this->db->escape($carrierId) . "', provider='" . $this->db->escape($provider) . "', external_id='" . $this->db->escape($externalId) . "', name='" . $this->db->escape(utf8_substr($name, 0, 191)) . "', region='" . $this->db->escape(utf8_substr($region, 0, 191)) . "', district='" . $this->db->escape(utf8_substr($district, 0, 191)) . "', search_name='" . $this->db->escape(utf8_substr($search, 0, 255)) . "', extra_json='" . $this->db->escape(json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "', date_modified=NOW() ON DUPLICATE KEY UPDATE provider=VALUES(provider), name=VALUES(name), region=VALUES(region), district=VALUES(district), search_name=VALUES(search_name), extra_json=VALUES(extra_json), date_modified=NOW()");
            $count++;
        }
        return $count;
    }

    private function searchCachedCities(array $carrier, $term, $limit) {
        if (!$this->tableExists('codecart_carrier_city')) { return array(); }
        $carrierId = $this->carrierId($carrier);
        $escaped = $this->db->escape(addcslashes((string)$term, '\\%_'));
        $query = $this->db->query("SELECT external_id,name,region,district,extra_json FROM `" . DB_PREFIX . "codecart_carrier_city` WHERE carrier_id='" . $this->db->escape($carrierId) . "' AND search_name LIKE '%" . $escaped . "%' ORDER BY CASE WHEN name LIKE '" . $escaped . "%' THEN 0 ELSE 1 END, name ASC LIMIT " . (int)$limit);
        $out = array();
        foreach ($query->rows as $row) {
            $label = $row['name'];
            if ($row['region'] !== '') { $label .= ' — ' . $row['region']; }
            if ($row['district'] !== '' && $row['district'] !== $row['region']) { $label .= ', ' . $row['district']; }
            $out[] = array('id' => $row['external_id'], 'name' => $row['name'], 'label' => $label, 'region' => $row['region'], 'district' => $row['district']);
        }
        return $out;
    }

    private function getCachedCity(array $carrier, $externalId) {
        if (!$this->tableExists('codecart_carrier_city')) { return array(); }
        $query = $this->db->query("SELECT external_id,name,region,district,extra_json FROM `" . DB_PREFIX . "codecart_carrier_city` WHERE carrier_id='" . $this->db->escape($this->carrierId($carrier)) . "' AND external_id='" . $this->db->escape($externalId) . "' LIMIT 1");
        return $query->num_rows ? $query->row : array();
    }

    private function carrierId(array $carrier) {
        $id = isset($carrier['id']) ? preg_replace('/[^a-z0-9]/', '', strtolower((string)$carrier['id'])) : '';
        if ($id === '') { throw new Exception('Carrier ID is missing.'); }
        return substr($id, 0, 16);
    }

    private function tableExists($table) {
        $query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
        return (bool)$query->num_rows;
    }

    private function requireSecret(array $carrier, $key, $label) {
        if (!isset($carrier[$key]) || trim((string)$carrier[$key]) === '') { throw new Exception($label . ' is not configured.'); }
    }

    private function xmlItems($raw) {
        if (!function_exists('simplexml_load_string')) { throw new Exception('PHP SimpleXML extension is required for Meest integration.'); }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($xml === false) { libxml_clear_errors(); throw new Exception('Meest API returned invalid XML.'); }
        $errors = isset($xml->errors->code) ? trim((string)$xml->errors->code) : '';
        if ($errors !== '' && $errors !== '000') { throw new Exception('Meest API error ' . $errors . '.'); }
        $out = array();
        if (isset($xml->result_table->items)) {
            foreach ($xml->result_table->items as $item) {
                $row = array();
                foreach ($item->children() as $key => $value) { $row[(string)$key] = trim((string)$value); }
                $out[] = $row;
            }
        }
        return $out;
    }

    private function ukrposhtaEntries(array $response) {
        if (!isset($response['Entries']) || !is_array($response['Entries']) || !isset($response['Entries']['Entry'])) { return array(); }
        $rows = $response['Entries']['Entry'];
        if (!is_array($rows)) { return array(); }
        if (!$rows) { return array(); }
        $keys = array_keys($rows);
        if ($keys === range(0, count($rows) - 1)) { return $rows; }
        return array($rows);
    }

    private function arrayPick(array $row, array $keys) {
        foreach ($keys as $key) { if (isset($row[$key])) { return trim((string)$row[$key]); } }
        return '';
    }

    private function xmlEscape($value) {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function safeLog($message, array $context) {
        foreach ($context as $key => $value) {
            if (preg_match('/key|token|password|secret|login/i', (string)$key)) { $context[$key] = '[masked]'; }
        }
        if ($this->log) { $this->log->write('[CARRIER_CHOICE] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); }
    }
}
