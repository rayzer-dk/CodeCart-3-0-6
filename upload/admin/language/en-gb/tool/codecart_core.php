<?php
$_['heading_title'] = 'Core / Compatibility';
$_['text_home'] = 'Home';
$_['text_runtime'] = 'Runtime';
$_['text_security'] = 'Security';
$_['text_privacy'] = 'Privacy';
$_['text_schema'] = 'Database Schema';
$_['text_extensions'] = 'Modern Extensions';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_ok'] = 'OK';
$_['text_missing'] = 'Missing';
$_['text_optional'] = 'Optional';
$_['text_schema_not_scanned'] = 'The check has not been run. It only reads INFORMATION_SCHEMA and changes nothing.';
$_['text_no_issues'] = 'No issues found';
$_['text_no_extensions'] = 'No Modern Extensions are registered';
$_['text_gdpr_requests'] = 'GDPR Requests';
$_['text_confirm_delete'] = 'Anonymise this customer? Orders and accounting records will be retained.';
$_['text_device_fail_open'] = 'When enabled, an unknown device requires an e-mail code. If the code cannot physically be sent because mail fails, the current session is not locked out and a critical system notification is created.';
$_['text_schema_readonly'] = 'Schema Registry is diagnostic only. Extra third-party tables and columns are not treated as errors. No automatic ALTER is performed.';
$_['text_extension_help'] = 'An additive isolated API layer for modern extensions alongside Legacy OpenCart 3. This is not binary OpenCart 4.1 compatibility: OpenCart 4.x extensions require adaptation or a bridge.';
$_['text_runtime_help'] = 'This checks the current WEB/FPM runtime serving the admin. CLI must be checked separately.';
$_['text_spam_help'] = 'Central rate-limit service for public forms, compatible with the existing FormGuard facade.';
$_['text_gdpr_help'] = 'Adds customer export and deletion requests. Deletion is controlled anonymisation; orders are not automatically removed.';
$_['text_cookie_help'] = 'Controls consent for optional cookies and analytics or marketing integrations.';
$_['text_notification_help'] = 'System-important events only, no advertising.';
$_['text_current_user_devices'] = 'Trusted devices for the current administrator';
$_['text_revoke_devices'] = 'Revoke trusted devices';
$_['text_refresh_registry'] = 'Refresh registry';
$_['text_scan_schema'] = 'Scan schema';
$_['text_save'] = 'Save';
$_['text_processed'] = 'Processed';
$_['text_pending'] = 'Pending';
$_['text_confirmed'] = 'Confirmed';
$_['text_superseded'] = 'Superseded';
$_['text_export'] = 'Export';
$_['text_delete'] = 'Delete';
$_['text_unknown'] = 'Unknown';
$_['text_system_notifications'] = 'System Notifications';
$_['text_scheduler'] = 'Cron / Scheduler';
$_['text_settings_saved'] = 'Settings saved.';
$_['text_extensions_refreshed'] = 'Modern Extension registry refreshed: %s.';
$_['text_devices_revoked'] = 'Trusted devices revoked.';
$_['text_gdpr_processed'] = 'GDPR request processed; the account was anonymised.';
$_['entry_spam'] = 'Central Spam Service';
$_['entry_admin_device'] = 'Administrator unknown-device protection';
$_['entry_customer_device'] = 'Customer unknown-device protection';
$_['entry_gdpr'] = 'GDPR Manager';
$_['entry_cookie'] = 'Cookie Consent';
$_['column_component'] = 'Component';
$_['column_value'] = 'Value';
$_['column_state'] = 'State';
$_['column_type'] = 'Type';
$_['column_table'] = 'Table';
$_['column_column'] = 'Column';
$_['column_message'] = 'Message';
$_['column_request'] = 'Request';
$_['column_customer'] = 'Customer';
$_['column_email'] = 'E-mail';
$_['column_date'] = 'Date';
$_['column_action'] = 'Action';
$_['button_process_delete'] = 'Anonymise';
$_['button_save'] = 'Save';
$_['button_scan'] = 'Scan';
$_['button_refresh'] = 'Refresh';
$_['button_revoke'] = 'Revoke';
$_['error_permission'] = 'Permission denied.';
$_['error_gdpr_disabled'] = 'GDPR Manager is disabled.';
$_['error_action'] = 'Unknown action.';

$_['button_cookie_settings'] = 'Configure cookies';

$_['column_details'] = 'Details';
$_['column_severity'] = 'Severity';
$_['text_info'] = 'Information';
$_['text_warning'] = 'Warning';
$_['text_error'] = 'Error';
$_['text_external'] = 'Third-party/custom';
$_['text_core'] = 'Core';
$_['text_schema_external_help'] = 'Additional third-party tables, columns and indexes are informational only and are never removed automatically.';
$_['text_preflight_help'] = 'Checks run in the real WEB/FPM profile serving this domain. A public phpinfo.php file is not required for ongoing diagnostics.';
$_['entry_login_protection'] = 'Administrator login protection';
$_['entry_login_max_attempts'] = 'Failed attempts per user/IP';
$_['entry_login_window'] = 'Attempt window, minutes';
$_['entry_security_audit_retention'] = 'Audit retention, days';
$_['entry_totp'] = 'Authenticator MFA (TOTP)';
$_['entry_security_headers'] = 'Security headers';
$_['entry_frame_options'] = 'Frame protection';
$_['entry_permissions_policy'] = 'Permissions-Policy';
$_['entry_hsts'] = 'HSTS';
$_['text_totp_enabled'] = 'TOTP enabled';
$_['text_totp_disabled'] = 'TOTP disabled';
$_['text_security_audit'] = 'Security audit';
$_['text_login_protection_help'] = 'Rate-limits failed administrator logins by username + IP and separately by source IP. It does not let another IP globally lock an administrator account.';
$_['text_security_headers_help'] = 'Safe headers are managed in Core. CSP is Report-Only first; HSTS is sent only over HTTPS.';
$_['text_hsts_help'] = 'Enable only after the whole domain and required subdomains are permanently HTTPS.';

$_['entry_nosniff'] = 'MIME sniffing protection';
$_['entry_referrer_policy'] = 'Referrer-Policy';

$_['text_about'] = 'About System';
$_['text_about_requirements'] = 'Server requirements';
$_['text_required'] = 'Required';
$_['text_recommended'] = 'Recommended';
$_['text_not_supported'] = 'Not supported';
$_['text_server_requirements_help'] = 'These checks apply to the PHP profile serving the website. CLI used by Cron must be checked separately.';
$_['text_component'] = 'Component';
$_['text_codecart_website'] = 'CodeCart PRO website';

$_['text_icons'] = 'Icons';
$_['text_icon_search'] = 'Search icon name';
$_['text_icon_help'] = 'Font Awesome 6.7.2 + FA4 compatibility catalog and CodeCart PRO aliases. Click an icon to copy its CSS class.';

$_['text_modern_social_icons'] = 'Modern social icons';

$_['diag_loaded'] = 'loaded';
$_['diag_missing'] = 'missing';
$_['diag_supported'] = 'supported';
$_['diag_not_supported'] = 'not supported';
$_['diag_enabled'] = 'enabled';
$_['diag_disabled'] = 'disabled';
$_['diag_recommended'] = 'recommended';
$_['diag_optional'] = 'optional';
$_['diag_active'] = 'active';
$_['diag_not_active'] = 'not active';
$_['diag_writable'] = 'writable';
$_['diag_inside_root'] = 'inside document root';
$_['diag_outside_root'] = 'outside document root';
$_['diag_unknown'] = 'unknown';
$_['diag_never'] = 'never';
$_['diag_not_configured'] = 'not configured';
$_['diag_check_failed'] = 'check failed';
$_['diag_msg_php'] = 'Supported: PHP 8.1–8.5 in the WEB/FPM profile.';
$_['diag_msg_required_ext'] = 'Required WEB/FPM extension.';
$_['diag_msg_required_image'] = 'Required image format.';
$_['diag_msg_avif'] = 'Recommended for native AVIF support.';
$_['diag_msg_production'] = 'Recommended for production.';
$_['diag_msg_current_web'] = 'Current WEB/FPM value.';
$_['diag_msg_upload_limit'] = 'Applied to customer/admin generic file uploads in addition to PHP limits.';
$_['diag_msg_extension_limit'] = 'Compressed .ocmod.zip limit; extracted archives also have entry/path safety limits.';
$_['diag_msg_display_errors'] = 'Production should use Off with log_errors On.';
$_['diag_msg_https'] = 'Required for production checkout and secure cookies.';
$_['diag_msg_writable'] = 'Writable.';
$_['diag_msg_not_writable'] = 'Directory must exist and be writable.';
$_['diag_msg_storage'] = 'Prefer storage outside the public web root.';
$_['diag_msg_strict_ok'] = 'Core is running with a strict SQL mode.';
$_['diag_msg_strict_warn'] = 'Compatibility SQL mode is active. Use strict SQL mode after the compatibility check reports no blockers.';
$_['diag_msg_db_modernize'] = 'Use Database Modernizer only after a full backup.';
$_['diag_msg_schema_ok'] = 'Schema version matches this build.';
$_['diag_msg_schema_warn'] = 'Core schema version differs from this build. Run the migration check.';
$_['diag_msg_migration_latest'] = 'Latest recorded Core migration.';
$_['diag_msg_migration_none'] = 'No CodeCart PRO Core migration records are available yet.';
$_['diag_msg_queue'] = 'Review Scheduler / Queue.';
$_['diag_msg_smtp'] = 'Configuration check only; delivery still requires a send-test.';
$_['diag_msg_mail'] = 'Current OpenCart mail engine.';
$_['diag_msg_disk'] = 'Low disk space can break cache, image generation, logs and updates.';
$_['schema_tables_expected'] = 'Tables expected';
$_['schema_tables_present'] = 'Tables present';
$_['schema_missing_tables'] = 'Missing tables';
$_['schema_missing_columns'] = 'Missing columns';
$_['schema_type_mismatches'] = 'Type mismatches';
$_['schema_engine_mismatches'] = 'Engine mismatches';
$_['schema_charset_mismatches'] = 'Charset mismatches';
$_['schema_missing_indexes'] = 'Missing indexes';
$_['schema_external_tables'] = 'External tables';
$_['schema_external_columns'] = 'External columns';
$_['schema_external_indexes'] = 'External indexes';
$_['schema_blocking_issues'] = 'Blocking issues';
$_['schema_warnings'] = 'Warnings';
$_['schema_informational'] = 'Informational';
$_['schema_type_missing_table'] = 'Missing table';
$_['schema_type_missing_column'] = 'Missing column';
$_['schema_type_type'] = 'Column type';
$_['schema_type_engine'] = 'Table engine';
$_['schema_type_charset'] = 'Charset';
$_['schema_type_missing_index'] = 'Missing index';
$_['schema_type_external_table'] = 'External table';
$_['schema_type_external_column'] = 'External column';
$_['schema_type_external_index'] = 'External index';
$_['schema_msg_missing_table'] = 'Missing table';
$_['schema_msg_missing_column'] = 'Missing column';
$_['schema_msg_missing_index'] = 'Missing index';
$_['schema_msg_missing_unique_index'] = 'Missing unique index';
$_['schema_msg_external_table'] = 'Additional table (third-party/custom); informational only';
$_['schema_msg_external_column'] = 'Additional column (third-party/custom); informational only';
$_['schema_msg_external_index'] = 'Additional index (third-party/custom); informational only';

// Schema Registry guidance and explicit safe repair
$_['button_schema_safe_fix'] = 'Fix safe schema issues';
$_['text_schema_safe_fix_help'] = 'The table below now explains what each finding means and what to do. The repair button only handles engine/charset mismatches and missing non-unique indexes. Missing tables, columns, type changes and unique indexes remain manual to protect data and module compatibility.';
$_['text_schema_backup_warning'] = 'Before any database repair, make a full backup of the database and site files. ALTER TABLE can lock large tables and should be run during low traffic.';
$_['text_schema_backup_confirm'] = 'I have made a full database and site-files backup.';
$_['text_schema_fixable'] = 'Safe repair';
$_['text_schema_manual'] = 'Manual';
$_['text_schema_external_ok'] = 'Normal';
$_['text_schema_fix_result'] = 'Schema repair completed: %d fixed, %d left for manual review.';
$_['text_schema_fix_errors'] = 'Schema repair encountered %d errors. Review the error log and remaining rows.';
$_['column_meaning'] = 'What it means';
$_['column_next_action'] = 'What to do';
$_['schema_meaning_missing_table'] = 'A table required by this CodeCart PRO build is absent.';
$_['schema_action_missing_table'] = 'Do not create it blindly. Run CodeCart PRO migrations first; if it remains missing, compare with the clean-install schema and restore only after a backup.';
$_['schema_meaning_missing_column'] = 'A required column is absent from an existing table.';
$_['schema_action_missing_column'] = 'Run CodeCart PRO migrations. If it remains missing, inspect the module/core migration that owns this column before changing the table manually.';
$_['schema_meaning_type'] = 'The column exists but its SQL type is incompatible with the canonical schema.';
$_['schema_action_type'] = 'Review real data and dependent modules before ALTER/MODIFY. This is intentionally not auto-fixed.';
$_['schema_meaning_engine'] = 'The table engine differs from the canonical CodeCart PRO engine.';
$_['schema_action_engine'] = 'After a full backup, Safe repair can convert it to the expected engine. Large tables may be locked during conversion.';
$_['schema_meaning_charset'] = 'The table charset/collation differs from the canonical CodeCart PRO schema.';
$_['schema_action_charset'] = 'After a full backup, Safe repair can convert the table to the expected charset/collation. Check legacy text data first.';
$_['schema_meaning_missing_index'] = 'A canonical index is missing, which can reduce query performance or data guarantees.';
$_['schema_action_missing_index'] = 'Safe repair adds only missing non-unique indexes. Unique indexes stay manual because duplicate data must be checked first.';
$_['schema_meaning_external'] = 'This is an additional table, column or index from a module/customisation. It is not a CodeCart PRO schema error.';
$_['schema_action_external'] = 'No action is required. CodeCart PRO does not delete or alter third-party schema objects.';

$_['text_icon_all'] = 'All';
$_['text_icon_social'] = 'Social';
$_['text_icon_commerce'] = 'Commerce';
$_['text_icon_payment'] = 'Payments';
$_['text_icon_shipping'] = 'Delivery';
$_['text_icon_contact'] = 'Contacts';
$_['text_icon_interface'] = 'Interface';
$_['text_icon_security'] = 'Security';
$_['text_icon_media'] = 'Media';
$_['text_icon_development'] = 'Development';
$_['text_icon_other'] = 'Other';
$_['text_icon_copied'] = 'Copied';
$_['text_icon_usage'] = 'The copied class can be used in Twig/HTML, for example <i class="fa-solid fa-cart-shopping"></i>. Brand icons use the standard Font Awesome 6 syntax, for example <i class="fa-brands fa-x-twitter"></i>.';
// CodeCart PRO retention controls
$_['entry_gdpr_retention'] = 'GDPR history retention, days';
$_['text_gdpr_retention_help'] = 'Only old processed or superseded requests are removed automatically. Active pending/confirmed requests are never removed by retention cleanup.';
$_['text_retention_auto'] = 'Automatic cleanup, days';
$_['button_cleanup_old'] = 'Clean old records';
$_['text_cleanup_done'] = 'Old records removed: %d';
$_['text_cleanup_confirm'] = 'Clean records older than the configured retention period?';


// CodeCart PRO contextual field guidance
$_['help_login_max_attempts'] = 'Failed attempts allowed before a temporary lock. 5–7 is recommended; lower can inconvenience admins, higher weakens protection.';
$_['help_login_window'] = 'Time window in which failed attempts are counted. 15 minutes is recommended.';
$_['help_totp'] = 'A second one-time code from an authenticator app. Recommended for administrators who can manage settings, orders or extensions.';
$_['help_customer_device'] = 'Verifies a new customer device by e-mail. Enable only when store mail delivery is reliable; it is not required for guest checkout.';
$_['help_nosniff'] = 'Prevents browsers from guessing a file MIME type. Recommended to keep enabled.';
$_['help_referrer_policy'] = 'Controls how much page-address information is sent to external sites. strict-origin-when-cross-origin is the recommended compatibility/privacy balance.';
$_['help_frame_options'] = 'Protects pages from iframe embedding. SAMEORIGIN is recommended; DENY is stricter and may block legitimate integrations.';
$_['help_permissions_policy'] = 'Restricts browser APIs such as camera, microphone and geolocation. Do not change without testing integrations.';
$_['help_gdpr'] = 'Controls personal-data export/anonymization requests. Orders and accounting records are not automatically deleted.';
$_['help_gdpr_retention'] = 'Days to retain completed/cancelled GDPR requests. 365 is recommended; active requests are not purged.';
$_['help_cookie'] = 'Shows Cookie Consent status and links to its dedicated settings. Optional cookies should run only after consent.';

$_['text_icon_scanner'] = 'Icon compatibility scanner';
$_['text_icon_scanner_help'] = 'Scans real storefront files and compiled catalog OCMOD output. For every icon outside Core it shows the CSS class, file and line. AUTO safely falls back to Full Compatibility.';
$_['text_icon_mode_requested'] = 'Configured mode';
$_['text_icon_mode_setting'] = 'Icon package mode';
$_['text_icon_mode_setting_help'] = 'Choose the icon package used by the storefront. AUTO uses Core and switches to Full only when required.';
$_['text_icon_auto'] = 'AUTO — Core with automatic Full fallback';
$_['text_icon_core'] = 'Core — lightweight CodeCart PRO subset';
$_['text_icon_full'] = 'Full Compatibility — Font Awesome Free + FA4 compatibility';
$_['text_icon_mode_saved'] = 'Icon package mode saved.';
$_['text_icon_mode_effective'] = 'Effective mode';
$_['text_icon_core_count'] = 'Icons in Core';
$_['text_icon_files_scanned'] = 'Files scanned';
$_['text_icon_last_scan'] = 'Last scan';
$_['text_icon_unknown'] = 'Outside Core';
$_['text_icon_location'] = 'File';
$_['text_icon_line'] = 'Line';
$_['text_icon_kind'] = 'Type';
$_['text_icon_resolution'] = 'Resolution';
$_['text_icon_resolution_full'] = 'Available in Full Free';
$_['text_icon_resolution_unresolved'] = 'Not present in Font Awesome Free 6.7.2. Possible typo or PRO-only icon; Full Compatibility cannot render it.';
$_['text_icon_unresolved'] = 'Unavailable in Free';
$_['text_icon_no_unknown'] = 'All detected icons are supported by the lightweight Core bundle.';
$_['text_icon_fallback_full'] = 'Icons outside Core were detected: AUTO uses Full Compatibility so no icon disappears.';
$_['text_icon_core_active'] = 'The Core bundle is sufficient for the current storefront.';
$_['text_icon_scan_error'] = 'The scanner could not finish. AUTO falls back to Full Compatibility.';
$_['text_icon_manual_build_help'] = 'Manual Core additions are release-build hints in system/config/codecart_fontawesome_core_manifest.json. Add the name without fa- to manual_extra_icons. Invalid, unknown or non-free names are skipped with a build warning; they do not stop the whole build. Production never recompiles fonts.';
$_['button_icon_rescan'] = 'Scan icons';
$_['text_icon_scan_complete'] = 'Icon scan completed: %d files, %d icons outside Core.';

$_['text_icon_core_package'] = 'Core / Minimal';
$_['text_icon_full_package'] = 'Full Free';
$_['text_icon_core_package_help'] = 'The Core package is the lightweight icon subset actually used on the storefront in Core/AUTO mode. AUTO switches to Full when an installed extension needs an icon outside Core.';
$_['text_icon_full_package_help'] = 'Full Font Awesome Free 6.7.2 catalog. Browsing this list does not make the storefront load the full package; it is a reference/copy catalog.';
$_['text_icon_full_count'] = 'Icons in Full Free';
$_['text_icon_styles'] = 'Styles';

$_['entry_icon_extras'] = 'Additional Core icons';
$_['help_icon_extras'] = 'Copy icon classes from Full Free below and paste here, separated by spaces or commas. Save the set, or add all available icons found by the scanner. Only the required small font files load. Unknown names are skipped. Remove a name and save to remove it from this set.';
$_['button_icon_add_found'] = 'Add detected icons';
$_['button_icon_save_extras'] = 'Save icon set';
$_['text_icon_extras_saved'] = 'Additional icons saved: %d. Unknown names skipped: %d. Built-in Core icons are already available.';
$_['error_icon_extras'] = 'The icon set could not be saved. Check the input and that all release files have been uploaded.';

$_['button_phpinfo'] = 'PHP Info';

$_['button_phpinfo_download'] = 'Download PHP Info';

$_['text_icon_loading'] = 'Loading the full icon catalogue…';
$_['text_icon_load_error'] = 'Unable to load the full icon catalogue.';
$_['text_architecture'] = 'Architecture';
$_['text_architecture_help'] = 'Additive modern core services. Legacy OpenCart/ocStore MVC-L, routes, Events and OCMOD remain supported and are not replaced.';
$_['text_compat_detected'] = 'Detected main category strategy';
$_['text_active_languages'] = 'active languages';
$_['text_service_layer_help'] = 'Additive services only; standard Registry, Loader and MVC-L remain the primary compatibility path.';
$_['text_event_layer_help'] = 'Optional handlers are isolated; one failed modern hook does not break the remaining handlers or standard OpenCart Events.';
$_['text_asset_manager_help'] = 'Simple CSS/JS keeps the standard addScript/addStyle bridge for legacy themes; advanced assets stay opt-in.';
$_['text_api_v1'] = 'REST API v1';
$_['text_api_v1_help'] = 'Optional read-only catalog API. Disabled by default. Enable only when a headless client, integration or external service needs it.';
$_['text_service_layer'] = 'Service layer';
$_['text_compat_layer'] = 'Compatibility layer';
$_['text_event_layer'] = 'Event layer';
$_['text_asset_manager'] = 'Asset manager';
$_['text_search_adapter'] = 'Search adapter';
$_['text_native_fallback'] = 'Native catalog search is always available as fallback; external search engines remain optional.';
$_['text_opt_in'] = 'Opt-in';
$_['text_read_only'] = 'Read-only catalog endpoints; standard OpenCart API and checkout are unchanged.';
$_['entry_api_v1_status'] = 'REST API v1';
$_['button_save_architecture'] = 'Save architecture settings';

// Storefront performance
$_['text_performance'] = 'Performance';
$_['text_performance_help'] = 'Safe CSS and JavaScript optimization settings for the storefront and admin.';
$_['entry_storefront_minify_css'] = 'Use minified storefront CSS when available';
$_['entry_storefront_minify_js'] = 'Use minified storefront JavaScript when available';
$_['entry_admin_minify_css'] = 'Use minified admin CSS when available';
$_['entry_admin_minify_js'] = 'Use minified admin JavaScript when available';
$_['help_storefront_minify_css'] = 'Use a local *.min.css only when an up-to-date minified sibling exists next to the source CSS. The source file is never changed.';
$_['help_storefront_minify_js'] = 'Use a local *.min.js only when an up-to-date minified sibling exists next to the source JavaScript. Script order is preserved.';
$_['help_admin_minify_css'] = 'Use a local *.min.css in admin only when an up-to-date minified sibling exists next to the source CSS. The source file is never changed.';
$_['help_admin_minify_js'] = 'Use a local *.min.js in admin only when an up-to-date minified sibling exists next to the source JavaScript. Script order is preserved.';
$_['text_safe_minify_note'] = 'Safe mode: files are not concatenated and third-party code is not minified with risky runtime regex transforms. When no verified .min sibling exists, the original asset is used automatically.';
$_['button_save_performance'] = 'Save performance';

$_['text_minify_diagnostics'] = 'Minified asset diagnostics';

$_['text_minify_available'] = 'Up-to-date minified files available';

$_['text_minify_stale'] = 'Stale minified files';

$_['text_minify_active'] = 'Active with current settings';

// Database modernization (web batches)
$_['text_db_modernize'] = 'Database table modernization (InnoDB + utf8mb4)';
$_['text_db_modernize_help'] = 'Converts every store table, including third-party module tables, to InnoDB/utf8mb4 one table per request, so it works on hosting without SSH. Tables of 256 MB or more are skipped; use php cli.php db:migrate --backup-confirmed --large for them.';
$_['text_db_modernize_ok'] = 'All tables already use InnoDB and utf8mb4.';
$_['text_db_modernize_running'] = 'Converted tables: %s, remaining: %s…';
$_['text_db_modernize_done'] = 'Done. Converted tables: %s, remaining: %s.';
$_['text_db_modernize_large'] = 'large — CLI only';
$_['button_db_modernize'] = 'Modernize tables';
$_['column_engine'] = 'Engine';
$_['column_collation'] = 'Collation';
$_['column_size'] = 'Size';

// Localized diagnostics and comparison table
$_['diag_component_runtime_php'] = 'PHP';
$_['diag_component_ext_xmlreader'] = 'XMLReader';
$_['diag_component_runtime_opcache'] = 'OPcache';
$_['diag_component_upload_store_limit'] = 'Store file upload limit';
$_['diag_component_upload_extension_limit'] = 'Extension installer limit';
$_['diag_component_security_display_errors'] = 'display_errors';
$_['diag_component_http_https'] = 'HTTPS';
$_['diag_component_path_storage_public'] = 'Storage exposure';
$_['diag_component_db_version'] = 'MySQL/MariaDB';
$_['diag_component_db_strict_sql'] = 'Strict SQL mode';
$_['diag_component_db_modern'] = 'DB engine/charset';
$_['diag_component_db_schema_version'] = 'Core schema version';
$_['diag_component_db_migration_history'] = 'Migration history';
$_['diag_component_db_connection'] = 'Database';
$_['diag_component_cron_health'] = 'Cron heartbeat';
$_['diag_component_queue_failed'] = 'Queue failed';
$_['diag_component_mail_smtp'] = 'SMTP';
$_['diag_component_mail_engine'] = 'Mail engine';
$_['diag_component_disk_free'] = 'Disk free';
$_['diag_msg_xlsx_stream'] = 'Large XLSX imports use streaming XMLReader mode.';
$_['diag_msg_xlsx_simplexml'] = 'Regular XLSX import remains available through SimpleXML, but very large workbooks can require significantly more memory.';
$_['diag_optional_cache_backends'] = 'optional cache backends';
$_['diag_storage_cache_logs'] = 'Storage / cache / logs';
$_['diag_msg_sql_compat'] = 'OpenCart-compatible SQL mode (standard for OpenCart/ocStore modules). Strict mode is enabled with codecart_db_strict_mode = 1.';

$_['text_icon_packages'] = 'Font Awesome packages';

$_['error_invalid_request_method'] = 'Invalid request method.';
$_['error_invalid_security_token'] = 'Invalid security token.';

$_['text_extension_code'] = 'Code';
$_['text_extension_name'] = 'Name';
$_['text_extension_version'] = 'Version';
$_['text_extension_namespace'] = 'Namespace';
$_['text_icon_style_solid'] = 'Solid';
$_['text_icon_style_regular'] = 'Regular';
$_['text_icon_style_brands'] = 'Brands';
$_['text_totp_own'] = 'Your account';
$_['button_totp_manage'] = 'Manage';
$_['text_totp_policy'] = 'Mandatory for groups';
$_['help_totp_policy'] = 'Members of the selected groups must set up two-factor verification at their next sign-in and cannot disable it. Leave shared demo or view-only groups unselected.';
$_['text_totp_group_stats'] = '%d of %d active users protected';
$_['entry_csp_report_only'] = 'Content Security Policy';
$_['entry_csp_policy'] = 'CSP policy';
$_['text_csp_report_only_help'] = 'Sends the policy below. In Report-only mode the browser only reports violations and blocks nothing.';
$_['help_csp_policy'] = 'Content-Security-Policy directives separated by semicolons. The same policy is used in both modes.';
$_['entry_csp_mode'] = 'CSP mode';
$_['text_csp_mode_report'] = 'Report-only (Content-Security-Policy-Report-Only)';
$_['text_csp_mode_enforce'] = 'Enforce (Content-Security-Policy)';
$_['help_csp_mode'] = 'Enforce makes the browser block everything the policy does not allow. OpenCart 3 themes and modules use inline scripts and external resources, so switch to Enforce only after testing the storefront, checkout, payments and the admin in Report-only mode without violations.';
$_['text_about_changes'] = 'What changed vs OpenCart/ocStore';
$_['text_about_build'] = 'Build';
$_['text_about_schema'] = 'Core schema';
$_['text_about_revision'] = 'Presentation revision';
$_['text_about_upstream'] = 'Based on';
$_['text_about_upgrade_required'] = 'Installer UPDATE required, database has';
$_['text_about_db'] = 'Database';
$_['text_about_driver'] = 'DB driver';
$_['text_about_components'] = 'Components and libraries';
$_['text_about_components_note'] = 'ocStore and OpenCart versions are taken from their release archives (composer.lock and bundled files). "—" means the component is not included.';
$_['text_about_advantages'] = 'Advantages';
$_['text_about_level_updated'] = 'Updated';
$_['text_about_level_added'] = 'Added';
$_['text_about_level_same'] = 'Same';
$_['text_about_group_added'] = 'Added';
$_['text_about_group_security'] = 'Security';
$_['text_about_group_updated'] = 'Updated';
$_['text_about_group_fixed'] = 'Fixed';
$_['text_about_group_removed'] = 'Removed';
$_['about_component_php'] = 'PHP';
$_['about_component_gallery'] = 'Product gallery';
$_['about_component_datepicker'] = 'Storefront date picker';
$_['about_component_password'] = 'Admin password hashing';
$_['about_component_smtp_tls'] = 'SMTP / HTTPS TLS';
$_['about_v_datepicker_native'] = 'Built-in (no moment.js)';
$_['about_v_datepicker_bootstrap'] = 'bootstrap-datetimepicker + moment.js';
$_['about_v_password_sha1'] = 'salted SHA-1';
$_['about_v_tls_strict'] = 'TLS 1.2–1.3, certificate check';
$_['about_v_tls_starttls'] = 'STARTTLS, no minimum version';
$_['about_advantages'] = array(
    'Runs on PHP 8.1–8.5. ocStore 3.0.3.7 declares PHP 5.4+, OpenCart 3.0.4.1 PHP 7.3+.',
    'Newer libraries than every base: Twig 3.30 vs 2.14 / 3.8 / 3.24, Guzzle 7.15 vs 5.3 / 6.5 / 7.10.',
    'Admin passwords use password_hash() instead of salted SHA-1, and two-factor sign-in is built in. None of the three base versions has either.',
    'Existing OpenCart 3 extensions keep working: Twig 2 class aliases, jQuery Migrate, Font Awesome 4 class names, OCMOD and events.',
    'Cron, queue, notifications, store health, integrity check and the database modernizer are part of the core, not separate modules.'
);
$_['about_added_items'] = array(
    'Two-factor sign-in (TOTP) for administrators with recovery codes; can be made mandatory per user group.',
    'Confirmation of new devices by e-mail code for administrators and customers.',
    'Scheduler, task queue, server Cron and CLI commands (cron:run, queue:work, db:migrate, styles:rebuild).',
    'System notifications and store health dashboard with a file integrity check against the release manifest.',
    'Abandoned carts with consent-based reminders, including guest contacts.',
    'Catalog import/export (XLSX), public documents for products, product relations.',
    'SEO: language URL prefixes, SeoPro, hreflang and canonical rules, lost URL monitor.',
    'Mail audit log, SPF/DKIM/DMARC DNS check, back-in-stock notifications.',
    'GDPR requests, cookie consent, central spam protection for public forms, Cloudflare Turnstile captcha.',
    'Read-only REST API v1 (off by default), Redis/APCu/Memcached cache, WebP/AVIF images.'
);
$_['about_updated_items'] = array(
    'PHP 8.1–8.5; Twig 3.30, Guzzle 7.15, scssphp 2.1.',
    'jQuery 3.7.1 everywhere, jQuery Migrate 3.4.1 for legacy modules.',
    'Font Awesome 6.7.2 Free; Font Awesome 4 class names keep working.',
    'Summernote 0.9.1, HTML Purifier 4.19.0, PhotoSwipe 5.4.4 as an alternative gallery.',
    'Storefront date fields use the built-in date picker; moment.js and bootstrap-datetimepicker stay only for third-party modules.',
    'New installs use utf8mb4 and InnoDB; the database modernizer converts old tables after a backup.',
    'Installer UPDATE upgrades existing stores with versioned migrations and a schema check.'
);
$_['about_fixed_items'] = array(
    'OpenCart 3.0.5.1 core fixes included: admin password encoding, GLOB_BRACE on musl, Redis authentication, captcha session.',
    'Extensions: Twig 2 class names are aliased to Twig 3, event triggers match by prefix, ocStore DB_DRIVER mpdo is accepted.',
    'MySQL 8: CodeCart tables use utf8mb4_unicode_ci, no collation error 1267.',
    'SeoPro returns 404 for unknown path segments and does not redirect cron.php or payment callbacks.',
    'Requests with a non-string route no longer end in HTTP 500.'
);
$_['about_security_items'] = array(
    'Admin passwords hashed with password_hash() and rehashed on sign-in; old SHA-1 hashes are still accepted.',
    'Login protection with attempt limits per account and source, security audit log.',
    'CSRF tokens on device authorization and GDPR forms; API rate limit by the real client IP.',
    'Security headers: nosniff, Referrer-Policy, Permissions-Policy, HSTS, CSP in report-only or enforce mode; the admin cannot be framed by other sites.',
    'SMTP and bundled HTTP integrations (PayPal, currency rates, Google, Turnstile) require TLS 1.2+ with certificate verification.'
);
$_['about_removed_items'] = array(
    'jQuery 2.1.1 copies.',
    'Font Awesome 4 font files (Font Awesome 6 webfonts are used, FA4 class names are mapped).',
    'Legacy php.ini in the web root (delete it from existing sites).',
    'Duplicate upgrade step 3054 and legacy catalog import actions in Tools → Backup.'
);
$_['about_component_twig'] = 'Twig';
$_['about_component_scssphp'] = 'scssphp';
$_['about_component_guzzle'] = 'Guzzle';
$_['about_component_jquery'] = 'jQuery';
$_['about_component_jquery_migrate'] = 'jQuery Migrate';
$_['about_component_bootstrap'] = 'Bootstrap';
$_['about_component_fontawesome'] = 'Font Awesome';
$_['about_component_swiper'] = 'Swiper';
$_['about_component_summernote'] = 'Summernote';
$_['about_component_htmlpurifier'] = 'HTML Purifier';
