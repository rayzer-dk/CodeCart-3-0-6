# Settings Cleaner PRO 1.0.0

Independent administrator module for OpenCart/ocStore 3.x and CodeCart PRO 3.0.6.0.

## Install

Upload `Settings_Cleaner_Pro_v1.0.0.ocmod.zip` in Extensions → Installer. In Extensions → Extensions → Modules, install **Settings Cleaner PRO** and open its editor. No OCMOD modification is required.

The installation creates two InnoDB tables using the site's `DB_PREFIX`:
`settings_cleaner_backup` and `settings_cleaner_backup_row`. It does not run on cron or change storefront output.

## Detection

Only settings whose `code` exactly begins with a known extension-type prefix and then a valid extension slug can become deletion candidates. The check rejects entries if the extension is registered in `extension`, controller files exist in the admin/catalog or modification cache, an installed parent extension may own the setting, module instances or layout bindings exist, or active OCMOD/Events reference the code/key.

Records in unknown namespaces and system settings are never offered for deletion. A candidate is **not proof** that a key is unused by custom PHP, integration code, or code that constructs setting names dynamically. Review every proposed deletion.

Values are not returned to the template. The scan is manual and reads at most 20,000 settings. If active OCMOD XML exceeds 16 MiB, classification is incomplete and deletion is blocked. 200 selected records can be deleted per action.

## Delete / restore

Select candidates, type `DELETE`, and confirm. A database transaction backs up original setting values/serialization/store IDs before deleting. Up to 30 newest backup batches appear in the module. Restoring a backup inserts only settings that don't conflict with currently existing `(store_id,code,key)` records; on conflict, restoration rolls back without overwriting anything.

The backups contain original secret values, so database access must be restricted and backed up securely. Uninstall drops the module's backup tables and their contents. Create a full database backup before cleaning or uninstalling.

## Compatibility and limitations

PHP 7.4–8.5 syntax. Existing MySQL/MariaDB OpenCart 3 tables are required (`setting`, `extension`, `module`, `layout_module`, `modification`, `event`). PHP requires filesystem read access to admin and catalog controller directories.

The current release is intentionally conservative and does not delete leftover OCMOD/Events or `module` records. No live-store integration test is included; exercise installation, scan, delete, rollback and restore on a staging database before production use.

## Development checks

`php tests/test_classifier.php`

`find upload -name '*.php' -print0 | xargs -0 -n1 php -l`
