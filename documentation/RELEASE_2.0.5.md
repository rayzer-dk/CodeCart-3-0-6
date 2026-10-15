# CodeCart PRO 3.0.6.0 — Build 2.0.5

This is a maintenance release for OpenCart/ocStore-based CodeCart PRO.

## Changes

- OCMOD compatibility counts now include filesystem-based system XML modifications; the main table continues to show editable database-registered modifications only.
- Restored two-tab layout (Modifications / OCMOD log), including on upgraded stores with an OCMOD progress bar.
- Kept action buttons on one line in the modifications table.
- Recalculated SHA-256 integrity manifest for the exact release files.
- Included official GPLv3 LICENSE and Composer package metadata.

## Installation / upgrade

Create and verify full backups of BOTH website files and database. Test on a staging copy first.
Install the distribution from the production archive, NOT the GitHub-generated Source Code ZIP; the latter excludes preinstalled dependencies.
For an existing 3.0.6.0 Build 2.0.4 store, review a file-by-file diff and preserve custom template overrides and installed modules before updating.
Do not replace third-party UniShop2 files with CodeCart core files.

## Known site-specific compatibility

A legacy `system/mms.multiselectimage.ocmod.xml` may require the obsolete `product_form.tpl` and fail on Twig-based stores. This third-party system file is not included in the Core. If Image Manager PRO handles multi-image selection, test the replacement first, then disable the legacy file and refresh OCMOD.
For UniShop2 encrypted administration modules, enable a compatible ionCube Loader in the correct site-specific PHP-FPM pool; CodeCart itself does not install ionCube.
A customized `catalog/language/en-gb/en-gb.png` can legitimately differ from the standard release manifest and will still be reported as modified; review it rather than suppressing file-integrity warnings.

## Verification scope

Syntax/integrity checks are not a substitute for functional QA. Verify fresh install, administration, checkout, payment integrations, OCMOD and upgrade on staging before marking the deployment ready.
