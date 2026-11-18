# Administrator language selector

CodeCart PRO has a native, compact flag-only selector in the admin header. Clicking
the flag opens the list of enabled, locally installed administration languages.
The current language is marked with a check. It uses the same header action sizing
as the adjacent theme and notification icons, including the mobile header.

Supported bundled locales: Ukrainian (uk-ua), Russian (ru-ru), English (en-gb).
Other languages appear only if they are enabled in the language table and their
admin PHP language pack and PNG flag are installed.

A choice is stored per authenticated user in `codecart_admin_language` and persists
across logins and devices. The table is part of the clean-install SQL and is created on existing stores by
core migration `PRESENTATION_SCHEMA_VERSION = 42`. Until migration succeeds the default configured
admin language is used and the authenticated save action reports a failure.

The POST-only route `common/language` requires a valid admin session, matching
user_token and completed MFA. The redirect is constrained to the admin origin and
same session token. Pages retain normal GET filters, search and edit identifiers
when changing language. User preferences do not change config_language, the
global config_admin_language setting in the database or storefront URLs.

Test: `php tests/admin_language_switch_smoke.php`. Confirm desktop/mobile, light/dark,
each of the three languages, disabled languages, session expiry, multiple admin users,
and no redirect outside the admin base.
