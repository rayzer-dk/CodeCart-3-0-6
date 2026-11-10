<?php
/**
 * Static regression checks for OpenCart extension lists and blog UI contracts.
 * Run: php tests/blog_extension_ui_smoke.php
 */
$root = dirname(__DIR__) . '/upload/';
$failures = array();
$check = function ($condition, $message) use (&$failures) {
    if (!$condition) { $failures[] = $message; }
};
$types = array('advertise','analytics','captcha','currency','dashboard','feed','fraud','menu','module','payment','report','shipping','theme','total');
foreach ($types as $type) {
    $path = $root . 'admin/view/template/extension/extension/' . $type . '.twig';
    $src = file_get_contents($path);
    $check(strpos($src, 'column_install_date') !== false, $type . ': install date missing');
    $check(strpos($src, 'column_upload_date') === false, $type . ': obsolete upload date');
    $check(strpos($src, 'column_installed') === false, $type . ': obsolete installed column');
    $check(strpos($src, 'date_uploaded_label') === false, $type . ': upload timestamp rendered');
    $check(strpos($src, 'ccp-extension-status') !== false, $type . ': status badge missing');
}
$extension = file_get_contents($root . 'admin/model/setting/extension.php');
$check(strpos($extension, 'Upload date') !== false, 'Existing package upload compatibility unexpectedly removed');
$check(strpos($extension, "public function refreshInstalledDatesFromPackage") !== false, 'Missing package installation timestamp hook');
$check(strpos($extension, '// Never overwrite the original installation timestamp.') === false || strpos($extension, 'return;') !== false, 'Upgrade may rewrite install date');
foreach (array('catalog/controller/blog/category.php', 'catalog/controller/blog/latest.php', 'catalog/controller/blog/article.php',
    'catalog/controller/extension/module/blog_latest.php', 'catalog/controller/extension/module/blog_featured.php',
    'catalog/controller/extension/module/featured_article.php') as $path) {
    $src = file_get_contents($root . $path);
    foreach (array('date','reviews','views') as $field) {
        $check(strpos($src, 'blog_show_' . $field) !== false, $path . ': missing ' . $field . ' setting');
    }
}
foreach (array('codecart','default') as $theme) {
    foreach (array('article','category','latest') as $template) {
        $path = 'catalog/view/theme/' . $theme . '/template/blog/' . $template . '.twig';
        $src = file_get_contents($root . $path);
        $check(strpos($src, 'blog_show_date') !== false, $path . ': date not guarded');
        $check(strpos($src, 'blog_show_views') !== false, $path . ': view count not guarded');
        $check(strpos($src, 'blog_show_reviews') !== false, $path . ': reviews not guarded');
    }
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "Extension date and blog UI: PASS\n";
