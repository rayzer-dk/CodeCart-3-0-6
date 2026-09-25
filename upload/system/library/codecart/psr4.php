<?php
/**
 * Lightweight PSR-4 compatibility autoloader for modern CodeCart PRO internals.
 * It does not replace OpenCart 3 Loader/Action/MVC-L and therefore does not
 * change legacy extension class resolution.
 */
final class CodeCartPsr4 {
    private static $prefixes = array();
    private static $registered = false;

    public static function register($prefix, $directory) {
        $prefix = trim((string)$prefix, '\\') . '\\';
        $directory = rtrim(str_replace('\\', '/', (string)$directory), '/') . '/';

        if ($prefix === '\\' || !is_dir($directory)) {
            return false;
        }

        self::$prefixes[$prefix] = $directory;

        if (!self::$registered) {
            spl_autoload_register(array(__CLASS__, 'autoload'), true, true);
            self::$registered = true;
        }

        return true;
    }

    public static function autoload($class) {
        $class = ltrim((string)$class, '\\');

        foreach (self::$prefixes as $prefix => $directory) {
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            if ($relative === '' || !preg_match('/^[A-Za-z0-9_\\\\]+$/', $relative)) {
                return false;
            }

            $file = $directory . str_replace('\\', '/', $relative) . '.php';
            $realBase = realpath($directory);
            $realFile = is_file($file) ? realpath($file) : false;

            if ($realBase && $realFile && strncmp($realFile, $realBase . DIRECTORY_SEPARATOR, strlen($realBase) + 1) === 0) {
                require_once($realFile);
                return true;
            }

            return false;
        }

        return false;
    }
}
