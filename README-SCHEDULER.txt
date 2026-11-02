CodeCart 3.0.6 - Cron execution context and Scheduler UI.
This ZIP includes 9 complete changed files in their upload/ paths.
FULL BACKUP of all site files and database is required before update.

Editing: Pencil opens a loading indicator directly BELOW the selected
task, then expands the existing standard Save form in the same row.
No accidental full-page jump, row mismatch or duplicate ID forms.
Task titles/descriptions are localized in en-gb, ru-ru and uk-ua;
technical codes and routes remain unchanged for compatibility.
Action buttons have identical 36x36 dimensions and tooltip titles.

Error repair: Automatic catalog Cron scheduling no longer executes
from the admin application context (where catalog handlers are absent).
Catalog traffic or the real cron.php/CLI entrypoint handles tasks.
Previous notifications remain in history; this package does not
guarantee live task success without running on a configured store.

Apply the files, refresh OpenCart modifications and Twig/asset cache,
and run the scheduler health check. No database migration required.
