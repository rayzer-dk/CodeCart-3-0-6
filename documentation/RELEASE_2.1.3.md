# CodeCart 3.0.6.0 — Build 2.1.3

Date: 2026-10-23. Package build: 2.1.3. Core schema remains 3.0.6.0.

## Product images

CodeCart product cards, product galleries and thumbnails explicitly use the new product-image policy. Default: contain the whole photo, allow enlargement up to 3x and draw a white background in the interface. Common settings and separate catalog/gallery/thumbnail overrides are available in Extensions → Themes → CodeCart → Images. Overrides inherit common settings until changed. Save through the normal theme Save button; the preview does not save settings.

Contain preserves proportions and writes only the scaled photo, with no added canvas padding. Examples for a 1100x1100 bound: 500x300 becomes 1100x660, 1600x900 becomes 1100x619, and 100x100 becomes 300x300 at the default 3x cap. Enlargement can be disabled or limited to 2x, 3x or explicitly unlimited. Cover is an optional central crop. Existing white pixels in an original photo are not removed.

White, transparent/theme and dark backgrounds are CSS options. They do not modify original or cached image pixels and do not require image cache regeneration. Photos in the same component retain its consistent image area. Resampling cannot reconstruct detail missing from small originals.

The existing Image::resize() and ModelToolImage::resize() signatures, padded canvas behavior and legacy cache paths are preserved for external modules. New resizeContain()/resizeCover() methods and productImage()/getProductImageSize() are additive. New mode and enlargement markers follow the source version in cache filenames, separating derivatives from legacy names. CodeCart product options do not apply to other themes. Controllers retain their original public resize/display calls and model events for other themes. In CodeCart, the existing display() helper creates proportional cache images while keeping its no-upscale contract for banners, blog images and non-product consumers.

Native WebP and AVIF generation already exists; do not install the supplied webp_img OCMOD as a second converter. Conversion remains disabled by default and is enabled manually in store image settings. Supported browser Accept headers select output; CLI/feed requests without Accept keep the source format. Resizing precedes conversion and retains actual dimensions and supported transparency. Vary: Accept also covers AVIF-only configuration. Third-party format optimizers require staging checks for their particular OCMOD/Event hooks; disable duplicate converters when using one of them.

## Articles and modifications

Blog grids and embedded article modules retain two mobile columns by default. Article carousels now use mobile columns throughout widths below 768px, matching the grid breakpoint. Desktop layout, list/compact views and an explicitly saved one-column module setting are retained.

The Modifications page has enable/disable buttons for selected rows. Both bulk routes require POST, a valid user token, access and modify permission, and a completely valid selection before changing statuses. Confirmation can be cancelled. Existing individual actions remain available. Refresh modifications manually to apply changed OCMOD statuses to generated files.

## Installation and validation boundary

Take a complete file and database backup before installation or update. Install the complete distribution through the established CodeCart update procedure, refresh modifications and theme/browser caches, then check the active theme, product gallery, checkout and installed extensions on staging. This is a CMS distribution; it is not an OCMOD module archive.

Targeted pixel/cache/format tests run on PHP 8.1–8.3; browser and HTTP/MySQL evidence records the actual local scenarios and interpreter used. Language resources are provided in en-gb, ru-ru and uk-ua. The archive acceptance report records final release-gate results and outstanding checks. Arbitrary commercial themes/modules, external payments/mail, production load and complete merchant backup restoration are not certified by local fixtures. No live store is changed by this release work.
