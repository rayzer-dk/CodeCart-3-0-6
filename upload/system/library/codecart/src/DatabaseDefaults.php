<?php
namespace CodeCart\Core;

/**
 * Database-level defaults are distinct from connection charset and table collation.
 * Only dedicated empty schemas are updated during installation; existing databases
 * require an explicit admin repair action or a change through database management.
 */
final class DatabaseDefaults {
    public static function ensureUtf8mb4ForNewInstallation($db): string {
        try {
            $objects = $db->query('SHOW FULL TABLES');
            if (!empty($objects->rows)) {
                return 'existing_objects';
            }
            return self::setCurrentDatabaseDefault($db);
        } catch (\Throwable $e) {
            // Installer tables specify utf8mb4 independently; lack of ALTER privileges
            // must not leave a half-installed store.
            return 'unavailable';
        }
    }

    /**
     * Explicitly update the default of the selected database, never its tables.
     * An unexpected SQL failure is deliberately propagated to the admin repair UI.
     */
    public static function setCurrentDatabaseDefault($db): string {
        $result = $db->query('SELECT SCHEMA_NAME AS schema_name, DEFAULT_CHARACTER_SET_NAME AS db_charset, DEFAULT_COLLATION_NAME AS db_collation FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE() LIMIT 1');
        $schema = isset($result->row['schema_name']) ? (string)$result->row['schema_name'] : '';
        $charset = isset($result->row['db_charset']) ? strtolower((string)$result->row['db_charset']) : '';
        $collation = isset($result->row['db_collation']) ? strtolower((string)$result->row['db_collation']) : '';

        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/D', $schema)) {
            return 'unsupported_name';
        }
        if ($charset === 'utf8mb4' && $collation === 'utf8mb4_unicode_ci') {
            return 'already_utf8mb4';
        }
        $db->query('ALTER DATABASE `' . $schema . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        return 'updated';
    }
}
