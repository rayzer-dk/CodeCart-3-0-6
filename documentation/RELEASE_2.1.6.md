# CodeCart 3.0.6.0 — Build 2.1.6

Date: 2026-11-02. Package build: 2.1.6. Core schema remains 3.0.6.0. Security update: install it on every store where an administrator enabled TOTP.

## Two-factor verification was not enforced

`admin/controller/startup/totp.php` started with `if (!isset($this->user) || …) return;`. `Controller` exposes registry services through `__get()` but has no `__isset()`, so the condition was always true and the check returned before looking at the account. With TOTP enabled, the administration, AJAX endpoints and the file manager opened after the password alone. The gate now uses `$this->registry->has('user')`, the same test as device authorization.

The existing defensive tests covered only `TotpManager`. `tools/qa_auth_defensive.php totp-pipeline` runs the startup gate the way the framework runs it. Against the previous code it reports 17 of 24 checks failing, and it is part of `tools/release_check.php` and the new `Admin Two-Factor QA` workflow.

## Behaviour

- **Unreadable state.** If the two-factor state cannot be read, the session is held on the verification page with HTTP 503 and an explanation. The previous code treated any database error as "not enabled". The only exception is a store that still needs the Installer UPDATE and has no MFA table yet (MySQL 1146, or SQLSTATE 42S02 with the PDO driver), because no second factor can exist there.
- **Pending session.** Until the code is accepted, the header, menu, footer, notifications and search are rendered as signed out. Previously, `error/permission` showed notifications and the menu.
- **After verification.** The session ID and `user_token` are replaced. The administrator lands on the page they originally opened: GET requests only, without the old token.
- **Attempt limits.** Only wrong codes are counted. Successful sign-ins no longer use up the limit. Limits are 5 per 15 minutes and 20 per 24 hours per account, and 15 per 15 minutes per source. When an account becomes locked, its owner receives an e-mail: a wrong second factor means the password is known. The verification form also checks the admin token, so a foreign page cannot post codes from the administrator's browser. Someone who knows the password can still use up the daily limit and lock verification for up to 24 hours; the owner is notified and another administrator can reset the second factor.
- **Self-service.** Management moved from CodeCart Core to the profile menu → Two-factor verification (`common/two_factor`). It is available to every administrator, not only to users who can modify CodeCart Core. Setup, disabling and new recovery codes require the account password (failures count as failed logins) and send a notice to the account e-mail. Disabling and new recovery codes also require a current code.
- **View-only accounts.** Accounts whose group has no modify permissions cannot set up two-factor themselves, so a visitor of a shared demo login cannot bind it to their phone.
- **Mandatory groups.** CodeCart Core → Security → "Mandatory for groups". Members without a second factor are sent to the setup page after the password, with the menu hidden, and cannot disable it. In this forced setup the new authenticator is accepted only together with a 6-digit code sent to the account e-mail (valid 15 minutes, 5 tries), so the password alone cannot enrol someone else's phone. Voluntary setup needs the password and a current authenticator code. The field shows how many active users in each group are protected.
- **Lost phone.** Users → edit shows the 2FA state. An administrator with modify rights on both `user/user` and `tool/codecart_core` can reset another administrator's second factor after entering their own password. Their own account cannot be reset there. The action is audited and the target is notified by e-mail. The users list has a 2FA column.
- **Other fixes.** Logout clears the two-factor session keys. A `route[]=` parameter is dropped in `Request` instead of producing HTTP 500 in the permission check.

## Deployment

1. Upload the files.
2. Refresh modifications.
3. Make sure the store e-mail settings work, because lock, reset and change notices are sent by e-mail.
4. Administrators who had TOTP enabled are asked for the code at their next request.
5. Before making two-factor mandatory for a group, confirm that store mail works and every member can receive codes and e-mail. A shared demo or view-only group should stay unselected.

## Verification scope

Checked over HTTPS on PHP 8.3 and MariaDB 10.11 with administrator accounts (TOTP and mandatory policy), a view-only "Demonstration" user and a second administrator.

- **Password alone.** Signing in with only the password led to the verification page for the dashboard, product edit, product autocomplete, file manager, `common/two_factor` and `user/user/resetTwoFactor`. `error/permission` showed no notifications, menu or environment details.
- **After the code.** A correct code returned the originally requested product page with a new session ID and token, and the old token stopped working.
- **Lockout.** Five wrong codes returned HTTP 429 with `Retry-After` and sent one lock e-mail. Successful sign-ins left no attempt rows.
- **Fail closed.** A broken MFA table (invalid view) returned 503 for pages and AJAX.
- **Self-service page.** New recovery codes, disabling with a recovery code and re-enabling all worked. A wrong password or code was rejected. A POST without `user_token` was rejected. Notices reached the SMTP sink.
- **Mandatory policy.** A manager without 2FA in a mandatory group got only the setup page. Enrolment needed the e-mailed code: a repeated start sent no second e-mail, a wrong or missing code was rejected, and the code stopped working after 5 wrong tries. A successful enrolment replaced the session ID and token, and the factor could not be disabled afterwards. Voluntary setup asked for no e-mail code.
- **Token check.** A POST to the verification form without the admin token was rejected without counting an attempt. A remembered page with an encoded filter value opened with the original value.
- **Demo account.** The demo account signed in without a code. It could view but not modify, and could not start 2FA setup.
- **Administrator reset.** Reset worked only through POST with the actor's password, never for one's own account, never without CodeCart Core modify rights, and the target was notified.

Static checks: PHPStan results for the changed files show no new findings compared with 2.1.5; all three languages have matching keys. Not covered: WebAuthn/passkeys and QR-code display of the setup key, which are not part of this build.
