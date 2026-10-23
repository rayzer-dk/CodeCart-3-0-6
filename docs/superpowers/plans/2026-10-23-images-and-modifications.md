# CodeCart Build 2.1.3

User-approved scope: mobile article grids and embedded article modules show two cards by default; selected OCMOD bulk enable/disable; proportional product images with independent interface backgrounds.

Baseline: verified Build 2.1.2, main commit 643691a. Existing WebP/AVIF generation remains optional and disabled by default. The supplied webp_img OCMOD duplicates core conversion and is not installed.

Compatibility boundaries: preserve legacy Image::resize and ModelToolImage::resize signatures, canvas behavior and cache URLs. Add explicit contain/cover APIs and mode-specific caches. Only CodeCart product components use the new product policy; other themes and external consumers keep legacy output. Keep schema version 3.0.6.0.

Product policy: contain, maximum upscale 3x, white CSS background by default. Common options plus catalog/gallery/thumbnail overrides, each inheriting common settings by default. Upscale choices: disabled (1x), 2x, 3x, unlimited. Cover crops centrally and is explicitly selected. Interface backgrounds white/theme/dark never change file bytes or cache keys. Merchant settings save through the normal theme form.

- [x] Verify pixel dimensions, borders, alpha, cache separation, output negotiation and unchanged sources on PHP 8.1–8.3.
- [x] Verify theme settings save/reopen, inheritance, input validation, preview and actual product/catalog rendering in light/dark mobile/desktop.
- [x] Verify bulk actions POST/token/permissions/selection/status and dirty-cache signal, including browser confirmation.
- [x] Verify mobile article grids and carousel breakpoints with rendered pages, preserving merchant settings.
- [ ] Independent final diff review, release gates, documentation/integrity refresh, complete archive verification.
- [ ] Commit and push verified changes to main (explicit user authorization); no release publication or live installation.
