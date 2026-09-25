# CodeCart PRO 3.0.6.0 Build 1.7.6

Upgrade reliability
- Installer 2.0 ignores stale legacy OpenCart/ocStore migration files left on disk by overlay updates and executes only CodeCart 3.0.6 migrations (3052+).
- Fixes the reproduced ocStore 3.0.4.1 upgrade failure where legacy migration 1010 was re-run and queried the already-removed url_alias table.
- Clean-install behavior is unchanged; UPDATE continues to preserve config, active theme, catalog and SEO data.

Runtime QA
- The previous production artifact passed clean-install runtime QA on MariaDB 11.4 and MySQL 8.4, including storefront/admin HTTP smoke, Redis tagged cache, guest COD checkout, duplicate callback protection, persistent mail queue, SMTP delivery and Scheduler.
- Build 1.7.6 is rebuilt and re-tested through the same production and ocStore upgrade pipelines.

# CodeCart PRO 3.0.6.0 Build 1.7.6

Upgrade reliability
- Installer 2.0 ignores stale legacy OpenCart/ocStore migration files left on disk by overlay updates and executes only CodeCart 3.0.6 migrations (3052+).
- Fixes the reproduced ocStore 3.0.4.1 upgrade failure where legacy migration 1010 was re-run and queried the already-removed url_alias table.
- Clean-install behavior is unchanged; UPDATE continues to preserve config, active theme, catalog and SEO data.

Runtime QA
- The previous production artifact passed clean-install runtime QA on MariaDB 11.4 and MySQL 8.4, including storefront/admin HTTP smoke, Redis tagged cache, guest COD checkout, duplicate callback protection, persistent mail queue, SMTP delivery and Scheduler.
- Build 1.7.6 is rebuilt and re-tested through the same production and ocStore upgrade pipelines.

# CodeCart PRO 3.0.6.0 Build 1.7.5

Localization / QA
- Removed confirmed hardcoded Ukrainian UI strings from first-party PHP/Twig/JavaScript paths and routed them through `uk-ua` language files.
- Storefront/admin generic icon-button tooltips now receive Ukrainian labels from language files via a small JSON i18n bridge; JavaScript no longer embeds Ukrainian UI copies.
- Form Builder and Purchase Blocks Ukrainian presets now come from PHP language files; JavaScript remains language-neutral for Ukrainian.
- Stock-notification Ukrainian subject/body/button strings moved to `catalog/language/uk-ua/cron/stock_notify.php`.
- Google Login secret/copy tooltips and admin report-mail subject/body moved to Ukrainian language files.
- Carrier-directory Ukrainian fallback branch label is supplied by the loaded shipping language file instead of the system library.
- Product page no longer identifies the bundled question form by a translated Ukrainian module name; it resolves the `codecart_form` module from the stable module code and form field schema.
- Added `tools/check_uk_locale.php` quality gate to detect new first-party hardcoded Ukrainian UI text outside language files while excluding demo seed content, transliteration dictionaries and third-party locales.
- Synchronized package/docs current build marker to 1.7.5; README latest-build line corrected from the stale 1.6.9 value.

# CodeCart PRO 3.0.6.0 Build 1.7.4

## Transactional mail queue
- System transactional mail is queued in the persistent CodeCart queue instead of waiting for SMTP inside checkout/account requests.
- Queue payloads contain message content and store_id only; SMTP credentials are resolved at worker execution time and are never persisted in queue arguments.
- Added `core.queue.worker` Scheduler task and `cron/mail_delivery` worker with retry/stale recovery inherited from the shared Queue service.
- Background campaign/stock-notify workers keep synchronous transport semantics so their own sent/failed state remains accurate.
- Attachment-bearing messages remain synchronous to avoid losing temporary attachment files.
- Queue infrastructure failure falls back to synchronous delivery so transactional mail is not silently lost.

# CodeCart PRO 3.0.6.0 Build 1.7.3

Security
- Added explicit POST + modify permission + user_token validation to PayPal capture, reauthorize, void, refund and tracking mutation endpoints.
- Completed checkout attribute-context escaping in CodeCart and default themes for dynamic form values while preserving compatibility with global Twig autoescape=false.
- SecurityAudit now uses the trusted-proxy-aware CodeCart client IP resolver.
- Replaced the remaining PayPal internal mt_rand token helper with random_int.

Commerce
- Made editOrder reversal and replacement atomic: processed-order stock, totals and affiliate reversal now occur inside the same transaction as the edited order write.

Performance
- Removed the standard catalog getProducts()/getProductSpecials N+1 hydration path. Product pages of IDs are now hydrated in one batch query while preserving the historical full result contract.

Reliability
- Added stale-processing recovery for persistent mail campaign and stock notification workers. Rows interrupted for more than 15 minutes are safely retried or failed after the retry limit.
- Stock notification worker now catches transport exceptions without aborting the whole batch.
- Preflight explicitly reports XMLReader availability and warns when very large XLSX imports will fall back to the higher-memory SimpleXML path.

# CodeCart PRO 3.0.6.0 Build 1.7.2

## Build 1.7.2

### Performance / scalability
- Cart::getProducts() now batch-loads cart products, selected option metadata/values, active discounts, specials, rewards, downloads and recurring profiles instead of issuing SQL per cart row/option. Request-local memoization from 1.7.0 is preserved.
- HTML sitemap builds the product-category tree from one getAllCategories() query instead of recursive category SQL calls. Blog category access already uses the model's in-request tree cache.
- Catalog Transfer adds XMLReader-based bounded-memory XLSX sheet streaming. Preflight and import process workbook rows incrementally when XMLReader is available; the legacy SimpleXML reader remains a compatibility fallback.

### Marketing / mail
- Back-in-stock subscriptions now support stock-tracked product option values (for example size/color): unavailable values remain selectable for notification, the subscription stores the selected stock values, and Scheduler sends only when the product and selected values are available again.
- Mass mailing is now persistent: clicking Send creates a campaign and durable recipient queue instead of requiring the admin browser tab to stay open through recursive AJAX batches.
- Scheduler task core.mail.campaign processes queued mail in bounded batches with retry, per-store SMTP settings, progress counters and recent campaign history in Marketing → Mail.
- Personalized 256-bit unsubscribe tokens are generated per queued recipient. Unsubscribe creates a persistent suppression entry and disables customer.newsletter when a customer account is known. Suppressed addresses are excluded even from the explicit “All customers” marketing audience.

### Database
- Presentation schema 26 adds mail_campaign, mail_campaign_queue and mail_suppression tables and extends stock_notify with option-specific subscription metadata.

### QA scope
- Modified PHP files were syntax-checked on PHP 8.4.23. XMLReader/Zip/SimpleXML runtime streaming still requires a server with the normal php-xml/php-zip extensions; this container lacks those extensions, so the new streaming path is static-reviewed here and remains part of the release runtime matrix.

---

# CodeCart PRO 3.0.6.0 Build 1.7.1

## Build 1.7.1

### Fixed / completed from independent audit
- Online visitors now use a hashed session visitor key instead of IP as the primary identity. Multiple users behind the same NAT/IP no longer overwrite each other; IP remains indexed for filtering. Presentation schema 24 migrates existing rows safely.
- Category Wall loads all visible child categories in one query instead of one query per parent card.
- Product page options load all option values in one query instead of one query per option group.
- Stock-notification administration now has filters, sortable columns, configurable page size, checkboxes, bulk delete/retry, statuses and standard CodeCart table styling.
- Catalog Transfer download now uses Response::setFile() and never loads the completed XLSX/ZIP into PHP memory.
- Catalog Transfer XLSX export writes worksheet XML to temporary files row-by-row and reads database rows in 1000-row batches; it no longer builds whole worksheet XML strings or table rowsets in PHP memory.
- Catalog Transfer pre-import SQL snapshot now reads source tables in 500-row batches instead of SELECT * into one PHP result set.

### Scope note
- XLSX import parsing itself still uses the compatibility SimpleXML reader and therefore remains size-limited by PHP memory for very large workbooks. Export and pre-import snapshot are now bounded-memory; streaming import is tracked separately because it requires an independent parser/refactor.

---

---

# CodeCart PRO 3.0.6.0 Build 1.7.0

## Build 1.7.0

### Fixed
- Google Merchant Feed: initialized the per-generation product type cache before use, preventing PHP 8 TypeError during feed generation.
- Marketing mail: selected store SMTP engine/host/user/password/port/timeout are now used for multistore sends instead of the current admin store SMTP settings.
- Gift vouchers: storefront/API-generated codes now use cryptographically secure random_bytes(); voucher/order_voucher code columns support 32 characters; fresh installs enforce UNIQUE voucher codes and UPDATE adds the unique index when existing data has no duplicates.
- PayPal and upload security tokens: replaced mt_rand()/uniqid()-based token generation with random_bytes()/random_int().
- Cart hot path: request-local getProducts() memoization avoids repeating the full product/options/discount/download query chain multiple times during the same checkout request; mutations invalidate the cache.

### Database
- Presentation schema 23: widens voucher and order_voucher code fields and adds a unique voucher-code index when safe.

### QA note
- This build fixes the highest-priority blockers identified by the independent Build 1.6.8/1.6.9 audit. Remaining audit items are tracked separately; this entry does not claim full production-ready status.

---

---

## Legacy 1.6.x history

The previous changelog snapshot had several older 1.6.x sections accidentally relabeled as Build 1.6.9 during a bulk version update. Those labels were not reliable release provenance, so they are no longer presented as exact per-build history. The underlying functionality remains documented in source comments and later verified 1.7.x entries. Future releases preserve historical headings instead of globally replacing version strings.