# Product condition — CodeCart PRO 3.0.6.0

The product editor now includes **Product condition** on **Catalog → Products → Edit → Data**, directly before the price, with exactly three values:

- `new` — Новий / Новый / New
- `refurbished` — Відновлений / Восстановленный / Refurbished
- `used` — Б/в / Б/у / Used

The selected condition is a language-independent product attribute. Labels and help text are translated in Ukrainian, Russian and English.

The storefront displays condition alongside model/stock on the native `codecart` and `default` product templates. JSON-LD `Offer.itemCondition` emits the matching Schema.org enumeration URL (`NewCondition`, `RefurbishedCondition`, or `UsedCondition`). The built-in Merchant Center XML feed sends `g:condition` with the selected value, using the same normalization rule.

## Database and compatibility

The product table receives a single `condition VARCHAR(12) NOT NULL DEFAULT 'new'` column. Fresh installations use the new SQL schema; existing CodeCart installations receive it through the idempotent core migration at presentation schema revision 41. This does not alter existing product pricing, availability, SEO URLs or tax calculations. Existing products default to `new`, matching the pre-upgrade behavior.

New products receive `new` unless the form explicitly selects another state. Third-party updates that omit the field preserve the existing state; copying a product copies its state. Unknown values are normalized to `new` before persistence/feeds.

No new index, additional SQL query per product on the storefront, new table, OCMOD, cron or separate module is needed.

## Google Merchant Center note

Google's `refurbished` definition involves professional restoration to working order and a warranty. Ordinary second-hand products belong under `used` even if repaired for sale, unless the refurbished criteria are met. The XML feed and the visible storefront value must agree. Merchant Center can still require additional fields for some product categories.

Regression check: `php tests/product_condition_smoke.php`.
