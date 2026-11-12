<?php
/** Newsletter header and button colours: php tests/newsletter_brand_smoke.php */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/NewsletterEmail.php';

use CodeCart\Core\NewsletterEmail;

$failures = array();
$check = function ($ok, $name) use (&$failures) { if (!$ok) { $failures[] = $name; } };

$check(NewsletterEmail::color('#FFE066', '#000000') === '#ffe066', 'colour is normalised to lower case');
foreach (array('red', '#fff', '#12345g', "#112233;x", 'url(x)', '', null) as $bad) {
    $check(NewsletterEmail::color($bad, '#102d56') === '#102d56', 'invalid colour falls back: ' . var_export($bad, true));
}

$values = array('unsubscribe_url' => 'https://shop.test/u');
$brand = array('name' => 'Shop', 'url' => 'https://shop.test/', 'locale' => 'en');
$default = NewsletterEmail::render('s', '<p>x</p>', $values, $brand);
$check(strpos($default, 'bgcolor="#102d56"') !== false && strpos($default, 'bgcolor="#0969da"') !== false, 'default colours');

$custom = NewsletterEmail::render('s', '<p>x</p>', $values, $brand + array('header_color' => '#ffe066', 'button_color' => '#e03131'));
$check(strpos($custom, 'bgcolor="#ffe066"') !== false && strpos($custom, 'background:#ffe066') !== false, 'custom header colour');
$check(strpos($custom, 'bgcolor="#e03131"') !== false, 'custom button colour');
$check(strpos($custom, 'color:#172b4d;">Shop') !== false, 'dark text on a light header');

$hostile = NewsletterEmail::render('s', '<p>x</p>', $values, $brand + array('header_color' => '#fff;background:url(x)'));
$check(strpos($hostile, 'url(x)') === false && strpos($hostile, 'bgcolor="#102d56"') !== false, 'hostile colour never reaches the markup');

if ($failures) {
    fwrite(STDERR, "FAIL: " . implode('; ', $failures) . "\n");
    exit(1);
}
echo "PASS newsletter brand colours\n";
