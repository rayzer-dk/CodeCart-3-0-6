<?php
namespace CodeCart\Core;

/**
 * One canonical product condition across admin, storefront, JSON-LD and Merchant Center.
 * Existing products and integrations default to 'new' for backwards compatibility.
 */
final class ProductCondition {
    private const SCHEMA_TYPES = array(
        'new' => 'NewCondition',
        'refurbished' => 'RefurbishedCondition',
        'used' => 'UsedCondition'
    );

    public static function normalize($value) {
        $value = is_string($value) ? strtolower(trim($value)) : '';
        return isset(self::SCHEMA_TYPES[$value]) ? $value : 'new';
    }

    public static function options() {
        return array_keys(self::SCHEMA_TYPES);
    }

    public static function schemaUrl($value) {
        $condition = self::normalize($value);
        return 'https://schema.org/' . self::SCHEMA_TYPES[$condition];
    }

    public static function merchantValue($value) {
        return self::normalize($value);
    }
}
