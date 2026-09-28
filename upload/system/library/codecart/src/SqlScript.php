<?php
namespace CodeCart\Core;

/**
 * Splits an SQL dump into statements.
 *
 * The OpenCart installer ended a statement at every line ending with ";". Any
 * multi-line string value containing an HTML entity at the end of a line
 * ("&gt;", "&quot;") was cut in the middle and the install failed with MySQL
 * error 1064. This splitter tracks quoted strings (with backslash escapes and
 * doubled quotes), identifiers and comments, and splits only on ";" outside them.
 */
final class SqlScript {
    /**
     * @return string[] statements without the trailing ";"
     */
    public static function statements(string $sql): array {
        $statements = array();
        $length = strlen($sql);
        $buffer = '';
        $quote = '';
        $start = 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== '') {
                if ($char === '\\' && $quote !== '`') {
                    $i++;
                    continue;
                }
                if ($char === $quote) {
                    if ($i + 1 < $length && $sql[$i + 1] === $quote) {
                        $i++;
                        continue;
                    }
                    $quote = '';
                }
                continue;
            }

            if ($char === '\'' || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            // Line comments: "-- " and "#" up to the end of the line.
            if (($char === '-' && $i + 1 < $length && $sql[$i + 1] === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) || $char === '#') {
                $buffer .= substr($sql, $start, $i - $start);
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $length : $end;
                $start = $i;
                continue;
            }

            // Block comments (executable /*! ... */ comments are kept).
            if ($char === '/' && $i + 1 < $length && $sql[$i + 1] === '*' && !($i + 2 < $length && $sql[$i + 2] === '!')) {
                $buffer .= substr($sql, $start, $i - $start);
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;
                $start = $i + 1;
                continue;
            }

            if ($char === ';') {
                $buffer .= substr($sql, $start, $i - $start);
                if (trim($buffer) !== '') {
                    $statements[] = trim($buffer);
                }
                $buffer = '';
                $start = $i + 1;
            }
        }

        if ($quote !== '') {
            throw new \RuntimeException('The SQL script contains an unterminated quoted value.');
        }

        $buffer .= substr($sql, $start);
        if (trim($buffer) !== '') {
            throw new \RuntimeException('The SQL script contains an unterminated statement.');
        }

        return $statements;
    }

    /**
     * Rewrites the default "oc_" table prefix of installer statements.
     */
    public static function applyPrefix(string $statement, string $prefix): string {
        if ($prefix === 'oc_') {
            return $statement;
        }
        return (string)preg_replace('/^(DROP TABLE IF EXISTS|CREATE TABLE|INSERT INTO)\s+`oc_/i', '$1 `' . $prefix, $statement, 1);
    }
}
