# CodeCart PRO: Schema.org JSON-LD

The built-in JSON-LD output is controlled by Settings > Store > Built-in JSON-LD Schema. This global switch disables only CodeCart's own markup; installed extensions can emit their own schema independently.

## Automatically selected types

- Home: OnlineStore and WebSite, with a WebPage node
- Products: Product, Offer and BreadcrumbList
- Categories: CollectionPage, ItemList and BreadcrumbList
- Manufacturers: Brand, CollectionPage and BreadcrumbList
- Blog categories: CollectionPage and BreadcrumbList
- Other listing pages: existing markup as appropriate

Only real, publicly shown values are used. Shipping, returns, ProductGroup and extended merchant information must not be invented from missing or ambiguous source data.

## Information pages and blog posts

Each information page has its own "Schema.org page type" dropdown under the SEO tab. Automatic retains WebPage. AboutPage, ContactPage, FAQPage, CollectionPage and Article are available where they reflect actual visible content. "No page-specific markup" removes only the current page's node, not the global WebSite/OnlineStore or BreadcrumbList nodes.

Blog posts have Automatic (BlogPosting), BlogPosting, Article, NewsArticle and No page-specific markup. NewsArticle is for genuine reported news. The selected type is saved by the standard content Save action and remains consistent across translations. Existing pages use their original defaults.

Selections are stored using prefixed entries in the standard `setting` table, avoiding schema migrations and preserving existing data.

## Avoid duplicate structured data

CodeCart's event inspects JSON-LD already present in the HTML header. Equivalent nodes with the same canonical identity are not emitted twice. There is no safe general-purpose way to detect scripts appended **after** the header is rendered (for example, by a template in the page body or a late module). When using another full-featured schema generator, disable the built-in CodeCart JSON-LD globally. Do not disable it just because a plugin adds unrelated schema types.

## Verification

Run `php tools/structured-data-check.php` for isolated regression checks. Validate storefront HTML in Google Rich Results Test and Schema.org Validator, checking product prices, availability, locale-specific canonical URLs and real business details.

Back up the website and database before deployment.
