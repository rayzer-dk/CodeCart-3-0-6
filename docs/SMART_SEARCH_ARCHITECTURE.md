# Smart Search: three-stage native extension

CodeCart PRO retains the standard catalog search contract for OpenCart/ocStore 3.x.
No Manticore daemon, external search host, new PHP extension or Composer dependency is required.

## 1. Attribute matching

Optional indexed matching combines normalized title, tag, product identifier and
attribute-name/value tokens for one product, store and language. Queries such as
"насос 220В 1,5 кВт" can match "насос" in the name and "220В", "1,5 кВт"
in separate attribute values. Ordinary catalog filters (store assignment,
status, date available, category, manufacturer and selected sorting) are applied
by the existing product model after candidate collection.

Attribute matching requires both Search index and Product attributes settings
enabled and a completed index generation. Until then the original search
continues to work without attribute token matching.

## 2. Synonyms, relevance and statistics

Existing spelling, keyboard layout, transliteration, model/SKU matching,
default relevance and keyword statistics remain active. Configured synonyms
augment native title matching, never replacing user input or product data.
Each line lists equivalent single words separated by commas, for example:
насос, помпа, pump.

Dictionary settings are independent for each store and language under Catalog >
Search queries. Existing zero-result statistics remain in the same screen, with
90-day retention and no stored IP addresses.

## 3. Optional batched native index

codecart_search_index_state and codecart_search_term are CodeCart-owned
InnoDB/utf8mb4 tables. No upstream OpenCart tables or indexes are altered.

The rebuild UI processes at most 100 products per HTTP request. A new generation
is used only after its full build. Old generations are deleted in bounded
cleanup batches. Each store/language pair can be rebuilt separately.

Product and attribute edits in the CodeCart admin, plus both CodeCart catalog
import paths, invalidate a completed index. Imported data written by third-party
code, direct SQL or external integrations may bypass these hooks; rebuild after
such operations. Native search is the fallback when an index is not ready.

This is an optional attribute-and-term lookup, not a replacement for all
existing LIKE/identifier/third-party OCMOD paths. Do not claim a global
performance improvement without real catalog benchmarks.

## Upgrade and compatibility

Implementation uses CodeCart Core SearchIndex and SmartSearch, with setting keys
under codecart_search. Tables are created idempotently when the Search queries
admin page opens. Existing stores have indexing disabled until explicitly enabled;
missing optional tables do not prevent product edits or storefront searches.

Settings are per store and index terms are additionally scoped by language.
No changes to the upstream OpenCart/ocStore installation schema, OCMOD or Events
definitions. Disabling indexed search prevents use of its index; it does not
disable the original storefront search.

Regression:
    php tests/smart_search_smoke.php
    php tests/smart_search_sql_smoke.php
    php tests/smart_search_index_smoke.php

CI runs PHP syntax, source integrity and search regression. Runtime and
multi-store tests remain part of the release gate.
