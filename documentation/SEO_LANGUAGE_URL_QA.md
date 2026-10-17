# Native language URL and SeoPro regression checks

CodeCart PRO 3.0.6.0 supports language folder prefixes configured in the active `language.url_prefix` records. Prefixes are not hard-coded. If a supported external URL router is explicitly enabled, its configured prefixes take precedence.

The native router now recognizes legacy language query selectors only when the requested language code is active and built-in folder routing is enabled. A matching language folder has priority over a conflicting query selector. For a recognized catalog page, the legacy URL is redirected to a localized canonical path without the `language` parameter, retaining other permitted query arguments. Unresolved URLs do not redirect to the home page. POST, AJAX, API, payment, feeds and disabled-prefix installations are not changed by this compatibility logic.

The fallback lookup for a legacy source-language SEO keyword is restricted to requests with a valid old language query and an unprefixed URL. It must not populate the target language SEO cache with another language's keyword mapping. Outgoing native catalog URLs omit recognized legacy selectors.

## Run regression checks

From the repository root:

    php tools/seo-language-url-check.php
    php tools/seo-language-startup-check.php

These tests use small mock SEO data and do not modify a database.

## On-server acceptance checks

On a staging installation with backup and configured active language prefixes, check an existing product in each active language:

- `/slug/?language=ru-ru` should perform one permanent redirect to the product's localized `/ru/...` URL when a Russian SEO keyword exists.
- `/ru/slug/?language=uk-ua` should remain Russian and remove the obsolete selector.
- `/ru/slug/` should load without an unnecessary redirect.
- Unknown slugs must remain 404; they must not redirect to a language homepage.
- Language switching must generate links without `?language=`; canonical and hreflang should agree with the selected folder.
- Pagination and catalog filters should retain their permitted query parameters.
- POST/AJAX and payment/API/feed requests must continue to function without extra redirects.
- The SEO URL master switch and external language-router integration should behave as before when native prefixes are not in use.

Inspect any remaining third-party OCMOD/Events rewrites and refresh modification/template caches after deployment. Deployments on other OpenCart/ocStore installations are independent and must be updated and tested separately.
