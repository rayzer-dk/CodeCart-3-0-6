<?php
declare(strict_types=1);

// Real MVC-L writers/readers; the SQL sink isolates persistence from a live store.
$root = dirname(__DIR__);
define('DB_PREFIX', 'qa_');
define('DIR_SYSTEM', $root . '/upload/system/');
require DIR_SYSTEM . 'engine/registry.php';
require DIR_SYSTEM . 'engine/model.php';
require DIR_SYSTEM . 'engine/controller.php';
require DIR_SYSTEM . 'library/config.php';
require DIR_SYSTEM . 'library/request.php';
require DIR_SYSTEM . 'helper/utf8.php';
require DIR_SYSTEM . 'helper/general.php';
spl_autoload_register(static function ($class) {
    $prefix = 'CodeCart\\Core\\';
    if (strpos($class, $prefix) === 0) { require DIR_SYSTEM . 'library/codecart/src/' . substr($class, strlen($prefix)) . '.php'; }
});
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});
$checks = 0;
function richCheck($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
final class RichHtmlSqlSink {
    public array $rows = array();
    public function beginTransaction() {}
    public function commit() {}
    public function rollback() {}
    public function escape($value) { return addslashes((string)$value); }
    public function getLastId() { return 7; }
    public function query($sql) {
        $rows = array();
        $tables = '(product_description|category_description|information_description|article_description|codecart_form_description|product_extra_tab)';
        if (strpos($sql, 'INSERT INTO `qa_setting`') === 0) {
            preg_match_all("/`?(code|key|value|serialized)`?\\s*=\\s*'((?:\\\\.|[^'])*)'/", $sql, $fields, PREG_SET_ORDER);
            $row = array(); foreach ($fields as $field) { $row[$field[1]] = stripslashes($field[2]); }
            $this->rows['setting'][$row['key']] = $row;
        } elseif (strpos($sql, 'SELECT * FROM qa_setting') === 0) {
            $rows = array_values($this->rows['setting'] ?? array());
        } elseif (preg_match('/^INSERT INTO qa_' . $tables . ' SET /', $sql, $table)) {
            preg_match_all("/`?([a-z0-9_]+)`?\\s*=\\s*'((?:\\\\.|[^'])*)'/", $sql, $fields, PREG_SET_ORDER);
            $row = array();
            foreach ($fields as $field) { $row[$field[1]] = stripslashes($field[2]); }
            $this->rows[$table[1]][(int)$row['language_id']] = $row;
        } elseif (preg_match('/^DELETE FROM qa_' . $tables . ' /', $sql, $table)) {
            $this->rows[$table[1]] = array();
        } elseif (preg_match('/^SELECT .* FROM qa_' . $tables . ' /', $sql, $table)) {
            $rows = array_values($this->rows[$table[1]] ?? array());
        }
        return (object)array('row' => $rows[0] ?? array(), 'rows' => $rows, 'num_rows' => count($rows));
    }
}
$registry = new Registry();
$db = new RichHtmlSqlSink();
$registry->set('db', $db);
$registry->set('config', new Config());
$registry->set('cache', new class {
    public function delete($key) {}
    public function deleteTag($tag, $fallback = '') {}
});
$data = array_fill_keys(array('model','sku','upc','ean','jan','isbn','mpn','location','quantity','minimum','subtract','stock_status_id','manufacturer_id','shipping','price','points','weight','weight_class_id','length','width','height','length_class_id','status','noindex','tax_class_id','sort_order','parent_id','column','bottom'), '1');
$data['date_available'] = '2026-01-01';
$data['tax_display_mode'] = 'inherit';
$request = new Request();
$input = '<h2>Опис</h2><p onclick="void(0)">A &amp; B, &lt;b&gt;literal&lt;/b&gt;</p><a href="javascript:void(0)">Link</a><iframe src="https://www.youtube.com/embed/example" allowfullscreen></iframe>';
$raster = imagecreatetruecolor(2, 2);
ob_start(); imagewebp($raster); $webpUri = 'data:image/webp;base64,' . base64_encode(ob_get_clean());
ob_start(); imageavif($raster); $avifUri = 'data:image/avif;base64,' . base64_encode(ob_get_clean());
imagedestroy($raster);
$input .= '<img src="' . $webpUri . '" alt="WebP"><img src="' . $avifUri . '" alt="AVIF">';
$description = array_fill_keys(array('name','title','tag','meta_title','meta_h1','meta_description','meta_keyword','submit_text','success_text'), 'QA');
$case = $argv[1] ?? 'product';
if (strpos($case, 'theme-') === 0) {
    $theme = substr($case, 6);
    if (!in_array($theme, array('default', 'codecart'), true)) { throw new InvalidArgumentException('Unknown theme'); }
    require $root . '/upload/admin/controller/extension/theme/' . $theme . '.php';
    require $root . '/upload/admin/model/setting/setting.php';
    $prefix = 'theme_' . $theme;
    $request->server['REQUEST_METHOD'] = 'POST'; $request->get = array('store_id' => 0);
    $post = array($prefix . '_product_limit' => 20, $prefix . '_product_description_length' => 100);
    foreach (array('category','manufacturer','thumb','popup','product','additional','related','compare','wishlist','cart','location') as $image) {
        foreach (array('width','height') as $dimension) { $post[$prefix . '_image_' . $image . '_' . $dimension] = 100; }
    }
    $post[$prefix . '_product_extra_tab_content'] = array(1 => $input, 2 => $input, 3 => $input);
    $registry->set('request', $request);
    $registry->get('config')->set('config_theme', 'other');
    $registry->set('model_setting_setting', new ModelSettingSetting($registry));
    $registry->set('load', new class { public function language($route) {} public function model($route) {} });
    $registry->set('language', new class { public function get($key) { return $key; } });
    $registry->set('document', new class { public function setTitle($title) {} });
    $registry->set('user', new class { public function hasPermission($action, $route) { return true; } });
    $registry->set('url', new class { public function link($route, $query, $secure) { return $route; } });
    $registry->set('session', (object)array('data' => array('user_token' => 'qa')));
    class RichHtmlSaved extends RuntimeException {}
    $registry->set('response', new class { public function redirect($url) { throw new RichHtmlSaved(); } });
    $class = 'ControllerExtensionTheme' . ucfirst($theme);
    $saved = null;
    for ($cycle = 0; $cycle < 2; $cycle++) {
        $request->post = $request->clean($post);
        try { (new $class($registry))->index(); } catch (RichHtmlSaved $e) {}
        $settings = $registry->get('model_setting_setting')->getSetting($prefix);
        $contents = $settings[$prefix . '_product_extra_tab_content'];
        foreach ($contents as $languageId => $html) {
            $rendered = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
            richCheck(strpos($rendered, '<h2>Опис</h2>') !== false && strpos($rendered, 'onclick') === false && strpos($rendered, 'javascript:') === false, $case . ' saves safe localized extra-tab HTML');
            richCheck(strpos($rendered, $webpUri) !== false && strpos($rendered, $avifUri) !== false, $case . ' preserves inline WebP/AVIF on settings save/reopen');
            $post[$prefix . '_product_extra_tab_content'][$languageId] = $rendered;
        }
        if ($saved !== null) { richCheck($contents === $saved, $case . ' settings edit/save/reopen is stable'); }
        $saved = $contents;
    }
    echo 'PASS: rich HTML persistence ' . $case . ' (' . $checks . ' checks, PHP ' . PHP_VERSION . ')' . PHP_EOL;
    exit(0);
}
if ($case === 'form') {
    $route = 'design/form'; $class = 'ModelDesignForm'; $entity = 'Form'; $key = 'form_description'; $table = 'codecart_form_description'; $encoded = false;
    $data['name'] = 'QA'; $data['recipient'] = 'qa@example.invalid';
} else {
    $route = $case === 'article' ? 'blog/article' : 'catalog/' . $case;
    $entity = ucfirst($case); $class = $case === 'article' ? 'ModelBlogArticle' : 'ModelCatalog' . $entity;
    $key = $case . '_description'; $table = $key; $encoded = true;
}
require $root . '/upload/admin/model/' . $route . '.php';
$model = new $class($registry);
$description['description'] = $input;
$description['fields_json'] = '[]';
$data[$key] = array(1 => $description, 2 => $description, 3 => $description);
if ($case === 'product') { $data['product_extra_tab_mode'] = 1; $data['product_extra_tab'] = array(1 => array('title' => 'QA', 'content' => $input)); }
$model->{'add' . $entity}($request->clean($data));
$first = $model->{'get' . $entity . 'Descriptions'}(7);
foreach (array(1, 2, 3) as $languageId) {
    $html = $first[$languageId]['description'];
    $rendered = $encoded ? html_entity_decode($html, ENT_QUOTES, 'UTF-8') : $html;
    richCheck(strpos($rendered, '<h2>Опис</h2>') !== false && strpos($rendered, 'A &amp; B') !== false, $case . ' preserves localized rich text on add/reopen');
    richCheck(strpos($rendered, 'onclick') === false && strpos($rendered, 'javascript:') === false && strpos($rendered, 'youtube.com/embed/example') !== false, $case . ' removes active attributes/URLs and preserves trusted media');
    richCheck(strpos($rendered, $webpUri) !== false && strpos($rendered, $avifUri) !== false, $case . ' preserves inline WebP/AVIF on add/reopen');
    $data[$key][$languageId]['description'] = $rendered;
}
if ($case === 'product') {
    $extra = $model->getProductExtraTab(7);
    $row = $db->rows['product_extra_tab'][1];
    $html = html_entity_decode($row['content'], ENT_QUOTES, 'UTF-8');
    richCheck(strpos($html, '<h2>Опис</h2>') !== false && strpos($html, 'onclick') === false, 'product extra-tab content follows native entity contract');
    richCheck(!empty($extra), 'product extra tab reopens through its public reader');
    $data['product_extra_tab'][1]['content'] = $html;
}
$model->{'edit' . $entity}(7, $request->clean($data));
$again = $model->{'get' . $entity . 'Descriptions'}(7);
foreach (array(1, 2, 3) as $languageId) { richCheck($again[$languageId]['description'] === $first[$languageId]['description'], $case . ' normal edit/save/reopen is stable'); }
echo 'PASS: rich HTML persistence ' . $case . ' (' . $checks . ' checks, PHP ' . PHP_VERSION . ')' . PHP_EOL;
