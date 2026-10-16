<?php
/** Offline mutation regressions for the source-quality checkers. No product files are changed. */
$root = dirname(__DIR__);
require_once $root . '/upload/system/storage/vendor/autoload.php';
require_once __DIR__ . '/lib/extension-layout.php';
require_once __DIR__ . '/lib/php-source.php';
require_once __DIR__ . '/check_paypal_financial_guards.php';
$twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader(), array('autoescape'=>false, 'cache'=>false));
$table = file_get_contents($root . '/upload/admin/view/template/extension/extension/module.twig');
$parent = file_get_contents($root . '/upload/admin/view/template/marketplace/extension.twig');
$passed = 0;

function checkerAssert($condition, $name) {
    global $passed;
    if (!$condition) { throw new RuntimeException('FAIL ' . $name); }
    $passed++;
    echo 'PASS ' . $name . PHP_EOL;
}

checkerAssert(ccpCheckExtensionTable($twig, $table) === array(), 'baseline rendered table');
checkerAssert(ccpCheckExtensionParent($twig, $parent) === array(), 'baseline parent DOM and CSS');
$formatted = str_replace('<th scope="col" class="text-center">', "<th class='additional text-center' scope='col'>", $table);
$formatted = str_replace('{{ column_action }}', '{{column_action}}', $formatted);
$formatted = str_replace('<thead>', '<thead data-example="yes">', $formatted);
checkerAssert(ccpCheckExtensionTable($twig, $formatted) === array(), 'attribute order, quotes, extra class, Twig spacing and thead attributes');
$formattedParent = str_replace(array('overflow-x:auto', 'table-layout:fixed'), array("overflow-x :\n auto", 'table-layout : fixed'), $parent);
$formattedParent = str_replace('{{ column_left }}', '{{ column_left }}<!-- harmless comment -->', $formattedParent);
$formattedParent = str_replace('<div id="content">', "<div class='extra' id='content'>", $formattedParent);
checkerAssert(ccpCheckExtensionParent($twig, $formattedParent) === array(), 'CSS whitespace and DOM adjacency ignore comment nodes');

$badTables = array(
    'thead attribute must not hide wrong colspan'=>str_replace('colspan="6"', 'colspan="99"', $formatted),
    'missing thead'=>str_replace(array('<thead>', '</thead>'), '', $table),
    'missing main row class'=>str_replace('ccp-extension-main', 'other-row', $table),
    'missing empty-state message'=>str_replace('{{ text_no_results }}', '', $table),
    'duplicate sort key with unchanged count'=>str_replace('data-ccp-sort="status"', 'data-ccp-sort="name"', $table),
    'sort key present only in comment'=>str_replace('data-ccp-sort="status"', 'data-other="status"', $table) . '<!-- data-ccp-sort="status" -->',
    'row colspan'=>str_replace('<td><b>{{ extension.name }}</b></td>', '<td colspan="2"><b>{{ extension.name }}</b></td>', $table),
    'missing action alignment'=>str_replace('<th scope="col" class="text-center">{{ column_action }}', '<th scope="col">{{ column_action }}', $table),
    'missing uploaded sort value'=>str_replace('data-sort-upload=', 'data-other-upload=', $table)
);
foreach ($badTables as $name=>$source) { checkerAssert(ccpCheckExtensionTable($twig, $source) !== array(), $name); }

$commentedCss = preg_replace_callback('~<style>(.*?)</style>~s', function ($match) {
    return '<style>/*' . preg_replace('~/\*.*?\*/~s', '', $match[1]) . '*/</style>';
}, $parent);
$badParents = array(
    'commented CSS is inactive'=>$commentedCss,
    'missing CSS declaration'=>str_replace('overflow-x:auto;', '', $parent),
    'declaration under wrong selector'=>str_replace('#extension .table-responsive {display', '#unrelated .table-responsive {display', $parent),
    'CSS width classes only in comment'=>preg_replace('~(#extension \.ccp-extension-cols-6 th:nth-child\(1\) \{[^}]+\})~', '/*$1*/', $parent),
    'script breaks element adjacency'=>str_replace('{{ column_left }}', '{{ column_left }}<script>void 0;</script>', $parent),
    'style breaks element adjacency'=>str_replace('{{ column_left }}', '{{ column_left }}<style>.sample{color:red}</style>', $parent),
    'missing content element'=>str_replace('id="content"', 'id="other-content"', $parent)
);
foreach ($badParents as $name=>$source) { checkerAssert(ccpCheckExtensionParent($twig, $source) !== array(), $name); }

$language = <<<'PHP'
<?php
$_['first'] = 'First'; // $_['first'] = 'Example';
/* $_['first'] = 'Block comment';
$_['first'] = 'Another example'; */
/** $_['first'] = 'Doc comment'; */
# $_['first'] = 'Hash comment';
$example = "\$_['first'] = 'Inside a string';";
$_ /* inline comment */ ['second'] = 'Second'; $_["third"] = 'Third';
PHP;
checkerAssert(ccpLanguageAssignmentKeys($language) === array('first','second','third'), 'PHP comment and string examples are not assignment keys');
checkerAssert(ccpLanguageAssignmentKeys($language . "\n\$_['first'] = 'Duplicate';") === array('first','second','third','first'), 'real duplicate assignment remains detectable');
$controller = <<<'PHP'
<?php
$dates = $model->getInstallDates ( "module" );
$uploaded = $model->getUploadDates(/* example */ 'module');
$data = array("code" => $extension);
PHP;
checkerAssert(ccpExtensionControllerHasMetadata($controller, 'module'), 'controller metadata tolerates whitespace, comments and string quoting');
checkerAssert(!ccpExtensionControllerHasMetadata("<?php /*" . substr($controller, 5) . '*/', 'module'), 'comment-only controller metadata is rejected');
$aliasedLanguage = <<<'PHP'
<?php
$this -> load -> language ( 'common/example' );
$this->load->language(/* alias dictionary */ 'marketplace/example', 'table_labels');
$labels = $this -> language -> get ('table_labels');
$data['column_date'] = $labels -> get ( 'column_date' );
$data['column_action'] = $this->language->get('table_labels')->get('column_action');
$data['heading_title'] = $this->language->get('heading_title');
PHP;
$references = ccpLanguageReferences($aliasedLanguage);
checkerAssert($references['routes'] === array('common/example') && $references['aliases'] === array('table_labels'=>array('marketplace/example')), 'aliased dictionaries stay outside the global route union');
checkerAssert($references['needed'] === array('heading_title'=>true), 'registered alias object is not a translated phrase');
checkerAssert($references['alias_keys'] === array('table_labels'=>array('column_date'=>true, 'column_action'=>true)), 'local alias and direct chained alias reads are tracked');
$dictionaryLoader = static function ($route) { return $route === 'marketplace/example' ? array('column_date'=>'Date','column_action'=>'Action') : null; };
checkerAssert(ccpLanguageAliasErrors($references, $dictionaryLoader) === array(), 'registered alias dictionary contains required keys');
checkerAssert(count(ccpLanguageAliasErrors($references, static function ($route) { return array('column_action'=>'Action'); })) === 1, 'missing actual alias key still fails');
checkerAssert(count(ccpLanguageAliasErrors($references, static function ($route) { return null; })) === 3, 'missing locale alias file and its two required keys fail');
$wrongBinding = ccpLanguageReferences($aliasedLanguage . "\n\$this->language->get('column_date');");
checkerAssert(isset($wrongBinding['needed']['column_date']) && !in_array('marketplace/example', $wrongBinding['routes'], true), 'aliased phrase read through global object remains a required global key');
$commentAlias = ccpLanguageReferences(<<<'PHP'
<?php
/* $this->load->language('marketplace/example', 'comment_alias'); */
$this->load->language('common/example');
$this->language->get('comment_alias');
PHP
);
checkerAssert($commentAlias['aliases'] === array() && isset($commentAlias['needed']['comment_alias']), 'comment-only alias is not registered and does not hide a missing phrase');
$dynamicObject = ccpLanguageReferences("<?php \$this->language->get('dynamic_object')->get('heading_title');");
checkerAssert($dynamicObject['needed'] === array(), 'dynamic object chaining remains outside static phrase analysis');
$paypal = file_get_contents($root . '/upload/admin/controller/extension/payment/paypal.php');
checkerAssert(paypalFinancialGuardErrors($paypal) === array(), 'PayPal baseline method guards');
$longComment = '/* ' . str_repeat('Harmless documentation. ', 50) . ' */';
$formattedPaypal = str_replace('if (!$this->validateFinancialActionRequest())', $longComment . "\nif ( ! \$this -> validateFinancialActionRequest ( ) )", $paypal);
checkerAssert(paypalFinancialGuardErrors($formattedPaypal) === array(), 'PayPal long comments and guard whitespace');
$quotedPaypal = str_replace(array("'REQUEST_METHOD'", "'modify'", "'extension/payment/paypal'"), array('"REQUEST_METHOD"', '"modify"', '"extension/payment/paypal"'), $paypal);
checkerAssert(paypalFinancialGuardErrors($quotedPaypal) === array(), 'PayPal equivalent double-quoted literals');
$commentedPaypal = str_replace('if (!$this->validateFinancialActionRequest())', "// Long harmless comment " . str_repeat('documentation ', 50) . "\nif ( ! \$this /* object */ -> validateFinancialActionRequest /* method */ ( ) )", $quotedPaypal);
checkerAssert(paypalFinancialGuardErrors($commentedPaypal) === array(), 'PayPal long inline and block comments with double-quoted literals');
$casedPaypal = str_replace(array('if (', 'return;', 'validateFinancialActionRequest', 'hasPermission', 'hash_equals'), array('IF (', 'RETURN;', 'VALIDATEFinancialActionRequest', 'HASPERMISSION', 'HASH_EQUALS'), $paypal);
checkerAssert(paypalFinancialGuardErrors($casedPaypal) === array(), 'PayPal keyword and method/function casing is equivalent');
$missingPaypal = str_replace('$this->validateFinancialActionRequest()', 'true', $paypal);
checkerAssert(paypalFinancialGuardErrors($missingPaypal) !== array(), 'PayPal missing real guards fail');
$fakePaypal = $missingPaypal . "\n/* function refundPayment() { if (!\$this->validateFinancialActionRequest()) { return; } } */";
checkerAssert(paypalFinancialGuardErrors($fakePaypal) !== array(), 'PayPal comment-only method and guards cannot pass');
$fakeStringPaypal = str_replace('if (true)', "\$example = '\$this->validateFinancialActionRequest()'; if (true)", $missingPaypal);
checkerAssert(paypalFinancialGuardErrors($fakeStringPaypal) !== array(), 'PayPal string-only guard cannot pass');
echo 'Checker mutation regressions: ' . $passed . " passed. Browser behavior is outside this suite.\n";
