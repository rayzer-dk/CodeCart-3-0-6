<?php
/** E-mailed unsubscribe and GDPR links only open a confirmation page: php tests/confirmation_pages_smoke.php */
$root = dirname(__DIR__) . '/upload/';
$read = function ($path) use ($root) { return (string)file_get_contents($root . $path); };
$failures = array();

$newsletter = $read('catalog/controller/account/newsletter.php');
$start = strpos($newsletter, 'public function unsubscribe()');
$method = $start === false ? '' : substr($newsletter, $start);
$write = strpos($method, "INSERT INTO `\" . DB_PREFIX . \"mail_suppression`");
$gate = strpos($method, '$known && $confirmed');
if ($method === '' || $write === false || $gate === false || $gate > $write) {
    $failures[] = 'The unsubscribe write must sit behind the POST confirmation.';
}

$gdpr = $read('catalog/controller/account/gdpr.php');
$confirm = strpos($gdpr, 'public function confirm()');
$post = strpos($gdpr, "!=='POST'", $confirm === false ? 0 : $confirm);
$apply = strpos($gdpr, '->confirm($token)', $confirm === false ? 0 : $confirm);
if ($confirm === false || $post === false || $apply === false || $post > $apply) {
    $failures[] = 'GDPR confirm() must show a page on GET and confirm only on POST.';
}

foreach (array('codecart', 'default') as $theme) {
    foreach (array('account/gdpr_confirm.twig', 'account/newsletter_unsubscribe.twig') as $view) {
        $file = 'catalog/view/theme/' . $theme . '/template/' . $view;
        if (strpos($read($file), 'method="post"') === false) { $failures[] = $file . ' needs the confirmation form.'; }
    }
}
foreach (array('en-gb', 'ru-ru', 'uk-ua') as $code) {
    foreach (array('account/newsletter.php' => 'button_unsubscribe_confirm', 'account/gdpr.php' => 'button_confirm_request') as $file => $key) {
        if (strpos($read('catalog/language/' . $code . '/' . $file), $key) === false) { $failures[] = $code . '/' . $file . ' lacks ' . $key; }
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS e-mailed unsubscribe and GDPR links need a button press\n";
