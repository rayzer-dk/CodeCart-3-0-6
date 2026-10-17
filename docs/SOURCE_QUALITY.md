# CodeCart source integrity and Rector

Development-only tools are installed in `tools/quality/`; the production
Composer manifest, vendor directory and release package remain unchanged.

Narrow automated Rector rewrites execute on pushes to trusted `autofix/**`
branches. Only PHP files changed in that commit under MVC and engine code
are eligible, excluding bundled vendor code. At most eight changes are
applied. The workflow checks PHP syntax, Unicode/source integrity, and
the normal release static check **before** committing validated edits.
Nothing is automatically merged or deployed.

Allowed refactoring rules must preserve PHP 8.1-8.5 compatibility.
No blanket modernization, removal of supposedly unused OpenCart hooks,
renaming of routes, modification of languages, OCMOD, or Twig templates.

Check sources with
`php tools/quality/source_guard.php --check upload/admin upload/catalog upload/system`.
Use `--fix-safe` only to remove a BOM at the start of a source file.
Other Unicode repairs require contextual inspection. Ukrainian and
Russian translations and useful source comments must remain intact.

Run fixture regression checks:
`php tools/quality/source_guard_test.php`

PHPStan and Rector cannot validate OCMOD/events/checkout integration alone.
All production changes require runtime and regression testing.

## BOM policy and Excel CSV

All tracked PHP, JS/TS, Twig, XML, CSS, SQL, JSON, YAML and shell source files must
be UTF-8 **without BOM**. The root `.editorconfig` expresses this preference;
Source Guard checks the actual leading bytes on changed source files.

UTF-8 CSV intended for direct opening in older Windows Excel installations can
optionally be emitted with the byte prefix `EF BB BF` by its **CSV exporter**.
That is an output-format decision, not a source-code encoding exception: do not
commit BOM-prefixed CSV as executable code and do not process export CSV through
Source Guard. Modern Excel import flows may support BOM-free UTF-8.

Never run the uploaded generic `code_sanitizer.py --fix` across CMS files: it
blindly replaces Unicode quotation marks, punctuation, invisible characters
and formatting in strings, HTML and translations. The integrated guard only
repairs a leading BOM; ambiguous symbols are reported for contextual review.
