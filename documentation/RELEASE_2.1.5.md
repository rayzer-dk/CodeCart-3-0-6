# CodeCart 3.0.6.0 — Build 2.1.5

Date: 2026-11-02. Package build: 2.1.5. Core schema remains 3.0.6.0.

## Cron endpoint

`cron.php` dispatches `route=cron/codecart`. With SeoPro enabled, `startup/seo_url` treated that request as a storefront page and answered with a 301 to the canonical address. The HTTPS Cron caller and the administration "Run now" button deliberately do not follow redirects (the key is in the query string), so every task failed with "HTTP 301: redirect is not a trusted HTTPS Cron endpoint". Machine routes (`cron/*`) are now excluded from canonical and lost-URL redirects; the router still rejects every HTTP `cron/*` route other than the key-protected gateway. If a redirect is configured on the web server or hosting (for example http→https or www), the scheduler shows the HTTP code and the redirect target so the storefront URL in `config.php` can be corrected.

## Abandoned carts

The consent script for guest contacts was added in `startup/startup` before `startup/seo_url` had decoded the route. On SEO checkout addresses (`/checkout`, `/simplecheckout`, custom paths) the route was still empty, the script was not loaded and no guest e-mail or name was stored, so nothing could be sent. Injection moved to the `startup/startup/cartRecovery` pre-action, which runs after SEO decoding. Custom checkout URLs are matched by their `route` parameter or by path.

Guest contacts are stored only after the customer ticks the reminder consent checkbox; carts without consent are listed as "No opt-in" and never receive e-mail. This is required for e-mail marketing consent in the EU.

Guest contacts now record the storefront language. Reminders use that language (uk, ru, da, en) and a recovery link with the language prefix; the link is single-use.

Missing CodeCart tables are recognised by MySQL error 1146 through the database wrapper and created on first use: guest contacts (with `lastname` and `language_id` columns added to existing tables), registered-customer reminder consent and the Cron run history and heartbeat. `install/opencart.sql` creates the Cron history and heartbeat tables for new installations.

## Administration

Cron: the status block names the source of the last server Cron call and shows the last run triggered by site visits separately; short intervals are localized; successful manual runs and result tooltips no longer carry the "Technical details" prefix; the run history shows "Success" and "Site visit"; result and history labels do not wrap.

Abandoned carts: the action column stays visible while the 1320 px table scrolls horizontally, including the dark theme; the contact column says "No contact left" and the mail status "No opt-in".

## Verification scope

Clean install on PHP 8.3 and MariaDB 10.11 behind HTTPS with SeoPro: `GET /checkout` loads the consent script; the consent endpoint stores the guest contact; `cron.php` over HTTPS returns 200 for the gateway and for a single task; the reminder is recorded and delivered to an SMTP sink; the recovery link restores the cart once; an English-prefixed guest receives an English reminder with an `/en/` link. "Run now" succeeds for every scheduler task; a simulated hosting redirect produces the localized message with the redirect target. Administration pages were checked at 1440 px, 390 px and in the dark theme without horizontal page overflow or JavaScript errors. PHPStan results for the changed files are identical to build 2.1.4. The live storefront theme (UniShop2 checkout markup) is not certified by these fixtures; after deployment refresh modifications and confirm that the consent checkbox appears on checkout.
