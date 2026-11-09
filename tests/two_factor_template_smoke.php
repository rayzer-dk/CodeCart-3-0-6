<?php
/** Renders the administrator two-factor page: php tests/two_factor_template_smoke.php */
$autoload = dirname(__DIR__) . '/upload/system/storage/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Composer dependencies are missing.\n");
    exit(1);
}
require $autoload;

$directory = dirname(__DIR__) . '/upload/admin/view/template/common';
$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($directory), array('autoescape' => false));

$codes = array('5C36-8584', 'A1B2-C3D4', '0F0F-1E1E');
$html = $twig->render('two_factor.twig', array('recovery_codes' => $codes, 'enabled' => true));

$failures = array();
if (!preg_match('~<pre class="ccp-two-factor-codes">(.*?)</pre>~s', $html, $match)) {
    $failures[] = 'Recovery code block is missing.';
} elseif (explode("\n", $match[1]) !== $codes) {
    $failures[] = 'Recovery codes must be printed one per line, got: ' . json_encode($match[1]);
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS two-factor recovery codes are listed one per line\n";
