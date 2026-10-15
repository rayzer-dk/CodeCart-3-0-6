# CodeCart PRO 3.0.6.0 — Build 2.0.6

Complete maintenance release combining Build 2.0.5 OCMOD fixes and the final installer, carousel and sample-banner improvements.

- Installer completion notice: proper left/right padding, readable in Ukrainian, English and Russian.
- Homepage Popular Categories carousel: one left-chevron and one right-chevron.
- Demo homepage slideshow: third and fourth images are now Samsung tablet and HP laptop product-only photos, 1140 × 380, without background overlays. The banner URLs and sorting in installer SQL are unchanged.
- Maintains Build 2.0.5 OCMOD tab, error counter, logging and compact action buttons fixes.
- ionCube: enable the loader separately in the relevant web PHP-FPM configuration only when using protected third-party UniShop2 administrative code. Core does not bundle ionCube.
- UniShop2 warnings: third-party `catalog/controller/extension/module/uni_new_data.php` reads missing `qty_switch.type` at lines 825 and 1029. Separate vendor maintenance is required. Do not patch proprietary themes into the CodeCart distribution.

## Safety and limitations

**Before installing or upgrading, create and verify backups of BOTH website files and database.** Apply changes to a staging clone first. On existing shops, preserve real products/images, third-party modules, config files, storage, local theme overrides and merchant data. Demo slideshow images are installed only with demo data on a new install; existing store banners may be managed in the database and should not be overwritten without explicit review.

Syntax checks and ZIP integrity checks do not prove successful checkout, payment APIs or complete upgrade compatibility.
