<?php
namespace CodeCart\Core;

/**
 * Storefront-only tax presentation helper.
 *
 * This class never changes cart/order/tax totals. It only decides how a catalog
 * price is rendered. OpenCart's tax engine remains the source of the gross tax
 * calculation and checkout continues to use its existing rules.
 */
final class TaxDisplay {
    const MODE_NATIVE = 'native';          // Exact native OpenCart catalog presentation.
    const MODE_NONE = 'none';              // Primary follows config_tax, no secondary line.
    const MODE_GROSS_NET = 'gross_net';    // Primary incl. tax, secondary excl. tax.
    const MODE_GROSS_TAX = 'gross_tax';    // Primary incl. tax, secondary tax amount only.
    const MODE_NET_GROSS = 'net_gross';    // Primary excl. tax, secondary incl. tax.

    public static function mode($config, $override = null) {
        $override = strtolower(trim((string)$override));
        $mode = ($override !== '' && $override !== 'inherit') ? $override : (string)$config->get('config_tax_display');

        // Backward compatibility with RC84 values.
        if ($mode === 'ex_tax') {
            $mode = self::MODE_GROSS_NET;
        } elseif ($mode === 'tax_amount') {
            $mode = self::MODE_GROSS_TAX;
        }

        return in_array($mode, array(self::MODE_NATIVE, self::MODE_NONE, self::MODE_GROSS_NET, self::MODE_GROSS_TAX, self::MODE_NET_GROSS), true)
            ? $mode
            : self::MODE_NATIVE;
    }

    /** Numeric primary catalog price before currency formatting. */
    public static function primaryValue($registry, $raw_price, $tax_class_id, $override = null) {
        $config = $registry->get('config');
        $tax = $registry->get('tax');
        $raw_price = (float)$raw_price;
        $tax_class_id = (int)$tax_class_id;
        $mode = self::mode($config, $override);

        if ($mode === self::MODE_GROSS_NET || $mode === self::MODE_GROSS_TAX) {
            return $tax ? (float)$tax->calculate($raw_price, $tax_class_id, true) : $raw_price;
        }
        if ($mode === self::MODE_NET_GROSS) {
            return $raw_price;
        }

        // Compatibility/default: preserve the native OpenCart storefront setting.
        return $tax ? (float)$tax->calculate($raw_price, $tax_class_id, (bool)$config->get('config_tax')) : $raw_price;
    }

    /** Formatted primary catalog price. */
    public static function primary($registry, $raw_price, $tax_class_id, $override = null) {
        $currency = $registry->get('currency');
        $session = $registry->get('session');
        if (!$currency || !$session || empty($session->data['currency'])) {
            return false;
        }
        return $currency->format(self::primaryValue($registry, $raw_price, $tax_class_id, $override), $session->data['currency']);
    }

    /** Numeric optional secondary catalog value, or false when hidden/not applicable. */
    public static function secondaryValue($registry, $raw_price, $tax_class_id, $override = null) {
        $config = $registry->get('config');
        $mode = self::mode($config, $override);
        if ($mode === self::MODE_NONE) {
            return false;
        }
        if ($mode === self::MODE_NATIVE && !$config->get('config_tax')) {
            return false;
        }

        $raw_price = (float)$raw_price;
        $tax_class_id = (int)$tax_class_id;
        $tax = $registry->get('tax');
        if (!$tax) {
            return false;
        }

        $gross = (float)$tax->calculate($raw_price, $tax_class_id, true);
        if ($mode === self::MODE_NATIVE || $mode === self::MODE_GROSS_NET) {
            return $raw_price;
        }
        if ($mode === self::MODE_GROSS_TAX) {
            $value = max(0.0, $gross - $raw_price);
            return $value < 0.000001 ? false : $value;
        }
        return $gross; // MODE_NET_GROSS
    }

    /** Formatted optional secondary catalog line. */
    public static function secondary($registry, $raw_price, $tax_class_id, $override = null) {
        $currency = $registry->get('currency');
        $session = $registry->get('session');
        if (!$currency || !$session || empty($session->data['currency'])) {
            return false;
        }
        $value = self::secondaryValue($registry, $raw_price, $tax_class_id, $override);
        return $value === false ? false : $currency->format($value, $session->data['currency']);
    }

    public static function label($config, $language, $override = null) {
        $mode = self::mode($config, $override);
        if ($mode === self::MODE_GROSS_TAX) {
            $label = (string)$language->get('text_tax_amount');
            return ($label === '' || $label === 'text_tax_amount') ? 'VAT:' : $label;
        }
        if ($mode === self::MODE_NET_GROSS) {
            $label = (string)$language->get('text_tax_included');
            return ($label === '' || $label === 'text_tax_included') ? 'Incl. VAT:' : $label;
        }
        $label = (string)$language->get('text_tax');
        return ($label === '' || $label === 'text_tax') ? 'Excl. VAT:' : $label;
    }
}
