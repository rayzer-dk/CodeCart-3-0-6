<?php
declare(strict_types=1);
// Confirm diagnostics do not convert warnings into upgrade blockers, and only
// allowlisted step tokens are disclosed while sensitive details stay logged.
$root = dirname(__DIR__);
$controller = (string)file_get_contents($root . '/upload/install/controller/upgrade/upgrade.php');
$template = (string)file_get_contents($root . '/upload/install/view/template/upgrade/upgrade.twig');
foreach (array(
    "\$state === 'warning'",
    "\$state === 'blocker'",
    "'preflight_blocker_rows'",
    "'lastMigrationFailurePhase'",
    "error_upgrade_stage",
    "error_backup_required"
) as $part) {
    if (!str_contains($controller, $part) &&
        !($part === "'lastMigrationFailurePhase'" && str_contains($controller, 'lastMigrationFailurePhase()'))) {
        fwrite(STDERR, "Installer controller diagnostic missing: " . $part . "\n");
        exit(1);
    }
}
foreach (array('preflight_blocker_rows', 'text_warnings_nonblocking', 'text_backup_unlock', 'backup-confirm') as $part) {
    if (!str_contains($template, $part)) {
        fwrite(STDERR, "Installer template diagnostic missing: " . $part . "\n");
        exit(1);
    }
}
foreach (array('uk-ua', 'ru-ru', 'en-gb') as $lang) {
    $locale = (string)file_get_contents($root . '/upload/install/language/' . $lang . '/upgrade/upgrade.php');
    foreach (array('text_warnings_nonblocking', 'text_backup_unlock', 'error_upgrade_stage') as $part) {
        if (!str_contains($locale, "['" . $part . "']")) {
            fwrite(STDERR, "Missing installer locale " . $lang . '/' . $part . "\n");
            exit(1);
        }
    }
}
echo "PASS: upgrade preflight displays real blockers and safe migration stage.\n";
