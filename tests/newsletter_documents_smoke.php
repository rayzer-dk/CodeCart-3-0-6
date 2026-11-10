<?php
/**
 * Read-only smoke tests. Run: php tests/newsletter_documents_smoke.php
 */
require dirname(__DIR__) . '/upload/system/library/codecart/src/NewsletterEmail.php';

use CodeCart\Core\NewsletterEmail;

function check($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); }
}
foreach (array('uk-ua', 'ru-ru', 'en-gb') as $locale) {
    $presets = NewsletterEmail::presets($locale);
    foreach (array('builtin_news','builtin_offer','builtin_new','builtin_info') as $id) {
        check(isset($presets[$id]) && $presets[$id]['built_in'] === true, 'Missing built-in design ' . $locale . ' ' . $id);
        check(strpos($presets[$id]['message'], '{{ customer_name }}') !== false, 'Missing customer personalization');
    }
}
$logo = NewsletterEmail::logoUrl('https://example.com', 'catalog/Logo Dänisch.png');
check($logo === 'https://example.com/image/catalog/Logo%20D%C3%A4nisch.png', 'Valid store logo URL');
check(NewsletterEmail::logoUrl('https://example.com', '../config.php') === '', 'Logo traversal rejected');
check(NewsletterEmail::logoUrl('https://example.com', 'https://evil.test/logo.png') === '', 'External logos rejected');
$values = array('customer_name' => 'Alex & Sam', 'store_name' => 'Example Shop', 'store_url' => 'https://example.com/', 'unsubscribe_url' => 'https://example.com/unsubscribe?token=123');
$brand = array('name'=>'Example Shop','url'=>'https://example.com/','email'=>'support@example.com','locale'=>'uk-ua','logo'=>$logo);
$render = NewsletterEmail::render('News & Updates', '<h2>New</h2><p>Hello {{ customer_name }}</p>', $values, $brand);
foreach (array('<!DOCTYPE html>', 'width="600"', 'max-width:620px', 'Logo%20D%C3%A4nisch.png', 'Alex &amp; Sam', 'support@example.com', 'https://example.com/unsubscribe?token=123', 'News &amp; Updates') as $needle) {
    check(strpos($render, $needle) !== false, 'Email output incomplete: ' . $needle);
}
check(strpos($render, 'Alex & Sam') === false, 'Variable not HTML escaped');
$root = dirname(__DIR__) . '/upload/';
$css = file_get_contents($root . 'admin/view/stylesheet/codecart_documents.css');
foreach (array('catalog/download.php','catalog/public_document.php') as $controller) {
    $code = file_get_contents($root . 'admin/controller/' . $controller);
    check(strpos($code, "addStyle('view/stylesheet/codecart_documents.css") !== false, 'Missing document style registration: ' . $controller);
}
check(strpos($css, '.ccp-document-admin .ccp-doc-table') !== false, 'Shared document stylesheet missing');
foreach (array('download_list.twig','public_document_list.twig','download_form.twig','public_document_form.twig') as $file) {
    $page = file_get_contents($root . 'admin/view/template/catalog/' . $file);
    check(strpos($page, 'ccp-document-admin') !== false, 'Document scope missing: ' . $file);
    check(strpos($page, 'form-download') !== false || strpos($page, 'form-documents') !== false || strpos($page,'form-document') !== false, 'Document form semantics damaged: ' . $file);
}
foreach (array('download_list.twig','public_document_list.twig') as $file) {
    $page = file_get_contents($root . 'admin/view/template/catalog/' . $file);
    check(strpos($page, 'ccp-doc-table') !== false, 'Document list not standardized: ' . $file);
    check(strpos($page, 'colspan="7"') !== false, 'Incorrect empty state column count: ' . $file);
}
$editor = file_get_contents($root . 'admin/view/template/marketing/contact.twig');
foreach (array('builtin_news', 'template_preview_url', 'mail-preview-mobile', 'mail-preview-desktop') as $needle) {
    check(strpos($editor, $needle) !== false, 'Missing newsletter editor feature: ' . $needle);
}
$cron = file_get_contents($root . 'catalog/controller/cron/mail_campaign.php');
check(strpos($cron, 'NewsletterEmail::render') !== false && strpos($cron, 'NewsletterEmail::logoUrl') !== false, 'Cron does not use shared branded renderer');
echo "Newsletter and document consistency: PASS\n";
