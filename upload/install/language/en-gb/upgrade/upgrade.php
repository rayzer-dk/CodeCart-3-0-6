<?php
$_['heading_title']='Upgrade'; $_['heading_success']='Upgrade complete'; $_['text_upgrade']='Upgrade an existing CodeCart PRO/OpenCart/ocStore installation'; $_['text_server']='Before upgrade'; $_['text_steps']='Migration progress'; $_['text_backup']='Create and verify a full backup of files and database before continuing.'; $_['text_error']='Migrations run in a controlled sequence. Technical errors are written to the protected log.'; $_['text_clear']='After completion refresh modifications and application caches if required.'; $_['text_admin']='Check administration login, permissions and main settings.'; $_['text_user']='Verify user groups and permissions after migration.'; $_['text_setting']='Review system settings without resetting existing store data.'; $_['text_store']='Check storefront, cart and checkout before reopening production traffic.'; $_['text_progress']='Migration %s applied (%s of %s)'; $_['text_success']='Upgrade migrations completed successfully.'; $_['entry_progress']='Progress'; $_['error_ajax']='The upgrade request failed. Check the protected server/installer log and retry only after identifying the cause.'; $_['error_upgrade_step']='A migration step failed. No raw technical details are exposed here; check the protected installer log.';

$_['text_preflight'] = 'Upgrade preflight';
$_['text_preflight_help'] = 'These checks run before any migration changes files or database structures. Blockers must be fixed first.';
$_['text_component'] = 'Component';
$_['text_current'] = 'Current';
$_['text_status'] = 'Status';
$_['text_ok'] = 'OK';
$_['text_warning'] = 'Warning';
$_['text_blocker'] = 'Blocker';
$_['text_backup_confirm'] = 'I created and verified a full backup of files and database.';
$_['text_preflight_ready'] = 'Preflight passed. The upgrade can start after backup confirmation.';
$_['text_preflight_blocked'] = 'Upgrade is blocked until all required checks pass.';
$_['error_preflight_blocked'] = 'Upgrade preflight has blockers. Return to the upgrade page and fix them before retrying.';
$_['error_backup_required'] = 'Confirm a verified full backup before starting the upgrade.';
$_['preflight_php'] = 'Use one supported PHP version: 8.1, 8.2, 8.3, 8.4 or 8.5.';
$_['preflight_extension'] = 'Required by the supported CodeCart PRO runtime.';
$_['preflight_config'] = 'Existing configuration must be present and readable. The updater does not rewrite config.php or admin/config.php.';
$_['preflight_db_connection'] = 'Existing store database must be readable before migration.';
$_['preflight_utf8mb4'] = 'The server must support utf8mb4; existing legacy tables can be migrated later.';
$_['preflight_innodb'] = 'The server must support InnoDB; existing legacy tables can be migrated later.';
$_['preflight_schema'] = 'Core OpenCart/ocStore tables must exist before an upgrade.';
$_['preflight_disk'] = 'At least 256 MB free is recommended for safe migration, temporary files and logs.';
$_['preflight_existing_db_format'] = 'Legacy MyISAM or non-utf8mb4 tables are reported but are not destructively rebuilt by the upgrade. Convert them later with Database Modernizer after a verified backup.';
$_['preflight_storage'] = 'Installer cache, logs and migration state require writable protected storage.';

$_['preflight_source_component'] = 'Supported source profile';
$_['preflight_source_range'] = 'OpenCart 3.0.2.0–3.0.5.1 / ocStore 3.0.2.0–3.0.5.0 / earlier CodeCart PRO 3.x releases';
$_['preflight_source_help'] = 'The source schema matches the certified OpenCart/ocStore 3.x migration profile. Exact historical version cannot always be read after new files have already replaced the old application files, so the installer validates the database schema itself.';
$_['preflight_source_blocked'] = 'The database is missing columns required by the supported 3.0.2.0+ migration profile. Do not force the update; use an intermediate supported upgrade or repair the source schema first.';

$_['preflight_transactional_component'] = 'Transactional commerce tables';
$_['preflight_transactional_ready'] = 'Critical tables use InnoDB';
$_['preflight_transactional_convertible'] = '%d critical table(s) will be converted to InnoDB during the confirmed update';
$_['preflight_transactional_help'] = 'Order and stock operations require InnoDB transactions. Small MyISAM/Aria commerce tables are converted only inside the confirmed Installer 2.0 update.';
$_['preflight_transactional_blocked'] = 'A critical commerce table is too large or uses an unsupported engine. Run db:preflight and db:migrate from CLI after a verified backup, then reopen the updater.';

$_['preflight_vendor_component'] = 'Shared Composer dependencies';
$_['preflight_vendor_ready'] = 'No foreign packages in the shared Core vendor';
$_['preflight_vendor_help'] = 'The Core vendor can be replaced safely. Extension dependencies should use an extension-local vendor/autoload.php or a registered namespace.';
$_['preflight_vendor_replace'] = 'Legacy or additional packages were found in the existing shared Core vendor. UPDATE will replace Core vendor/autoload as one consistent Composer set and preserve the previous shared vendor separately for legacy compatibility and rollback inspection.';

$_['preflight_ocmod_component'] = 'Active OCMOD overlap';
$_['preflight_ocmod_none'] = 'No active overlap detected';
$_['preflight_ocmod_help'] = 'No active OCMOD modification targets the high-risk product/default-theme files changed by this update.';
$_['preflight_ocmod_warning'] = 'Active OCMOD modifications target files also changed by CodeCart. This is a compatibility warning, not an automatic conflict. Keep the modifications enabled, complete the update on staging, refresh Modifications, and verify product save, product page, options and the active theme before production.';

$_['preflight_tls_ready'] = 'TLS 1.0/1.1 are disabled for CodeCart PRO outbound connections; TLS 1.2 is required and TLS 1.3 is used when supported.';
$_['preflight_tls_blocked'] = 'TLS 1.2 or newer support is required in OpenSSL and cURL.';
$_['preflight_files_component'] = 'File replacement policy';
$_['preflight_files_current'] = 'Core files are updated in place; config.php and storage data are preserved';
$_['preflight_files_help'] = 'Matching names and replacement of Core files are expected during UPDATE and do not block it. UPDATE is blocked only by a genuinely unsafe compatibility condition. Active OCMOD overlaps and foreign Composer packages are reported separately as warnings.';

$_['text_repair_mode'] = 'Repair current build';
$_['text_repair_help'] = 'Force a repair when reinstalling the same Build: resynchronise the bundled Composer/vendor to the active storage, clear template cache and invalidate the OCMOD build marker. Database migrations remain idempotent and are not reset.';

// Administrator verification for UPDATE on an installed store
$_['text_login_required'] = 'This store is already installed. Sign in with a store administrator account that can manage users or extensions to view the preflight and run UPDATE.';
$_['entry_username'] = 'Administrator username';
$_['entry_password'] = 'Password';
$_['button_login'] = 'Sign in';
$_['error_login'] = 'Invalid username or password, or the account has no administrator permissions.';
$_['error_login_attempts'] = 'Too many failed attempts. Try again in 15 minutes.';
$_['error_login_required'] = 'The installer session is not authorised or has expired. Reload the page and sign in again.';
