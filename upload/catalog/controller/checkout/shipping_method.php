<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerCheckoutShippingMethod extends Controller {
    public function index() {
        $this->load->language('checkout/checkout');
        $this->load->language('extension/shipping/carrier_choice');
        $this->load->helper('codecart_checkout');

        $quick = !empty($this->request->get['quick']);
        $compact = $quick && $this->useCompactCarrierCheckout();

        if (isset($this->session->data['shipping_address'])) {
            $method_data = array();
            $this->load->model('setting/extension');
            $results = $this->model_setting_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if (!$this->config->get('shipping_' . $result['code'] . '_status')) { continue; }

                $this->load->model('extension/shipping/' . $result['code']);
                $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);
                if ($quote) {
                    $method_data[$result['code']] = array(
                        'title' => $quote['title'],
                        'quote' => $quote['quote'],
                        'sort_order' => $quote['sort_order'],
                        'error' => $quote['error']
                    );
                }
            }

            $sort_order = array();
            foreach ($method_data as $key => $value) { $sort_order[$key] = $value['sort_order']; }
            if ($sort_order) { array_multisort($sort_order, SORT_ASC, $method_data); }
            $this->session->data['shipping_methods'] = $method_data;
        }

        $data['error_warning'] = empty($this->session->data['shipping_methods'])
            ? sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact')) : '';
        $data['shipping_methods'] = isset($this->session->data['shipping_methods']) ? $this->session->data['shipping_methods'] : array();
        $data['code'] = isset($this->session->data['shipping_method']['code']) ? $this->session->data['shipping_method']['code'] : '';
        $data['comment'] = isset($this->session->data['comment']) ? $this->session->data['comment'] : '';
        $data['quick_checkout'] = $quick;
        $data['compact_delivery_checkout'] = $compact;
        $data['carrier_city_url'] = html_entity_decode($this->url->link('extension/shipping/carrier_choice/cities', '', true), ENT_QUOTES, 'UTF-8');
        $data['carrier_branch_url'] = html_entity_decode($this->url->link('extension/shipping/carrier_choice/branches', '', true), ENT_QUOTES, 'UTF-8');
        $data['carrier_delivery'] = isset($this->session->data['codecart_delivery']) && is_array($this->session->data['codecart_delivery']) ? $this->session->data['codecart_delivery'] : array();
        $data['text_carrier_city'] = $this->language->get('text_city');
        $data['text_carrier_city_placeholder'] = $this->language->get('text_city_placeholder');
        $data['text_carrier_branch'] = $this->language->get('text_branch');
        $data['text_carrier_branch_placeholder'] = $this->language->get('text_branch_placeholder');
        $data['text_carrier_branch_loading'] = $this->language->get('text_branch_loading');
        $data['text_carrier_no_results'] = $this->language->get('text_no_results');
        $data['error_directory_unavailable'] = $this->language->get('error_directory_unavailable');

        $this->response->setOutput($this->load->view('checkout/shipping_method', $data));
    }

    public function save() {
        $this->load->language('checkout/checkout');
        $this->load->language('extension/shipping/carrier_choice');
        $json = array();

        if (!$this->cart->hasShipping()) { $json['redirect'] = $this->url->link('checkout/checkout', '', true); }
        if (!isset($this->session->data['shipping_address'])) { $json['redirect'] = $this->url->link('checkout/checkout', '', true); }
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart');
        }

        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            $product_total = 0;
            foreach ($products as $product_2) { if ($product_2['product_id'] == $product['product_id']) { $product_total += $product_2['quantity']; } }
            if ($product['minimum'] > $product_total) { $json['redirect'] = $this->url->link('checkout/cart'); break; }
        }

        if (!isset($this->request->post['shipping_method'])) {
            $json['error']['warning'] = $this->language->get('error_shipping');
        } else {
            $shipping = explode('.', (string)$this->request->post['shipping_method']);
            if (!isset($shipping[0], $shipping[1], $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                $json['error']['warning'] = $this->language->get('error_shipping');
            }
        }

        $delivery = null;
        if (!$json && $shipping[0] === 'carrier_choice') {
            $quote = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
            $this->load->model('extension/shipping/carrier_choice');
            $carrier = $this->model_extension_shipping_carrier_choice->getCarrierByQuoteKey($shipping[1]);
            if (!$carrier) {
                $json['error']['warning'] = $this->language->get('error_carrier');
            } elseif (!empty($quote['requires_location'])) {
                $city_id = isset($this->request->post['delivery_city_id']) ? trim((string)$this->request->post['delivery_city_id']) : '';
                $branch_id = isset($this->request->post['delivery_branch_id']) ? trim((string)$this->request->post['delivery_branch_id']) : '';
                if ($city_id === '') {
                    $json['error']['warning'] = $this->language->get('error_city_required');
                } elseif ($branch_id === '') {
                    $json['error']['warning'] = $this->language->get('error_branch_required');
                } else {
                    try {
                        $selection = $this->model_extension_shipping_carrier_choice->validateSelection($carrier, $city_id, $branch_id);
                        $city = $selection['city'];
                        $branch = $selection['branch'];
                        $carrier_title = $this->model_extension_shipping_carrier_choice->getCarrierTitle($carrier);
                        $delivery = array(
                            'carrier_id' => isset($quote['carrier_id']) ? (string)$quote['carrier_id'] : '',
                            'provider' => isset($quote['provider']) ? (string)$quote['provider'] : 'manual',
                            'carrier_name' => $carrier_title,
                            'city_external_id' => (string)$city['external_id'],
                            'city_name' => (string)$city['name'],
                            'branch_external_id' => (string)$branch['id'],
                            'branch_name' => (string)$branch['name'],
                            'branch_address' => isset($branch['address']) ? (string)$branch['address'] : '',
                            'postcode' => isset($branch['postcode']) ? (string)$branch['postcode'] : ''
                        );
                    } catch (Exception $e) {
                        if ($this->log) { $this->log->write('Carrier selection validation failed: ' . get_class($e)); }
                        $json['error']['warning'] = $this->language->get('error_branch_invalid');
                    }
                }
            } else {
                $delivery = array(
                    'carrier_id' => isset($quote['carrier_id']) ? (string)$quote['carrier_id'] : '',
                    'provider' => isset($quote['provider']) ? (string)$quote['provider'] : 'manual',
                    'carrier_name' => $this->model_extension_shipping_carrier_choice->getCarrierTitle($carrier),
                    'city_external_id' => '',
                    'city_name' => '',
                    'branch_external_id' => '',
                    'branch_name' => '',
                    'branch_address' => '',
                    'postcode' => '',
                    'requires_location' => 0
                );
            }
        }

        if (!$json) {
            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
            if ($delivery) {
                $this->session->data['codecart_delivery'] = $delivery;
                if (!empty($delivery['city_name']) || !empty($delivery['branch_name'])) {
                    $this->session->data['shipping_method']['title'] = $delivery['carrier_name'] . ' — ' . $delivery['city_name'] . ', ' . $delivery['branch_name'];
                    $this->applyDeliveryAddress($delivery);
                } else {
                    // Pickup/manual delivery intentionally keeps the compact guest address empty.
                    $this->session->data['shipping_method']['title'] = $delivery['carrier_name'];
                }
            } else {
                unset($this->session->data['codecart_delivery']);
            }
            $this->session->data['comment'] = isset($this->request->post['comment']) ? strip_tags((string)$this->request->post['comment']) : '';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function useCompactCarrierCheckout() {
        if (!codecart_carrier_compact_checkout($this->config)) {
            return false;
        }

        $this->load->model('setting/extension');
        $extensions = $this->model_setting_extension->getExtensions('shipping');

        foreach ($extensions as $extension) {
            $code = isset($extension['code']) ? (string)$extension['code'] : '';
            if ($code === '' || !$this->config->get('shipping_' . $code . '_status')) {
                continue;
            }
            if ($code !== 'carrier_choice') {
                return false;
            }
        }

        return true;
    }

    private function applyDeliveryAddress(array $delivery) {
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $country_id = isset($this->session->data['shipping_address']['country_id']) && (int)$this->session->data['shipping_address']['country_id'] > 0
            ? (int)$this->session->data['shipping_address']['country_id'] : (int)$this->config->get('config_country_id');
        $zone_id = isset($this->session->data['shipping_address']['zone_id']) && (int)$this->session->data['shipping_address']['zone_id'] > 0
            ? (int)$this->session->data['shipping_address']['zone_id'] : (int)$this->config->get('config_zone_id');
        $country = $this->model_localisation_country->getCountry($country_id);
        $zone = $this->model_localisation_zone->getZone($zone_id);
        $firstname = isset($this->session->data['guest']['firstname']) ? (string)$this->session->data['guest']['firstname'] : (isset($this->session->data['shipping_address']['firstname']) ? (string)$this->session->data['shipping_address']['firstname'] : '');
        $lastname = isset($this->session->data['guest']['lastname']) ? (string)$this->session->data['guest']['lastname'] : (isset($this->session->data['shipping_address']['lastname']) ? (string)$this->session->data['shipping_address']['lastname'] : '');
        $address = array(
            'firstname' => $firstname,
            'lastname' => $lastname,
            'company' => '',
            'address_1' => $delivery['branch_address'] !== '' ? $delivery['branch_address'] : $delivery['branch_name'],
            'address_2' => $delivery['carrier_name'],
            'postcode' => $delivery['postcode'],
            'city' => $delivery['city_name'],
            'country_id' => $country_id,
            'country' => $country ? $country['name'] : '',
            'iso_code_2' => $country ? $country['iso_code_2'] : '',
            'iso_code_3' => $country ? $country['iso_code_3'] : '',
            'address_format' => $country ? $country['address_format'] : '',
            'zone_id' => $zone_id,
            'zone' => $zone ? $zone['name'] : '',
            'zone_code' => $zone ? $zone['code'] : '',
            'custom_field' => array()
        );
        $this->session->data['shipping_address'] = $address;
        if (isset($this->session->data['payment_address'])) {
            $this->session->data['payment_address'] = $address;
        }
    }
}
