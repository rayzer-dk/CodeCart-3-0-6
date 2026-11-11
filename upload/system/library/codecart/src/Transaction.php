<?php
namespace CodeCart\Core;

/**
 * Runs related writes as one InnoDB transaction: everything is saved, or, when any statement
 * fails, nothing is. Exceptions are re-thrown unchanged so callers keep their error handling.
 */
final class Transaction {
    /**
     * @return mixed the callable's return value
     */
    public static function run($db, callable $work) {
        $db->beginTransaction();

        try {
            $result = $work();
            $db->commit();

            return $result;
        } catch (\Throwable $e) {
            try {
                $db->rollback();
            } catch (\Throwable $ignored) {
                // The original failure is the one to report.
            }

            throw $e;
        }
    }
}
