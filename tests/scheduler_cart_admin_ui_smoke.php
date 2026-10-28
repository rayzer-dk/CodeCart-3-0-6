<?php
/** Lightweight source-contract tests for the October 2026 admin UX fixes. */
$root = dirname(__DIR__);
$read = static function ($path) use ($root) { return file_get_contents($root . '/' . $path); };
$assert = static function ($condition, $label) {
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $label . "\n"); exit(1); }
};
$footer = $read('upload/admin/view/template/common/footer.twig');
$assert(strpos($footer, 'ccp-footer-runtime-details') === false, 'footer diagnostics expander removed');
$assert(strpos($footer, 'runtime_db_charset') !== false, 'database encoding still visible');
$editor = $read('upload/admin/view/javascript/codecart-scheduler.js');
$assert(strpos($editor, 'editorRow && editingId === id') !== false, 'second pencil click toggles editor closed');
$assert(strpos($editor, 'pendingRequest.abort()') !== false, 'in-flight request aborted on collapse');
$cron = $read('upload/admin/view/template/tool/scheduler.twig');
$assert(strpos($cron, 'ccp-host-technical-fields') !== false, 'raw cron fields moved to advanced details');
$assert(strpos($cron, "'value':'*/5'") !== false, 'valid five-minute cron expression preserved');
$assert(strpos($cron, 'Mirohost') === false, 'host-specific branding absent from scheduler UI');
$cart = $read('upload/admin/view/template/sale/abandoned_cart_list.twig');
$assert(strpos($cart, '<col class="ccp-width-email">') === false, 'extra email column removed');
$assert(strpos($cart, 'ccp-cart-contact-email') !== false, 'email retained in customer cell');
$assert(strpos($cart, 'sort_email') !== false, 'email sorting preserved');
$assert(strpos($cart, 'text_source_only_explanation') !== false && strpos($cart, 'text_checkout_only_explanation') !== false, 'data source / checkout are clarified');
foreach (array('en-gb', 'ru-ru', 'uk-ua') as $locale) {
    $l = $read('upload/admin/language/' . $locale . '/tool/scheduler.php');
    $assert(strpos($l, 'Mirohost') === false, $locale . ' hosting neutral');
    $assert(strpos($l, "\$_['text_host_expression_hint']") !== false, $locale . ' cron syntax help present');
    $c = $read('upload/admin/language/' . $locale . '/sale/abandoned_cart.php');
    $assert(strpos($c, "\$_['text_source_only_explanation']") !== false && strpos($c, "\$_['text_checkout_only_explanation']") !== false, $locale . ' distinct concepts help');
}
fwrite(STDOUT, "PASS: footer, scheduler editor, host-neutral Cron, cart table and settings semantics\n");
