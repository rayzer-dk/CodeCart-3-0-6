# CodeCart PRO 3.0.6.0 — Production Build 2.0.8 (2026-10-09)

## Public documents: simplified product-first workflow

- Public document editor now contains only document titles and the file upload. Titles are shown for all currently **active** languages in the OpenCart/ocStore language settings, with matching language flags, like paid digital download names. Original PDF/DOC/DOCX/XLS/XLSX/image/TXT/ZIP rules (25 MiB max) remain unchanged.
- Document type, placement, numeric product/category ID, sorting and publication controls are removed from the public document **form**. Newly saved documents are published automatically; editing an existing document retains its file, descriptions, counters, prior category affiliation and sorting.
- Product form has a new **Documents** admin tab with an autocomplete search of previously uploaded public documents. Multiple documents can be associated with one product; the same document can be associated with multiple products. Removing an association never deletes the physical document.
- The old single-product association format from Build 2.0.7 is migrated safely into the new mapping table on first authorized admin use. Previously linked category documents remain linked, and all public download statistics remain unchanged. The operation is idempotent; it does not rewrite product content or touch paid product downloads.
- Public documents list displays filename, totals, 30-day downloads and download date, without redundant type/placement/status columns. Product and category storefront output is preserved for CodeCart Theme and the default theme. Auto-rendering in third-party commercial themes such as UniShop2 has not been verified.
- Adds `codecart_document_to_product` (InnoDB, DB_PREFIX) to the clean-install SQL, the core schema helper, and installer upgrade script 3054. Existing-store overlay upgrades never require running the installer; migration activates after the first authorized admin visit.
- Paid digital files, order-status entitlements, checkout, paid download counts and storage directories are unchanged. The store is not opened to unauthenticated paid downloads.

## Deployment and limitations

Before installation or upgrade, make a verified backup of site files and database. Install the full Production archive only on a new store. For an existing Build 2.0.7 use the small Existing_Store_Update package to overlay the upload tree (not the installer). Purge Twig/OCMOD caches; open Catalog → Public Documents or an authorized product edit page to activate the new mapping table, then test selecting, saving, editing and removing a document and the storefront tab.

Static tests: PHP 8.4 token parser over all included PHP, PHP lint for changed files, Twig parse for document and product templates, documented SQL/schema inspection, archive and checksum verification. No end-to-end MySQL/MariaDB integration or runtime browser session against a production site has been performed.
