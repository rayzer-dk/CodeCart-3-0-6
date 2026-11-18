# Compact catalog completeness indicators

Scope: CodeCart PRO / OpenCart 3.x admin product, catalog category, manufacturer,
information page, blog article and blog category listings.

An unobtrusive percentage chip appears next to the name. Category listings also
show the count of directly assigned records (products or blog articles), not
recursive descendants. The chip tooltip lists missing fields and the affected
language/store. This is an informational quality score, not a publication gate.

## Lightweight loading

* No quality work is done in the original catalog listing SQL queries.
* Visible chips are fetched lazily via IntersectionObserver, at most 40 IDs
  per request and one in-flight request per table. Offscreen categories
  do not produce database requests.
* The endpoint allows only validated positive IDs and reuses each existing
  catalog/blog controller's access permission and user token.
* Each batch reads only the requested rows using indexed product/category IDs,
  description primary keys, direct category-assignment indexes and the existing
  SEO URL query index. Descriptions are reduced to DB-side presence flags.
* No background scan, index table, cron task or persistent score cache.
* The toolbar icon toggles quality diagnostics on/off. The preference is kept
  in the current browser's localStorage; OFF means no quality requests.
* Do not add a global completeness sort: that would demand scores for the
  entire catalog and would defeat the bounded-visibility design.

## Score

Each active language contributes name/title where applicable, description,
Meta Title, Meta Description and an SEO URL per assigned store. The base entity
checks appropriate fields: image, product model, nonzero price, product/article
category assignment and store assignment.

The SEO check accepts a language-specific URL or a generic language_id=0 URL
for the assigned store. This respects existing OpenCart SEO fallbacks.

Manufacturer names are stored on the manufacturer object rather than in its
per-language description. Category counts include direct assignments even
when products are disabled; this is a relationship count, not a stock count.

Meta Keywords are not scored because they are not required for modern search
engines. Meta H1 is not scored when the system can fall back to the entity name.
Zero stock and noindex flags are intentional business/SEO states, not incomplete
data. A zero product price is displayed as an informational missing item; free
products remain usable.

A plain image filename such as no_image.webp is not considered a valid product
photo. No image resize, file-stat, filesystem scan or search index rebuild is
performed.

## Compatibility

Only first-party CodeCart PHP helper, existing admin controller route methods,
admin listing templates, language files, JS and CSS are touched. The OpenCart
installation schema, catalog data, storefront search, OCMOD and Events are
unchanged. In case of a data error, the optional badge shows a dash and the
main listing continues to function.

Run the regression checks:

    php tests/catalog_quality_smoke.php

Source integrity and PHP 7.4/8.1/8.5 matrices include the quality test.
