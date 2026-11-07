# CodeCart 3.0.6.0 — Build 2.1.7

Date: 2026-11-07. Package build: 2.1.7. Core schema remains 3.0.6.0. No database migration.

## Deleted system notifications came back

`SystemNotification::delete()` keeps a hidden row (`dismissed`) so the next check does not insert the same notice again. The scheduler calls `resolve('scheduler.<task>')` after every successful run, and `resolve()` changed every row except `resolved` to `resolved`. That included hidden ones, and "resolved" is shown under All and Resolved. A deleted Cron error therefore returned after the task's next successful run, which is the next day for daily tasks.

## Status rules

| Event | unread / read | dismissed (hidden, problem active) | resolved | archived (hidden, problem fixed) |
| --- | --- | --- | --- | --- |
| The problem is reported again | unread if the content changed | stays hidden; unread only if the severity rises | unread (the problem returned) | unread (the problem returned) |
| The problem is fixed (`resolve`) | resolved | archived | unchanged | unchanged |
| Delete / Clear read | dismissed | – | archived | – |

The Archive tab lists `dismissed` and `archived`. The header and the active list show only unread and read notices. A changing failure count in the message ("1 failed", "2 failed") no longer reopens a hidden notice.

Notices that are already listed as "Resolved" from earlier builds stay visible until they are deleted once. After that they remain archived.

## Verification

`tools/notification-check.php` was updated for the new statements. A behavioural scenario against MariaDB ran on both builds:

| Scenario | 2.1.6 | 2.1.7 |
| --- | --- | --- |
| Notify, then delete | hidden | hidden |
| Same failure with a new count | reappears | stays hidden |
| Successful run | reappears | stays hidden |
| Repeated successful runs | reappears | stays hidden |
| Failure after the fix | shown | shown |
| Severity rise while hidden | shown | shown |
| Clear read and resolved | – | archived |

2.1.6 failed 4 of these 10 checks; 2.1.7 passed all of them.

On the test store a Cron error notice was deleted through the notifications page. The real `core.cart.reminder` task then ran through `cron.php` and succeeded. The notice became `archived`, it was not listed under All, and the Archive tab showed it without action buttons.
