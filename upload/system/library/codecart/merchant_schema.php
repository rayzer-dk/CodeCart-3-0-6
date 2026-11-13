<?php
/**
 * CodeCart PRO merchant identity, shipping and return policies for Schema.org.
 *
 * No database writes, external requests or extra DB reads on the storefront.
 * Only explicit, validated merchant claims are published. Policies are scoped
 * to a particular OpenCart store to prevent settings inheritance leakage.
 */
final class CodeCartMerchantSchema {
    public static function defaults() {
        return array(
            'config_codecart_schema_legal_name' => '',
            'config_codecart_schema_vat_id' => '',
            'config_codecart_schema_same_as' => '',
            'config_codecart_schema_shipping_status' => 0,
            'config_codecart_schema_shipping_name' => '',
            'config_codecart_schema_shipping_countries' => '',
            'config_codecart_schema_shipping_currency' => '',
            'config_codecart_schema_shipping_price' => '',
            'config_codecart_schema_shipping_handling_min' => '',
            'config_codecart_schema_shipping_handling_max' => '',
            'config_codecart_schema_shipping_transit_min' => '',
            'config_codecart_schema_shipping_transit_max' => '',
            'config_codecart_schema_shipping_info_id' => 0,
            'config_codecart_schema_shipping_offers' => 0,
            'config_codecart_schema_return_status' => 0,
            'config_codecart_schema_return_countries' => '',
            'config_codecart_schema_return_category' => 'finite',
            'config_codecart_schema_return_days' => '',
            'config_codecart_schema_return_method' => '',
            'config_codecart_schema_return_fees' => '',
            'config_codecart_schema_return_cost' => '',
            'config_codecart_schema_return_currency' => '',
            'config_codecart_schema_return_info_id' => 0
        );
    }

    public static function clean(array $source) {
        $defaults = self::defaults();
        $result = array();
        foreach ($defaults as $key => $fallback) {
            $value = isset($source[$key]) && is_scalar($source[$key]) ? trim((string)$source[$key]) : (string)$fallback;
            if (in_array($key, array(
                'config_codecart_schema_shipping_status',
                'config_codecart_schema_shipping_offers',
                'config_codecart_schema_return_status'
            ), true)) {
                $result[$key] = $value === '1' ? 1 : 0;
            } elseif (substr($key, -8) === '_info_id') {
                $result[$key] = max(0, (int)$value);
            } elseif (substr($key, -10) === '_countries') {
                $result[$key] = strtoupper(str_replace(array("\r", "\n", ';'), ',', $value));
            } elseif (substr($key, -9) === '_currency') {
                $result[$key] = strtoupper($value);
            } elseif ($key === 'config_codecart_schema_return_category') {
                $result[$key] = in_array($value, array('finite', 'unlimited', 'not_permitted'), true) ? $value : 'finite';
            } elseif ($key === 'config_codecart_schema_return_method') {
                $result[$key] = in_array($value, array('', 'ReturnByMail', 'ReturnInStore', 'ReturnAtKiosk'), true) ? $value : '';
            } elseif ($key === 'config_codecart_schema_return_fees') {
                $result[$key] = in_array($value, array('', 'FreeReturn', 'ReturnFeesCustomerResponsibility', 'ReturnShippingFees'), true) ? $value : '';
            } elseif (in_array($key, array('config_codecart_schema_legal_name', 'config_codecart_schema_vat_id', 'config_codecart_schema_shipping_name'), true)) {
                $result[$key] = substr(strip_tags($value), 0, 180);
            } elseif ($key === 'config_codecart_schema_same_as') {
                $result[$key] = substr($value, 0, 2500);
            } else {
                $result[$key] = substr($value, 0, 80);
            }
        }
        return $result;
    }

    private static function get($settings, $suffix) {
        $key = 'config_codecart_schema_' . $suffix;
        return is_object($settings) && method_exists($settings, 'get')
            ? $settings->get($key) : (isset($settings[$key]) ? $settings[$key] : null);
    }

    private static function countries($text) {
        $values = preg_split('/[\s,;]+/u', strtoupper(trim((string)$text)), -1, PREG_SPLIT_NO_EMPTY);
        if (!$values || count($values) > 50) { return array(); }
        $codes = array();
        foreach ($values as $value) {
            if (!preg_match('/^[A-Z]{2}$/D', $value)) { return array(); }
            $codes[$value] = $value;
        }
        return array_values($codes);
    }

    private static function amount($value) {
        $value = trim((string)$value);
        return preg_match('/^\d{1,8}(?:\.\d{1,4})?$/D', $value) ? (float)$value : null;
    }

    private static function days($min, $max) {
        $min = trim((string)$min);
        $max = trim((string)$max);
        if (!preg_match('/^\d{1,4}$/D', $min) || !preg_match('/^\d{1,4}$/D', $max) || (int)$min > (int)$max) {
            return null;
        }
        return array('@type' => 'QuantitativeValue', 'minValue' => (int)$min, 'maxValue' => (int)$max, 'unitCode' => 'DAY');
    }

    private static function currency($value) {
        return preg_match('/^[A-Z]{3}$/D', (string)$value) === 1;
    }

    public static function validShipping($settings) {
        return count(self::countries(self::get($settings, 'shipping_countries'))) > 0
            && self::currency(self::get($settings, 'shipping_currency'))
            && self::amount(self::get($settings, 'shipping_price')) !== null
            && self::days(self::get($settings, 'shipping_handling_min'), self::get($settings, 'shipping_handling_max')) !== null
            && self::days(self::get($settings, 'shipping_transit_min'), self::get($settings, 'shipping_transit_max')) !== null;
    }

    public static function validReturn($settings) {
        if (!self::countries(self::get($settings, 'return_countries'))) { return false; }
        $category = (string)self::get($settings, 'return_category');
        if (!in_array($category, array('finite', 'unlimited', 'not_permitted'), true)) { return false; }
        if ($category === 'finite' && (!preg_match('/^\d{1,4}$/D', (string)self::get($settings, 'return_days')) || (int)self::get($settings, 'return_days') < 1)) {
            return false;
        }
        if ($category === 'not_permitted') { return true; }
        $method = (string)self::get($settings, 'return_method');
        $fees = (string)self::get($settings, 'return_fees');
        if (!in_array($method, array('', 'ReturnByMail', 'ReturnInStore', 'ReturnAtKiosk'), true)) { return false; }
        if (!in_array($fees, array('', 'FreeReturn', 'ReturnFeesCustomerResponsibility', 'ReturnShippingFees'), true)) { return false; }
        if ($fees === 'ReturnShippingFees') {
            return self::amount(self::get($settings, 'return_cost')) !== null
                && self::currency(self::get($settings, 'return_currency'));
        }
        return true;
    }

    public static function errors(array $settings) {
        $errors = array();
        if (!empty($settings['config_codecart_schema_shipping_status']) && !self::validShipping($settings)) {
            $errors[] = 'shipping';
        }
        if (!empty($settings['config_codecart_schema_return_status']) && !self::validReturn($settings)) {
            $errors[] = 'return';
        }
        $sameAs = trim((string)self::get($settings, 'same_as'));
        if ($sameAs !== '') {
            foreach (preg_split('/[\s,]+/', $sameAs, -1, PREG_SPLIT_NO_EMPTY) as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                    $errors[] = 'identity';
                    break;
                }
            }
        }
        return $errors;
    }

    private static function matchesStore($config) {
        $actual = is_object($config) ? (int)$config->get('config_store_id') : (int)($config['config_store_id'] ?? 0);
        $owner = self::get($config, 'store_id');
        return $owner !== null && (int)$owner === $actual;
    }

    private static function regions($config, $suffix) {
        $regions = array();
        foreach (self::countries(self::get($config, $suffix . '_countries')) as $country) {
            $regions[] = array('@type' => 'DefinedRegion', 'addressCountry' => $country);
        }
        return $regions;
    }

    public static function identity($config) {
        if (!self::matchesStore($config)) { return array(); }
        $identity = array();
        $legal = trim((string)self::get($config, 'legal_name'));
        if ($legal !== '') { $identity['legalName'] = $legal; }
        $vat = trim((string)self::get($config, 'vat_id'));
        if ($vat !== '') { $identity['vatID'] = $vat; }
        $profiles = array();
        foreach (preg_split('/[\s,]+/', (string)self::get($config, 'same_as'), -1, PREG_SPLIT_NO_EMPTY) as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https') {
                $profiles[$url] = $url;
            }
            if (count($profiles) >= 20) { break; }
        }
        if ($profiles) { $identity['sameAs'] = array_values($profiles); }
        return $identity;
    }

    public static function shipping($config, $base, $policyUrl = '') {
        if (!self::matchesStore($config) || (int)self::get($config, 'shipping_status') !== 1 || !self::validShipping($config)) {
            return null;
        }
        $handling = self::days(self::get($config, 'shipping_handling_min'), self::get($config, 'shipping_handling_max'));
        $transit = self::days(self::get($config, 'shipping_transit_min'), self::get($config, 'shipping_transit_max'));
        $service = array(
            '@type' => 'ShippingService',
            '@id' => rtrim($base, '/') . '/#shipping-service',
            'fulfillmentType' => 'https://schema.org/FulfillmentTypeDelivery',
            'handlingTime' => array('@type' => 'ServicePeriod', 'duration' => $handling),
            'shippingConditions' => array(
                array(
                    '@type' => 'ShippingConditions',
                    'shippingDestination' => self::regions($config, 'shipping'),
                    'shippingRate' => array('@type' => 'MonetaryAmount',
                        'value' => self::amount(self::get($config, 'shipping_price')),
                        'currency' => (string)self::get($config, 'shipping_currency')),
                    'transitTime' => array('@type' => 'ServicePeriod', 'duration' => $transit)
                )
            )
        );
        $name = trim((string)self::get($config, 'shipping_name'));
        if ($name !== '') { $service['name'] = $name; }
        if ($policyUrl !== '') { $service['url'] = $policyUrl; }
        return $service;
    }

    public static function offerShipping($config, $currency) {
        if (!self::matchesStore($config) || (int)self::get($config, 'shipping_offers') !== 1 ||
            (int)self::get($config, 'shipping_status') !== 1 || !self::validShipping($config) ||
            strtoupper((string)$currency) !== (string)self::get($config, 'shipping_currency')) {
            return null;
        }
        return array(
            '@type' => 'OfferShippingDetails',
            'shippingDestination' => self::regions($config, 'shipping'),
            'shippingRate' => array('@type' => 'MonetaryAmount',
                'value' => self::amount(self::get($config, 'shipping_price')),
                'currency' => (string)self::get($config, 'shipping_currency')),
            'deliveryTime' => array(
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => self::days(self::get($config, 'shipping_handling_min'), self::get($config, 'shipping_handling_max')),
                'transitTime' => self::days(self::get($config, 'shipping_transit_min'), self::get($config, 'shipping_transit_max'))
            )
        );
    }

    public static function returns($config, $policyUrl = '') {
        if (!self::matchesStore($config) || (int)self::get($config, 'return_status') !== 1 || !self::validReturn($config)) {
            return null;
        }
        $category = (string)self::get($config, 'return_category');
        $mapping = array(
            'finite' => 'MerchantReturnFiniteReturnWindow',
            'unlimited' => 'MerchantReturnUnlimitedWindow',
            'not_permitted' => 'MerchantReturnNotPermitted'
        );
        $countries = self::countries(self::get($config, 'return_countries'));
        $result = array(
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => $countries,
            'returnPolicyCategory' => 'https://schema.org/' . $mapping[$category]
        );
        if ($policyUrl !== '') { $result['merchantReturnLink'] = $policyUrl; }
        if ($category === 'finite') { $result['merchantReturnDays'] = (int)self::get($config, 'return_days'); }
        if ($category !== 'not_permitted') {
            $method = (string)self::get($config, 'return_method');
            $fees = (string)self::get($config, 'return_fees');
            if ($method !== '') { $result['returnMethod'] = 'https://schema.org/' . $method; }
            if ($fees !== '') {
                $result['returnFees'] = 'https://schema.org/' . $fees;
                if ($fees === 'ReturnShippingFees') {
                    $result['returnShippingFeesAmount'] = array(
                        '@type' => 'MonetaryAmount',
                        'value' => self::amount(self::get($config, 'return_cost')),
                        'currency' => (string)self::get($config, 'return_currency')
                    );
                }
            }
        }
        return $result;
    }
}
