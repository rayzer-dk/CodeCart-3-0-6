# System notification center — regression tests

This document covers CodeCart PRO 3.0.6.0.

## Root cause of repeated login prompts

`Url::link()` produces HTML-escaped `&amp;user_token`. The notification page
previously passed this through `e('html_attr')` a second time, resulting in
`&amp;amp;user_token` inside links. The browser sent `amp;user_token` instead
of `user_token`. AJAX links had the same encoding problem and received an
HTML login form rather than JSON. The fix normalizes the URL in the controller
and escapes it exactly once at its final output context: HTML attribute or
JavaScript string. It does not relax session or permission checks.

## Visibility and history

The page shows 50 messages at a time with All, New, Read, Resolved, and Archived
filters. All includes non-archived messages. Archived contains dismissed
messages and is excluded from the normal list and header badge. The header
shows the latest five active messages. Resolved messages are retained in the
database. Security Cron no longer automatically purges 30-day-old notification
history. An archived issue that later becomes resolved moves to Resolved; a
subsequent recurrence appears as New. Entries already deleted by older builds
are not restored without an earlier database backup.

System alerts come from the health Cron check, scheduler and queue failures,
selected account security events, and GDPR events. The health Cron is scheduled
by the core scheduler and requires the site's configured cron worker. A static
code audit cannot verify execution of the worker or actual table contents on
a live server.

## Offline tests

From the repository root:

- `php tools/notification-check.php` tests status filtering, archiving,
  reinstatement and upsert semantics.
- `php tools/notification-url-check.php` compiles the real Twig view and checks
  correctly generated HTML, AJAX URLs, archived status and pagination.
- `php tools/i18n-check.php` checks active translation parity.

## Staging tests

With an administrator in a backed-up staging database, inspect the request URL
of each filter and confirm `user_token` is passed as a normal query parameter.
Switch between all five tabs and pages without a second login. Test Delete,
Read, Read All and Clear Read and verify JSON success and correct counts after
reload. Dismiss a health warning, run the health Cron, and verify the same
warning remains in Archived when unchanged; resolve the underlying issue and
run the worker to confirm it moves to Resolved. Check notifications created by
an actual failing scheduler task and, where appropriate, a test queue task.

A missing or expired session must still require an ordinary admin login, and
a user without Modify permission must remain blocked from changing messages.
