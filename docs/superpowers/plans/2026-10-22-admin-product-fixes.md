# Administration and product counter implementation plan

> **For agentic workers:** use the existing engineering-autopilot workflow. Work on main; do not commit, publish or deploy without authorization.

**Goal:** Correct the three demonstrated Build 2.1.1 defects without changing extension contracts.

**Architecture:** Use native form submission, guard only autocomplete source callbacks with per-field sequence/value checks, and restore the existing product-model counter call in the successful product-page branch. Keep models, storage schema, OCMOD/Events APIs and selector behavior unchanged.

**Tech stack:** PHP 8.1–8.3, OpenCart/ocStore MVC-L, jQuery, Bootstrap 3, Twig.

**Spec:** The 2026-10-22 user-approved audit in audit_codecart_build211_20261022/AUDIT.md, plus the requirement to preserve OpenCart/ocStore and third-party module compatibility.

**Constraints:** No new dependencies, queues, global minimum search length, cache policy changes or speculative optimizations. Preserve callbacks, native form association, language files and schema version 3.0.6.0. Package build becomes 2.1.2.

- [x] Demonstrate RED and GREEN with tools/qa_admin_forms_autocomplete.cjs: native submit/required/cancel/copy/Enter, stale replies, blur/Escape, refocus, empty and grouped selectors, independent fields, direct response API.
- [x] Demonstrate RED and GREEN with tools/qa_product_view_counter.php against an isolated local installation: successful openings count once; rejected products/paths and AJAX/list routes do not count.
- [x] Run the release gate on available PHP 8.1, 8.2 and 8.3, plus JavaScript syntax and adjacent Bootstrap/OCMOD compatibility regressions. Distinguish fixture and full runtime results.
- [x] Review the actual diff independently; resolve supported blockers and retest the affected behavior.
- [x] Refresh entry-point build numbers, release notes, documentation archive and integrity hashes; produce a complete ZIP, entry manifest, checksum and acceptance report.

**Review focus:** Third-party submit listeners and external buttons; direct autocomplete response calls; mouse selection during blur grace; non-product getProduct consumers; duplicate view increments injected by separately installed OCMOD/Events. Do not claim every commercial extension was exercised.
