<?php
class ControllerExtensionShippingCarrierChoice extends Controller {
    private $error = array();
    private $providers = array('pickup', 'nova_poshta', 'meest', 'delivery', 'ukrposhta', 'manual');
    private $jsonBufferLevel = null;

    public function index() {
        $this->load->language('extension/shipping/carrier_choice');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('localisation/language');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->request->post['shipping_carrier_choice_carriers'] = $this->normalizeCarriers(isset($this->request->post['shipping_carrier_choice_carriers']) ? $this->request->post['shipping_carrier_choice_carriers'] : array());
            $this->request->post['shipping_carrier_choice_compact_checkout'] = !empty($this->request->post['shipping_carrier_choice_compact_checkout']) ? 1 : 0;
            $this->model_setting_setting->editSetting('shipping_carrier_choice', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/shipping/carrier_choice', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        $data['error_carriers'] = isset($this->error['carriers']) ? $this->error['carriers'] : '';
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        $data['breadcrumbs'][] = array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        $data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/shipping/carrier_choice', 'user_token=' . $this->session->data['user_token'], true));
        $data['action'] = $this->url->link('extension/shipping/carrier_choice', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
        $data['sync_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/carrier_choice/syncCities', 'user_token=' . $this->session->data['user_token'], true));

        $languages = $this->model_localisation_language->getLanguages();
        $data['languages'] = array();
        foreach ($languages as $language) {
            if (!isset($language['status']) || $language['status']) { $data['languages'][] = $language; }
        }

        if (isset($this->request->post['shipping_carrier_choice_carriers'])) {
            $carriers = $this->normalizeCarriers($this->request->post['shipping_carrier_choice_carriers']);
        } else {
            $stored = $this->config->get('shipping_carrier_choice_carriers');
            $carriers = $this->normalizeCarriers(is_array($stored) ? $stored : array());
        }

        $directory = $this->directory();
        foreach ($carriers as &$carrier) {
            $carrier['city_count'] = 0;
            $carrier['full_sync'] = $directory->supportsFullCitySync($carrier);
            try { $carrier['city_count'] = $directory->getCachedCityCount($carrier); } catch (Throwable $e) { $carrier['city_count'] = 0; }
        }
        unset($carrier);
        $data['shipping_carrier_choice_carriers'] = $carriers;

        foreach (array('tax_class_id', 'geo_zone_id', 'status', 'sort_order', 'compact_checkout') as $field) {
            $key = 'shipping_carrier_choice_' . $field;
            if (isset($this->request->post[$key])) { $data[$key] = $this->request->post[$key]; }
            elseif ($this->config->has($key)) { $data[$key] = $this->config->get($key); }
            else { $data[$key] = $field === 'compact_checkout' ? 1 : 0; }
        }

        $data['provider_options'] = array(
            'pickup' => $this->language->get('text_provider_pickup'),
            'nova_poshta' => $this->language->get('text_provider_nova_poshta'),
            'meest' => $this->language->get('text_provider_meest'),
            'delivery' => $this->language->get('text_provider_delivery'),
            'ukrposhta' => $this->language->get('text_provider_ukrposhta'),
            'manual' => $this->language->get('text_provider_manual')
        );


        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/shipping/carrier_choice', $data));
    }

    public function syncCities() {
        $this->jsonBufferLevel = ob_get_level();
        ob_start();
        $this->load->language('extension/shipping/carrier_choice');
        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/shipping/carrier_choice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->jsonResponse($json);
        }

        // Explicit administrator synchronization is allowed even when the storefront
        // shipping method is disabled. Automatic storefront/API calls remain disabled.

        $carrierId = isset($this->request->post['carrier_id']) ? preg_replace('/[^a-z0-9]/', '', strtolower((string)$this->request->post['carrier_id'])) : '';
        $postedCarrier = isset($this->request->post['carrier']) && is_array($this->request->post['carrier']) ? $this->request->post['carrier'] : array();
        $carrier = $this->getCarrierById($carrierId);

        // The synchronization button belongs to the editable row itself. It must work
        // before the whole settings form is saved, otherwise a newly added carrier can
        // never be tested/synchronized until after an unrelated full-page save.
        if (!$carrier && $carrierId !== '' && $postedCarrier) {
            $carrier = array('id' => $carrierId, 'provider' => 'manual');
        }

        if (!$carrier) {
            $json['error'] = $this->language->get('error_carrier_not_found');
            return $this->jsonResponse($json);
        }

        // Use the currently visible provider/credential fields as well as the saved row.
        // This prevents stale provider/API credentials from being used by AJAX actions.
        if ($postedCarrier) {
            $carrier = $this->mergeRuntimeCarrier($carrier, $postedCarrier);
        }

        try {
            $result = $this->directory()->syncCities($carrier);
            $json['success'] = $result['mode'] === 'full'
                ? sprintf($this->language->get('text_sync_success'), (int)$result['count'])
                : sprintf($this->language->get('text_sync_on_demand'), (int)$result['count']);
            $json['count'] = (int)$result['count'];
            $json['mode'] = isset($result['mode']) ? (string)$result['mode'] : '';
        } catch (\Throwable $e) {
            $message = $this->safeExceptionMessage($e, $carrier);
            if ($this->log) {
                $this->log->write('Carrier city sync failed [' . (isset($carrier['provider']) ? $carrier['provider'] : 'unknown') . '] ' . get_class($e) . ': ' . $message . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
            $json['error'] = $this->language->get('error_sync_failed') . ($message !== '' ? ' ' . $message : '');
        }

        return $this->jsonResponse($json);
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/shipping/carrier_choice')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!empty($this->request->post['shipping_carrier_choice_status'])) {
            $carriers = $this->normalizeCarriers(isset($this->request->post['shipping_carrier_choice_carriers']) ? $this->request->post['shipping_carrier_choice_carriers'] : array());
            $hasEnabled = false;
            foreach ($carriers as $carrier) {
                if (empty($carrier['status']) || !$this->hasCarrierName($carrier)) { continue; }
                $hasEnabled = true;
                if ($carrier['provider'] === 'nova_poshta' && $carrier['api_key'] === '') { $this->error['carriers'] = $this->language->get('error_nova_key'); }
                if ($carrier['provider'] === 'meest' && ($carrier['api_login'] === '' || $carrier['api_password'] === '')) { $this->error['carriers'] = $this->language->get('error_meest_credentials'); }
                if ($carrier['provider'] === 'ukrposhta' && $carrier['api_token'] === '') { $this->error['carriers'] = $this->language->get('error_ukrposhta_token'); }
            }
            if (!$hasEnabled) { $this->error['carriers'] = $this->language->get('error_carriers'); }
        }
        return !$this->error;
    }

    private function normalizeCarriers($carriers) {
        if (!is_array($carriers)) { return array(); }
        $clean = array();
        foreach ($carriers as $carrier) {
            if (!is_array($carrier)) { continue; }
            $names = array();
            if (isset($carrier['name']) && is_array($carrier['name'])) {
                foreach ($carrier['name'] as $languageId => $name) {
                    $name = trim(str_replace(array('<', '>'), '', strip_tags((string)$name)));
                    $names[(int)$languageId] = utf8_substr($name, 0, 128);
                }
            }
            $id = isset($carrier['id']) ? preg_replace('/[^a-z0-9]/', '', strtolower((string)$carrier['id'])) : '';
            if ($id === '') {
                try { $id = bin2hex(random_bytes(4)); } catch (Throwable $e) { $id = substr(sha1(uniqid('', true)), 0, 8); }
            }
            $provider = isset($carrier['provider']) ? strtolower(trim((string)$carrier['provider'])) : 'manual';
            if (!in_array($provider, $this->providers, true)) { $provider = 'manual'; }
            $cost = isset($carrier['cost']) ? str_replace(',', '.', trim((string)$carrier['cost'])) : '0';
            $cost = is_numeric($cost) ? max(0, (float)$cost) : 0;
            $clean[] = array(
                'id' => substr($id, 0, 16),
                'name' => $names,
                'provider' => $provider,
                'api_key' => $this->cleanSecret(isset($carrier['api_key']) ? $carrier['api_key'] : '', 128),
                'api_login' => $this->cleanSecret(isset($carrier['api_login']) ? $carrier['api_login'] : '', 128),
                'api_password' => $this->cleanSecret(isset($carrier['api_password']) ? $carrier['api_password'] : '', 191),
                'api_token' => $this->cleanSecret(isset($carrier['api_token']) ? $carrier['api_token'] : '', 255),
                'cost' => number_format($cost, 4, '.', ''),
                'status' => !empty($carrier['status']) ? 1 : 0,
                'sort_order' => isset($carrier['sort_order']) ? (int)$carrier['sort_order'] : 0
            );
        }
        usort($clean, function($a, $b) { return $a['sort_order'] == $b['sort_order'] ? 0 : ($a['sort_order'] < $b['sort_order'] ? -1 : 1); });
        return array_values($clean);
    }

    private function cleanSecret($value, $limit) {
        $value = trim(str_replace(array("\0", "\r", "\n"), '', (string)$value));
        return utf8_substr($value, 0, (int)$limit);
    }

    private function hasCarrierName(array $carrier) {
        if (empty($carrier['name']) || !is_array($carrier['name'])) { return false; }
        foreach ($carrier['name'] as $name) { if (trim((string)$name) !== '') { return true; } }
        return false;
    }

    private function getCarrierById($id) {
        $stored = $this->config->get('shipping_carrier_choice_carriers');
        foreach ($this->normalizeCarriers(is_array($stored) ? $stored : array()) as $carrier) {
            if ((string)$carrier['id'] === (string)$id) { return $carrier; }
        }
        return array();
    }

    private function mergeRuntimeCarrier(array $stored, array $posted) {
        $provider = isset($posted['provider']) ? strtolower(trim((string)$posted['provider'])) : (isset($stored['provider']) ? (string)$stored['provider'] : 'manual');
        if (!in_array($provider, $this->providers, true)) { $provider = 'manual'; }
        $stored['provider'] = $provider;

        foreach (array('api_key' => 128, 'api_login' => 128, 'api_password' => 191, 'api_token' => 255) as $key => $limit) {
            if (array_key_exists($key, $posted)) {
                $stored[$key] = $this->cleanSecret($posted[$key], $limit);
            }
        }

        return $stored;
    }

    private function safeExceptionMessage(\Throwable $e, array $carrier) {
        $message = trim(preg_replace('/\s+/', ' ', (string)$e->getMessage()));
        foreach (array('api_key', 'api_login', 'api_password', 'api_token') as $key) {
            if (!empty($carrier[$key])) {
                $message = str_replace((string)$carrier[$key], '[masked]', $message);
            }
        }
        $message = strip_tags($message);
        if (strlen($message) > 700) { $message = substr($message, 0, 700) . '...'; }
        return $message;
    }

    private function jsonResponse(array $json) {
        $unexpected = '';
        $baseLevel = is_int($this->jsonBufferLevel) ? $this->jsonBufferLevel : ob_get_level();
        while (ob_get_level() > $baseLevel) {
            $chunk = @ob_get_clean();
            if (is_string($chunk) && $chunk !== '') { $unexpected = $chunk . $unexpected; }
        }
        if ($unexpected !== '' && $this->log) {
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags($unexpected)));
            if (strlen($clean) > 1200) { $clean = substr($clean, 0, 1200) . '...'; }
            $this->log->write('Carrier AJAX emitted unexpected output before JSON: ' . $clean);
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $encoded = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $this->response->setOutput($encoded !== false ? $encoded : '{"error":"JSON encoding failed"}');
    }

    private function directory() {
        require_once(DIR_SYSTEM . 'library/codecart/carrier_directory.php');
        return new CodeCartCarrierDirectory($this->registry);
    }
}
