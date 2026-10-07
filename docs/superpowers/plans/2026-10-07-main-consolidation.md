# CodeCart main consolidation Implementation Plan

> Execute inline with superpowers:executing-plans. The user explicitly authorized consolidation in main and subsequent error and compatibility checks.

**Goal:** Preserve all branch work in main, import the supplied Build 1.9.12 baseline, validate dependencies and OpenCart/ocStore upgrades, then publish and remove incorporated branches.

**Architecture:** Fast-forward the cumulative audit branch, overlay the newer archive without removing repository-only CI and tests, combine dependency updates in Composer, and retain all branch tips as merge parents. Keep generated vendor and runtime evidence out of source control.

**Tech Stack:** PHP 8.1–8.3, Composer, MariaDB 10.11, OpenCart 3.0.5.x, ocStore 3.0.4.1, GitHub Actions.

**Spec:** User messages in this chat and supplied CodeCart-3.0.6-production-Build-1.9.12.zip.

## Global Constraints

- Work and releases use main; no new development branches.
- Preserve a verified bundle before branch removal.
- Do not treat prior documentation claims as current test evidence.
- Never overwrite live store data, credentials, or an unrelated test database.
- Delete remote branches only after verified publication and ancestry checks.

## Review Focus

- Archive updates must retain branch-only CI and tests.
- Combined dependency updates must install on PHP 8.1 and remain locked.
- Migration must retain store data, active themes, and repeat safely.
- Main-only releases must not disable pull-request checks.
- Runtime and secrets must never enter Git or release output.

### Task 1: Consolidate source and dependencies

- [x] Verify and save all refs in a Git bundle.
- [x] Fast-forward main to audit/build-1.9.10, which also contains audit/build-1.8.4-fixes.
- [x] Overlay the supplied archive; retain repository-only tools and workflows, reconcile bonus sources, omit runtime artifacts from Git.
- [x] Combine all three Dependabot constraints and regenerate the lock with Composer.
- [x] Restrict release packaging to main and stop Dependabot creating branches.
- [x] Verify archive deltas, Composer installation, syntax, templates, JavaScript and manifests.

### Task 2: Compatibility and regression verification

- [x] Run existing release and compatibility checks on PHP 8.1, 8.2 and 8.3.
- [x] Run clean install in a new isolated MariaDB lab.
- [x] Test upgrades from exact upstream OpenCart 3.0.5.x and ocStore 3.0.4.1 fixtures, including preservation, schema preflight and repeated migrations.
- [x] Verify storefront, admin authentication, cart and order flows; record precise coverage and limitations.
- [x] Fix only reproduced errors, rerun affected checks, and perform independent review.

### Task 3: Publish and clean branches

- [x] Commit verified source and integration records on main with all incorporated branch tips preserved as parents.
- [ ] Publish main after authentication is available, verify remote SHA and CI.
- [ ] Remove only fully incorporated remote branches and re-list remote refs.
- [x] Deliver the verified artifact and report; clearly state any remaining blocker.

## Execution record

- User main-only instruction overrides worktree/menu defaults; no extra permission requested for authorized integration.
- Archive Build 1.9.12 retained as original baseline; consolidated release renumbered 2.0.0 to follow single-digit build-version policy. Platform schema version stays3.0.6.0.
- Session storage failure reproducer failed before the fix and passes after clearing identity on both false and Throwable. Independent re-review found no important remaining defect in the fix.
- Initial ocStore CLI snapshot mismatch was not evidence of lost data; direct mysqli comparison against the restored verified preupgrade dump matched all nine groups. Migration source was not changed.
- Legacy schema advisory differences remain37/38; no blocking differences, conversion errors or engine/charset differences after explicit modernization.
- Remote publication, remote CI and deletion remain pending Git authentication.
