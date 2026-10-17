# Pull request quality review: CodeCart (OpenCart/ocStore 3.x, PHP 8.1–8.5)

Review actual changes for bugs, security and regressions, not artificial code style score or PR volume.
Report only actionable high-confidence findings, with file/line, failure scenario and concrete fix.
Verify methods and services exist in this repository; never guess APIs. Examine dependency and call chains.
Flag potential SQL injection, unsafe user input handling, missing authorization, exposed secrets, and unsafe web output.
Check source is UTF-8 without BOM and no invisible Trojan Source or copy-pasted Markdown/AI code placeholders.
Preserve legitimate multilingual strings, language data, third-party licenses, PHPDoc and useful design comments.
Do not recommend blindly removing comments, adding pointless tests, redundant defensive checks or new packages.
Never let a Rector refactor break functional behavior; check full build, tests and relevant integration contracts.
Never treat PHPStan / Rector / source guard success as a substitute for runtime tests or security review.
Honor OpenCart Registry, dynamic models/Proxy, OCMOD, Events, theme overrides and admin/catalog separation. Check upgrade/install/off-mode and legacy PHP 8.1 compatibility.

Keep public-facing CodeCart documentation generic: describe compatibility with OpenCart/ocStore and standard extension/theme APIs, but do not name or promote specific external themes, vendors or modules. Do not delete adapters or change compatibility logic to achieve neutral documentation.
Enforce full source integrity (no BOM), reject actual duplicate class methods and copy-pasted code only after semantic review. Do not manufacture tests or remove meaningful PHPDoc.
