<?php
namespace CodeCart\Core;

/** Classification of errors thrown by the DB driver, which hides server messages (they may contain SQL). */
final class DatabaseError {
    /**
     * MySQL 1146: table does not exist. mysqli keeps the server error number as the
     * exception code; PDO uses SQLSTATE 42S02 and keeps the number in errorInfo.
     */
    public static function isMissingTable(\Throwable $error): bool {
        for ($current = $error; $current !== null; $current = $current->getPrevious()) {
            if ((int)$current->getCode() === 1146 || (string)$current->getCode() === '42S02') { return true; }
            if ($current instanceof \PDOException && isset($current->errorInfo[1]) && (int)$current->errorInfo[1] === 1146) { return true; }
        }
        return false;
    }
}
