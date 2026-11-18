# Blog home: multilingual SEO (CodeCart PRO 3.0.6)

The blog landing page (`blog/latest`) now supports language-specific:
name (also used in blog breadcrumbs/navigation), H1, Meta Title, Meta Description.

## Admin interface

Blog → Settings → General → **Blog landing SEO by language**.
Four simple fields appear under a tab for each active language (Ukrainian,
Russian, English and any additional enabled OpenCart languages).
Blank fields inherit the existing shared values above those tabs.

The legacy settings remain unchanged:
`configblog_name`, `configblog_html_h1`,
`configblog_meta_title`, `configblog_meta_description` and
`configblog_meta_keyword`. The optional Meta Keywords field is not
localized because Google does not use it for indexing/ranking.

Values are stored as a single serialized `configblog_localized` setting,
using OpenCart's existing settings table and existing blog-settings
permission. No installation SQL, OCMOD, Events, additional module, cron
or background process is introduced. The update retains all existing
settings until an administrator explicitly fills a localized override.
Inactive language values are preserved when saving active languages.

## Storefront behavior

`blog/latest` uses the active `config_language_id` to choose the
language-specific heading, document title, meta description and JSON-LD
CollectionPage description. Blog breadcrumbs on article/category pages
and the blog menu display the localized blog name.

If a localized value is blank or unavailable, the previous global
setting takes precedence, followed by the existing built-in translation.
Article and blog-category individual metadata, URLs, sitemap and
canonical URL handling are unchanged. No per-request SQL queries or
caching changes are needed.

## Regression checks

Run `php tests/blog_landing_locale_smoke.php`.
The source-integrity workflow also runs the test on PHP 7.4, 8.1 and
8.5. On a live storefront, additionally verify all configured
languages, individual blog article/category pages and the blog menu
with a real multilingual database and active SEO URL setup.
