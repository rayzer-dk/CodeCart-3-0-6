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
$assert(strpos($cart, '<col class="ccp-width-email">') !== false, 'compact email column present');
$assert(strpos($cart, '<col class="ccp-width-phone">') !== false, 'dedicated telephone column present');
$assert(strpos($cart, 'sort_telephone') !== false, 'telephone column supports sorting');
$assert(strpos($cart, '× {{ product.quantity }}') !== false, 'each product must display its individual quantity');
$assert(strpos($cart, 'ccp-source-advanced') !== false, 'external SQL source tucked into advanced settings');
$assert(strpos($cart, 'ccp-cart-contact-email') !== false, 'email remains visible in separate truncated cell');
$assert(strpos($cart, 'sort_email') !== false, 'email sorting preserved');
$assert(strpos($cart, 'ccp-source-advanced') !== false && strpos($cart, 'text_cart_source_help') !== false && strpos($cart, 'text_checkout_only_explanation') !== false, 'data source / checkout remain distinct');
$assert(strpos($cart, 'ccp-settings-core-grid') !== false && strpos($cart, 'ccp-settings-reminder-list') !== false, 'compact responsive settings groups present');
$form = explode('</form>', explode('class="ccp-cart-settings-form"', $cart, 2)[1] ?? '', 2)[0];
$assert(strpos($form, '<details') === false && strpos($form, '<summary') === false, 'all cart settings visible without collapsing');
$assert(strpos($cart, 'ccp-capture-diagnostics-toggle') !== false && strpos($cart, 'ccp-check-guest-capture') !== false, 'diagnostics visible and actionable');
foreach (array('en-gb', 'ru-ru', 'uk-ua') as $locale) {
    $l = $read('upload/admin/language/' . $locale . '/tool/scheduler.php');
    $assert(strpos($l, 'Mirohost') === false, $locale . ' hosting neutral');
    $assert(strpos($l, "\$_['text_host_expression_hint']") !== false, $locale . ' cron syntax help present');
    $c = $read('upload/admin/language/' . $locale . '/sale/abandoned_cart.php');
    $assert(strpos($c, "\$_['text_source_only_explanation']") !== false && strpos($c, "\$_['text_checkout_only_explanation']") !== false, $locale . ' distinct concepts help');
}
fwrite(STDOUT, "PASS: footer, scheduler editor, host-neutral Cron, cart table and settings semantics\n");
