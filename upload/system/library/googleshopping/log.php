<?php

namespace googleshopping;

/**
* Log class
*/
class Log {
    private $handle;
    
    /**
     * Constructor
     *
     * @param   string  $filename
    */
    public function __construct($filename, $max_size = 8388608) {
        $file = DIR_LOGS . $filename;

        clearstatcache(true);

        if ((!file_exists($file) && !is_writable(DIR_LOGS)) || (file_exists($file) && !is_writable($file))) {
            // Do nothing, as we have no permissions
            return;
        }

        if (file_exists($file) && filesize($file) >= $max_size) {
            $mode = 'wb';
        } else {
            $mode = 'ab';
        }

        $error = '';
        set_error_handler(function($severity, $message) use (&$error) {
            $error = $message;
            return true;
        });

        try {
            $this->handle = fopen($file, $mode);
        } finally {
            restore_error_handler();
        }

        if (!is_resource($this->handle) && $error) {
            error_log('Google Shopping log open failed: ' . $error);
        }
    }
    
    /**
     * 
     *
     * @param   string  $message
     */
    public function write($message) {
        if (is_resource($this->handle)) {
            fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
        }
    }
    
    /**
     * 
     *
     */
    public function __destruct() {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
