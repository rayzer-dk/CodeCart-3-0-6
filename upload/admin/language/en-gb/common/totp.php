<?php
$_['heading_title'] = 'Two-factor verification';
$_['text_instruction'] = 'Enter the 6-digit code from your authenticator app. You can also use one recovery code.';
$_['text_code'] = 'Verification code';
$_['text_recovery'] = 'A recovery code can be used only once.';
$_['button_verify'] = 'Verify';
$_['button_logout'] = 'Log out';
$_['error_code_required'] = 'Enter a verification code.';
$_['error_code'] = 'The code is invalid or has already been used.';

$_['error_attempts'] = 'Too many verification attempts. Try again in %s.';
$_['error_unavailable'] = 'Two-factor status cannot be checked right now, so access is blocked. Check the database or run the Installer update, then sign in again.';
$_['text_wait_minutes'] = '%d min';
$_['text_wait_hours'] = '%d h';
$_['mail_locked_subject'] = 'Two-factor verification locked';
$_['mail_locked_text'] = 'Several wrong two-factor codes were entered for the administrator account "%s" (IP %s, %s), so verification is temporarily locked.

This means someone knows the account password. If it was not you, change the password now and review the administrators list.';
