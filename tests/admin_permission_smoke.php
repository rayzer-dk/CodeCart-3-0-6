<?php
/** Root administrator keeps access to every shipped admin screen: php tests/admin_permission_smoke.php */
$migration = (string)file_get_contents(dirname(__DIR__) . '/upload/system/library/codecart/migration.php');
$failures = array();

$start = strpos($migration, 'private function ensureAdministratorExtensionPermissions()');
$end = strpos($migration, 'private function ensureFormBuilderAdminPermission()');
$body = ($start !== false && $end !== false) ? substr($migration, $start, $end - $start) : '';

if ($body === '' || strpos($body, "admin/controller/") === false || strpos($body, 'RecursiveDirectoryIterator') === false) {
    $failures[] = 'The update must grant the root administrator every route of the shipped admin controllers.';
}
if (strpos($body, 'array_diff') !== false || strpos($body, 'unset(') !== false) {
    $failures[] = 'The update may only add administrator permissions, never remove them.';
}
if (strpos($migration, '$this->ensureAdministratorExtensionPermissions();') === false) {
    $failures[] = 'The administrator permission step is not part of the update.';
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL ' . $failure . "\n");
    }
    exit(1);
}
echo "PASS the update restores administrator access to all shipped admin routes\n";
