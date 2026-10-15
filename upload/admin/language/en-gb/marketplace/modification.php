<?php
// Heading
$_['heading_title']     = 'Modifications';

// Text
$_['text_success']      = 'Success: You have modified modifications!';
$_['text_refresh']      = 'Whenever you enable / disable or delete a modification you need to click the refresh button to rebuild your modification cache!';
$_['text_list']         = 'Modification List';

$_['text_incompatible'] = 'Incompatible';
$_['text_compatible_skips'] = 'Compatible / skips';
$_['text_compatible_skips_title'] = 'Compatible with the current Core, but %d optional OCMOD operation(s) were skipped. See ocmod.log for exact searches/files.';

// Column
$_['column_name']       = 'Modification Name';
$_['column_author']     = 'Author';
$_['column_version']    = 'Version';
$_['column_status']     = 'Status';
$_['column_date_added'] = 'Date Added';
$_['column_action']     = 'Action';

// Error
$_['error_permission']  = 'Warning: You do not have permission to modify modifications!';
$_['error_dom_extension'] = 'Refreshing modifications requires the PHP DOM extension in the active WEB/FPM profile. The current OCMOD cache was left unchanged.';

$_['error_xml'] = 'The modification XML file is invalid or damaged.';

$_['warning_refresh_partial'] = 'Modification refresh completed with %d blocking compatibility issue(s) in %d modification(s). Incompatible modifications are marked in red and were rolled back completely; compatible modifications remain active. See ocmod.log for details.';
$_['error_build_marker'] = 'The modification cache was generated, but its Core build marker could not be written. The generated cache will not be activated.';
$_['error_publish_failed'] = 'The OCMOD plan was built, but publishing the generated cache failed: %s. The generated cache is inactive and original Core files will be used.';
$_['column_apply_result'] = 'OCMOD Result';
$_['text_applied'] = 'Applied';
$_['text_applied_title'] = 'The modification was fully applied to the current Core.';
$_['text_applied_skips'] = 'Applied / skips';
$_['text_not_applied'] = 'Not applied';
$_['text_not_active'] = 'Disabled';
$_['text_not_active_title'] = 'The modification is disabled and is not included in the current OCMOD cache.';
$_['text_not_checked'] = 'Not checked';
$_['text_not_checked_title'] = 'There is no refresh result for this modification yet.';
$_['text_apply_metrics'] = 'Matches: %d · skipped: %d · changed files: %d';
$_['text_summary_checked'] = 'OCMOD sources checked';
$_['text_summary_applied'] = 'Fully applied';
$_['text_summary_partial'] = 'With skips';
$_['text_summary_failed'] = 'Not applied';
$_['text_summary_skipped'] = 'Optional skips';
$_['text_summary_issues'] = 'Diagnostic entries';
$_['text_last_refresh'] = 'Last cache refresh';
$_['text_not_available'] = 'no data';
$_['text_log_info'] = 'Information';
$_['text_log_help'] = 'For each OCMOD the log shows target files, matched operations, optional skips, changed files and final result. On a blocking error the whole modification is rolled back and is not included in the active cache.';
$_['text_log_tail'] = 'Showing the latest part of a large ocmod.log.';
$_['text_changed_files_title'] = 'Changed files: %s';

$_['button_problems'] = 'Problems';
$_['text_problem_details'] = 'OCMOD diagnostics';
$_['text_issue_operation'] = 'Operation';
$_['text_issue_file'] = 'File / target';
$_['text_issue_reason'] = 'Reason';
$_['text_issue_search'] = 'Search';
$_['text_changed_files'] = 'Changed files';
$_['text_no_issues'] = 'No compatibility issues were recorded for this modification.';
$_['text_rolled_back'] = 'The complete modification was rolled back and was not included in the active cache.';
$_['text_problem_loading'] = 'Loading diagnostics...';
$_['text_problem_load_error'] = 'Unable to load OCMOD diagnostics.';
$_['error_not_found'] = 'Modification or diagnostic result was not found.';

// CodeCart PRO modification editor / backup compatibility
$_['text_clear'] = 'Clear modification log';
$_['text_form'] = 'Modification';
$_['text_remove'] = 'Remove';
$_['text_xml'] = 'XML';
$_['column_id'] = 'ID';
$_['column_code'] = 'OCMOD identifier';
$_['column_restore'] = 'Restore';
$_['entry_name'] = 'Name';
$_['entry_xml'] = 'Code';
$_['entry_upload'] = 'Upload file';
$_['entry_overwrite'] = 'Files will be overwritten';
$_['entry_progress'] = 'XML update';
$_['tab_backup'] = 'Backup';
$_['help_upload'] = 'Upload the current modification XML file (code.ocmod.xml).';
$_['button_download'] = 'Download XML';
$_['button_update'] = 'Apply changes';
$_['button_restore'] = 'Restore backup';
$_['button_history'] = 'Clear backups';
$_['error_upload'] = 'The file could not be uploaded.';
$_['error_filetype'] = 'Invalid file type.';
$_['error_file'] = 'File not found.';
$_['error_code'] = 'The modification requires a unique ID code.';
$_['error_exists'] = 'Modification %s already uses this unique ID code.';
$_['error_directory'] = 'The upload file directory was not found.';
// CodeCart PRO compact OCMOD UI
$_['tab_general'] = 'Modifications';
$_['tab_log'] = 'OCMOD Log';
$_['text_show_all'] = 'Show all';
$_['text_filter_active'] = 'Filter: active';
$_['text_filter_ok'] = 'Filter: applied';
$_['text_filter_warning'] = 'Filter: with skips';
$_['text_filter_error'] = 'Filter: not applied';
$_['text_filter_issues'] = 'Filter: issues';
$_['button_download_log'] = 'Download log';
$_['text_log_compact_help'] = 'Combined OCMOD log. Results, skips and errors are shown; successful technical match details are hidden for a compact view.';

$_['text_copy_log'] = 'Copy log';
$_['text_copy_error'] = 'Copy errors';
$_['error_name'] = 'Option Name must be between 1 and 128 characters!';
$_['error_warning'] = 'Warning: Please check the form carefully for errors!';

$_['text_filesystem_modifications'] = 'File-based OCMOD sources';
$_['text_filesystem_readonly'] = 'Read-only. Managed as XML files, not through the extensions list.';
$_['text_filesystem_source'] = 'Source file';
