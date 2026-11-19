# Error pages

[Українська](ERROR_PAGES.uk.md) · [All guides](README.md) · [Community](https://t.me/codecartpro)

## Design system

CodeCart PRO shares one responsive, accessible error experience across the storefront and administration. The bundled CodeCart and default themes use the same full-width design for HTTP 404 and maintenance HTTP 503. The administrator uses the same visual language for HTTP 404 and HTTP 403 inside its normal header/sidebar shell. Unhandled application errors use a fully standalone HTTP 500 response.

Ukrainian is the primary language for all error pages, independently of the legacy storefront default and the browser's Accept-Language. Russian and English are used only after an explicit storefront language selection, a language-prefixed URL, or a saved administrator preference. The same rule applies to the standalone 500 response; Ukrainian is not merely a fallback for missing translations.

The shared stylesheet is `catalog/view/theme/codecart/stylesheet/error-page.css`. It contains status-specific design tokens for 404 (blue), 403 (amber), 500 (coral) and 503 (teal), responsive typography, visual status panel, primary and secondary actions, clear focus outlines, dark and light theme palettes and reduced-motion handling. Admin uses `admin/view/stylesheet/codecart-error-page.css` to import the same tokens with shell-specific rules. No remote fonts, stock photos or JavaScript are required.

The 404 page offers the configured store homepage and catalog search; 503 provides a check-again link. Admin errors link to the authenticated dashboard with the normal session token. All visible messages and buttons are localized in English, Ukrainian and Russian. The decorative HTTP status illustration is excluded from assistive technologies, while the text has a labelled primary heading.

## Status and security

Missing products, categories, manufacturers and information pages retain HTTP 404 with `noindex,follow`. Maintenance sends HTTP 503 with `Retry-After: 3600` and `noindex,nofollow`. Admin missing-page and permission errors send HTTP 404 and 403 with `noindex,nofollow`.

The HTTP 500 fallback is rendered directly from `system/helper/error_page.php` without Twig, database, theme header, scripts or external assets. Styles are safely embedded from the bundled file and an offline CSS fallback is included. The response provides a restrictive Content Security Policy. Technical exception messages, SQL fragments and local file paths must never appear in the public HTML. API/JSON/AJAX exceptions still receive JSON; CLI receives text.

This renderer applies only to requests reaching the PHP application. Web server, proxy and CDN-generated 500/502/503 pages, errors before handler registration and fatal memory exhaustion require separate hosting configuration.

Run `php tools/qa_error_pages.php` and the **CodeCart Error Page Design QA** GitHub workflow to validate localization, HTTP contracts, security and responsive design. Verify desktop and narrow mobile layouts after deployment. Third-party theme overrides may replace bundled templates. The Lost URLs tool can review missing content; its journal is disabled by default.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [Community](https://t.me/codecartpro)
