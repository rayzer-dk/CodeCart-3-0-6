<?php
namespace CodeCart\Core;

/**
 * Backwards-compatible localized SEO for the blog landing page.
 * A blank localized field deliberately inherits the original global value.
 * No new database tables, external lookups or per-request database queries.
 */
final class BlogLocaleSeo {
    private static function legacyKeys() {
        return array(
            'name' => 'configblog_name',
            'html_h1' => 'configblog_html_h1',
            'meta_title' => 'configblog_meta_title',
            'meta_description' => 'configblog_meta_description'
        );
    }

    public static function fields() {
        return array_keys(self::legacyKeys());
    }

    public static function resolve($config, $languageId, $field, $fallback = '') {
        $keys = self::legacyKeys();
        if (!isset($keys[$field])) {
            return (string)$fallback;
        }
        $languageId = (int)$languageId;
        $localized = $config->get('configblog_localized');
        if ($languageId > 0 && is_array($localized) && isset($localized[$languageId])
            && is_array($localized[$languageId]) && isset($localized[$languageId][$field])
            && is_string($localized[$languageId][$field])) {
            $value = trim($localized[$languageId][$field]);
            if ($value !== '') {
                return $value;
            }
        }

        $legacy = $config->get($keys[$field]);
        if (is_string($legacy) || is_numeric($legacy)) {
            $legacy = trim((string)$legacy);
            if ($legacy !== '') {
                return $legacy;
            }
        }
        return (string)$fallback;
    }

    /**
     * Preserve settings of languages not shown in the current admin form.
     * Only server-approved language IDs and the four allowed keys are saved.
     */
    public static function sanitize($input, $existing, array $allowedLanguageIds) {
        $result = array();
        $existing = is_array($existing) ? $existing : array();
        foreach ($existing as $key => $value) {
            $id = filter_var($key, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
            if ($id !== false && is_array($value)) {
                $result[(int)$id] = self::sanitizeRow($value);
            }
        }
        $input = is_array($input) ? $input : array();
        foreach (array_slice(array_unique(array_map('intval', $allowedLanguageIds)), 0, 50) as $id) {
            if ($id <= 0 || !array_key_exists($id, $input) || !is_array($input[$id])) {
                continue;
            }
            $result[$id] = self::sanitizeRow($input[$id]);
        }
        return $result;
    }

    private static function sanitizeRow(array $row) {
        $result = array();
        foreach (self::legacyKeys() as $field => $unused) {
            $value = isset($row[$field]) && is_string($row[$field]) ? trim($row[$field]) : '';
            $value = str_replace(array("\r", "\n", "\0"), ' ', $value);
            $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value);
            // utf8_strlen is available in OpenCart, but mbstring is optional.
            $limit = $field === 'meta_description' ? 1000 : 255;
            if (function_exists('utf8_substr')) {
                $value = utf8_substr($value, 0, $limit);
            } elseif (function_exists('mb_substr')) {
                $value = mb_substr($value, 0, $limit, 'UTF-8');
            } else {
                $value = substr($value, 0, $limit);
            }
            $result[$field] = $value;
        }
        return $result;
    }
}
