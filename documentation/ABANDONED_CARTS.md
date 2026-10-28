# Abandoned carts — CodeCart 3.0.6

This feature is opt-in. The cart analytics switch, guest contact capture and automated reminders are separate settings under Sales → Abandoned carts → Settings. A disabled feature must not run storefront SQL, AJAX, send email or use additional front-end assets.

## Installation and update

Make a full backup of the website and database before updating. Deploy the changed files at their existing paths. Open the Abandoned carts Settings tab and save settings once after a file-only update to provision and reconcile the consent tables and scheduler tasks. The migration also creates the registered consent table during normal installation/update. The registered consent bridge can provision that table on its first explicit write when upgrading files independently. To use the guest checkout checkbox, select Standard OpenCart source and enable Contact capture for guests. It is disabled by default. This integration applies to the bundled CodeCart and default guest checkout templates; third-party checkouts require separate support.

## Consent and delivery

Registered customers: reminders require a valid email and explicit, unchecked-by-default cart reminder consent for the current storefront/session. A general newsletter subscription is never sufficient. The separate consent checkbox is offered on logged-in checkout pages, including UniShop2 where the standard route is active. Reminders also respect the optional mail suppression list. Guest customers: an explicit unchecked-by-default cart-recovery consent checkbox appears at checkout when enabled. Registered and guest consent data expire after seven days and are purged by `core.cart.guest_purge` (every 12 hours while cart analytics is enabled). A link sent by email can restore the guest cart only once; the token is generated using random bytes, stored as a hash and expires in seven days. The recovery endpoint redirects to the cart without leaving the token in the final URL.

Automated sending is disabled by default. Enable it in cart settings after confirming the lawful basis for contacting customers and configuring storefront SMTP. The dedicated job `core.cart.reminder` runs every five minutes through the global scheduler. An enabled scheduler task alone does not guarantee that the server invokes `cron.php` or that SMTP delivers email. Always perform a controlled test on a staging shop first.

A successful mail status means accepted by the store transport, not necessarily delivered to the inbox. Reminders are limited to one per customer/email per day, once per cart fingerprint, and at most three manual attempts with ten-minute intervals for failures. Abandoned cart retention remains seven days and should not be confused with deleting shopping cart data. A “restored cart” statistic means a guest used a recovery link, not that an order was paid.

## Multistore behavior and limits

Consent is scoped to one storefront, customer and checkout session. The sender uses the originating store's URL, name, mail settings and language, with default-store mail settings only as inherited values. If the originating store cannot be established, the reminder is not sent. This is deliberately conservative: old baskets without explicit origin/consent are visible in analytics but cannot receive automated mail. Consent and accepted-by-transport counts are not paid-order conversions.

An automatic checkout URL choice uses the **active theme**, not merely the presence of a UniShop2 file. For an unsupported checkout choose a custom URL. The latter does not automatically create an integration to collect its contacts.

## Admin UX

Carts: search by email, customer, phone, product; filter by contactability or guests; paginate 10/25/50/100; select multiple rows to send or delete with confirmation. Bulk send is capped at 10, bulk delete at 100 per request. External data-source carts remain read-only. History: filter by email/status and paginate 25 per page. Settings: source mapping, idle window, guest capture and mail automation. Destructive clear actions are under the Settings danger zone.

## Security and cleanup

All mutating admin actions require POST, user-token check and access/modify permissions. Source field names use an identifier allowlist. Guest recovery uses an unguessable single-use token. Disabling guest capture or the whole cart feature removes no-longer-required guest contact records after an explicit settings save. Disabling the entire feature also removes registered consent data. The scheduled consent purge runs only while abandoned-cart analytics is enabled and uses the standard data source.

## External SQL cart source

Use the Standard OpenCart source unless the checkout writes cart lines to a **separate real SQL table**. External mode reads only and cannot send automated reminders or capture guest checkout contacts. Enter the table name, select **Detect columns**, review the suggestions and save. If a non-standard column cannot be detected, expand **Column mapping** and enter its actual database identifier. A session ID, product ID, quantity and timestamp are required per cart line; customer ID, email and telephone are optional. The adapter does not support carts stored exclusively in localStorage or arbitrary serialized JSON payloads. It does not delete or edit third-party tables. Backup your site and database before deploying updates.

## Scheduler and hosting Cron

CodeCart internal tasks select an installed `catalog/controller/cron/*.php` handler, choose a schedule (minimum five minutes for new custom tasks) and save. The technical code and route are generated from the selected handler; existing built-in jobs are not duplicated. The optional hosting helper accepts a third-party module's one-line CLI/HTTPS command and shows the five matching Mirohost cron scheduling fields, but **does not execute or store that command**. Paste it into the hosting control panel as a separate host job. The shared CodeCart CLI command `php /path/to/site/cli.php cron:run` should be installed once on the host, ideally every five minutes. Do not execute both the CodeCart CLI and HTTPS commands for the same schedule.
