# Events diagnostics — CodeCart PRO 3.0.6.1

Use the existing Extensions → Events screen. Static verification starts only after clicking **Check Events**; it never executes third-party controllers. Disabled and catalog/admin-scoped events are handled by their appropriate startup loaders.

Click **Record 10 min** to temporarily record observed PHP Event calls, durations and exception classes. Click **Stop recording** to end early, or **Clear log** to discard history. Recording expires automatically after 600 seconds and stores at most 1 MB in `storage/logs/codecart-event-trace.jsonl`. It never stores action arguments, order data, request URLs or exception messages. A successful PHP Event call is not evidence that JavaScript or storefront UI behavior worked.

The OCMOD compatibility scanner remains unchanged. No new database tables, background cron jobs or page-load scans were added. An Event not observed during tracing is **not** a failure.

This module follows OpenCart 3.x Event routes (`admin/...` and `catalog/...`), supports PHP 8.1–8.5 and is compatible with the existing extension API (the optional fourth registration argument is used only by CodeCart's DB loaders).
