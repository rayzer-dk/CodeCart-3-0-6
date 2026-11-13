<?php
namespace CodeCart\Core;

/**
 * Daily counters of what visitors search for: how often, how often nothing was found and
 * which searches were answered only after a spelling correction. No IP address, no session.
 */
final class SearchStats {
    private const RETENTION_DAYS = 90;

    public static function install($db) {
        $db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "codecart_search_query` (
            `day` DATE NOT NULL,
            `store_id` INT NOT NULL DEFAULT 0,
            `language_id` INT NOT NULL DEFAULT 0,
            `query_hash` CHAR(64) NOT NULL,
            `query_text` VARCHAR(190) NOT NULL,
            `searches` INT UNSIGNED NOT NULL DEFAULT 0,
            `zero_results` INT UNSIGNED NOT NULL DEFAULT 0,
            `corrected` INT UNSIGNED NOT NULL DEFAULT 0,
            `corrected_to` VARCHAR(190) NOT NULL DEFAULT '',
            PRIMARY KEY (`day`, `store_id`, `language_id`, `query_hash`),
            KEY `day_zero` (`day`, `zero_results`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** $found is the number of products shown; $correctedTo is the spelling that produced them. */
    public static function record($db, $storeId, $languageId, $query, $found, $correctedTo = '') {
        $text = mb_strtolower(SmartSearch::anonymize(trim((string)$query)), 'UTF-8');
        if ($text === '') {
            return;
        }
        $zero = (int)$found < 1 ? 1 : 0;
        $corrected = $correctedTo !== '' && !$zero ? 1 : 0;
        $db->query("INSERT INTO `" . DB_PREFIX . "codecart_search_query` SET day = CURDATE(), store_id = '" . (int)$storeId . "', language_id = '" . (int)$languageId . "', query_hash = '" . $db->escape(hash('sha256', $text)) . "', query_text = '" . $db->escape($text) . "', searches = 1, zero_results = '" . $zero . "', corrected = '" . $corrected . "', corrected_to = '" . $db->escape(mb_substr((string)$correctedTo, 0, 190, 'UTF-8')) . "' ON DUPLICATE KEY UPDATE searches = searches + 1, zero_results = zero_results + VALUES(zero_results), corrected = corrected + VALUES(corrected), corrected_to = IF(VALUES(corrected_to) <> '', VALUES(corrected_to), corrected_to)");
        if (random_int(1, 200) === 1) {
            $db->query("DELETE FROM `" . DB_PREFIX . "codecart_search_query` WHERE day < DATE_SUB(CURDATE(), INTERVAL " . self::RETENTION_DAYS . " DAY)");
        }
    }
}
