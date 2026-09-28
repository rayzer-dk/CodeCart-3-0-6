<?php
// Text
$_['text_success']     = 'Success: You have modified extensions!';
$_['text_unzip']       = 'Extracting files!';
$_['text_move']        = 'Copying files!';
$_['text_xml']         = 'Applying modifications!';
$_['text_remove']      = 'Removing temporary files!';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify extensions!';
$_['error_install']    = 'Extension installation taking place please wait a few seconds before trying to install!';
$_['error_unzip']      = 'Zip file could not be opened!';
$_['error_file']       = 'Install file could not be found!';
$_['error_directory']  = 'Install directory could not be found!';
$_['error_code']       = 'Unique code is required for modification XML!';
$_['error_xml']        = 'Modification %s is already being used!';
$_['error_exists']     = 'The file %s already exists!';
$_['error_allowed']    = 'The extension path %s is outside the installer allowlist.';
$_['error_unsafe_archive'] = 'The extension archive contains an unsafe or invalid path. Installation was stopped.';

$_['error_dom_extension'] = 'The PHP DOM extension is required in the WEB/FPM profile to install OCMOD packages. The archive was not processed.';

// CodeCart PRO safe extension preflight / rollback
$_['text_preflight_ok'] = 'Preflight passed: no existing files will be overwritten.';
$_['text_preflight_conflicts'] = 'Preflight checked %d file(s); %d existing file(s) would be overwritten.';
$_['text_preflight_backed_up'] = 'Preflight passed: %d existing file(s) backed up to the persistent rollback journal.';
$_['text_conflict_preview'] = 'This extension will overwrite %d existing file(s). Review the paths below and confirm installation:';
$_['text_conflict_more'] = '... and %d more file(s).';
$_['error_preflight'] = 'Extension preflight failed. Installation was stopped before files were changed.';
$_['error_preflight_required'] = 'Extension preflight journal is missing. Installation was stopped.';
$_['error_move'] = 'Extension files could not be installed. Applied files were rolled back.';
$_['error_journal'] = 'Extension files were installed, but the rollback journal could not be finalized. Installation was stopped and logged.';
$_['error_uninstall_restore'] = 'The extension could not be safely uninstalled because original files could not be restored.';
$_['warning_uninstall_changed'] = '%d installed file(s) were changed after installation and were left untouched to prevent data loss.';
$_['error_xml_install'] = 'The modification XML could not be applied safely. Installed files were rolled back.';
$_['error_rollback_incomplete'] = 'Extension files could not be installed and the automatic rollback did not complete. Check the installation journal and restore the files from a backup.';
