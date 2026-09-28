<?php
class ControllerExtensionShippingCarrierChoice extends Controller {
    public function cities() {
        $this->load->language('extension/shipping/carrier_choice');
        $json = array('cities' => array());
        if (!$this->config->get('shipping_carrier_choice_status')) {
            $this->response->setStatusCode(404);
            return $this->json($json);
        }
        if (!$this->allowDirectoryRequest('cities', 120, 300)) {
            $this->response->setStatusCode(429);
            $json['error'] = $this->language->get('error_rate_limit');
            return $this->json($json);
        }
        $carrierId = isset($this->request->get['carrier_id']) ? preg_replace('/[^a-z0-9]/', '', strtolower((string)$this->request->get['carrier_id'])) : '';
        $term = isset($this->request->get['q']) ? trim(strip_tags((string)$this->request->get['q'])) : '';
        if ($carrierId === '' || utf8_strlen($term) < 2 || utf8_strlen($term) > 80) { return $this->json($json); }
        $this->load->model('extension/shipping/carrier_choice');
        $carrier = $this->model_extension_shipping_carrier_choice->getCarrierByQuoteKey($carrierId);
        if (!$carrier) { $json['error'] = $this->language->get('error_carrier'); return $this->json($json); }
        try { $json['cities'] = $this->model_extension_shipping_carrier_choice->searchCities($carrier, $term, 20); }
        catch (Throwable $e) { $this->logFailure('city search', $carrier, $e); $json['error'] = $this->language->get('error_directory_unavailable'); }
        return $this->json($json);
    }

    public function branches() {
        $this->load->language('extension/shipping/carrier_choice');
        $json = array('branches' => array());
        if (!$this->config->get('shipping_carrier_choice_status')) {
            $this->response->setStatusCode(404);
            return $this->json($json);
        }
        if (!$this->allowDirectoryRequest('branches', 60, 300)) {
            $this->response->setStatusCode(429);
            $json['error'] = $this->language->get('error_rate_limit');
            return $this->json($json);
        }
        $carrierId = isset($this->request->get['carrier_id']) ? preg_replace('/[^a-z0-9]/', '', strtolower((string)$this->request->get['carrier_id'])) : '';
        $cityId = isset($this->request->get['city_id']) ? trim((string)$this->request->get['city_id']) : '';
        if ($carrierId === '' || $cityId === '' || strlen($cityId) > 128) { return $this->json($json); }
        $this->load->model('extension/shipping/carrier_choice');
        $carrier = $this->model_extension_shipping_carrier_choice->getCarrierByQuoteKey($carrierId);
        if (!$carrier) { $json['error'] = $this->language->get('error_carrier'); return $this->json($json); }
        try { $json['branches'] = $this->model_extension_shipping_carrier_choice->getBranches($carrier, $cityId); }
        catch (Throwable $e) { $this->logFailure('branch lookup', $carrier, $e); $json['error'] = $this->language->get('error_directory_unavailable'); }
        return $this->json($json);
    }

    private function allowDirectoryRequest($scope, $limit, $window) {
        $scope = preg_replace('/[^a-z0-9_.-]/i', '', (string)$scope);
        $limit = max(1, (int)$limit);
        $window = max(60, (int)$window);
        $now = time();

        // Always keep a small per-session ceiling so carrier API credentials cannot be
        // exhausted by an accidental frontend loop even when the optional SpamService is OFF.
        if ($this->session) {
            $key = 'codecart_carrier_rate_' . $scope;
            $hits = isset($this->session->data[$key]) && is_array($this->session->data[$key]) ? $this->session->data[$key] : array();
            $cutoff = $now - $window;
            $hits = array_values(array_filter($hits, function($timestamp) use ($cutoff) { return (int)$timestamp > $cutoff; }));
            if (count($hits) >= $limit) {
                $this->session->data[$key] = $hits;
                return false;
            }
            $hits[] = $now;
            $this->session->data[$key] = $hits;
        }

        if (!class_exists('\CodeCart\Core\SpamService')) { return true; }
        try {
            $spam = new \CodeCart\Core\SpamService($this->registry);
            $result = $spam->consume('carrier.directory.' . $scope, $limit, $window, 0);
            return !empty($result['allowed']);
        } catch (\Throwable $e) {
            // The mandatory per-session ceiling above remains active if the central limiter fails.
            return true;
        }
    }

    private function logFailure($action, array $carrier, \Throwable $e) {
        // The buyer sees a generic message; the merchant needs the carrier's reason
        // (invalid key, quota, network) in the error log. Secrets are masked.
        $message = trim(preg_replace('/\s+/', ' ', strip_tags((string)$e->getMessage())));
        foreach (array('api_key', 'api_login', 'api_password', 'api_token') as $key) {
            if (!empty($carrier[$key])) { $message = str_replace((string)$carrier[$key], '[masked]', $message); }
        }
        $this->log->write('Carrier ' . $action . ' failed [' . (isset($carrier['provider']) ? $carrier['provider'] : 'unknown') . ']: ' . substr($message, 0, 500));
    }

    private function json(array $json) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
