CodeCart PRO 3.0.6.0 — Build 1.7.8

PRODUCTION INSTALL / UPDATE

Upload ONLY the CONTENTS of the upload/ directory to the web root of your store.
Do NOT upload the documentation/ directory or README_FIRST.txt to the web root.

Existing store update:
- Make a full backup of files and database first.
- Preserve the existing config.php and admin/config.php, image/ and the real storage directory.
- Upload the CONTENTS of upload/ over the existing store files.
- Open /install/ and use UPDATE mode. Do not import install/opencart.sql into an existing store.
- Reinstalling the same Build for repair is supported: enable “Repair current build” in UPDATE. It force-resynchronizes the packaged Composer/vendor to the active storage, clears template cache and invalidates the old OCMOD build marker without rerunning completed DB migrations.

Clean installation:
- Upload the CONTENTS of upload/ to an empty web root.
- Open /install/ and follow Installer 2.0.
- The administrator login is the username entered during installation; the e-mail address is not the login.

See documentation/INSTALL.md and documentation/UPGRADE.md for details.

BUILD 1.6.9 MODERN COMPATIBILITY FOUNDATION
- Adds additive CompatibilityLayer for OpenCart/ocStore schema differences without replacing MVC-L.
- Adds SearchAdapter with native catalog fallback; external engines remain optional.
- Adds opt-in read-only REST API v1 routes: api/v1/health, api/v1/product, api/v1/search. API is disabled by default.
- Extends managed extension points with diagnostics while preserving existing handlers.
- Asset Manager now bridges simple assets through legacy addScript/addStyle so third-party themes that do not render modern_assets remain compatible; advanced assets keep the modern renderer.
- No PostgreSQL, Headless, SPA, RoadRunner/FrankenPHP or external search dependency is required.

BUILD 1.6.9 UPDATE NOTES
- Added same-Build Repair mode for safe recovery after damaged or partially overwritten Core/vendor files. It works even when database schema and package version are already current.
- Any Core/package filesystem update now invalidates the previous OCMOD build marker and marks Modifications as pending refresh, not only explicit Repair mode.
- Modifications page keeps a compact color-coded summary and now shows total recorded compatibility problems in addition to applied, partial, failed and skipped counts.
- Warning/error modifiers expose a direct Problems button with operation number, target file, failed search expression, reason, changed files and rollback state.
- OCMOD publication is fail-safe: compatibility is analysed before the active generated tree is replaced; the old build marker is removed before publishing; any filesystem write failure leaves generated OCMOD inactive and restores maintenance mode instead of activating a partial cache.
- Extension Installer can optionally refresh OCMOD automatically after a completely successful install/update/uninstall. The option is off by default and can be enabled on the Installer page.
- When automatic refresh is off, a one-click Refresh Modifications action is shown after the extension change. The global admin refresh icon now has a green/orange/red/grey state indicator for current, pending, issues or unknown OCMOD state.
- Installing, editing, enabling, disabling, restoring or deleting a modification marks the OCMOD cache as pending; the state is stored persistently under DIR_STORAGE/codecart/.
- UPDATE continues to allow normal Core file replacement. Existing config.php/admin/config.php and persistent DIR_STORAGE data are not overwritten by migration. OCMOD overlaps and foreign/legacy Composer packages are warnings, not UPDATE blockers.

BUILD 1.6.9 COMPATIBILITY HARDENING
- No new mandatory runtime, database, frontend framework or external service was added.
- Compatibility metadata checks now use exact table/column discovery and tolerate older language-table variants.
- Optional modern extension-point failures are isolated in safe mode and cannot stop the remaining optional handlers.
- REST API v1 exposes a stable public product subset instead of raw model rows; health no longer exposes PHP or DB internals.
- Search API limits are revalidated after extension hooks and native OpenCart search remains the mandatory fallback.
- No Meilisearch, SPA framework, WebSocket, GraphQL or Headless requirement is enabled by this build.

Build 1.6.9 compatibility hardening:
- Preserves synchronous jQuery -> Bootstrap -> OpenCart common.js execution order in the bundled storefront. This avoids regressions in legacy OpenCart/ocStore modules that execute inline JavaScript during HTML parsing.
- Modern Asset Manager dependency cycles are fail-safe and cannot take down storefront/admin rendering.
- Safe extension points remain non-fatal even if a diagnostic error callback itself fails.
- REST search re-applies the configured result limit to optional third-party search providers.
- Static UniShop2 v3.6.5.2 OCMOD audit against this build found no missing required search operation; optional skip operations remain optional by design.

BUILD 1.6.9 BRANDING / FAVICON CONSISTENCY
- Unifies visible system branding as CodeCart PRO without renaming internal routes, namespaces or compatibility identifiers.
- Removes the obsolete lower tagline from the system logo used by admin, Installer and clean-install defaults.
- Administration now uses the configured store favicon, matching the storefront; the CodeCart PRO favicon is only a fallback when no valid store favicon exists.
- Installer remains a system surface and intentionally keeps the CodeCart PRO favicon. Existing merchant store name, storefront logo, favicon setting and images are never replaced during UPDATE.
- Favicon and system-logo URLs are cache-busted so changed assets appear after upgrade without requiring browser cache clearing.
