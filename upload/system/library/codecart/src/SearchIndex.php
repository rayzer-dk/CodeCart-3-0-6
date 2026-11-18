<?php
namespace CodeCart\Core;

/**
 * Optional native inverted index. Own tables do not change OpenCart catalog schemas.
 * Rebuild generations prevent incomplete indexes from affecting storefront results.
 */
final class SearchIndex {
    private const BATCH_MAX = 150;
    private const TERM_MAX = 160;

    public static function install($db) {
        $db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "codecart_search_index_state (
            store_id INT NOT NULL,
            language_id INT NOT NULL,
            generation INT UNSIGNED NOT NULL DEFAULT 0,
            cursor INT UNSIGNED NOT NULL DEFAULT 0,
            indexed INT UNSIGNED NOT NULL DEFAULT 0,
            ready TINYINT(1) NOT NULL DEFAULT 0,
            modified_at DATETIME NOT NULL,
            PRIMARY KEY (store_id, language_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "codecart_search_term (
            store_id INT NOT NULL,
            language_id INT NOT NULL,
            generation INT UNSIGNED NOT NULL,
            product_id INT NOT NULL,
            term VARCHAR(64) NOT NULL,
            PRIMARY KEY (store_id, language_id, generation, product_id, term),
            KEY search_term_lookup (store_id, language_id, generation, term, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function state($db, $storeId, $languageId) {
        $query = $db->query("SELECT generation, cursor, indexed, ready FROM " . DB_PREFIX . "codecart_search_index_state WHERE store_id = '" . (int)$storeId . "' AND language_id = '" . (int)$languageId . "' LIMIT 1");
        return $query->num_rows ? $query->row : array('generation' => 0, 'cursor' => 0, 'indexed' => 0, 'ready' => 0);
    }

    public static function begin($db, $storeId, $languageId) {
        self::install($db);
        $old = self::state($db, $storeId, $languageId);
        $generation = (int)$old['generation'] + 1;
        $db->query("INSERT INTO " . DB_PREFIX . "codecart_search_index_state SET store_id = '" . (int)$storeId . "', language_id = '" . (int)$languageId . "', generation = '" . $generation . "', cursor = 0, indexed = 0, ready = 0, modified_at = NOW() ON DUPLICATE KEY UPDATE generation = VALUES(generation), cursor = 0, indexed = 0, ready = 0, modified_at = NOW()");
        return self::state($db, $storeId, $languageId);
    }

    /** Rebuild one bounded page, or clean up a bounded page of old generations. */
    public static function step($db, $storeId, $languageId, $limit = 100) {
        $storeId = (int)$storeId;
        $languageId = (int)$languageId;
        $limit = max(1, min(self::BATCH_MAX, (int)$limit));
        $state = self::state($db, $storeId, $languageId);
        $generation = (int)$state['generation'];
        if (!$generation) {
            throw new \RuntimeException('Start search indexing before processing batches.');
        }

        if ((int)$state['ready']) {
            $db->query("DELETE FROM " . DB_PREFIX . "codecart_search_term WHERE store_id = '" . $storeId . "' AND language_id = '" . $languageId . "' AND generation <> '" . $generation . "' LIMIT 2000");
            $remaining = $db->query("SELECT product_id FROM " . DB_PREFIX . "codecart_search_term WHERE store_id = '" . $storeId . "' AND language_id = '" . $languageId . "' AND generation <> '" . $generation . "' LIMIT 1");
            $state['done'] = !$remaining->num_rows;
            return $state;
        }

        $idsQuery = $db->query("SELECT p2s.product_id FROM " . DB_PREFIX . "product_to_store p2s WHERE p2s.store_id = '" . $storeId . "' AND p2s.product_id > '" . (int)$state['cursor'] . "' ORDER BY p2s.product_id ASC LIMIT " . $limit);
        $ids = array();
        foreach ($idsQuery->rows as $row) {
            $ids[] = (int)$row['product_id'];
        }

        if ($ids) {
            $idList = implode(',', $ids);
            $products = $db->query("SELECT p.product_id, p.model, p.sku, p.upc, p.ean, p.jan, p.isbn, p.mpn, pd.name, pd.tag FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $languageId . "') WHERE p.product_id IN (" . $idList . ")");
            $attributes = $db->query("SELECT pa.product_id, pa.text, ad.name AS attribute_name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (ad.attribute_id = pa.attribute_id AND ad.language_id = '" . $languageId . "') WHERE pa.product_id IN (" . $idList . ") AND pa.language_id = '" . $languageId . "'");

            $terms = array();
            foreach ($products->rows as $product) {
                $id = (int)$product['product_id'];
                $combined = array($product['name'], $product['tag']);
                foreach (array('model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn') as $code) {
                    $combined[] = $product[$code];
                }
                foreach ($combined as $value) {
                    foreach (SmartSearch::searchTerms($value) as $term) {
                        if (count($terms[$id] ?? array()) >= self::TERM_MAX) {
                            break 2;
                        }
                        $terms[$id][$term] = $term;
                    }
                }
            }
            foreach ($attributes->rows as $attribute) {
                $id = (int)$attribute['product_id'];
                foreach (array('attribute_name', 'text') as $field) {
                    foreach (SmartSearch::searchTerms($attribute[$field] ?? '') as $term) {
                        if (count($terms[$id] ?? array()) >= self::TERM_MAX) {
                            break 2;
                        }
                        $terms[$id][$term] = $term;
                    }
                }
            }

            $values = array();
            foreach ($terms as $id => $words) {
                foreach ($words as $word) {
                    $values[] = "('" . $storeId . "','" . $languageId . "','" . $generation . "','" . (int)$id . "','" . $db->escape($word) . "')";
                    if (count($values) >= 250) {
                        $db->query("INSERT IGNORE INTO " . DB_PREFIX . "codecart_search_term (store_id, language_id, generation, product_id, term) VALUES " . implode(',', $values));
                        $values = array();
                    }
                }
            }
            if ($values) {
                $db->query("INSERT IGNORE INTO " . DB_PREFIX . "codecart_search_term (store_id, language_id, generation, product_id, term) VALUES " . implode(',', $values));
            }
            $state['cursor'] = end($ids);
            $state['indexed'] = (int)$state['indexed'] + count($ids);
        }

        $ready = count($ids) < $limit ? 1 : 0;
        $db->query("UPDATE " . DB_PREFIX . "codecart_search_index_state SET cursor = '" . (int)$state['cursor'] . "', indexed = '" . (int)$state['indexed'] . "', ready = '" . $ready . "', modified_at = NOW() WHERE store_id = '" . $storeId . "' AND language_id = '" . $languageId . "' AND generation = '" . $generation . "'");
        $state['ready'] = $ready;
        $state['done'] = false;
        return $state;
    }

    /** Only a completed generation is eligible, otherwise native search stays authoritative. */
    public static function matchingSelect($db, $storeId, $languageId, $query, array $synonyms = array()) {
        $terms = SmartSearch::searchTerms($query);
        if (!$terms || count($terms) > 6) {
            return '';
        }
        $state = self::state($db, $storeId, $languageId);
        if (!(int)$state['ready']) {
            return '';
        }

        $conditions = array();
        $all = array();
        foreach ($terms as $term) {
            $choices = array($term => $term);
            if (isset($synonyms[$term])) {
                foreach (array_slice($synonyms[$term], 0, 8) as $equivalent) {
                    $choices[$equivalent] = $equivalent;
                }
            }
            $sqlChoices = array();
            foreach ($choices as $choice) {
                $sqlChoices[] = "'" . $db->escape($choice) . "'";
                $all[$choice] = "'" . $db->escape($choice) . "'";
            }
            $conditions[] = "MAX(st.term IN (" . implode(',', $sqlChoices) . ")) = 1";
        }
        return "SELECT st.product_id FROM " . DB_PREFIX . "codecart_search_term st WHERE st.store_id = '" . (int)$storeId . "' AND st.language_id = '" . (int)$languageId . "' AND st.generation = '" . (int)$state['generation'] . "' AND st.term IN (" . implode(',', array_values($all)) . ") GROUP BY st.product_id HAVING " . implode(' AND ', $conditions);
    }

    /** Native installs without the optional table must keep product writes working. */
    public static function invalidateIfInstalled($db) {
        static $available = null;
        if ($available === null) {
            $query = $db->query("SELECT 1 AS found FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $db->escape(DB_PREFIX . "codecart_search_index_state") . "' LIMIT 1");
            $available = (bool)$query->num_rows;
        }
        if ($available) {
            self::invalidate($db);
        }
    }

    /** Pause indexed matching after native product mutations; rebuild restores it. */
    public static function invalidate($db) {
        $db->query("UPDATE " . DB_PREFIX . "codecart_search_index_state SET ready = 0, modified_at = NOW() WHERE ready = 1");
    }
}
