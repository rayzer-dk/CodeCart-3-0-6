<?php
namespace CodeCart\Core;

/**
 * Per-administrator interface language. Does not alter storefront or global store settings.
 */
final class AdminLanguagePreference {
    public static function available($db): array {
        $result = array();
        $labels = array('uk-ua' => 'Українська', 'ru-ru' => 'Русский', 'en-gb' => 'English');
        // Interface languages are independent from enabled storefront languages.
        // A clean store may have no ru-ru database row but still ships ru-ru admin PHP.
        $query = $db->query("SELECT language_id, name, code, status FROM `" . DB_PREFIX . "language` ORDER BY sort_order ASC, name ASC");
        $rows = array();
        foreach ($query->rows as $row) {
            $code = strtolower(trim((string)$row['code']));
            if (preg_match('/^[a-z]{2,3}-[a-z]{2,3}$/D', $code)) {
                $rows[$code] = $row;
            }
        }

        // Bundled admin packs are always selectable when installed, regardless of store configuration.
        $codes = array_keys($labels);
        foreach ($rows as $code => $row) {
            if (!isset($labels[$code]) && (int)$row['status'] === 1) {
                $codes[] = $code;
            }
        }
        foreach ($codes as $code) {
            // Never turn a database value into an arbitrary filesystem path.
            if (!is_file(DIR_LANGUAGE . $code . '/' . $code . '.php')
                || !is_file(DIR_LANGUAGE . $code . '/' . $code . '.png')) {
                continue;
            }
            $result[$code] = array(
                'code' => $code,
                'name' => isset($labels[$code]) ? $labels[$code] : (string)$rows[$code]['name'],
                'flag' => 'language/' . $code . '/' . $code . '.png',
                'language_id' => isset($rows[$code]) ? (int)$rows[$code]['language_id'] : 0
            );
        }

        return $result;
    }

    public static function read($db, int $userId): string {
        if ($userId < 1) {
            return '';
        }
        try {
            // During a rolling deployment the new preference table may not exist yet.
            $query = $db->query("SELECT language_code FROM `" . DB_PREFIX . "codecart_admin_language` WHERE user_id = '" . $userId . "' LIMIT 1");
            return !empty($query->row['language_code']) ? (string)$query->row['language_code'] : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function save($db, int $userId, string $code): void {
        if ($userId < 1 || !preg_match('/^[a-z]{2,3}-[a-z]{2,3}$/D', $code)) {
            throw new \InvalidArgumentException('Invalid administrator language preference.');
        }
        $db->query("INSERT INTO `" . DB_PREFIX . "codecart_admin_language` (user_id, language_code, date_modified) VALUES ('" . $userId . "', '" . $db->escape($code) . "', NOW()) ON DUPLICATE KEY UPDATE language_code = VALUES(language_code), date_modified = NOW()");
    }
}
