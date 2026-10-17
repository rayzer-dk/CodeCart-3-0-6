# CodeCart 3.0.6.0 — Build 2.1.0

Date: 2026-10-16. Package build: 2.1.0. Core schema: 3.0.6.0.

This revision imports the supplied CodeCart distribution and repairs its development checks. Extension-table checks inspect rendered table structure and CSS declarations instead of requiring exact attribute order, whitespace or CSS formatting. Translation assignment checks use PHP tokens so comments and examples are not counted as executable assignments. A separate mutation suite checks that harmless formatting changes pass and broken structure fails.

The release gate now runs the language, notification, SEO, integrity and regression checks. A missing checker is an error. JSON, XML and stylesheet checks report success only when their own checks pass; unavailable PHP DOM support is an explicit error. Runtime workflows compare staged entry files with the current source instead of expecting an obsolete build number.

Integrity generation preserves the schema version, records the package build separately and replaces the manifest atomically after hashing all existing monitored files. The production package includes the GPL license and excludes development tools and private audit evidence.

The optional compatibility framework normalizes supported legacy data structures through isolated adapters. Compatibility behavior is exercised by focused regression checks and does not certify every external extension combination.

## Verification scope

The local source audit covered PHP syntax on PHP 8.1, 8.2 and 8.3, first-party JavaScript syntax, Twig parsing, UTF-8/BOM, JSON and XML validation. Offline regression checks use mock services or render templates without a live shop database. They do not establish browser layout, checkout, payment-provider behavior or complete compatibility with external installation-specific extensions. File counts and machine-readable logs belong to the audit evidence; checks must be repeated against the final release commit after all changes and manifest regeneration.

Production acceptance requires a clean installation, upgrade and restore rehearsal, browser checks for administration and storefront, order completion, payment/shipping integration and deployment-specific theme/module checks. Those scenarios must be recorded as NOT RUN until actually exercised. Passing static or offline checks alone does not make this build production-ready.

## Installation and update

Create a full backup of site files and the database before installation or update. Validate restoration and test the release on a staging copy first. Preserve deployment configuration and runtime storage. Follow the existing installation/upgrade documentation and refresh applicable modification, Twig and asset caches through the normal administration workflow. Regenerate release hashes only after the final source and asset changes.
