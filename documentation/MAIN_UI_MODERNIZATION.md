# Admin UI changes on the main branch

This document records changes applied directly to `main` as requested. These are incremental, backward-compatible UI improvements to the existing CodeCart 3.0.6 runtime. The main branch still loads Bootstrap 3 and jQuery 3.7.1.

The experimental PHP 8.2–8.5 / Bootstrap 5.3.9 / jQuery 4 CodeCart 3.7.0 modernization remains under `feature/codecart-3.7.0-modernization`. No full-version upgrade or branch merge was performed in this batch.

## Changes on main

- Scoped responsive admin CSS `upload/admin/view/stylesheet/codecart-main-ui.css`.
- UI scopes in seven existing screens: product edit/list, order list/info, system settings, category edit and customer list.
- Compact wrapping page-header actions, horizontal scrolling for long tab rows, mobile form spacing, more predictable table containers, improved focus indicators and dark-mode-aware borders.
- All three sidebar hierarchy levels now use `aria-controls` and `aria-expanded` for their existing Bootstrap 3 collapse targets.
- Mobile admin header overflow moves keyboard focus to its first real action. Third-party icon DOM nodes and their event listeners are retained.

## QA

- `tests/ui/main-admin-static-check.cjs`: verifies scoped template classes, CSS import and navigation semantics.
- `tests/ui/main-admin-navigation-smoke.cjs`: checks the *actual Bootstrap 3 stack* through a Chromium fixture with three-level collapse, keyboard focus, Escape and resize restoration.
- `.github/workflows/main-admin-navigation-qa.yml`: runs the static and browser checks on relevant `main` pushes or manual dispatch.

These fixture tests do **not** certify a live installed shop, OCMOD modifications, custom extensions, checkout or the experimental Bootstrap 5 build. GitHub Actions passing has not been confirmed through the available connector; keep production validation separate.
