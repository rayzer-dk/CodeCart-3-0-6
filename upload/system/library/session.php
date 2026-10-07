<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Session class
*/
class Session {
	protected $adaptor;
	protected $session_id = '';
	public $data = array();

	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 * @param	object	$registry
 	*/
	public function __construct($adaptor, $registry = '') {
		$class = 'Session\\' . $adaptor;
		
		if (class_exists($class)) {
			if ($registry) {
				$this->adaptor = new $class($registry);
			} else {
				$this->adaptor = new $class();
			}	
			
			register_shutdown_function(array($this, 'close'));
		} else {
			trigger_error('Error: Could not load cache adaptor ' . $adaptor . ' session!');
			exit();
		}	
	}
	
	/**
	 * 
	 *
	 * @return	string
 	*/	
	public function getId() {
		return $this->session_id;
	}

	/**
	 *
	 *
	 * @param	string	$session_id
	 *
	 * @return	string
 	*/	
	public function start($session_id = '') {
		if (!is_string($session_id) || !preg_match('/^[a-zA-Z0-9,\-]{22,64}$/', $session_id)) {
			$session_id = $this->createId();
		}

		$this->session_id = $session_id;
		$this->data = $this->adaptor->read($session_id);

		if (!is_array($this->data)) {
			$this->data = array();
		}

		return $session_id;
	}

	public function regenerate($destroy = true) {
		$old_session_id = $this->session_id;
		$new_session_id = $this->createId();

		// Persist the authenticated/session state under the new ID before the old
		// record is removed.  Relying only on the shutdown writer after a redirect
		// can otherwise leave the next request without user_id/user_token.
		try {
			$written = $this->adaptor->write($new_session_id, $this->data);
		} catch (\Throwable $error) {
			$this->data = array();
			throw new \RuntimeException('Session initialization failed. Please try again.', 0, $error);
		}
		if ($written === false) {
			// Login callers have already added identity to data. Clear it before
			// aborting so the shutdown writer cannot authenticate the old ID.
			$this->data = array();
			throw new \RuntimeException('Session initialization failed. Please try again.');
		}

		$this->session_id = $new_session_id;

		if ($destroy && $old_session_id && $old_session_id !== $new_session_id) {
			$this->adaptor->destroy($old_session_id);
		}

		return $this->session_id;
	}

	private function createId() {
		return bin2hex(random_bytes(16));
	}
	
	/**
	 * 
 	*/
	public function close() {
		if ($this->session_id) {
			$this->adaptor->write($this->session_id, $this->data);
		}
	}
	
	/**
	 * 
 	*/	
	public function destroy() {
		$this->adaptor->destroy($this->session_id);
	}
}
