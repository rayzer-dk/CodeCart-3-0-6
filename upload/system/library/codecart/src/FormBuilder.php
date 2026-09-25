<?php
namespace CodeCart\Core;

/**
 * Sanitizer for reusable storefront forms.
 * Definitions are language-specific and stored as JSON in codecart_form_description.
 */
final class FormBuilder {
    const MAX_FIELDS = 30;
    const MAX_OPTIONS = 50;

    public static function decode($json) {
        if (is_array($json)) { return self::sanitizeFields($json); }
        $json = html_entity_decode((string)$json, ENT_QUOTES, 'UTF-8');
        $data = json_decode($json, true);
        return is_array($data) ? self::sanitizeFields($data) : array();
    }

    public static function encode($value) {
        return json_encode(self::decode($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function sanitizeFields($fields) {
        if (!is_array($fields)) { return array(); }
        $out = array();
        $used = array();
        $types = array('text','email','tel','textarea','select','checkbox');

        foreach ($fields as $index => $field) {
            if (count($out) >= self::MAX_FIELDS || !is_array($field)) { break; }
            $type = isset($field['type']) ? strtolower(trim((string)$field['type'])) : 'text';
            if (!in_array($type, $types, true)) { $type = 'text'; }
            $key = isset($field['key']) ? strtolower(trim((string)$field['key'])) : '';
            $key = preg_replace('/[^a-z0-9_]/', '_', $key);
            $key = trim((string)$key, '_');
            if ($key === '') { $key = 'field_' . (count($out) + 1); }
            $key = substr($key, 0, 48);
            $base = $key;
            $suffix = 2;
            while (isset($used[$key])) { $key = substr($base, 0, 43) . '_' . $suffix++; }
            $used[$key] = true;

            $clean = array(
                'key' => $key,
                'type' => $type,
                'label' => self::text(isset($field['label']) ? $field['label'] : '', 128),
                'placeholder' => self::text(isset($field['placeholder']) ? $field['placeholder'] : '', 160),
                'required' => !empty($field['required']) ? 1 : 0
            );

            if ($type === 'select') {
                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : preg_split('/\r\n|\r|\n/', (string)(isset($field['options']) ? $field['options'] : ''));
                $clean['options'] = array();
                foreach (array_slice((array)$options, 0, self::MAX_OPTIONS) as $option) {
                    $option = self::text($option, 128);
                    if ($option !== '') { $clean['options'][] = $option; }
                }
            }

            if ($clean['label'] === '') { $clean['label'] = $key; }
            $out[] = $clean;
        }

        return $out;
    }

    private static function text($value, $limit) {
        $value = strip_tags(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'));
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        return utf8_substr($value, 0, (int)$limit);
    }
}
