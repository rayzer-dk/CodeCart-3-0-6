# CodeCart 3.0.6.0 — Build 2.1.1

Date: 2026-10-21. Package build: 2.1.1. Core schema: 3.0.6.0.

Validation of the final source, optional packages and complete production archive is pending. No production-ready status is assigned to this revision. The final acceptance record must identify the exact source commit, artifact hashes, executed checks and remaining NOT RUN scenarios.

## Packaging

The package build is synchronized across storefront, administration, installer, CLI and cron entry points. The production builder writes fixed ZIP timestamps and permissions, verifies every archived file against its entry hash and emits a complete `.zip.manifest.json` beside the archive and its `.zip.sha256` checksum. The manifest records the source commit and Python/zlib compression versions. Rebuilding identical file bytes with the same toolchain must produce the same archive regardless of checkout timestamps.

Optional package metadata and installer compatibility are being validated separately. A compatibility adapter contains its own installation rules; it does not supply a third-party theme or a license for another product. Production source fixes require their own regression results before they can be recorded here as verified changes.

The bundled documentation is rebuilt from the current README files and documentation tree, with the accepted license and third-party notices retained. Its ZIP uses the same fixed timestamp and permission metadata; the release workflow compares a fresh rebuild with the bundled bytes before packaging.

## Authentication and schema repair

Administrator TOTP verification serializes attempts from the same source across different accounts. Failure to acquire the source lock rejects the attempt before starting a transaction or consuming a code. Existing account limits, recovery-code consumption and TOTP replay protection remain in place.

Presentation schema revision 33 widens eight IPv6 address fields to VARCHAR(45) and the order shipping-company field to VARCHAR(60) when their existing columns are too short. The additive repair also runs for an installation already marked with core schema 3.0.6.0 and presentation revision 32. Existing wider compatible columns are retained; the core schema version remains 3.0.6.0. Fresh-install and CI revision markers are synchronized to 33.

The presentation revision is published only after the upgrade journal and core-version save succeed. A failed upgrade retains its previous revision so the next attempt performs the upgrade again; a successfully completed upgrade keeps the existing zero-query fast path.

## Local verification

The standalone SEO URL and language-startup regressions pass on PHP 8.1, 8.2 and 8.3. Their fixtures now register the production PSR-4 loader and provide database table/column metadata to the existing main-category resolver. The URL, language-selection and redirect assertions are unchanged. These fixtures do not establish live-store SEO integration or upgrade compatibility.

The current documentation archive passes CRC, exact source-content and retained-notice checks. Two independent rebuilds and a rebuild after changing source file timestamps produce identical bytes. The final full release gate, source revision and complete distribution hashes belong in the separate acceptance record; the checks in this section do not replace them.

TOTP fixtures pass 31 checks on each of PHP 8.1, 8.2 and 8.3. A separate MariaDB concurrency probe on PHP 8.3 passes eight checks, including a shared-source race starting with 14 attempts and ending at the limit of 15. Native concurrency on PHP 8.1 and 8.2 remains untested.

Database and legacy-framework analysis contracts are corrected, with development-only cache stubs excluded from the production package. Targeted PHPStan checks pass for five administration and seven catalog files affected by this release, plus the administration entry point. These scoped results do not establish repository-wide static-analysis cleanliness. Related syntax and device, stock, product-category and image regressions pass on PHP 8.1, 8.2 and 8.3; twelve search-condition edge cases produce the same SQL as the previously checked source on all three versions.

The schema regression suite passes 41 standalone public-API assertions on each of PHP 8.1, 8.2 and 8.3. Separate MariaDB checks cover ocStore 3.0.4.1 and OpenCart 3.0.5.1 fixtures, both from their original schema and from an already upgraded 3.0.6.0 state. All four scenarios persist the nine boundary-length values on PHP 8.1, 8.2 and 8.3 under strict SQL mode, with no remaining type-parity issues, removed columns or changes on repeated migration. On both baselines, an injected late journal failure retains revision 32 in the database and configuration; retry reaches revision 33, preserves merchant data and returns to zero queries on the following run. These isolated fixtures do not establish compatibility with every merchant schema or extension.

## Installation and update

Create and verify a full backup of site files and the database before installation or update. Rehearse restoration and installation on a staging copy. Use the complete reviewed production distribution, preserve deployment configuration and persistent storage, and follow the existing installation and upgrade documentation. Never import the fresh-install database into an existing shop.

Administration session isolation may require administrators to sign in again after updating. Test customer cart preservation across sign-in and administration use, saved content, scheduled tasks and checkout before accepting the update. Refresh applicable modification, Twig and asset caches through the normal administration workflow.

## Acceptance requirements

Repeat PHP 8.1–8.3 syntax and regression checks, language-key parity, Twig/JavaScript/CSS/XML validation, UTF-8/BOM checks and source-integrity checks against the final source. The repository also retains its PHP 8.4–8.5 CI matrix. Validate nested archive contents and metadata, clean installation, upgrades from supported OpenCart/ocStore baselines, repeated migration, backup restoration, privileged actions and failure paths.

Browser checks must cover storefront and administration interactions, light/dark display and mobile layouts. Complete a synthetic local order and verify database side effects. External payment providers, deployment-specific themes/extensions, web-server rewrites, mail delivery and production load remain NOT RUN unless their own runtime evidence is recorded. Static checks and fixture tests alone do not establish production readiness.
