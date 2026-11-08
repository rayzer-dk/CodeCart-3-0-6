# Multilingual quality checks — CodeCart PRO 3.0.6.0

CodeCart uses active OpenCart/ocStore language directories `uk-ua`, `en-gb` and `ru-ru` in the admin, storefront and installer. Translatable labels, messages, errors and tooltips should be defined in the appropriate language PHP file and passed to PHP/Twig/JavaScript. Do not hardcode locale folders in storefront SEO routing: the active languages and URL prefixes remain dynamically configured.

## Automated consistency audit

From the project root run:

    php tools/i18n-check.php
    php tools/i18n-coverage-check.php

The command checks UTF-8 encoding and BOM in language files and compares active controller language files with the Ukrainian reference. For every active route it checks that the English and Russian files exist, that each key exists and retains its value type (string/array), and that string formatting parameters such as `%s`, `%d`, and positional `%1$s` match. A failure exits with code 1.

The audit includes the storefront, admin and installer. The script loads only the trusted language PHP files from this repository; do not run it on unreviewed third-party packages.

## Legacy extension compatibility

Some older ocStore language files, notably OpenBay and removed add-ons, remain available for compatibility but no longer have matching core controllers. They are deliberately not copied or removed during localization: installing a compatible optional extension may require its own translated files. The CLI command measures the currently executable core routes, not language completeness of third-party extensions.

## Translation rules

Use identical keys for all supported locales, with the same PHP value types and formatting placeholders. Keep links, routes, identifiers, CSS class names, HTML attributes, JSON property names, brand names and technical tokens unchanged. Do not substitute translated text into SQL, JS selectors or route names. Use separate language resources for background email jobs in the recipient's language. Avoid duplicate definitions, which can silently override the first value.

## Audit scope

The audit does not prove how a translated screen appears in the browser or catch every hardcoded text fragment in optional JavaScript and external libraries. Verify dynamic widgets, checkout, emails, SEO and installed third-party themes on staging, in each active language, before publishing a production update.

## Additional source and key parity check

Run `php tools/i18n-coverage-check.php` after any localization change. It checks file/key symmetry for all resources present in all three languages, including non-controller common resources and value-array shapes, and verifies statically referenced language files in controller source. It deliberately reports older locale-only resources without any executable controller separately rather than creating fabricated translations for removed extensions.

User-facing labels are translated; protocol names, technology trademarks, API identifiers, file paths, routes, option codes and formatting parameters are not translated. A shared-language-key test cannot confirm all client-side dynamic texts or third-party extensions; runtime checks remain necessary.
