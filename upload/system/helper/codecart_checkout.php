<?php
/**
 * CodeCart PRO checkout field helpers.
 * Keeps the modern quick checkout additive while preserving legacy field settings.
 */

if (!function_exists('codecart_checkout_field_mode')) {
    function codecart_checkout_field_mode($config, $field) {
        $allowed = array('required', 'optional', 'hidden');
        $key = 'config_checkout_field_' . $field . '_mode';

        if ($config->has($key)) {
            $mode = strtolower(trim((string)$config->get($key)));
            if (in_array($mode, $allowed, true)) {
                return $mode;
            }
        }

        // Preserve the behaviour of stores upgraded from RC62 and older.
        $legacyKey = 'config_checkout_field_' . $field;
        if ($config->has($legacyKey) && !$config->get($legacyKey)) {
            return 'hidden';
        }

        if (in_array($field, array('company', 'address_2'), true)) {
            return 'optional';
        }

        return 'required';
    }
}

if (!function_exists('codecart_checkout_field_visible')) {
    function codecart_checkout_field_visible($config, $field) {
        return codecart_checkout_field_mode($config, $field) !== 'hidden';
    }
}

if (!function_exists('codecart_checkout_field_required')) {
    function codecart_checkout_field_required($config, $field) {
        return codecart_checkout_field_mode($config, $field) === 'required';
    }
}

if (!function_exists('codecart_checkout_field_label')) {
    function codecart_checkout_field_label($config, $languageId, $field, $fallback) {
        $labels = $config->get('config_checkout_field_labels');
        if (is_array($labels) && isset($labels[(int)$languageId]) && is_array($labels[(int)$languageId])) {
            $label = trim(strip_tags((string)($labels[(int)$languageId][$field] ?? '')));
            if ($label !== '') {
                return utf8_substr($label, 0, 80);
            }
        }
        return $fallback;
    }
}

if (!function_exists('codecart_checkout_email_fallback')) {
    function codecart_checkout_email_fallback($config) {
        $email = trim((string)$config->get('config_checkout_email_fallback'));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return 'checkout@invalid.local';
    }
}

if (!function_exists('codecart_carrier_compact_checkout')) {
    function codecart_carrier_compact_checkout($config) {
        return (bool)$config->get('shipping_carrier_choice_status')
            && (bool)$config->get('shipping_carrier_choice_compact_checkout')
            && (string)$config->get('theme_default_commerce_style') === 'modern'
            && (bool)$config->get('config_quick_checkout_status');
    }
}
