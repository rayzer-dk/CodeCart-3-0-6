<?php
/** Image Manager delete: usage warning and path handling: php tests/filemanager_usage_smoke.php */
$source = (string)file_get_contents(dirname(__DIR__) . '/upload/admin/controller/common/filemanager.php');
$template = (string)file_get_contents(dirname(__DIR__) . '/upload/admin/view/template/common/filemanager.twig');
$failures = array();
$doubled = chr(39) . str_repeat(chr(92), 4) . chr(39);

if (strpos($source, 'countImageUsage') === false || strpos($source, "post['force']") === false) {
    $failures[] = 'Deleting an image that is still used must warn first and require confirmation.';
}
if (strpos($source, $doubled) !== false) {
    $failures[] = 'Path separators must be one escaped backslash; the doubled form rejects every delete on Windows hosts.';
}
if (strpos($template, 'ccpDeleteSelected') === false || strpos($template, "json['warning']") === false) {
    $failures[] = 'The Image Manager must ask for confirmation when the server reports usage.';
}
foreach (array('en-gb', 'ru-ru', 'uk-ua') as $code) {
    $language = (string)file_get_contents(dirname(__DIR__) . '/upload/admin/language/' . $code . '/common/filemanager.php');
    if (strpos($language, 'text_in_use') === false) {
        $failures[] = 'Missing text_in_use in ' . $code;
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS Image Manager warns before deleting images that are in use\n";
