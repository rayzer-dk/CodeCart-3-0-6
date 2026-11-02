<?php
namespace CodeCart\Core;

/** Classification of errors thrown by the DB driver, which hides server messages (they may contain SQL). */
final class DatabaseError {
    /** MySQL 1146: table does not exist. The driver keeps the server error number as the exception code. */
    public static function isMissingTable(\Throwable $error): bool {
        for ($current = $error; $current !== null; $current = $current->getPrevious()) {
            if ((int)$current->getCode() === 1146) { return true; }
        }
        return false;
    }
}
