# Settings Cleaner PRO 1.1.0

Independent administrator-only extension for OpenCart/ocStore 3.x and CodeCart PRO 3.0.6.0. PHP 7.4–8.5; InnoDB/utf8mb4. No OCMOD XML, core patch or cron job.

## Install / upgrade

1. Make a full filesystem and database backup on staging first.
2. Upload `Settings_Cleaner_Pro_v1.1.0.ocmod.zip` via Extensions → Installer, then install/open **Settings Cleaner PRO** in Extensions → Extensions → Modules. When upgrading 1.0.0, upload the new ZIP and reopen the module. The additional file-backup tables are automatically created when opening the new tabs, without deleting existing settings backup records.
3. In **Payment methods** review the server inventory. To remove an installed payment extension, open the **native payment extension list**, disable it, and use the native **Uninstall** action. Some payment extensions run their own uninstall hook and may delete associated business data; inspect subscriptions, refunds and callbacks before performing that action.
4. Return to **Payment methods** and check the blockers. If the module is now unregistered, has no settings, historical orders, OCMOD/Event references, installer manifest references, or custom-theme files, you can explicitly archive the listed matching files. Enter the exact payment code and confirm. Otherwise the action is disabled.
5. The **Payment file backups** panel can restore original files without overwriting an existing file. Refresh Modifications and storefront/OPcache after archiving or restoring, and verify the checkout.

## Tabs

- **Settings DB**: previous conservative `oc_setting` scan (20,000 row scan cap, 200 entries per delete; transactional database backups).
- **Payment methods**: real server inventory of payment extension controllers/models/language files/default-theme templates and extension registrations; per-code blockers, historical order count, size, explicit reversible file archive, restoration.
- **Extension files**: read-only controller file inventory for extension types including module, payment, shipping, analytics, feed and others. Unregistered does **not** mean safe to remove. Maximum 500 visible rows; this is NOT a generic filesystem sweeper.
- **OCMOD / Events**: read-only records of the latest 250 of each type. No automated deletion.
- **Backups**: setting-row and archived-payment-file restoration.

## File safety rules

The payment archive operates on one code at a time. It only touches exact basename-matched files in predefined `admin` and `catalog` extension directories (controllers, models, payment-language files, admin templates and the default catalog theme template). It NEVER removes a directory, arbitrary file, symbolic link, vendor library, installer package, custom-theme file, modification-cache copy, database order, or installed extension registration.

The archive will not run if the payment extension is still installed, payment namespace settings remain in `oc_setting`, the payment code occurs in existing `oc_order` history, an OCMOD/Event references the code, a matching installer path exists, custom-theme payment files are detected, or the OCMOD and installer scans are incomplete. Maximum 40 files / 8 MiB per payment. Evidence is checked again immediately before archiving, and file hashes are checked before unlinking. A database backup of the exact original file bytes (base64 data with SHA-256 integrity) is committed BEFORE any file is removed. If an operation is interrupted, use the file backups screen and system logs to recover; deleting files is not transactional.

**Not an automatic orphan detector for arbitrary code.** Dynamic PHP includes, webhooks, scheduled tasks, third-party API callbacks, and dependencies in uninspected files cannot be conclusively ruled out. The user must still inspect each candidate and test on staging. Past orders are intentionally a blocker even though OpenCart stores payment names in order records. Third-party payment packages may require vendor-specific removal procedures.

The module creates two additional tables named `{DB_PREFIX}settings_cleaner_file_backup` and `{DB_PREFIX}settings_cleaner_file_backup_row`. They store copies of source files, possibly including credentials. Protect DB access and backups. Uninstalling Settings Cleaner PRO intentionally drops **all of its own** backup tables: export or restore them before uninstall.

## Checks

`php tests/test_classifier.php`

`php tests/test_payments.php`

`find upload -name '*.php' -print0 | xargs -0 -n1 php -l`

Test results in this build are from local CLI tests and syntax checks. They are not full staging OpenCart E2E/DB integration tests; test backup/restore and all three admin languages on a real OpenCart installation before production.
