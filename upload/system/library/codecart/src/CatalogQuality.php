<?php
namespace CodeCart\Core;

/**
 * Admin-only, read-only completeness preview for at most 40 visible catalog rows.
 * No catalog-wide scans, schema changes, scheduled tasks or persistent quality cache.
 */
final class CatalogQuality {
    private const LIMIT = 40;

    private static function specs() {
        return array(
            'product' => array('table' => 'product', 'id' => 'product_id', 'description' => 'product_description', 'title' => 'name', 'base' => array('image', 'model', 'price'), 'relation' => 'product_to_category', 'relation_id' => 'category_id', 'count' => ''),
            'category' => array('table' => 'category', 'id' => 'category_id', 'description' => 'category_description', 'title' => 'name', 'base' => array('image'), 'relation' => '', 'relation_id' => '', 'count' => 'product_to_category'),
            'manufacturer' => array('table' => 'manufacturer', 'id' => 'manufacturer_id', 'description' => 'manufacturer_description', 'title' => '', 'base' => array('name', 'image'), 'relation' => '', 'relation_id' => '', 'count' => ''),
            'information' => array('table' => 'information', 'id' => 'information_id', 'description' => 'information_description', 'title' => 'title', 'base' => array(), 'relation' => '', 'relation_id' => '', 'count' => ''),
            'article' => array('table' => 'article', 'id' => 'article_id', 'description' => 'article_description', 'title' => 'name', 'base' => array('image'), 'relation' => 'article_to_blog_category', 'relation_id' => 'blog_category_id', 'count' => ''),
            'blog_category' => array('table' => 'blog_category', 'id' => 'blog_category_id', 'description' => 'blog_category_description', 'title' => 'name', 'base' => array('image'), 'relation' => '', 'relation_id' => '', 'count' => 'article_to_blog_category')
        );
    }

    public static function types() {
        return array_keys(self::specs());
    }

    public static function normalizeIds($raw) {
        if (!is_string($raw) || strlen($raw) > 400 || !preg_match('/^[0-9]+(?:,[0-9]+){0,39}$/D', $raw)) {
            return array();
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));
        return array_slice($ids, 0, self::LIMIT);
    }

    private static function languageRows($db) {
        $rows = $db->query('SELECT language_id, name FROM ' . DB_PREFIX . 'language WHERE status = 1 ORDER BY sort_order, language_id')->rows;
        return array_slice($rows, 0, 20);
    }

    /**
     * All SQL identifiers are hardcoded in specs(). WHERE clauses contain only
     * bounded integer IDs, and each query uses existing primary or lookup indexes.
     */
    public static function inspect($db, $type, array $ids, array $languages = array(), callable $label = null) {
        $specs = self::specs();
        if (!isset($specs[$type])) {
            throw new \InvalidArgumentException('Unknown quality entity.');
        }
        $spec = $specs[$type];
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), 0, self::LIMIT);
        if (!$ids) {
            return array();
        }
        $idList = implode(',', $ids);
        if (!$languages) {
            $languages = self::languageRows($db);
        }
        $languageNames = array();
        foreach ($languages as $row) {
            $languageId = (int)$row['language_id'];
            if ($languageId > 0 && count($languageNames) < 20) {
                $languageNames[$languageId] = (string)$row['name'];
            }
        }
        $languageIds = array_keys($languageNames);
        $descId = $spec['id'];
        $baseFields = array_unique(array_merge(array($descId), $spec['base']));
        $baseSql = 'SELECT ' . implode(',', $baseFields) . ' FROM ' . DB_PREFIX . $spec['table'] . ' WHERE ' . $descId . ' IN (' . $idList . ')';
        $base = array();
        foreach ($db->query($baseSql)->rows as $row) {
            $base[(int)$row[$descId]] = $row;
        }
        if (!$base) {
            return array();
        }

        // Compact DB-side flags; never transfer full HTML descriptions in JSON.
        $descriptions = array();
        if ($languageIds) {
            $fields = $spec['title'] !== '' ? array($spec['title']) : array();
            $fields = array_merge($fields, array('description', 'meta_title', 'meta_description'));
            $checks = array();
            foreach ($fields as $field) {
                if ($field === 'description') {
                    // Bounded sample; evaluate meaningful visible text in PHP, not markup length.
                    $checks[] = "LEFT(d.description, 8192) AS description_sample";
                } else {
                    $checks[] = "IF(CHAR_LENGTH(TRIM(d." . $field . ")) > 0,1,0) AS " . $field;
                }
            }
            $sql = "SELECT d." . $descId . ", d.language_id, " . implode(', ', $checks)
                . " FROM " . DB_PREFIX . $spec['description'] . " d WHERE d." . $descId . " IN (" . $idList
                . ") AND d.language_id IN (" . implode(',', $languageIds) . ")";
            foreach ($db->query($sql)->rows as $row) {
                $descriptions[(int)$row[$descId]][(int)$row['language_id']] = $row;
            }
        }

        // Store assignments (including additional stores) determine required SEO URLs.
        $stores = array();
        $toStore = DB_PREFIX . $spec['table'] . '_to_store';
        foreach ($db->query('SELECT ' . $descId . ', store_id FROM ' . $toStore . ' WHERE ' . $descId . ' IN (' . $idList . ')')->rows as $row) {
            $stores[(int)$row[$descId]][(int)$row['store_id']] = true;
        }

        $queries = array();
        foreach (array_keys($base) as $id) {
            $queries[] = "'" . $db->escape($descId . '=' . $id) . "'";
        }
        $seo = array();
        $sql = "SELECT query, store_id, language_id FROM " . DB_PREFIX . "seo_url WHERE query IN ("
            . implode(',', $queries) . ") AND keyword <> ''";
        foreach ($db->query($sql)->rows as $row) {
            $parts = explode('=', (string)$row['query'], 2);
            if (count($parts) === 2 && $parts[0] === $descId) {
                $seo[(int)$parts[1]][(int)$row['store_id']][(int)$row['language_id']] = true;
            }
        }

        $attached = array();
        if ($spec['relation'] !== '') {
            $sql = 'SELECT ' . $descId . ', COUNT(*) AS total FROM ' . DB_PREFIX . $spec['relation']
                . ' WHERE ' . $descId . ' IN (' . $idList . ') GROUP BY ' . $descId;
            foreach ($db->query($sql)->rows as $row) {
                $attached[(int)$row[$descId]] = (int)$row['total'];
            }
        }

        $counts = array();
        if ($spec['count'] !== '') {
            $sql = 'SELECT ' . $descId . ', COUNT(*) AS total FROM ' . DB_PREFIX . $spec['count']
                . ' WHERE ' . $descId . ' IN (' . $idList . ') GROUP BY ' . $descId;
            foreach ($db->query($sql)->rows as $row) {
                $counts[(int)$row[$descId]] = (int)$row['total'];
            }
        }

        $translate = $label ?: function ($key) { return $key; };
        $items = array();
        foreach ($base as $id => $row) {
            $missing = array();
            $checks = 0;
            $passed = 0;
            $hasUsefulDescription = false;
            $hasMeaningfulTitle = false;
            $add = function ($key, $present, $locale = '') use (&$missing, &$checks, &$passed, $translate) {
                // Product/category information matters more than metadata or technical defaults.
                $weight = $key === 'description' ? 5 : ($key === 'name' || $key === 'title' ? 2 : 1);
                $checks += $weight;
                if ($present) {
                    $passed += $weight;
                    return;
                }
                $name = (string)$translate($key);
                $missing[] = $locale === '' ? $name : $locale . ': ' . $name;
            };

            foreach ($spec['base'] as $field) {
                if ($field === 'price') {
                    // Zero-priced products may be intentional; this is informational.
                    $add('price', isset($row[$field]) && (float)$row[$field] > 0);
                } elseif ($field === 'image') {
                    $image = trim((string)($row[$field] ?? ''));
                    $add('image', $image !== '' && !preg_match('#(?:^|/)no_image\.(?:png|jpe?g|webp|gif|svg)$#i', $image));
                } else {
                    $add($field, trim((string)($row[$field] ?? '')) !== '');
                }
            }
            if ($spec['relation'] !== '') {
                $add('assignment', !empty($attached[$id]));
            }
            $assigned = array_keys($stores[$id] ?? array());
            $add('store', (bool)$assigned);
            if (!$assigned) {
                $assigned = array(0);
            }
            foreach ($languageNames as $languageId => $lang) {
                $fields = $spec['title'] !== '' ? array($spec['title']) : array();
                $fields = array_merge($fields, array('description', 'meta_title', 'meta_description'));
                foreach ($fields as $field) {
                    if ($field === 'description') {
                        $html = (string)($descriptions[$id][$languageId]['description_sample'] ?? '');
                        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $plain = preg_replace('/[\\s\\x{00A0}]+/u', ' ', $plain);
                        $plain = trim((string)$plain);
                        $length = function_exists('mb_strlen') ? mb_strlen($plain, 'UTF-8') : strlen($plain);
                        $present = $length >= 80;
                        $hasUsefulDescription = $hasUsefulDescription || $present;
                    } else {
                        $present = !empty($descriptions[$id][$languageId][$field]);
                        if (($field === 'name' || $field === 'title') && $present) {
                            $hasMeaningfulTitle = true;
                        }
                    }
                    $add($field, $present, $lang);
                }
                foreach ($assigned as $storeId) {
                    $hasUrl = !empty($seo[$id][$storeId][$languageId]) || !empty($seo[$id][$storeId][0]);
                    $add('seo_url', $hasUrl, $lang . ' / #' . $storeId);
                }
            }
            $percent = $checks > 0 ? (int)round($passed * 100 / $checks) : 0;
            // SEO fields alone must never make an empty content page look complete.
            if (!$hasUsefulDescription) {
                $percent = min($percent, 39);
            }
            if (!$hasMeaningfulTitle && $spec['title'] !== '') {
                $percent = min($percent, 15);
            }
            $items[$id] = array(
                'percent' => $percent,
                'complete' => $passed,
                'total' => $checks,
                'missing' => array_slice($missing, 0, 28)
            );
            if ($spec['count'] !== '') {
                $items[$id]['count'] = $counts[$id] ?? 0;
            }
        }
        return $items;
    }

    public static function respond($registry, $type) {
        $request = $registry->get('request');
        $response = $registry->get('response');
        $session = $registry->get('session');
        $user = $registry->get('user');
        $route = $type === 'article' || $type === 'blog_category'
            ? 'blog/' . ($type === 'article' ? 'article' : 'category')
            : 'catalog/' . $type;
        $token = (string)($request->get['user_token'] ?? '');
        $expected = (string)($session->data['user_token'] ?? '');
        $response->addHeader('Content-Type: application/json; charset=utf-8');
        $response->addHeader('X-Content-Type-Options: nosniff');
        if ($token === '' || $expected === '' || !hash_equals($expected, $token)
            || !$user->hasPermission('access', $route)
            || ($request->server['REQUEST_METHOD'] ?? '') !== 'GET') {
            $response->setStatusCode(403);
            $response->setOutput('{"error":"permission"}');
            return;
        }
        $ids = self::normalizeIds($request->get['ids'] ?? '');
        if (!$ids) {
            $response->setStatusCode(400);
            $response->setOutput('{"error":"invalid_ids"}');
            return;
        }
        try {
            $language = $registry->get('language');
            $language->load('catalog/quality');
            $items = self::inspect($registry->get('db'), $type, $ids, array(), function ($key) use ($language) {
                return $language->get('quality_' . $key);
            });
            $response->setOutput(json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $error) {
            $registry->get('log')->write('Catalog quality (read-only): ' . $error->getMessage());
            $response->setStatusCode(500);
            $response->setOutput('{"error":"unavailable"}');
        }
    }
}
