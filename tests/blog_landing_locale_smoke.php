<?php
/** PHP 7.4-8.5 regression: multilingual blog index SEO and legacy fallback. */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/BlogLocaleSeo.php';

class BlogLocaleConfigStub {
    private $values;
    public function __construct(array $values) { $this->values = $values; }
    public function get($key) { return array_key_exists($key, $this->values) ? $this->values[$key] : null; }
}

use CodeCart\Core\BlogLocaleSeo;

$issues = array();
$check = function ($condition, $message) use (&$issues) {
    if (!$condition) { $issues[] = $message; }
};

$legacy = array(
    'configblog_name' => 'Новости',
    'configblog_html_h1' => 'Новости и статьи',
    'configblog_meta_title' => 'Блог магазина',
    'configblog_meta_description' => 'Новости и полезные советы для покупателей'
);
$old = new BlogLocaleConfigStub($legacy);
foreach (array('name' => 'configblog_name', 'html_h1' => 'configblog_html_h1', 'meta_title' => 'configblog_meta_title', 'meta_description' => 'configblog_meta_description') as $field => $key) {
    $check(BlogLocaleSeo::resolve($old, 1, $field) === $legacy[$key], 'untouched legacy setting: ' . $field);
}
$check(BlogLocaleSeo::resolve(new BlogLocaleConfigStub(array()), 10, 'name', 'Blog') === 'Blog', 'language default fallback');
$check(BlogLocaleSeo::resolve($old, 3, 'unknown', 'safe') === 'safe', 'unknown field fallback');

$source = array(
    1 => array('name' => 'Новини', 'html_h1' => 'Статті та новини', 'meta_title' => 'Блог українською', 'meta_description' => 'Новини та поради для покупців'),
    2 => array('name' => '', 'meta_title' => ' ', 'meta_description' => 'Описание сайта'),
    3 => array('name' => 'News', 'meta_title' => 'Store blog', 'html_h1' => 'News and tips'),
    4 => array('name' => 'Preserved inactive language'),
    999 => array('name' => 'Disallowed language'),
    'bad' => array('name' => 'Not a language'),
);
$existing = array(4 => array('name' => 'Preserved inactive language', 'meta_title' => 'Old title'));
$post = array(
    1 => $source[1],
    2 => $source[2],
    3 => $source[3],
    999 => $source[999],
    4 => array('name' => 'Fake update to inactive language')
);
$clean = BlogLocaleSeo::sanitize($post, $existing, array(1, 2, 3));
$check(isset($clean[1], $clean[2], $clean[3]), 'three active locales saved');
$check(!isset($clean[999]), 'unapproved language excluded');
$check($clean[4]['name'] === 'Preserved inactive language', 'inactive language preserved');
$check($clean[4]['meta_title'] === 'Old title', 'inactive language SEO preserved');
$check(!isset($clean[1]['unexpected']), 'only whitelisted SEO properties stored');
$config = new BlogLocaleConfigStub(array_merge($legacy, array('configblog_localized' => $clean)));
$check(BlogLocaleSeo::resolve($config, 1, 'name') === 'Новини', 'Ukrainian name');
$check(BlogLocaleSeo::resolve($config, 1, 'html_h1') === 'Статті та новини', 'Ukrainian H1');
$check(BlogLocaleSeo::resolve($config, 1, 'meta_title') === 'Блог українською', 'Ukrainian title');
$check(BlogLocaleSeo::resolve($config, 1, 'meta_description') === 'Новини та поради для покупців', 'Ukrainian description');
$check(BlogLocaleSeo::resolve($config, 2, 'name') === 'Новости', 'empty Russian name inherits legacy');
$check(BlogLocaleSeo::resolve($config, 2, 'meta_title') === 'Блог магазина', 'empty Russian title inherits legacy');
$check(BlogLocaleSeo::resolve($config, 2, 'meta_description') === 'Описание сайта', 'Russian description overrides');
$check(BlogLocaleSeo::resolve($config, 3, 'name') === 'News', 'English name');
$check(BlogLocaleSeo::resolve($config, 3, 'meta_description') === $legacy['configblog_meta_description'], 'English unspecified field inherits legacy');
$check(BlogLocaleSeo::resolve($config, 99, 'name') === 'Новости', 'unconfigured language fallback');

$large = str_repeat('x', 2000);
$bounded = BlogLocaleSeo::sanitize(array(1 => array('name' => $large, 'meta_description' => $large)), array(), array(1));
$check(strlen($bounded[1]['name']) <= 255, 'name length bounded');
$check(strlen($bounded[1]['meta_description']) <= 1000, 'description length bounded');

$root = dirname(__DIR__) . '/upload/';
$routes = array(
    'catalog/controller/blog/latest.php',
    'catalog/controller/blog/article.php',
    'catalog/controller/blog/category.php',
    'catalog/controller/blog/menu.php'
);
foreach ($routes as $route) {
    $body = file_get_contents($root . $route);
    $check(strpos($body, 'BlogLocaleSeo::resolve') !== false, $route . ' uses localized title');
}
$latest = file_get_contents($root . 'catalog/controller/blog/latest.php');
$check(strpos($latest, "'description' => \$blog_meta_description") !== false, 'blog landing JSON-LD localized');
$check(strpos($latest, 'setDescription($blog_meta_description)') !== false, 'blog landing meta description localized');
$check(strpos($latest, "configblog_meta_keyword") !== false, 'legacy optional keyword preserved');

$twig = file_get_contents($root . 'admin/view/template/blog/setting.twig');
foreach (BlogLocaleSeo::fields() as $field) {
    $check(strpos($twig, '][' . $field . ']') !== false, 'admin input for ' . $field);
}
$controller = file_get_contents($root . 'admin/controller/blog/setting.php');
$check(strpos($controller, "BlogLocaleSeo::sanitize") !== false, 'controller saves only recognized fields');
$check(strpos($controller, "editSetting('configblog'") !== false, 'existing settings persistence unchanged');
foreach (array('uk-ua', 'ru-ru', 'en-gb') as $language) {
    $content = file_get_contents($root . 'admin/language/' . $language . '/blog/setting.php');
    $check(strpos($content, 'help_blog_language_seo') !== false, $language . ' localized interface');
}
if ($issues) {
    fwrite(STDERR, 'FAIL blog language SEO: ' . implode('; ', $issues) . "\n");
    exit(1);
}
echo "PASS blog landing SEO for Ukrainian, Russian, English, legacy inheritance, admin wiring\n";
