<?php
/**
 * Per-content Schema.org type overrides.
 * Stored in the standard setting table to support existing installations
 * without a schema migration. Only the main form save changes these values.
 */
class CodeCartSchemaTypes {
    public static function options($kind) {
        if ($kind === 'information') {
            return array('auto', 'WebPage', 'AboutPage', 'ContactPage', 'FAQPage', 'CollectionPage', 'Article', 'none');
        }
        if ($kind === 'article') {
            return array('auto', 'BlogPosting', 'Article', 'NewsArticle', 'none');
        }
        return array('auto');
    }

    public static function normalize($kind, $value) {
        $value = is_string($value) ? trim($value) : '';
        return in_array($value, self::options($kind), true) ? $value : 'auto';
    }

    private static function key($kind, $id) {
        if (!in_array($kind, array('information', 'article'), true) || (int)$id < 1) {
            return '';
        }
        return 'codecart_schema_' . $kind . '_' . (int)$id;
    }

    public static function get($db, $kind, $id) {
        $key = self::key($kind, $id);
        if ($key === '') {
            return 'auto';
        }
        $result = $db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE store_id = 0 AND `code` = 'codecart_schema' AND `key` = '" . $db->escape($key) . "' LIMIT 1");
        return $result->num_rows ? self::normalize($kind, $result->row['value']) : 'auto';
    }

    public static function save($db, $kind, $id, $value) {
        $key = self::key($kind, $id);
        if ($key === '') {
            return;
        }
        $value = self::normalize($kind, $value);
        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = 0 AND `code` = 'codecart_schema' AND `key` = '" . $db->escape($key) . "'");
        if ($value !== 'auto') {
            $db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = 0, `code` = 'codecart_schema', `key` = '" . $db->escape($key) . "', `value` = '" . $db->escape($value) . "', serialized = 0");
        }
    }

    public static function delete($db, $kind, $id) {
        $key = self::key($kind, $id);
        if ($key !== '') {
            $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = 0 AND `code` = 'codecart_schema' AND `key` = '" . $db->escape($key) . "'");
        }
    }
}
