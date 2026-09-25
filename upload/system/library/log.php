<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Log class
*/
class Log {
	private $handle;
	
	/**
	 * Constructor
	 *
	 * @param	string	$filename
 	*/
	public function __construct($filename) {
		$this->handle = @fopen(DIR_LOGS . $filename, 'a');
	}
	
	/**
     * 
     *
     * @param	string	$message
     */
	public function write($message) {
		$line = date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n";

		if (is_resource($this->handle)) {
			@fwrite($this->handle, $line);
		} else {
			// Logging must degrade safely. A permissions/disk problem must not turn
			// the original application request into a secondary PHP TypeError.
			@error_log(rtrim($line));
		}
	}
	
	/**
     * 
     *
     */
	public function __destruct() {
		if (is_resource($this->handle)) {
			@fclose($this->handle);
		}
	}
}