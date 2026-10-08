# Error pages — Build 2.0.4

[Українська](ERROR_PAGES.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

Bundled CodeCart/default storefront templates provide responsive 404 and maintenance pages with English, Ukrainian and Russian text. A missing page offers home/search; maintenance offers a retry action. Missing products/categories/manufacturers/information pages retain HTTP 404. Maintenance returns HTTP 503 with `Retry-After: 3600`; error pages are not indexed.

Unhandled application exceptions use a standalone HTTP 500 page without Twig/database rendering. Technical exception messages and file paths are excluded from the public response. API/JSON/AJAX requests receive JSON; CLI receives text. Inspect the server's technical logs to investigate the cause.

The renderer covers errors reaching the application. Web server/CDN errors, syntax errors before handler registration and memory exhaustion need separate hosting handling. Administration 404 retains its usual appearance. Check third-party theme overrides and OCMOD on the actual store. Use [Lost URLs](LOST_URLS.md) to review missing content; the journal is disabled by default.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
