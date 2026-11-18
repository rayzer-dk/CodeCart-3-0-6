<?php
/** Native search index regression: php tests/smart_search_index_smoke.php */
define('DB_PREFIX', 'oc_');
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SmartSearch.php';
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SearchIndex.php';

use CodeCart\Core\SmartSearch;
use CodeCart\Core\SearchIndex;

class SearchIndexFakeDb {
    public $queries = array();
    public $state = array('generation' => 0, 'cursor' => 0, 'indexed' => 0, 'ready' => 0);

    public function escape($value) {
        return addslashes((string)$value);
    }

    private function result($rows) {
        return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows));
    }

    public function query($sql) {
        $this->queries[] = $sql;
        if (strpos($sql, 'SELECT generation, cursor, indexed, ready FROM oc_codecart_search_index_state') === 0) {
            return $this->result(array($this->state));
        }
        if (strpos($sql, 'INSERT INTO oc_codecart_search_index_state SET') === 0) {
            if (preg_match("/generation = '(\d+)'/", $sql, $matches)) {
                $this->state = array('generation' => (int)$matches[1], 'cursor' => 0, 'indexed' => 0, 'ready' => 0);
            }
        }
        if (strpos($sql, 'SELECT p2s.product_id FROM oc_product_to_store') === 0) {
            return $this->result((int)$this->state['cursor'] ? array() : array(array('product_id' => 7)));
        }
        if (strpos($sql, 'SELECT p.product_id, p.model, p.sku') === 0) {
            return $this->result(array(array(
                'product_id' => 7, 'name' => 'Насос для дому', 'tag' => '',
                'model' => 'PM-7', 'sku' => 'P-007', 'upc' => '', 'ean' => '', 'jan' => '', 'isbn' => '', 'mpn' => ''
            )));
        }
        if (strpos($sql, 'SELECT pa.product_id, pa.text') === 0) {
            return $this->result(array(
                array('product_id' => 7, 'text' => '220В', 'attribute_name' => 'Напруга'),
                array('product_id' => 7, 'text' => '1,5 кВт', 'attribute_name' => 'Потужність')
            ));
        }
        if (strpos($sql, 'UPDATE oc_codecart_search_index_state SET cursor =') === 0) {
            if (preg_match("/cursor = '(\d+)', indexed = '(\d+)', ready = '(\d+)'/", $sql, $m)) {
                $this->state['cursor'] = (int)$m[1];
                $this->state['indexed'] = (int)$m[2];
                $this->state['ready'] = (int)$m[3];
            }
        }
        if (strpos($sql, 'SELECT product_id FROM oc_codecart_search_term') === 0) {
            return $this->result(array());
        }
        if (strpos($sql, 'SELECT 1 AS found FROM INFORMATION_SCHEMA.TABLES') === 0) {
            return $this->result(array(array('found' => 1)));
        }
        return $this->result(array());
    }
}

$failures = array();
$check = function ($condition, $label) use (&$failures) {
    if (!$condition) {
        $failures[] = $label;
    }
};

$terms = SmartSearch::searchTerms('Насос 220В 1,5 кВт');
$check(in_array('насос', $terms, true), 'Unicode product name');
$check(in_array('220', $terms, true), 'numeric voltage');
$check(in_array('1.5', $terms, true), 'decimal normalization');
$check(in_array('квт', $terms, true), 'Cyrillic unit');
$check(SmartSearch::searchTerms('1') === array(), 'single-digit tokens are ignored');

$synonyms = SmartSearch::parseSynonyms("насос, помпа, pump\n# ignored\nстіл, стол\n");
$check(in_array('помпа', $synonyms['насос'], true), 'synonyms grouped for Ukrainian');
$check(in_array('насос', $synonyms['pump'], true), 'synonyms are symmetric');
$variants = SmartSearch::synonymWordGroups('насос 220', $synonyms);
$check(in_array(array('помпа', '220'), $variants, true), 'synonym substitution does not discard numeric terms');
$check(SmartSearch::synonymWordGroups('насос', array()) === array(), 'empty dictionary no expansion');

$db = new SearchIndexFakeDb();
$check(SearchIndex::matchingSelect($db, 2, 3, 'насос 220') === '', 'incomplete index is ignored');
SearchIndex::begin($db, 2, 3);
$check((int)$db->state['generation'] === 1, 'index generation starts from one');
$state = SearchIndex::step($db, 2, 3, 100);
$check((int)$state['indexed'] === 1 && (int)$state['ready'] === 1, 'one product is indexed and generation marked ready');
$sql = implode("\n", $db->queries);
$check(strpos($sql, "'насос'") !== false, 'product title token stored');
$check(strpos($sql, "'220'") !== false, 'attribute numeric token stored');
$check(strpos($sql, "'1.5'") !== false, 'attribute decimal token stored');
$check(strpos($sql, 'p2s.store_id = \'2\'') !== false, 'store isolation on batch');

$select = SearchIndex::matchingSelect($db, 2, 3, 'помпа 220', $synonyms);
$check(strpos($select, "st.store_id = '2'") !== false && strpos($select, "st.language_id = '3'") !== false, 'query scoped by store and language');
$check(strpos($select, "st.generation = '1'") !== false, 'query scoped by complete generation');
$check(strpos($select, "MAX(st.term IN ('помпа','насос','pump')) = 1") !== false, 'index supports synonyms');
$check(strpos($select, "MAX(st.term IN ('220')) = 1") !== false, 'index requires all word groups');

$complete = SearchIndex::step($db, 2, 3);
$check(!empty($complete['done']), 'bounded generation cleanup completes');
SearchIndex::invalidateIfInstalled($db);
$check(strpos(end($db->queries), 'SET ready = 0') !== false, 'mutations invalidate ready index');

if ($failures) {
    fwrite(STDERR, 'FAIL: ' . implode('; ', $failures) . "\n");
    exit(1);
}
echo "PASS native indexed search and synonyms\n";
