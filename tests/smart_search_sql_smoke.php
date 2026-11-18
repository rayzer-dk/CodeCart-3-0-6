<?php
/** SQL search regression without a database: php tests/smart_search_sql_smoke.php */
define('DB_PREFIX', 'oc_');
function utf8_strtolower($value) { return mb_strtolower((string)$value, 'UTF-8'); }
function utf8_strlen($value) { return mb_strlen((string)$value, 'UTF-8'); }
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SmartSearch.php';

class Model {
    public $db;
    public $config;
}

class SearchSqlDb {
    public $queries = array();

    public function escape($value) {
        return addslashes((string)$value);
    }

    public function query($sql) {
        $this->queries[] = $sql;
        return (object)array('rows' => array(), 'row' => array('total' => 0), 'num_rows' => 0);
    }
}

class SearchSqlConfig {
    public function get($key) {
        return 1;
    }
}

require_once dirname(__DIR__) . '/upload/catalog/model/catalog/product.php';

$model = new ModelCatalogProduct();
$model->db = new SearchSqlDb();
$model->config = new SearchSqlConfig();
$failures = array();
$check = function ($ok, $name) use (&$failures) {
    if (!$ok) {
        $failures[] = $name;
    }
};

$check(\CodeCart\Core\SmartSearch::identifierFragment('786') === '786', 'numeric code fragments');
$check(\CodeCart\Core\SmartSearch::identifierFragment('HM-786') === 'hm-786', 'hyphenated code fragments');
$check(\CodeCart\Core\SmartSearch::identifierFragment('tel') === '', 'ordinary words do not cause a code scan');
$check(\CodeCart\Core\SmartSearch::identifierFragment('1') === '', 'short values do not cause a code scan');
$check(\CodeCart\Core\SmartSearch::identifierFragment('128 GB') === '', 'phrases do not cause a code scan');
$check(\CodeCart\Core\SmartSearch::identifierFragment('%__') === '', 'wildcard-only input does not cause a code scan');

$method = new ReflectionMethod('ModelCatalogProduct', 'buildSearchSource');
$method->setAccessible(true);
$method->invoke($model, array('filter_name' => '786'));
$code_sql = implode("\n", $model->db->queries);
$check(strpos($code_sql, "p_f.model LIKE '%786%' ESCAPE '='") !== false, 'model substrings');
$check(strpos($code_sql, "p_f.mpn LIKE '%786%' ESCAPE '='") !== false, 'manufacturer code substrings');
$check(strpos($code_sql, "p_c.sku = '786'") !== false, 'existing exact code search preserved');

$model->db->queries = array();
$method->invoke($model, array('filter_name' => 'ab%_'));
$escaped_sql = implode("\n", $model->db->queries);
$check(strpos($escaped_sql, "pd_n.name LIKE '%ab=%=_%' ESCAPE '='") !== false, 'LIKE metacharacters are literal');
$check(strpos($escaped_sql, "p_f.model LIKE") === false, 'ordinary text does not trigger code scan');

$model->db->queries = array();
$model->getProducts(array('filter_name' => '786', 'sort' => 'p.sort_order', 'order' => 'ASC'));
$listing_sql = end($model->db->queries);
$check(strpos($listing_sql, 'ORDER BY CASE WHEN p.model') !== false, 'search relevance takes precedence');
$check(strpos($listing_sql, 'THEN 0 WHEN pd.name') !== false, 'exact code before name');
$check(strpos($listing_sql, "p.mpn LIKE '%786%' ESCAPE '='") !== false, 'partial code rank');
$check(strpos($listing_sql, 'END, p.sort_order ASC') !== false, 'stable secondary ordering');

$model->db->queries = array();
$model->getProducts(array('filter_name' => '786', 'sort' => 'pd.name', 'order' => 'ASC'));
$explicit_sort = end($model->db->queries);
$check(strpos($explicit_sort, 'ORDER BY LCASE(pd.name) ASC') !== false, 'explicit name sort preserved');
$check(strpos($explicit_sort, 'ORDER BY CASE WHEN p.model') === false, 'explicit sort is not overridden');

if ($failures) {
    fwrite(STDERR, 'FAIL: ' . implode('; ', $failures) . "\n");
    exit(1);
}
echo "PASS search SQL and ranking\n";
