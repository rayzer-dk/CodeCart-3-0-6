<?php
namespace CodeCart\Core;

/**
 * Set a predictable utf8mb4 default for a dedicated, empty installation schema.
 * Existing/shared schemas and their tables are never modified implicitly.
 */
final class DatabaseDefaults {
    public static function ensureUtf8mb4ForNewInstallation($db): string {
        try {
            $objects = $db->query('SHOW FULL TABLES');
            if (!empty($objects->rows)) {
                return 'existing_objects';
            }

            $result = $db->query('SELECT DATABASE() AS schema_name, @@character_set_database AS db_charset');
            $schema = isset($result->row['schema_name']) ? (string)$result->row['schema_name'] : '';
            $charset = isset($result->row['db_charset']) ? strtolower((string)$result->row['db_charset']) : '';

            // SQL identifiers cannot be safely escaped with value escaping.
            if (!preg_match('/^[A-Za-z0-9_]{1,64}$/D', $schema)) {
                return 'unsupported_name';
            }
            if ($charset === 'utf8mb4') {
                return 'already_utf8mb4';
            }

            $db->query('ALTER DATABASE `' . $schema . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            return 'updated';
        } catch (\Throwable $e) {
            // Installation is still valid: CodeCart tables have explicit utf8mb4 attributes.
            return 'unavailable';
        }
    }
}
