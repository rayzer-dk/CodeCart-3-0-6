# Lost URLs (404)
[Українська](LOST_URLS.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

**Back up website files and database before enabling.** Open **Reports → Lost URLs (404)**, enable monitoring and save. The feature is disabled by default. Viewing requires `report/online` access; changes require `design/seo_url` modify permission.

## Review missing content

The journal considers actual HTML HTTP 404 responses to storefront GET requests. JSON/AJAX, HEAD/POST, assets, API, checkout/account paths and common scanner probes are excluded. Declared bots do not create records; they can increment a separate counter for an existing address.

**Needs attention** shows repeated browser-like requests or referrals from the store, recognized search engines/social sites. Other single requests appear under **Doubtful**. A browser-like request is not proof of a person and referrals can be forged. Counts represent requests, not unique visitors; same-session repeats within 60 seconds count once.

Inspect each missing address and its intended content. Restore the content, mark it ignored, or create a manual redirect. No automatic redirect is created. The journal retains path, relevant content route/IDs, store/language, counts, first/last dates and last referral path. It does not retain IP, full User-Agent or permanent visitor ID; unrelated query parameters and referral queries/fragments are discarded.

## Manual redirects

Enter terminal ASCII SEO slugs only: no domain, full path, surrounding slash or `.html`. The target must be one unambiguous content SEO URL in the same store/language. Verify the target page actually exists and is published before saving.

Rules return HTTP 301, preserve native language prefixes and discard query parameters. Existing source SEO URLs, external targets, equal source/target, chains and cycles are rejected. Restoring a source SEO URL takes precedence over its old rule; removal of the target SEO URL stops the rule. Technical/API/AJAX paths do not redirect. Numeric PHP routes can be reported but cannot be redirected under this slug-only rule: restore the content or fix the original link.

Redirect rules are paginated and can be removed manually. Removing a rule returns affected records to review.

## Retention, scheduling and disabling

Permanent InnoDB/utf8mb4 tables are created only on explicit enabling. Retention is **30, 60 or 90 days** from the last request; default **60 days, doubtful and closed only**. This safe mode removes old ignored/fixed/doubtful records while preserving useful unresolved addresses. Explicit **All old journal entries** also removes unresolved old records and requires confirmation. **Redirect rules are retained in both modes.**

Each store retains at most **5000 addresses**. At capacity, new addresses wait until space is available; existing records still accumulate counts. Daily cleanup removes at most **500 records per execution** through the [shared Scheduler](SCHEDULER.md). An existing scheduler cron needs no separate lost URL cron.

OFF stops recording, redirects and cleanup before working database/service access. The read-only report and enable settings remain available; disabling preserves stored records and rules.

## Coverage

Native CodeCart articles with missing content receive an actual 404. The original UniShop2 3.6.6.0 missing-news controller/model was checked with a minimal empty news schema and CodeCart Theme; this focused check does not certify the complete commercial theme installation or UI. Soft 404 pages returning HTTP 200 and errors served directly by a web server/CDN are not recorded.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
