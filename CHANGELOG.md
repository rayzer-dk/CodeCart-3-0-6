# CodeCart 3.0.6.0 — Build 2.3.7 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 37 (was 36). Updated stores run UPDATE in `/install/` once: the migration now also creates the Mail Audit table, which until now was created only by saving the mail settings; a clean install now ships it too (the Mail Audit history showed "structure missing, error 1146" on a fresh install).

- Reminder emails for abandoned carts from a secondary storefront failed with SQL error 1064: the `ssl` column of the store table was not quoted.
- Storefront, "notify me when in stock": the request URL reached the script with `&amp;`, so the product id was lost and the visitor saw an error. The URL is now passed unescaped.
- Administration, "Customer approvals" showed a red "check the form" warning on opening, because the language string `error_warning` was rendered as a message. The Analytics and Advertising extension lists logged `Undefined array key "status"` and showed an empty status column; the status of the default store is now shown, and each store row gets its on/off colour.
- Administration, header: the refresh, quick navigation and theme icons are now the same size as the notification bell.
- Core / Compatibility → About: removed the build, schema and revision line.
- Mail campaigns: the template panel has header and button colour settings (saved for all later campaigns, also applied in the preview before saving) and a separate button to upload an HTML template file; the "load" button no longer fails silently. Colours are accepted only as `#rrggbb`; the header text switches between white and dark by contrast.
- Products list: the bulk edit button is now an icon with a tooltip (the undo button too). A second icon opens a dialog to add the selected products to a category or move them (keep only that category), with a category filter and a confirmation for "move". The category is no longer part of the bulk edit dialog. "Undo last bulk change" also restores the removed categories. Checked on a clean install: add, move and undo give the expected `product_to_category` rows.
- Modifications (OCMOD): search by name, author, code or version; it works together with the status filters and sorting.
- Public documents: wider search field.
- Cart dashboard: the log line for a failed summary now carries the database error code.

# CodeCart 3.0.6.0 — Build 2.3.6 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- Administration, unsaved-changes guard (added in 2.2.8): ticking rows in a list page (products, orders, customers and so on) counted as an unsaved change, so navigating away after selecting rows asked "Leave site?". The guard now watches edit forms only and skips list forms that carry row checkboxes. Checked in a browser: ticking a product row no longer prompts, editing a field on the product form still does.
- Whole branch reviewed after builds 2.2.8 to 2.3.5: the administration pages touched by the feedback layer (29 routes) load without new JavaScript errors or server errors compared with build 2.2.7, and the release gate, installation from a clean database and the product, order-support, GDPR, cart and mail flows pass.
- The package must be built after `composer install --no-dev` against the current `composer.lock` (the vendor tree is not in git). Guzzle 7.16.1 and symfony/filesystem 6.4.50, which closes the advisory, are now in the archive; `composer audit` reports no advisories.

# CodeCart 3.0.6.0 — Build 2.3.5 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- Supported PHP range is unchanged: 8.1 to 8.5. PHP 7.4 was removed from the Catalog Transfer and Newsletter document CI matrices; both now run on 8.1 and newer. The `PHP_VERSION_ID` guards added to the QA scripts in 2.3.4 are removed together with the calls they protected (`setAccessible()` has had no effect since PHP 8.1, `imagedestroy()` since 8.0).
- CI: one version of each GitHub Action across all workflows (`actions/checkout`, `actions/upload-artifact`, `actions/setup-node` at the current major); previously three different `checkout` majors were mixed.
- CI: new workflow "Dependency Audit" — `composer validate`, `composer audit --locked` (also weekly) and a check that `composer.lock` and `THIRD_PARTY_DEPENDENCIES.json` list the same versions. Security advisories in locked packages used to be found only by hand.

# CodeCart 3.0.6.0 — Build 2.3.4 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- PHP 8.5 readiness of the QA tooling: `ReflectionMethod::setAccessible()`, `ReflectionProperty::setAccessible()` and `imagedestroy()` are deprecated in PHP 8.5 and have had no effect since PHP 8.1 / 8.0. The release-gate scripts and smoke tests still run on PHP 7.4 in the upgrade matrix, so the calls are now guarded by `PHP_VERSION_ID` instead of being removed. Shipped code in `upload/` did not contain them.
- The complete release gate (75 checks) passes on PHP 8.3, 8.4 and 8.5 with GD built with AVIF support, and PHP 8.5 reports no deprecation notices from the gate.

# CodeCart 3.0.6.0 — Build 2.3.2 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- Dependencies (minor and patch updates, exact pins): guzzlehttp/guzzle 7.16.1, guzzlehttp/promises 2.6.0, guzzlehttp/psr7 2.13.2, league/uri and league/uri-interfaces 7.10.0, symfony/deprecation-contracts 3.8.1, symfony/polyfill-mbstring 1.43.3, symfony/filesystem 6.4.50. The filesystem update closes advisory GHSA-6prc-h956-hxjq reported by `composer audit`. Major versions (Guzzle 8, psr7 3, promises 3, symfony/filesystem 7) are intentionally not applied.
- Third-party theme compatibility: the home banners module of the supported third-party theme was lost because its OCMOD anchor does not exist in the batched CodeCart layout model. The layout model now exposes the `catalog.layout.modules` compatibility contract and the theme adapter appends the module to the configured layout position (ordering, layout list and status flag as before). The theme is not changed.
- Modification compatibility report: one operation that the compatibility layer already satisfies (Twig loader chain) was reported as skipped because the expected search string in the legacy contract table had a typo; the compatibility layer now recognises it.
- New regression test `tests/theme_layout_compat_smoke.php` (17 checks) is part of the release gate.

# CodeCart 3.0.6.0 — Build 2.3.1 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- Administration: a short, localized message when an AJAX request receives the sign-in page (expired session) instead of the raw page text; parser errors no longer show the JavaScript exception text in the notification title.
- Administration: notifications in the dark theme keep their status colour on the left edge.
- Quality gates: removed a redundant `method_exists()` guard in the administration header controller (`Document::getAssets()` is part of the same package), which made the legacy PHPStan check fail on that file.
- Release checks: the bundled documentation archive is verified by member names and content (`tools/check_documentation_archive.py`) instead of comparing compressed bytes. Compressed output depends on the zlib implementation of the machine that built the archive. Applies to the full production, production release and release workflows.
- Release packaging: the build number check in `tools/package_production.py` accepted only single-digit components; it now accepts any number of digits per component.
- Changelog wording for build 2.2.8 no longer names a third-party theme, as required by the public documentation policy.

# CodeCart 3.0.6.0 — Build 2.2.9 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- Administration: quick navigation on Ctrl/Cmd+K (also a compass button in the header). It filters every link of the left menu, including nested groups and extension menus, and shows the menu path of each result. Arrow keys and Enter open a page, Ctrl/Cmd+Enter opens it in a new tab, Esc closes. Strings are localized (English, Ukrainian, Russian).
- Administration: the unsaved-changes guard also works when the page `load` event has already fired before the script initializes.
- Checked in Chrome on a static harness with the real admin CSS and JavaScript (light and dark theme, desktop and 390 px width): palette, toasts, quick-save bar and the native leave-page confirmation. Not checked on a running store.

# CodeCart 3.0.6.0 — Build 2.2.8 (2026-11-12)

Core schema 3.0.6.0; presentation schema revision 36 (unchanged).

- Administration: one feedback layer (`CodeCartAdmin.toast`, `CodeCartAdmin.ajaxError`). AJAX failures in 26 admin templates used to show the raw server response (often a full HTML page) in a browser `alert`; they now show a short toast with the HTTP status and a one-line summary, and the full response goes to the browser console. Success and error messages that used `alert(json.success)` / `alert(json.error)` use the same toasts.
- Administration: on long forms a floating Save button appears when the page header buttons scroll out of view, and leaving a form page with unsaved changes asks for confirmation. Password and file fields are ignored; a successful AJAX save of the same route resets the baseline.
- Administration: the layout fix for a third-party theme's settings pages moved from an inline `<style>` in the header template to its own stylesheet, loaded only on the two routes that need it.
- Administration: the "New" menu toggle is a focusable link with ARIA state, per-item inline styles were removed, keyboard focus is visible on header, menu, tabs and buttons, and animations are disabled for `prefers-reduced-motion`.
- Administration: `stylesheet.css`, `codecart-admin-ui.js` and `codecart-admin-dark.css` are versioned by the package build, so a browser cache can no longer keep an old copy after an update.
- Not part of this build: replacing the `confirm()` dialogs (87), de-duplicating the dark theme overrides, and storefront fragment responses for catalog AJAX navigation.

# CodeCart 3.0.6.0 — Build 2.2.7 (2026-11-11)

Core schema 3.0.6.0; presentation schema revision 36 (new event; updated stores register it on the next update run).

- Product and category saves are one transaction: when any statement fails halfway, nothing is saved. Before, a failure after the old relations were deleted left the product without its categories and SEO URLs. The public methods (`addProduct`, `editProduct`, `addCategory`, `editCategory`) keep their names and signatures for third-party modifications. Verified against a real database with an injected failure: the product is unchanged with the transaction, and was left half-saved without it.
- E-mailed links no longer act on their own: the newsletter unsubscribe link and the GDPR confirmation link open a page with a button, and only the button (POST) unsubscribes or confirms. Mail scanners that open links can no longer unsubscribe a customer or use up a one-time token.
- Administrator change audit: every create, edit, delete, copy, install, enable and similar change made in the administration is written to the security audit log (Core / Compatibility → Security) with the administrator, the model call and the record id. Submitted form values are never stored.
- Product list, bulk edit: change price (set, by percent or by amount), quantity (set or add), manufacturer and add a category for all ticked products at once. The last bulk edit can be undone; a field is restored only while it still holds the value the bulk edit wrote, so later manual changes survive. Bulk edits and undo are recorded in the audit log.
- Product list: warnings stored in the session (for example an invalid bulk value) are now shown.
- Sales report: comparison with the previous period of the same length (orders, revenue, average order value, change in percent).
- Scheduled database backup (`core.backup.database`, off by default): writes a verified SQL dump with a SHA-256 file to the private storage backup folder and keeps the newest copies (task argument `keep`, default 7). A copy is accepted only after a completeness check, and the visitor heartbeat never runs it. The dump code moved to `CodeCart\Core\SqlBackup` and is shared with the manual SQL export.
- Administrator access after an update: the update now adds every route of the shipped admin controllers to the root administrator's group (permissions are only added, never removed). Stores whose Administrator group lacked newer routes showed "insufficient permissions" on Modifications and System notifications and hid the on/off switches in the blog article list.
- Core / Compatibility → About the system: the comparison table OpenCart 3.0.5.x / ocStore 3.0.5.x / CodeCart PRO 3.0.6.x with a status column is back. Versions of the bundled Composer packages in the CodeCart column are read from the installed vendor tree, so they match the package (Twig 3.30.0, Polyfill Mbstring and PHP 8.0 1.43.0), and jQuery Migrate is listed.
- Mail Audit: a missing audit table is no longer written to the error log on every history request; the administrator still sees the repair prompt.
- Audit fixes, each confirmed in the code and, where it runs without PayPal or external services, on a fresh installation:
  - Controller dispatch only calls public methods, so a private or protected method can no longer be reached through a route. The PayPal module migration (`update()`, which drops its tables on an old version) is private and runs from the controller constructor only while PayPal is enabled.
  - The `store_id` URL parameter selects a store only for API routes (administrator order editing). The storefront store always follows the requested domain.
  - Cart: a product with "subtract stock" off is no longer flagged out of stock when its quantity is 0 (it blocked checkout when out-of-stock orders are disallowed).
  - Password reset links (customers and administrators) expire after one hour; a code issued without an expiry is refused. The reset and administrator login forms never echo a typed password back into the page.
  - `Money::normalize()` rounds with `round($value, $scale)`: scaling by hand first turned 79364.02835 into 79364.0283 on PHP 8.4 instead of 79364.0284.
  - ECB currency refresh bridges the missing UAH rate from the NBU whenever UAH is an active currency, not only when it is the base currency; before, a EUR store kept a stale hryvnia rate.
  - A mail campaign in which no message could be sent ends as "failed" instead of "completed".
- Image Manager: deleting a file or folder that products, categories or manufacturers still use now asks for confirmation first and says how many records lose their picture. The delete action also works on Windows hosts again: the path separator was escaped twice, so every delete was refused there.
- Development aid: `define('CODECART_SQL_PROFILE', true);` in `config.php` logs queries slower than `CODECART_SQL_PROFILE_MS` (default 50) and a per-request summary to `sql_profile.log`. Off by default and without cost when off.
- Verified on a fresh installation (MariaDB 10.11, PHP 8.3): bulk edit and undo on real products, the audit rows, the sales report comparison with two test orders, three scheduled backups with rotation, and a restore of the dump into an empty database with identical row counts and a matching checksum of product data.
- Not part of this build: numeric range filters for attributes.

# CodeCart 3.0.6.0 — Build 2.2.6 (2026-11-10)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- Installer: a clean installation selects the utf8mb4 database default; Core detects and safely repairs a database default that is not utf8mb4_unicode_ci.
- Extension lists: the real installation date is kept and shown (the ZIP upload date is gone), with compact enabled/disabled status badges on every extension type.
- Blog: AJAX status and indexing switches in the article list with authorization, independent storefront controls for publication date, ratings and view counters, and the same flags honoured by the blog modules and cards.
- Newsletter: responsive branded renderer with built-in localized designs, logo, signature, unsubscribe link and a readable text version; server-side desktop and mobile preview; the same sanitization for preview and sending.
- Downloads: paid downloads and public documents share one responsive admin layout with search, sortable dates and download counts.
- Mail Audit: missing storage is repaired on demand; browsing the history never changes it.
- PDF icon included in the Core icon package.

# CodeCart 3.0.6.0 — Build 2.2.5 (2026-11-09)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- Transactional e-mail without a server Cron: build 2.1.8 stopped the visitor heartbeat from running the mail queue when the host blocks loopback requests. On such a host password resets, registration and order e-mails stayed in the queue and were never sent. The heartbeat again delivers the mail queue itself; only currency refresh, mail campaigns, cart reminders and stock notices are left to a real Cron.
- Administrator two-factor setup: the recovery codes were shown as one unbroken string (Twig drops a newline that directly follows a tag). They are listed one per line again.
- Release checks cover both fixes (`tests/heartbeat_policy_smoke.php`, `tests/two_factor_template_smoke.php`).
- Verified end to end on a fresh installation: registration, password reset and order e-mails, two-factor sign-in with a code, recovery codes (single use), lock-out after repeated wrong codes, contact form, compare, wishlist, review validation, search.

# CodeCart 3.0.6.0 — Build 2.2.4 (2026-11-09)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- A new installation now enables the Core Product structured data (JSON-LD) switch. Before, the row was missing from the installation SQL: the settings page showed "Enabled" while the storefront printed no Product, Organization or BreadcrumbList data until the settings were saved once. Merchant Center and search engines read this markup from the initial HTML of the product page.
- Updated stores keep the switch as it is (off when it was never set), so no duplicate markup appears next to a theme or SEO extension.
- A Playwright test checks that the product page of a fresh installation carries Product JSON-LD with price, currency and availability in `<head>`.

# CodeCart 3.0.6.0 — Build 2.2.3 (2026-11-08)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- `jquery-2.1.1.min.js` is back as a 1 KB compatibility stub (storefront and administration). Themes and modules made for ocStore/OpenCart 3 that link to this file (for example Listore, NiceTheme, invoice templates) were left without jQuery after build 2.2.2. The stub loads the bundled jQuery 3.7.1 with jQuery Migrate 3.4.1 and does nothing when jQuery is already on the page, so a module that adds the old file after jQuery 3.7.1 no longer replaces it.
- The old 85 KB jQuery 2.1.1 library and the Font Awesome 4 font files stay removed. Font Awesome 4 class names are served by Font Awesome 6.7.2 (784 of 786 icon names; two discontinued brands are not in Font Awesome 6 either).

# CodeCart 3.0.6.0 — Build 2.2.2 (2026-11-08)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- Slideshow, banner and carousel (`codecart-slider.js` 1.2.3): after the slide change the strip slid back to the start with a second animation and showed the next picture for a moment. The reset to the start position is now flushed before transitions are re-enabled, and a size change during a slide change (scrollbar, image load, font swap) no longer resets the strip in the middle of the move.
- `jquery-2.1.1.min.js` is no longer shipped as a library (storefront and administration); build 2.2.3 replaces it with a compatibility stub.

# CodeCart 3.0.6.0 — Build 2.2.1 (2026-11-08)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- Font Awesome 4 font files are not shipped: Font Awesome 4 class names and `font-family: FontAwesome` are served by the bundled Font Awesome 6.7.2 compatibility layer. `jquery-2.1.1.min.js` stays in the package (not loaded by CodeCart) for ocStore-era themes that link to it directly.
- Storefront telephone fields: the input pattern is valid in current browsers (Chrome compiles `pattern` with the `v` flag and previously rejected it, so the format was not checked).
- Verified: legacy ocStore `DB_DRIVER` `mpdo` with storefront, administration and a browser checkout on MariaDB 10.11 and PHP 8.4.

# CodeCart 3.0.6.0 — Build 2.2.0 (2026-11-08)

Compatibility release. Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

- Font Awesome 4 font files (`font-awesome/fonts/`) and `jquery-2.1.1.min.js` are shipped again in the storefront and administration, as in OpenCart 3.0.5.1. They are not loaded by CodeCart; themes and modules made for ocStore/OpenCart 3 that link to these files directly keep working on new installations.
- jQuery Migrate 3.4.1 stays enabled after jQuery 3.7.1 (warnings muted): it restores APIs removed in jQuery 3 that older ocStore-era modules call.
- Release QA: the browser checkout gate installs Playwright from the pinned project dependency and shows its output.

# CodeCart 3.0.6.0 — Build 2.1.9 (2026-11-08)

Core schema 3.0.6.0; presentation schema revision 35 (unchanged).

Added
- Cloudflare Turnstile captcha extension (Extensions → Captcha). Off until configured; verification fails closed when Cloudflare is unreachable.
- jQuery Migrate 3.4.1 after jQuery 3.7.1 in the storefront, administration and installer, so OpenCart 3 modules written for jQuery 1.x/2.x keep working; warnings are muted.
- Content Security Policy mode (CodeCart Core → Security): report-only (default) or enforce. In the administration the enforced policy and frame protection are sent as one header.
- Core / Compatibility → About system: build, schema and live runtime data, component versions compared with ocStore 3.0.3.7 and OpenCart 3.0.4.1 / 3.0.5.1, grouped changes and advantages.

Fixes
- Storefront CMS model called a non-existent customer method (fatal error for logged-in customers).
- Download file names were always replaced by `download.bin` because of a broken pattern.
- PayPal webhook retries on timeouts are limited to three attempts.
- PHP 8.1–8.5: removed deprecated `imagepolygon()` point count, explicit `fputcsv()` escape, no `imagedestroy()` / `curl_close()` / `finfo_close()` calls.
- PayPal capture in the administration kept an undefined transaction status.
- Store health, preflight, mail delivery and relation clean-up failures are logged instead of being silently ignored.

Removed
- An empty migration step and an orphaned template.

# CodeCart 3.0.6.0 — Build 2.1.8 (2026-11-08)

Final audit release. Core schema 3.0.6.0; presentation schema revision 35 (applied by the Installer UPDATE).

Fixes
- Database: mail campaign, mail suppression and stock notification tables are created with `utf8mb4_unicode_ci`. On MySQL 8 they previously got `utf8mb4_0900_ai_ci`, and building a customer mail campaign failed with error 1267 (illegal mix of collations). The update converts existing tables.
- Fresh installations record the current presentation schema. Before, a new store reported "update required" forever, which skipped administrator device authorization.
- Security: failed password logins can no longer fill the two-factor lockout of an administrator; device authorization codes are limited to 5 per account per hour; customer device authorization and GDPR request forms require a CSRF token; the public API rate limit uses the real client IP, cleans up its files and also covers `api/v1/product`; the API hides prices and stock when the store hides them from guests.
- Scheduler: visitor-triggered runs no longer perform network or mail tasks (currency rates, mail, reminders, stock notices) inside a page request and are skipped while a server Cron is active; the lost-URL cleanup task no longer fails without a key; guest cart reminders work for additional stores; consent saving works on HTTP stores and behind TLS proxies; resolved queue notices are cleared and old resolved/archived notices are purged.
- Compatibility with OpenCart/ocStore 3.x extensions: event triggers without wildcards match by prefix again; Twig 2 class names (`Twig_Environment`, `Twig_SimpleFunction`, ...) are aliased to Twig 3; ocStore `DB_DRIVER` `mpdo` is accepted; the installer uses the same SQL mode as the store; PHP 8.4 deprecation in catalog import removed.
- SEO: unprefixed sitemap, feed, payment callback and API URLs are not redirected when every language has a URL prefix; SeoPro returns 404 for unknown path segments, notices changed SEO keywords and keeps encoded query values in canonical redirects; the language and currency switchers keep encoded query values.
- Catalog import: failed or abandoned uploads are removed; file installation errors after a committed import are reported as warnings; NBU settings show their save confirmation; all carrier validation errors are shown.
- Theme: product card content and attribute limit follow the active theme's settings; category and brand wall controls are translated.

Removed
- Duplicate upgrade step 3054, the legacy `php.ini` in the web root (delete it from existing sites), legacy catalog import actions in `tool/backup`, redundant helper guards and development leftovers.

# CodeCart 3.0.6.0 — Build 2.1.7 (2026-11-07)

- System notifications: a deleted notification no longer comes back. Every successful Cron run resolved its task notice, and `resolve()` turned the hidden ("dismissed") row into "resolved", which is listed again; daily tasks therefore restored deleted notices the next day. A hidden notice now becomes "archived" when the problem is fixed and stays in the Archive tab. It reappears only if the problem returns after a fix or its severity rises; a changed failure count no longer reopens it. Deleting or clearing an already resolved notice archives it.
- Keep schema 3.0.6.0; `archived` uses the existing `status` column.

# CodeCart 3.0.6.0 — Build 2.1.6 (2026-11-02)

- Security: administrator two-factor verification is enforced. In earlier builds `startup/totp` tested `isset($this->user)`, which is always false on `Controller`, so the code was never requested and a password alone opened the administration.
- Fail closed: an unreadable two-factor state blocks the administration instead of skipping the check. Only a store that still needs the Installer UPDATE and has no MFA table yet is let through.
- While the second factor is pending, the header, menu, footer, notifications and search behave as signed out.
- After a correct code the session ID and `user_token` are replaced and the originally requested page opens.
- Only failed codes count: 5 per 15 minutes and 20 per 24 hours per account, 15 per 15 minutes per source. The account owner receives an e-mail when verification is locked.
- New "Two-factor verification" page in the profile menu for every administrator: set up, disable, new recovery codes. Each action needs the account password, and the owner is notified by e-mail. View-only accounts (no modify rights, such as a shared demo login) cannot bind the account to a phone.
- Mandatory two-factor per user group (CodeCart Core → Security). Members are sent to set it up at the next sign-in, confirm the setup with a code sent to the account e-mail and cannot disable it.
- The verification form checks the admin token, so a foreign page cannot post wrong codes to lock an account. Missing-table detection also recognises PDO errors (SQLSTATE 42S02).
- Users list shows the 2FA state. An administrator with modify rights on users and CodeCart Core can reset another administrator's second factor after entering their own password; the target is notified.
- Requests with a non-string `route` parameter are normalized instead of failing with HTTP 500.
- Keep schema 3.0.6.0.

# CodeCart 3.0.6.0 — Build 2.1.5 (2026-11-02)

- Cron: SeoPro no longer canonical-redirects `cron.php` and `route=cron/*` requests, so HTTPS Cron URLs and the administration "Run now" button stop failing with HTTP 301. A redirect configured on the hosting side is now reported with its HTTP code and target address.
- Abandoned carts: the consent bridge for guest contacts is injected after SEO URL decoding (`startup/startup/cartRecovery`), so it also loads on `/checkout` and other SEO checkout addresses. Guest contacts store the storefront language; reminders are sent in that language with a language-prefixed one-time recovery link.
- Missing tables are detected by MySQL error 1146 (`CodeCart\Core\DatabaseError`) and created on first use for guest contacts, reminder consent and the Cron run history/heartbeat; fresh installs create the Cron history and heartbeat tables.
- Cron screen: server Cron status with its source, last site-visit run, localized short intervals, success results without the "Technical details" wrapper, "Success" labels and a "Site visit" source in the run history, columns that no longer wrap.
- Abandoned carts screen: sticky action column for the wide table, "No contact left" / "No opt-in" wording.
- Keep schema 3.0.6.0.

# CodeCart 3.0.6.0 — Build 2.1.4 (2026-11-01)

- Generate storefront SEO links from a request-scoped index: taxonomy aliases load once per store/language, product and article aliases are prefetched by listing models, misses are remembered. Applies to native SEO URLs and SeoPro.
- SeoPro cache: the cached keyword map is authoritative while the highest seo_url_id is unchanged, product category paths survive between requests and cache files are rewritten only when changed.
- Batch per-item lookups in compare, header product links, product attributes, category filters, breadcrumbs, blog subcategory counts, blog article lists, account addresses and order options; share layout and module lookups between the four layout positions.
- Lock checkout stock with one ordered statement per table; restock and reprocessing read order options once per order.
- Administration: batch product autocomplete options, customer list login attempts, customer autocomplete addresses, IP totals, form selections, bulk invoice/shipping printing and related/link-table writes; rebuild category paths in memory.
- Cron and import: mail campaigns and stock notifications read suppression, settings and option stock once per batch; catalog import writes main categories per chunk; the SEO alias upgrade backfill preloads existing aliases.
- Keep schema 3.0.6.0 and public model methods.

# CodeCart 3.0.6.0 — Build 2.1.3 (2026-10-23)

- Add explicit contain/cover product image processing, configurable enlargement limits and separate CSS backgrounds for catalog cards, galleries and thumbnails; retain legacy resize contracts.
- Reuse optional native WebP/AVIF conversion after scaling; retain transparency, distinct mode cache keys and AVIF-only Accept cache variation.
- Align mobile article carousel columns with the existing grid breakpoint below 768px, preserving merchant column settings and desktop/list layouts.
- Add validated POST bulk enable/disable for selected OCMOD modifications with confirmation and existing compatibility dirty signals.
- Keep schema 3.0.6.0.

# CodeCart 3.0.6.0 — Build 2.1.2 (2026-10-22)

- Use native administration form submission instead of submitting every form from a legacy IE click handler.
- Ignore outdated autocomplete callbacks after newer requests, input changes, blur or Escape, preserving empty selectors and the public callback API.
- Restore the existing product-view increment in the successful product page controller, leaving list and AJAX consumers unchanged.
- Retain schema 3.0.6.0 and existing extension interfaces.

# CodeCart 3.0.6.0 — Build 2.1.1 (2026-10-21)

- Align the package build reported by storefront, administration, installer, CLI and cron entry points.
- Normalize production ZIP metadata and include a complete entry manifest with file sizes, SHA-256 hashes and source revision.
- Update optional extension packaging to include installer metadata and the maintained compatibility package builder.
- Rebuild bundled documentation from current sources with deterministic ZIP metadata, retaining accepted licenses and notices; verify it in the release workflow.
- Restore the production autoloader and database capability metadata in standalone SEO regression fixtures, preserving existing assertions.
- Serialize administrator TOTP attempts from a shared source across accounts, retaining account limits and replay protection.
- Apply additive presentation revision 33 to undersized IPv6 and order shipping-company columns, including stores already using core schema 3.0.6.0; synchronize fresh-install and CI markers.
- Publish the presentation revision after a successful upgrade journal and core-version save, allowing failed upgrades to retry safely.
- Correct database and legacy-framework analysis contracts, with scoped development-only stubs and regression checks for the affected administration and catalog behavior.
- Validation of the final source and archive is pending.

# CodeCart 3.0.6.0 — Build 2.1.0 (2026-10-16)

- Replace formatting-sensitive extension table and translation-key checks with rendered structure checks and PHP token analysis; add mutation regressions.
- Wire imported notification, language, integrity and SEO checks into the release gate. Missing gates and XML support now fail explicitly; failed checks no longer print contradictory PASS lines.
- Update runtime workflows to compare staged source with the current checkout instead of requiring an obsolete build number.
- Keep schema version separate from package build in integrity generation, write manifests atomically, and retain the GPL license in production archives.
- Review development comments and compatibility behavior. Native theme behavior still requires deployment-specific verification.
- This source revision is not a declaration of production acceptance.

## CodeCart 3.0.6.0 — product list UI patch 3.0.6.4 (2026-10-14)

- Corrected left/right pagination alignment by removing Bootstrap grid classes from the toolbar.
- Added stylesheet URL version parameter for cache busting.
- Product table and core functions unchanged. Underlying core remains 3.0.6.0.

# CodeCart 3.0.6.0 — Product list UI patch 3.0.6.3 (2026-10-14)

- Product listing: one-line truncated product names and models with full-text tooltips.
- Product listing: horizontal view/edit actions and 20/30/50/100 products per page.
- Restored native admin layout by moving the product-list stylesheet inside #content.
- Column widths support third-party OCMOD photo-count columns.
- Aligned pagination to the left and result count/page size to the right.
- Refreshed the product-controller SHA-256 in the integrity manifest to reflect the bundled file.
- This is a full distribution containing the product-list patch; the underlying platform core remains 3.0.6.0.

# CodeCart 3.0.6 (2026-10-10)

- Product list: removed two bulk extra-tab controls from the toolbar. Existing per-product extra tabs, saved content, endpoints, and storefront behavior remain unchanged.
- Category carousel: use matching, single-chevron previous/next icons from the bundled Font Awesome 4 set. Category corner-link icon also uses the bundled icon family.
- Expanded English and Ukrainian project descriptions and browser-based Codespaces publishing instructions. Public project name displays CodeCart 3.0.6 without internal build labels.
- Refreshed the core file-integrity manifest to the actual packaged content, including changes already present in the prior update.

# CodeCart PRO 3.0.6.0 — Production Build 2.0.9 (2026-10-10)

- Fixed homepage hreflang: native language folder roots always end with /, regardless of SeoPro's trailing slash setting for content pages.
- SEO routing delegates language detection to configured language-prefix providers, preventing locale changes from unrelated SEO keywords.
- SeoPro no longer canonicalizes unresolved paths or missing entity SEO URLs to the homepage.
- Product edit: removed the separate Documents tab; free public documents now appear directly beneath paid Downloads in Links, with matching add/remove controls and safe autocomplete rendering.
- Harmonized the public document creation form and manager actions with Downloads. No changes to stored documents, paid downloads or access restrictions.

# Changelog

# CodeCart PRO 3.0.6.0 — Production Build 2.0.8 (2026-10-09)

## Public documents: simplified product-first workflow

- Public document editor now contains only document titles and the file upload. Titles are shown for all currently **active** languages in the OpenCart/ocStore language settings, with matching language flags, like paid digital download names. Original PDF/DOC/DOCX/XLS/XLSX/image/TXT/ZIP rules (25 MiB max) remain unchanged.
- Document type, placement, numeric product/category ID, sorting and publication controls are removed from the public document **form**. Newly saved documents are published automatically; editing an existing document retains its file, descriptions, counters, prior category affiliation and sorting.
- Product form has a new **Documents** admin tab with an autocomplete search of previously uploaded public documents. Multiple documents can be associated with one product; the same document can be associated with multiple products. Removing an association never deletes the physical document.
- The old single-product association format from Build 2.0.7 is migrated safely into the new mapping table on first authorized admin use. Previously linked category documents remain linked, and all public download statistics remain unchanged. The operation is idempotent; it does not rewrite product content or touch paid product downloads.
- Public documents list displays filename, totals, 30-day downloads and download date, without redundant type/placement/status columns. Product and category storefront output remains compatible with bundled views; custom overrides require separate verification.
- Adds `codecart_document_to_product` (InnoDB, DB_PREFIX) to the clean-install SQL, the core schema helper, and installer upgrade script 3054. Existing-store overlay upgrades never require running the installer; migration activates after the first authorized admin visit.
- Paid digital files, order-status entitlements, checkout, paid download counts and storage directories are unchanged. The store is not opened to unauthenticated paid downloads.

## Deployment and limitations

Before installation or upgrade, make a verified backup of site files and database. Install the full Production archive only on a new store. For an existing Build 2.0.7 use the small Existing_Store_Update package to overlay the upload tree (not the installer). Purge Twig/OCMOD caches; open Catalog → Public Documents or an authorized product edit page to activate the new mapping table, then test selecting, saving, editing and removing a document and the storefront tab.

Static tests: PHP 8.4 token parser over all included PHP, PHP lint for changed files, Twig parse for document and product templates, documented SQL/schema inspection, archive and checksum verification. No end-to-end MySQL/MariaDB integration or runtime browser session against a production site has been performed.

# CodeCart PRO 3.0.6.0 — Production Build 2.0.7 (2026-10-09)

## New: public document library

- Separate admin entry **Catalog → Public Documents** next to the built-in paid Downloads section.
- Upload public manuals, certificates, catalogs and technical drawings with names in all active store languages (25 MiB maximum per file; PDF, DOCX/DOC, XLSX/XLS, JPG/PNG/WEBP, TXT and ZIP).
- Public documents are disabled by default, stored in `DIR_STORAGE/public_documents/`, outside the public web root, and always served via an explicit downloadable route with a randomized physical filename and safe attachment headers.
- Link to a product or category via the typeahead lookup (or by numeric ID); CodeCart and default storefront templates show the file automatically when published.
- Separate download statistics for public documents and paid digital files: total, previous 30 calendar days, and last download. Counting starts after this build is installed; old download history is not recoverable from existing aggregate sales records. A count represents a successful streaming attempt, not proof that a remote user kept a full copy.
- Paid digital download authorization is unchanged: only signed-in purchasers whose order is in a configured completed status can retrieve their files. Public document access never reads or bypasses the paid-entitlement tables.
- Fresh-install completed statuses default to **Complete (ID 5)** rather than Complete + Shipped/Delivered. Existing stores retain their configured statuses without changes. This prevents shipping-only status from automatically unlocking paid digital downloads on new stores.
- Adds five independent InnoDB tables using the configured DB_PREFIX, with a fresh-install schema and idempotent upgrade migration (`3053.php`). Overlay upgrades safely initialize the new tables on first authorized visit to Catalog → Public Documents.
- Compatible with the independent OpenCart download records: replacing public documents does not delete previous physical files, and public documents cannot be accessed when their record or feature is disabled.

## Existing release fixes retained

Installer text padding, single chevron category navigation, clean demo banners, consolidated OCMOD diagnostics, status counters and restored OCMOD log tab from 2.0.5/2.0.6 remain unchanged.

## Security and deployment

**Back up the database and website files before installing or upgrading.** Existing-store update packages intentionally omit `install/`, demo data, credentials, config.php, vendor libraries and mutable storefront assets. When uploading public documents, storage must be outside the public web root. The administrator must have access/modify rights to either `catalog/public_document` or the existing `catalog/download` route.

The core adds no encrypted-code runtime dependency. External extensions may require separately configured loaders; do not change core classes to mask incompatible extensions.

**Tests performed:** PHP 8.4 token parser (all supplied PHP), modified PHP `php -l`, Twig parser for affected templates, SQL statement parser, table schema mock and SHA-256 checks. Functional installation, SQL execution against live MySQL/MariaDB, payment provider integration and end-to-end browser checks on user sites were not run and must be validated on staging.

## 3.0.6.0 — Production Build 2.0.6 (2026-10-09)

- Installer: add reliable, responsive horizontal padding to the administration login notice at the end of installation.
- CodeCart Theme: use symmetrical single-chevron previous/next buttons on the Popular Categories carousel.
- OpenCart demonstration slideshow: replace the last two banners (Samsung and Hewlett-Packard) with clean product-only photos on a plain white background. No branded background graphics or promotional overlays.
- Retain all OCMOD fixes from Build 2.0.5 and verified runtime dependencies. No proprietary theme code or encrypted-code runtime loader is bundled.
- Update the build constants and SHA-256 file-integrity manifest to match this production package.

Validation: PHP syntax/static checks, Twig parser, manifest validation and ZIP CRC. Live install and storefront regressions must be verified on a staging deployment before production rollout.

## 3.0.6.0 — Production Build 2.0.5 (2026-10-09)

Maintenance release integrating confirmed OpenCart Modification (OCMOD) fixes:

- Restore visible “Modifications” and “OCMOD log” tabs on existing upgraded stores by clearing Bootstrap layout floats.
- Account for system-level XML OCMOD sources during compatibility checks, including full rollbacks and skipped optional operations, without treating them as DB-installed extensions.
- Keep system-level XML out of the editable extensions table; log and aggregate diagnostics remain accessible.
- Keep modification action buttons compact on one line and allow horizontal table scrolling on narrow screens.
- Distinguish informational diagnostics from blocking modification failures; preserve both light and dark admin styling.
- Update the file integrity manifest to the exact verified contents of Build 2.0.5.
- Include the root GPLv3 LICENSE and align Composer license metadata, while preserving third-party license notices.

Site-specific notes: the legacy `mms.multiselectimage.ocmod.xml` targeting `.tpl` belongs to the installed store and is not part of the CodeCart core release; disable it only after testing the newer multiple-image workflow. External encrypted extensions retain their own hosting requirements, not bundled in CodeCart.

Validation: archive structure, all manifest file digests, PHP 8.4 syntax, Twig parsing, archive checksums. Live installation/checkout/payment regression tests have not been executed for this release.

## 3.0.6.0 — Production Build 2.0.4

This entry identifies the supplied production archive `CodeCart-3.0.6-production-Build-2.0.4.zip`.

- OpenCart/ocStore-based CodeCart core and bundled CodeCart Theme.
- Installation, upgrade, compatibility, nginx and demo documentation.
- Bundled PHP Composer runtime dependencies for the production archive.

A change-by-change comparison against a previous published CodeCart build has not been supplied and has not been verified. Avoid claiming individual fixes, migrations or performance improvements without a tested comparison.

The recommended extension-compatibility target stated in the supplied documentation is PHP 8.1–8.3; the core describes support for PHP 8.4/8.5. Individual third-party components must be tested separately.

## Build 2.1.2

- Demo homepage slideshow: all four default banner images are now neutral CodeCart-style technology banners without brand logos, keeping the existing file names and installer links.

## Build 2.1.3

- Restored the classic default homepage slideshow banner images from the earlier demo set.
- Product page gallery thumbnails now use a horizontal carousel/scroll strip on mobile and desktop instead of wrapping into a tile grid.
- The product gallery shows navigation arrows only when the thumbnails overflow the available width.
- File manager modal footer updated: the multi-select Choose button is aligned cleanly with pagination and behaves better on narrow screens.

## Build 2.1.4

- Extensions > Themes: removed the preview image from the template list so only the theme name is shown there.
- Settings > Store / Main settings: kept the theme preview image and replaced it with the new CodeCart brand banner.
- Replaced CodeCart logo/banner assets across admin, installer, default logo assets, and demo brand images with the new logo.
- Homepage demo slideshow banners restored to the four generated CodeCart demo banners (replacing the legacy set).

## Build 2.1.5

- Replaced all CodeCart logo and theme preview assets with the corrected official CodeCart PRO logo provided by the user.
- Updated admin logo, installer logo, default fallback logo, catalog brand images, and the theme preview image used in store settings.

## Build 2.1.6

- Fixed a fatal Twig parsing error in the admin file manager introduced by inline CSS containing an unintended Twig comment opener.
- Moved the file manager selection-footer styles into the admin stylesheet, preserving responsive layout without Twig conflicts.
- Updated the administrator CSS cache version and integrity records.

## Build 2.1.7

- Theme preview in Stores / Settings now shows a genuine screenshot of the demo storefront, not the CodeCart PRO brand logo. Theme list stays text-only.
- Theme preview URLs include the image modification timestamp to prevent a stale cached picture.
- Retains the Twig file manager unclosed-comment fix from Build 2.1.6 and the official CodeCart PRO logo elsewhere.

## Build 2.1.8

- Normalized active Ukrainian, English and Russian language keys across admin, storefront and installer; preserved legacy optional extension files without creating fictitious controllers.
- Added and aligned 458 English and 443 Russian active language entries, eliminated duplicate definitions, and retained formatting parameter compatibility.
- Localized product gallery navigation, CodeCart editor tools, modal tooltips and AJAX validation messages.
- Back-in-stock email notifications now use dedicated per-language resources for Ukrainian, English and Russian recipients.
- Added a portable i18n CLI audit and multilingual QA documentation.

## Build 2.1.9

- Native language URL routing: interpret recognized legacy `?language=code` selectors only when native language folders are active; physical language prefixes still take precedence.
- SeoPro and standard SEO links: canonicalize old catalog URLs to the corresponding localized paths without the obsolete parameter, preserving supported query arguments and avoiding redirect chains.
- A controlled lookup of default-language aliases supports legacy cross-language product links without mixing target-language SEO cache entries.
- Keep unknown SEO URLs as 404; skip language canonicalization for POST, AJAX, API, payment and feed routes.
- Add automated regression checks for legacy language links, generated product URLs and native startup language selection.

## Build 2.2.0

- System notifications: replace truncated 200-item listing with paginated status-filtered history.
- Dismissing recurring notices now persists until the underlying issue changes; the notice reappears when a resolved condition recurs.
- Clear-read no longer allows unchanged recurring warnings to reappear immediately.
- AJAX actions now show permission, network and server errors rather than silently failing.
- Added an offline notification service regression test and tri-language interface labels.

## Build 2.2.1

- Fixed double HTML escaping of admin notification URLs. Filter pagination and AJAX actions now pass a correct `user_token` without bypassing permission checks.
- Added an Archived filter for dismissed notifications and preserved resolved notification history instead of silently deleting it.
- Removed automatic 30-day notification history purge from the security cron task.
- Added localized session-expired errors for notifications on all three supported languages.

## Build 2.2.2 — Integrity status localization and manifest coverage

- Translate file integrity states in the Store Health dashboard into Ukrainian, Russian, and English without changing machine-readable state codes.
- Verify all previously monitored Core file hashes against the complete packaged release and extend coverage to first-party authentication, SEO language, notification, file manager, dashboard and CodeCart library components.
- Align the manifest version with the official 3.0.6.0 release and add read-only CLI manifest validation and documentation; existing security checks, license rules, and site data remain unchanged.

## Build 2.2.3 — Language resource cleanup

- Localized remaining confirmed user-facing English labels in Ukrainian and Russian admin language resources for shipping/API settings, Google login, currency sources, notifications, storefront health, relation scores and core diagnostics.
- Corrected Russian inherited English strings in retained compatibility language files without renaming language keys or changing technical identifiers.
- Removed a hardcoded English clipboard fallback prompt in the public document list; it now reuses the translated button title.
- Added a read-only, tri-language coverage check for shared dictionaries, value shapes, format specifiers and static language-file references in installer, admin and catalog.
- Kept legacy language packs for optional extensions unchanged except for applicable existing label translations; no executable routes, data or application permissions were changed.

## Build 2.2.4

- Added client-side sorting for all extension categories by name, status, installation status and installation date.
- Extension install date is now recorded on new installations without changing database schema; historical dates are not invented.
- Preserved nested module/store instances and AJAX refresh behavior; added Ukrainian, English and Russian labels.

## Build 2.2.5

- Extensions: added an independent sortable Upload date column alongside Installation date in all 14 extension types.
- Upload timestamp is linked only to a recorded installer package that supplied the extension controller file. Unknown historical dates remain unknown.
- Added language labels in uk-ua, ru-ru and en-gb and extended extension sorting regression tests.

## Build 2.2.6

- Extensions: restored missing status cells in legacy rows so all 14 tables align correctly with their headers.
- Fixed nonfunctional sorting: delegate header clicks at document level because extension lists load through AJAX after the script initializes.
- All five sortable headings now display full localized labels and sort directions, including after an AJAX refresh; grouped module/store rows stay together.
- Fixed 6/7/8-column widths, fixed table layout and mobile-only horizontal table scrolling without widening the admin page.
- Existing unknown historical upload/install dates remain unknown rather than being fabricated. Updated automated extension-table regression checks.

## Build 2.2.7 — Extensions layout and alignment

- Fixed the original admin-sidebar overlap: Styles and scripts previously placed between `#column-left` and `#content` broke the core adjacent-sibling layout selector. These now reside inside `#content`, preserving the standard admin layout.
- Normalized extension table columns: module name left; status, installed, upload/install dates centered; action heading and buttons aligned together in the center.
- Removed the oversized fixed table minimum widths, using compact 6/7/8-column ratios and a horizontally scrollable table container on narrow screens.
- Preserved all sorting handlers, AJAX reloads, nested module rows, dates, permissions and extension settings.
- Added layout regression assertions to the extension sort QA test.
