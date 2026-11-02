CodeCart 3.0.6 - Notification and Scheduler language updates
Patch contains nine complete files retaining the upload/ structure.
Required: a matching CodeCart 3.0.6 main build.

Known scheduler/queue errors, results and statuses are displayed in
the currently selected admin language (en-gb, ru-ru, uk-ua).
Previously saved errors display translated immediately; original
diagnostic details remain unchanged in database and technical logs.
Unknown provider/PHP errors retain the original text with a
localized technical-details prefix, to avoid hiding root causes.
Route names and codes like core.queue.worker are never translated.

Before updating, BACK UP all website files and the complete database.
Copy the files over the corresponding CodeCart paths, then refresh
OCMOD modifications and clear template/opcache as appropriate.
Existing Cron execution errors require separate verification: this
change translates their presentation and does not claim to repair
the failing jobs.
