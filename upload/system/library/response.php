<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Response class
*/
class Response {
	private $headers = array();
	private $level = 0;
	private $output;
	private $status_code = 0;
	private $file = null;
	private $delete_file = false;

	/**
	 * Constructor
	 *
	 * @param	string	$header
	 *
 	*/
	public function addHeader($header) {
		$this->headers[] = $header;
	}

	/** Effective status, including OpenCart 3 legacy HTTP status headers. */
	public function getStatusCode() {
		$status = $this->status_code ?: (int)http_response_code();
		foreach ($this->headers as $header) {
			if (preg_match('~^HTTP/[0-9.]+\s+([1-5][0-9]{2})(?:\s|$)~i', $header, $match)) { $status = (int)$match[1]; }
		}
		return $status ?: 200;
	}

	/**
	 * Set an HTTP status without hard-coding an HTTP/1.x status line.
	 * This is protocol-neutral and works correctly behind HTTP/2/HTTP/3 proxies.
	 *
	 * @param int $status
	 */
	public function setStatusCode($status) {
		$status = (int)$status;

		if ($status < 100 || $status > 599) {
			$status = 500;
		}

		$this->status_code = $status;

		if (!headers_sent()) {
			http_response_code($status);
		}
	}
	
	/**
	 * 
	 *
	 * @param	string	$url
	 * @param	int		$status
	 *
 	*/
	public function redirect($url, $status = 302) {
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
		exit();
	}
	
	/**
	 * 
	 *
	 * @param	int		$level
 	*/
	public function setCompression($level) {
		$this->level = $level;
	}
	
	/**
	 * 
	 *
	 * @return	array
 	*/
	public function getOutput() {
		return $this->output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$output
 	*/	
	public function setOutput($output) {
		$this->file = null;
		$this->delete_file = false;
		$this->output = $output;
	}

	/** Stream a prepared file without loading it into PHP memory. */
	public function setFile($file, $delete_after = false) {
		$this->file = is_string($file) ? $file : null;
		$this->delete_file = (bool)$delete_after;
		$this->output = null;
	}
	
	/**
	 * 
	 *
	 * @param	string	$data
	 * @param	int		$level
	 * 
	 * @return	string
 	*/
	private function compress($data, $level = 0) {
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding) || ($level < -1 || $level > 9)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Vary: Accept-Encoding');
		$this->headers = array_values(array_filter($this->headers, function($header) { return stripos((string)$header, 'Content-Length:') !== 0; }));
		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
	}
	
	/**
	 * 
 	*/
	public function output() {
		$has_output = ($this->output !== null && $this->output !== '');
		$output = $has_output && $this->level ? $this->compress($this->output, $this->level) : $this->output;

		if (!headers_sent()) {
			if ($this->status_code) {
				http_response_code($this->status_code);
			}

			foreach ($this->headers as $header) {
				header($header, true);
			}
		}

		if ($this->file !== null && is_file($this->file)) {
			$handle = @fopen($this->file, 'rb');
			if ($handle) {
				while (!feof($handle)) {
					$chunk = fread($handle, 1048576);
					if ($chunk === false) { break; }
					echo $chunk;
					if (function_exists('flush')) { @flush(); }
				}
				fclose($handle);
			}
			if ($this->delete_file) { @unlink($this->file); }
			return;
		}

		if ($has_output) {
			echo $output;
		}
	}
}
