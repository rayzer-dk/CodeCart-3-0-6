# CodeCart PRO 3.0.6.1 — vendor compatibility refresh

Build 2.3.60 (2026-11-22). PHP support: 8.1–8.5. Bootstrap 3.4.1 is retained for OpenCart 3.x and existing extensions.

## Updated bundled components

- jQuery 3.8.0 (admin, catalog, installer references); old 3.7.1 assets are kept for legacy extensions.
- jQuery Migrate 3.6.0; old 3.4.1 assets are retained.
- Moment.js 2.31.0 (admin and catalog, including locales).
- Bootstrap DateTimePicker 4.17.49 (legacy vendor assets); CodeCart native DateTimePicker 1.8.7 remains the active core implementation.
- Flot 4.2.6 (both primary ES5 bundle entrypoints); the CodeCart SVG chart remains primary. Obsolete standalone Flot plugins remain as legacy compatibility assets, so external extension integrations require their own testing.
- jQVMap npm 1.5.1 (script and stylesheet); the CodeCart SVG world map remains primary.
- Guzzle 8.5.5, Promises 3.2.0, PSR-7 3.6.0, and Symfony polyfill-php82 1.43.0; Composer lockfile records exact resolved versions.
- Symfony Filesystem 6.4.50 was already the latest 6.4 maintenance release and is unchanged.

## Verification

The pinned upgrade workflow installs upstream packages, rebuilds Composer dependencies and runs PHP release checks before committing. The production release pipeline additionally regenerates integrity hashes, packages the release and runs regression tests. Any external OpenCart/ocStore extension using undocumented jQuery, Flot or Guzzle internals may require additional runtime testing against the actual store. The CI suite is not a substitute for a staging-store compatibility test.
