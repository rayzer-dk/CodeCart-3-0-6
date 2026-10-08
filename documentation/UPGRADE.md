# Upgrade and repair — Build 2.0.4

[Українська](UPGRADE.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

**Create and verify a complete website/database backup before updating.** Rehearse on a staging copy with the store's themes, OCMOD, Events, payments and shipping. Runtime upgrade coverage exists for ocStore 3.0.4.1, OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta; it does not certify every extension combination.

1. Record PHP version, active themes for all stores, storage path and cron tasks. Enable maintenance and stop external imports/queues during file replacement.
2. Preserve store-specific `.htaccess` and `robots.txt` separately; merge the new neutral defaults with reviewed hosting/SEO rules rather than blindly replacing them. Preserve `config.php`, `admin/config.php`, merchant images and actual `DIR_STORAGE`, including downloads, uploads and persistent module data. Overlay application files; do not delete existing theme/extension files or replace the whole site with an empty directory.
3. Upload only the contents of the production package's `upload/`. Same-name Core files are replaced. Keep tools and documentation outside the web root.
4. Open `/install/`, choose **UPDATE**, and sign in as a store administrator authorized to modify users, extensions or Core. Review preflight warnings/blockers, confirm backup and complete the update. Never force a blocked migration.
5. Refresh **Extensions → Modifications**, review compatibility and Problems entries, then clear relevant theme/template caches. Extension Installer's optional automatic refresh is disabled by default.
6. Check login, settings, themes for every store, products, categories, images, SEO URLs, cart, checkout, payments/shipping, cron and logs. Remove `/install/` and restore normal operation after verification.

Never import `install/opencart.sql` into an existing database. Keep the backup until real orders/integrations are checked. Rollback requires matching files **and** database from the same backup.

The updater preserves every store's `config_theme`, store name and configured logo/favicon. CodeCart Theme is added as an option without automatic activation. Persistent storage is preserved. Legacy schema differences can remain as warnings; an upgrade does not guarantee a schema identical to a clean install.

## Repair and modernization

To repair damaged files/dependencies, upload the same production package and choose **UPDATE → Repair current build**. This resynchronizes bundled vendor with active storage, clears template cache and invalidates OCMOD. Completed database migrations are not reset. Refresh Modifications and repeat store checks. Repair cannot restore deleted merchant data.

For remaining MyISAM/older character sets, use **Core / Compatibility → Database Schema → Modernize tables** after a verified backup. Large tables require the CLI path described by that screen. Verify extensions before enabling strict SQL mode; the default connection mode follows OpenCart compatibility.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
