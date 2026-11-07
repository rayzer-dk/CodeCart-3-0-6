<?php
$_['heading_title'] = 'Two-factor verification';
$_['text_status'] = 'Status';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_required'] = 'Required for your user group';
$_['text_setup_required'] = 'Your user group requires two-factor verification. Set it up to continue working in the administration.';
$_['text_intro'] = 'After the password, the administration will ask for a 6-digit code from an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden and similar).';
$_['text_recovery_left'] = 'recovery codes left';
$_['text_step_app'] = 'Add a new account in your authenticator app with the key below (choose "Enter a setup key", time-based), then enter the current 6-digit code and your password.';
$_['text_secret'] = 'Setup key';
$_['text_account'] = 'Account';
$_['text_uri'] = 'otpauth link';
$_['text_code'] = 'Authenticator or recovery code';
$_['text_password'] = 'Your password';
$_['text_recovery_title'] = 'Recovery codes';
$_['text_recovery_help'] = 'Each code works once if the phone is lost. They are shown only now: save them in a password manager or print them.';
$_['text_disable_locked'] = 'Your user group requires two-factor verification, so it cannot be disabled.';
$_['text_manage_help'] = 'Enter a current code and your password to create new recovery codes or to disable two-factor verification.';
$_['button_start'] = 'Set up';
$_['button_enable'] = 'Enable';
$_['button_cancel'] = 'Cancel';
$_['button_disable'] = 'Disable';
$_['button_regenerate'] = 'New recovery codes';
$_['button_copy'] = 'Copy';
$_['button_logout'] = 'Log out';
$_['button_back'] = 'Back';
$_['text_success_enabled'] = 'Two-factor verification is enabled.';
$_['text_success_disabled'] = 'Two-factor verification is disabled.';
$_['text_success_regenerated'] = 'New recovery codes created; the previous ones no longer work.';
$_['error_start'] = 'Start the setup again.';
$_['error_code'] = 'The code is invalid or has already been used.';
$_['error_attempts'] = 'Too many wrong codes. Try again later.';
$_['error_password'] = 'Wrong password.';
$_['error_password_attempts'] = 'Too many wrong passwords. Try again later.';
$_['error_required'] = 'Your user group requires two-factor verification.';
$_['error_unavailable'] = 'The two-factor settings cannot be changed right now. Try again later.';
$_['mail_text'] = 'Administrator account "%s": %s.
IP %s, %s.

If it was not you, change the password now and review the administrators list.';
$_['mail_enabled_subject'] = 'Two-factor verification enabled';
$_['mail_enabled_event'] = 'two-factor verification was enabled';
$_['mail_disabled_subject'] = 'Two-factor verification disabled';
$_['mail_disabled_event'] = 'two-factor verification was disabled';
$_['mail_regenerated_subject'] = 'New recovery codes';
$_['mail_regenerated_event'] = 'new recovery codes were created';
$_['text_read_only'] = 'This account has view-only rights (for example a shared demo login), so two-factor verification cannot be set up here. An administrator can make it mandatory for the group.';
$_['error_read_only'] = 'View-only accounts cannot set up two-factor verification.';
$_['text_email_code'] = 'Code from the e-mail';
$_['text_email_code_help'] = 'Sent to the account e-mail address; valid for 15 minutes.';
$_['error_mail'] = 'The confirmation e-mail could not be sent. Check the store mail settings or ask an administrator.';
$_['error_email_code'] = 'The code from the e-mail is wrong.';
$_['error_email_code_expired'] = 'The e-mail code has expired or was entered wrongly too many times. Start the setup again.';
$_['mail_enroll_subject'] = 'Confirm two-factor setup';
$_['mail_enroll_text'] = 'Two-factor verification is being set up for the administrator account "%s".

Confirmation code: %s (valid for 15 minutes).

If it was not you, someone knows the account password: do not share this code, change the password and tell the store owner.';
