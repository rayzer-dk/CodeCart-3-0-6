# CodeCart 3.0.6.0 — Build 2.1.4

Date: 2026-11-01. Package build: 2.1.4. Core schema remains 3.0.6.0.

## Query reduction

Storefront link generation no longer issues one seo_url query per generated link. `CodeCart\Core\SeoUrlIndex` loads category, manufacturer, information and blog category aliases once per store and language; product and article aliases are prefetched when listing models hydrate their rows; missing aliases are remembered for the request. Native SEO URLs and SeoPro both use it. With the SeoPro cache enabled, the cached keyword map is trusted for misses while `MAX(seo_url_id)` is unchanged, so rows added by imports or extensions rebuild the map automatically. Product category paths and blog paths are kept between requests and cache files are written only when they change. When several aliases exist for one query, the newest non-empty keyword is used.

Other per-item queries were replaced by batched reads with unchanged results: product comparison, header product links, product attributes, category filters, breadcrumbs, blog subcategory counts and article lists, account addresses, order options in checkout, order mail and account history, the four layout positions, admin product autocomplete, customer list/autocomplete/IP totals, admin form selections and bulk invoice/shipping printing. Checkout stock locking uses one `SELECT … FOR UPDATE` per table in primary-key order. Product, category and manufacturer related/link tables are written with multi-row inserts; category path moves and repair are computed in memory. Mail campaign and stock notification cron jobs read suppression, store settings and option stock once per batch. Catalog import writes main categories per chunk, and the SEO alias upgrade backfill preloads existing aliases.

Public model methods keep their signatures and return shapes; batched variants are additive (`getProductAttributesByProductIds`, `getOrderOptionsByOrderId`, `getProductsByIds`, `getProductOptionsByProductIds`, `getAddressesByCustomerIds` and similar).

## Verification scope

Measured on a clean install with MariaDB 10.11 and PHP 8.3 (demo catalog, 30 customers, 20 orders). Storefront queries per page, native SEO URLs: home 211 → 64, category 213 → 61, product 172 → 73, blog category 168 → 51, cart 129 → 43. SeoPro without cache: home 295 → 98, product 296 → 137; with cache: home 91 → 69. Administration: product autocomplete 63 → 22, customer list 46 → 27, bulk invoice for 20 orders 319 → 144, bulk shipping list 419 → 144.

Rendered HTML of 15 storefront pages in all three SEO modes and 18 administration pages was compared against build 2.1.3 on the same database; differences were limited to random form tokens and timestamps. Admin product/category/manufacturer saves, category move and repair, catalog import main-category mapping, mail campaign and stock notification cron runs produced identical database state. Real checkout with options, the concurrent stock race, order cancellation restock and reprocessing were exercised over HTTP. Production load, third-party modules hooking the changed model methods and merchant-specific themes are not certified by these fixtures.
