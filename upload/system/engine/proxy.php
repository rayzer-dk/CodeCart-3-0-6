<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Proxy class
 *
 * @template TWraps of Model
 *
 * @mixin TWraps
*/
class Proxy extends \stdClass {
    /**
     * 
     *
     * @param	string	$key
     */	
	public function __get($key) {
		return $this->{$key};
	}	

    /**
     * 
     *
     * @param	string	$key
	 * @param	string	$value
     */	
	public function __set($key, $value) {
		 $this->{$key} = $value;
	}
	
	public function __call($key, $args) {
		$arg_data = array();
		
		$args = func_get_args();
		
		foreach ($args as $arg) {
			$arg_data[] =& $arg;
		}
		
		if (isset($this->{$key})) {		
			return call_user_func_array($this->{$key}, $arg_data);	
		} else {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$file = isset($trace[1]['file']) ? (string)$trace[1]['file'] : 'unknown';
			$line = isset($trace[1]['line']) ? (int)$trace[1]['line'] : 0;
			@error_log('CodeCart Proxy: undefined method/property Proxy::' . $key . ' at ' . $file . ':' . $line);
			throw new \RuntimeException('Requested component method is unavailable. Check the application error log.');
		}
	}
}
