# Installation
[Українська](INSTALL.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

**Create and verify a complete website and database backup before installing.** An existing store requires [UPDATE](UPGRADE.md); clean installation requires an empty database and web root.

## Requirements

Use PHP 8.1–8.3 for broad extension compatibility and MySQL/MariaDB. Core also supports PHP 8.4/8.5; check every theme/module separately. The installer validates extensions, TLS and writable configuration/storage paths. Requirements include a MySQL driver (`mysqli` or `pdo_mysql`), GD, cURL, OpenSSL, zlib, ZIP, SimpleXML, mbstring, DOM, XMLWriter, fileinfo, JSON, hash and iconv. Resolve all blocking checks.

Use HTTPS in production. Apache needs the supplied `.htaccess` and enabled rewriting; nginx needs [server rules](NGINX.md). Keep persistent storage outside the web root where possible and grant PHP write access to runtime directories. Avoid blanket `777` permissions.

Stock `.htaccess` and `robots.txt` are domain-neutral. Add only sitemap URLs that exist on your store. Preserve WAF protection; avoid blanket `route=` crawl bans, year-long immutable caching of unversioned CSS/JS, hard-coded third-party sitemap routes and PHP-FPM-incompatible `php_value` directives. Custom HTTP error documents must exist; application 404/500 pages are already provided.

## Steps

1. Obtain the production release ZIP with bundled `vendor`. A GitHub source ZIP requires Composer preparation.
2. Create an empty database and a database user with installation permissions. Extract the package locally.
3. Upload only the contents of `upload/`, including hidden `.htaccess`, into the web root. Keep documentation, tools and repository files outside it.
4. Open `/install/`, complete environment checks and enter database details and the administrator account. Log in with the chosen **username**, not email.
5. Check the storefront and administration. Refresh **Extensions → Modifications**, configure store URLs, language, currency, email, shipping and payment, then test a complete order.
6. Remove `/install/` after verification. Ensure configuration and storage cannot be downloaded publicly.

CodeCart Theme is the only theme registered and active on a clean installation. Bundled `default` files are a compatibility fallback, not a second installed theme. Store logo/favicon can be changed in settings; updates preserve merchant assets.

Set up [Scheduler](SCHEDULER.md) for background tasks. Optional lost URL monitoring remains disabled until explicitly enabled.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
