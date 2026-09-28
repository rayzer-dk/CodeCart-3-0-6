# CodeCart 3.0.6 Build 1.8.4

Security:
- Installer 2.0 UPDATE on an already installed store now requires store administrator credentials (a user with modify permission for users, extensions or Core). Previously any visitor could open /install/, read preflight diagnostics and run UPDATE/"Repair current build", which invalidated the OCMOD build marker and disabled all modifications (UniShop2 storefront lost its CSS/JS) until an administrator refreshed Modifications.
- Administration always sends X-Frame-Options: SAMEORIGIN and CSP frame-ancestors 'self' (opt-out: codecart_security_admin_frame_protection=0). Storefront framing settings are unchanged.
- Blog category/article enable/disable and PayPal recurring enable/disable now require modify permission (PayPal also POST + user_token).

Fixed:
- Product pages opened from a parent category returned 404 when the product belongs only to a subcategory, although category listings include subcategory products. checkProductCategory() now accepts descendants of the path via category_path.
- Product card rows (latest/bestseller/popular/special/featured modules, wishlist, recently viewed, related, CMS/blog products) again expose the full historical getProduct() contract (ean, isbn, mpn, date_available, manufacturer, weight, ...). UniShop2 and third-party OCMOD code no longer raise warnings and the "New" sticker works again.
- ru-ru admin and storefront language packs were partial overlays: an overlay update replaced complete ocStore ru-ru files and Russian stores fell back to English (cart, checkout, account, product page, admin). Complete ru-ru packs are shipped now; new CodeCart strings are translated.
- uk-ua: checkout step numbers are printf placeholders again (guest checkout showed "Step 6" where step 4 was expected); password-reset mail showed a literal "%s"; return activity lost its link; untranslated PayPal Smart Button strings.
- The default/codecart themes served the unminified stylesheet in the normal production state (developer_theme=1 is OpenCart's default) and the shipped codecart stylesheet.min.css was stale. The minified file is now built from the source with a SHA-256 marker and served unless a merchant edited stylesheet.css.
- robots.txt advertised /sitemap.xml on a clean install while the sitemap feed was not enabled (404 for crawlers). Web and CLI installers now enable the Google Sitemap feed.
- Text logo is an H1 only on the home page (no duplicate H1 on product/category pages); compact text logo on mobile.

Performance:
- Category/manufacturer/search listing query no longer evaluates rating/discount/special correlated subqueries for every matching row unless the active sort uses them (20k products: category page 0.55 s -> 0.25 s; manufacturer 0.44 s -> 0.16 s). Third-party modified SQL is left untouched.
- Theme Editor overrides and design/translation overrides are resolved with one query per request instead of one query per template/language file (home page 100 -> 57 SQL queries).
- Traffic heartbeat no longer runs scheduled work (currency providers, mail queue) inside the visitor request on mod_php/CGI: it uses fastcgi_finish_request/litespeed_finish_request or a detached loopback cron request, with automatic fallback when loopback is blocked.

Improved:
- Core / Compatibility > Database Schema: "Modernize tables" converts remaining MyISAM/non-utf8mb4 tables (including third-party tables not covered by Schema Registry) one table per request, for hosting without SSH. The modernization flag is refreshed after safe schema repair.
- Release gates: language pack completeness/placeholder gate, minified stylesheet freshness gate; heavy runtime/upgrade CI gates now run on source changes and the 3.0.5 baseline gate tests the current source instead of a pinned 1.7.8 artifact.

Upgrade note: after uploading 1.8.4 and running UPDATE (administrator sign-in required), refresh Extensions > Modifications.

# CodeCart 3.0.6.0 Build 1.8.3

Fixed:
- Fixed malformed inline JavaScript in checkout shipping carrier city results, reproduced by real Chromium/Playwright E2E.
- Aligned UniShop2 release gate with the Compatibility Framework: OCMOD satisfaction is an optional adapter capability rather than a mandatory base-interface method.
- Fixed Monobank webhook QA SQL quoting so signed webhook and stale-event idempotency are exercised on live MariaDB.
- Proxy no longer exposes absolute server paths to visitors when an unavailable method is called; details go to the server log.
- Google Login secret is no longer rendered back into admin HTML; blank input preserves the stored secret.
- API v1 search has a per-IP rate limit.
- Legacy Braintree cleanup deletes only known stock controller/template files by SHA-256 and never removes an active integration.
- Active legacy Divido is preserved during update and reported as a compatibility warning instead of being removed.
- UniShop2/Braintree/Divido compatibility notices never block PHP 8.4/8.5 update; CodeCart Core remains PHP 8.1–8.5.

Changed:
- Bonus Monobank Payment Modern updated to v1.1.0 with one-invoice-per-order locking, currency_value-aware amount calculation, unique order id and safe secret handling.

Runtime QA:
- Live ocStore 3.0.4.1 -> CodeCart upgrade on MariaDB passed data preservation, Installer 2.0 migration, repeat-upgrade idempotency, storefront/admin login and error-log gates.
- Live commerce QA passed on MySQL 8.4 and MariaDB 10.11, including stock=1 concurrent checkout, Chromium guest checkout, signed Monobank webhook/stale-event idempotency and parallel HTTP load smoke.
- Build 1.8.3 integrity manifest was regenerated from the final main source tree.

# CodeCart PRO 3.0.6.0 Build 1.8.2

## Added — Modern Extension bonus
- Added `bonuses/Monobank_Payment_Modern_v1.1.0.ocmod.zip` as an optional demonstration extension.
- The payment method appears in Extensions -> Payments through thin OpenCart bridge files while API/webhook/idempotency logic is loaded from `system/extension/monobank_payment` through Modern Extension Registry.
- The bonus is not installed or enabled automatically.


## Compatibility Framework

- Replaced direct UniShop2 calls in catalog controllers with a generic Compatibility Framework.
- Added `CompatibilityAdapterInterface` and isolated runtime adapter execution with safe failure logging.
- UniShop2 is now the first built-in theme adapter instead of a Core-specific special case.
- Added installable compatibility adapters through modern extension `manifest.json` capability `compatibility.adapters`.
- External adapters are restricted to their extension namespace and must implement the official interface.
- OCMOD diagnostics now query the generic Compatibility Framework; adapters can optionally declare legacy OCMOD searches they supersede.
- Added stable contracts for menu, category module/page, product options and banner data.
- Preserved OFF-means-OFF: an installed but inactive theme does not execute its adapter.
- Preserved CodeCart batched category loading and modern image pipeline; no old ocStore/OpenCart N+1 implementation was restored.
- Added developer documentation in `documentation/COMPATIBILITY_FRAMEWORK.md`.
- Fixed Modern Extension Registry namespace normalization that could trim a trailing `t`, and fixed global `CodeCartPsr4` registration so newly installed adapter namespaces can be registered correctly.


UniShop2 compatibility
- Added an isolated `CodeCart\Core\Unishop2Compatibility` adapter for UniShop2 v3.6.6.0. The adapter activates only when UniShop2 is the active theme and does not affect the default CodeCart storefront.
- Restored the functional equivalents of 13 UniShop2 OCMOD contracts that no longer match the modern CodeCart catalog core: mega-menu (4), category module (3), category page (3), product options (2) and banner (1).
- Preserved CodeCart batched category loading instead of reintroducing UniShop/OpenCart N+1 `getCategories()` loops or legacy category cache behavior.
- UniShop menu data now receives icon/banner metadata, second-level images, third-level children/limits and landing-link compatibility from the existing CodeCart category tree.
- UniShop category pages receive subcategory visibility/images and `uni_banner_in_category` support without replacing CodeCart category routing/SEO logic.
- UniShop product options receive compatible ended/maximum/image-size fields while preserving CodeCart option-image switching and modern image pipeline.
- Standard banner items expose UniShop `width`/`height` aliases while retaining CodeCart slider effects and third-party Swiper fallback.
- OCMOD diagnostics recognize the 13 superseded UniShop2 search operations as satisfied by the CodeCart compatibility layer instead of reporting false compatibility warnings.
- UniShop2 fix Installer/Twig operations already implemented by CodeCart are likewise recognized as compatibility-satisfied; the legacy global SQL-mode relaxation is intentionally not claimed as equivalent.

# CodeCart PRO 3.0.6.0 Build 1.7.7

Upgrade compatibility
- Fixed OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta upgrades on MySQL 8.x when legacy product.date_available still uses DEFAULT '0000-00-00'. The migration preserves existing row values, replaces only the invalid default with 1970-01-01 in a temporary compatible session and restores the original SQL mode after the InnoDB conversion.
- Fixed legacy MyISAM/utf8mb4 upload.code index creation on MySQL where a full VARCHAR(255) key exceeds the 1000-byte MyISAM key limit. CodeCart now uses a compatible 191-character prefix index.
- Exact source fingerprints were verified against the supplied OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta archives before upgrade testing.
- Full upgrade QA passed for OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta on both MariaDB 10.11 and MySQL 8.4.
- Upgrade QA verified preservation of products, categories, SEO URLs, store settings, active theme, customers, orders, OCMOD records and Events. ocStore-specific meta_h1 and blog data were also preserved.
- Re-running Installer 2.0 UPDATE passed idempotency checks and storefront/admin login remained functional after upgrade.

# CodeCart PRO 3.0.6.0 Build 1.7.6

Upgrade reliability
- Installer 2.0 ignores stale legacy OpenCart/ocStore migration files left on disk by overlay updates and executes only CodeCart 3.0.6 migrations (3052+).
- Fixes the reproduced ocStore 3.0.4.1 upgrade failure where legacy migration 1010 was re-run and queried the already-removed url_alias table.
- Clean-install behavior is unchanged; UPDATE continues to preserve config, active theme, catalog and SEO data.

Runtime QA
- The production artifact passed clean-install runtime QA on MariaDB 10.11 and MySQL 8.4, including storefront/admin HTTP smoke, Redis tagged cache, persistent mail queue, SMTP delivery and Scheduler.
- Deep commerce release QA passed on MariaDB 10.11 and MySQL 8.4: full guest checkout, COD confirmation, duplicate payment-confirm protection, stock decrement/restore, one-use coupon enforcement, voucher accounting, order cancellation reversal and repeat-cancel idempotency.
- PayPal capture/refund/void/reauthorize/tracker mutation endpoints are gated by POST, user_token/hash_equals and modify permission before provider access. Provider-side refund/capture remains an external sandbox/live integration test requiring PayPal credentials.
- Build 1.7.6 passed the PHP 8.1, 8.2, 8.3, 8.4 and 8.5 release matrix and the ocStore 3.0.4.1 upgrade runtime pipeline.

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