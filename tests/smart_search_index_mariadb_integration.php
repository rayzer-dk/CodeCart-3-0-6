<?php
/** Real MariaDB coverage of the native attribute index and generation fallback. */
define('DB_PREFIX', 'si_');
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SmartSearch.php';
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SearchIndex.php';

use CodeCart\Core\SmartSearch;
use CodeCart\Core\SearchIndex;

class SearchIndexMariaDb {
    private $link;
    public function __construct($link) { $this->link = $link; }
    public function escape($value) { return $this->link->real_escape_string((string)$value); }
    public function query($sql) {
        $result = $this->link->query($sql);
        if ($result === false) {
            throw new RuntimeException($this->link->error . ': ' . $sql);
        }
        $rows = $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : array();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
        return (object)array('rows' => $rows, 'num_rows' => count($rows), 'row' => $rows ? $rows[0] : array());
    }
}

$link = new mysqli(getenv('QA_DB_HOST') ?: '127.0.0.1', getenv('QA_DB_USER') ?: 'root', getenv('QA_DB_PASS') ?: 'root', 'codecart_catalog_qa');
$link->set_charset('utf8mb4');
$db = new SearchIndexMariaDb($link);

$tables = array('si_codecart_search_term', 'si_codecart_search_index_state',
    'si_product', 'si_product_description', 'si_product_to_store', 'si_product_attribute', 'si_attribute_description');
try {
    foreach ($tables as $table) { $db->query('DROP TABLE IF EXISTS ' . $table); }
    $db->query('CREATE TABLE si_product (
        product_id INT PRIMARY KEY, model VARCHAR(64), sku VARCHAR(64), upc VARCHAR(64),
        ean VARCHAR(64), jan VARCHAR(64), isbn VARCHAR(64), mpn VARCHAR(64)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE si_product_to_store (product_id INT, store_id INT, PRIMARY KEY(product_id, store_id)) ENGINE=InnoDB');
    $db->query('CREATE TABLE si_product_description (
        product_id INT, language_id INT, name VARCHAR(255), tag VARCHAR(255),
        PRIMARY KEY(product_id,language_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE si_product_attribute (
        product_id INT, attribute_id INT, language_id INT, text TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query('CREATE TABLE si_attribute_description (
        attribute_id INT, language_id INT, name VARCHAR(255),
        PRIMARY KEY(attribute_id,language_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->query("INSERT INTO si_product VALUES
        (7,'PM-7','P-007','','','','',''),(9,'VM-9','V-009','','','','','')");
    $db->query("INSERT INTO si_product_to_store VALUES (7,1),(9,1)");
    $db->query("INSERT INTO si_product_description VALUES
        (7,2,'Насос для дому',''),(9,2,'Вентилятор','')");
    $db->query("INSERT INTO si_product_attribute VALUES
        (7,15,2,'220В'),(7,16,2,'1,5 кВт'),(9,15,2,'24В')");
    $db->query("INSERT INTO si_attribute_description VALUES
        (15,2,'Напруга'),(16,2,'Потужність')");

    SearchIndex::begin($db, 1, 2);
    SearchIndex::step($db, 1, 2, 1);
    if (SearchIndex::matchingSelect($db, 1, 2, 'насос 220') !== '') {
        throw new RuntimeException('Partial generation leaked into search.');
    }
    SearchIndex::step($db, 1, 2, 1);
    $last = SearchIndex::step($db, 1, 2, 1);
    if (!(int)$last['ready']) {
        throw new RuntimeException('Completed index generation not marked ready.');
    }

    $synonyms = SmartSearch::parseSynonyms('насос, помпа, pump');
    $sql = SearchIndex::matchingSelect($db, 1, 2, 'помпа 220 1.5 квт', $synonyms);
    $rows = $db->query($sql)->rows;
    if (count($rows) !== 1 || (int)$rows[0]['product_id'] !== 7) {
        throw new RuntimeException('Mixed title/attributes/synonyms matching failed.');
    }
    if (SearchIndex::matchingSelect($db, 2, 2, 'насос') !== '') {
        throw new RuntimeException('Non-indexed store must use fallback.');
    }

    SearchIndex::invalidateIfInstalled($db);
    if (SearchIndex::matchingSelect($db, 1, 2, 'насос') !== '') {
        throw new RuntimeException('Invalidated index still active.');
    }
    SearchIndex::begin($db, 1, 2);
    $state = SearchIndex::step($db, 1, 2, 100);
    if ((int)$state['generation'] !== 2 || !(int)$state['ready']) {
        throw new RuntimeException('Second index generation did not complete.');
    }
    SearchIndex::step($db, 1, 2, 100);
    $old = $db->query('SELECT COUNT(*) AS total FROM si_codecart_search_term WHERE generation = 1')->row;
    if ((int)$old['total'] !== 0) {
        throw new RuntimeException('Old index generation not cleaned.');
    }
    echo "PASS MariaDB search index, synonyms and invalidation\n";
} finally {
    foreach ($tables as $table) { $db->query('DROP TABLE IF EXISTS ' . $table); }
    $link->close();
}
