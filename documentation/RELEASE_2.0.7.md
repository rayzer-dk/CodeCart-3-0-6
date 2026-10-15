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

No ionCube dependency is added. Protected commercial themes such as UniShop2 require the appropriate ionCube Loader enabled for their own PHP-FPM runtime. Do not edit CodeCart core to mask third-party missing controller classes.

**Tests performed:** PHP 8.4 token parser (all supplied PHP), modified PHP `php -l`, Twig parser for affected templates, SQL statement parser, table schema mock and SHA-256 checks. Functional installation, SQL execution against live MySQL/MariaDB, payment provider integration and end-to-end browser checks on user sites were not run and must be validated on staging.
