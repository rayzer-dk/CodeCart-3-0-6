# Scheduler — Build 2.0.4

[Українська](SCHEDULER.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

Open **System → Scheduler** and use its actual command with the correct PHP binary and absolute store path. One server cron launches registered CodeCart tasks; task intervals are configured internally. Daily lost URL cleanup uses this same scheduler. Independent third-party cron commands still need their own configuration. Visitor heartbeat cannot guarantee execution without traffic.

Example command (replace the path):

```sh
php /absolute/store/path/cli.php cron:run
```

For Mirohost enter separate fields:

| Field | Value |
|---|---|
| Minutes | `*/5` |
| Hours | `*` |
| Days of month | `*` |
| Months | `*` |
| Days of week | `*` |
| Command | The actual command above |

Do not schedule more frequently than every five minutes on Mirohost. Confirm the CLI PHP version/extensions match the store requirements and inspect Scheduler status/logs after a run. Avoid duplicate cron entries.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
