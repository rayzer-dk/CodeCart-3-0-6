# CodeCart PRO 3.0.6.0 — Core file integrity

The Store Health dashboard compares a **curated set** of first-party Core files against `upload/system/config/codecart_integrity.json`, using SHA-256. It does not compare all files in the distribution.

* `changed` means the SHA-256 of an original, monitored server file differs from the shipped build. `missing` means that the file is absent. Both are internal state identifiers; the dashboard translates their visible labels using the active administrator language (uk-ua, ru-ru, en-gb).
* The manifest intentionally does not include the installer, generated caches, modification copies, user uploads, secrets, environment configuration, or the manifest itself. Third-party vendor assets and ordinary customizations are not automatically registered as trusted Core files.
* The manifest is generated from the **verified release payload**, not from an arbitrary live installation. The application must never silently redefine known-good hashes based on potentially altered server files.
* The newest release additionally monitors key authentication, administration, file manager, notification, SEO language routing, and CodeCart library files that were previously omitted.

For release QA, run `php tools/integrity-manifest-check.php` from the repository root. It checks all expected SHA-256 values, missing paths, and required Core components. A failure must block release publication.

When an upgraded store reports changed files, compare that path to the official release ZIP. A hash mismatch alone does not establish malicious modification: mixed-version FTP uploads, locally edited files, older versions, and external modifications can also cause it. Restoring matching release files is a deployment operation, not a reason to accept every difference automatically. Use backups, compare actual bytes, and refresh the relevant caches after deployment.
