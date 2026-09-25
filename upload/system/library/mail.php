<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Mail class
*/
class Mail extends \stdClass {
	protected $to;
	protected $from;
	protected $sender;
	protected $reply_to;
	protected $subject;
	protected $text;
	protected $html;
	protected $attachments = array();
	public $parameter;
	protected static $sentFingerprints = array();

	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 *
 	*/
	public function __construct($adaptor = 'mail') {
		$class = 'Mail\\' . $adaptor;
		
		if (class_exists($class)) {
			$this->adaptor = new $class();
		} else {
			trigger_error('Error: Could not load mail adaptor ' . $adaptor . '!');
			exit();
		}	
	}
	
	/**
     * 
     *
     * @param	mixed	$to
     */
	public function setTo($to) {
		if (is_array($to)) {
			$unique = array();
			foreach ($to as $recipient) {
				$recipient = trim((string)$recipient);
				if ($recipient === '') { continue; }
				$key = strtolower($recipient);
				if (!isset($unique[$key])) { $unique[$key] = $recipient; }
			}
			$this->to = array_values($unique);
		} else {
			$this->to = trim((string)$to);
		}
	}
	
	/**
     * 
     *
     * @param	string	$from
     */
	public function setFrom($from) {
		$this->from = $from;
	}
	
	/**
     * 
     *
     * @param	string	$sender
     */
	public function setSender($sender) {
		$this->sender = $sender;
	}
	
	/**
     * 
     *
     * @param	string	$reply_to
     */
	public function setReplyTo($reply_to) {
		$this->reply_to = $reply_to;
	}
	
	/**
     * 
     *
     * @param	string	$subject
     */
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	/**
     * 
     *
     * @param	string	$text
     */
	public function setText($text) {
		$this->text = $text;
	}
	
	/**
     * 
     *
     * @param	string	$html
     */
	public function setHtml($html) {
		$this->html = $html;
	}
	
	/**
     * 
     *
     * @param	string	$filename
     */
	public function addAttachment($filename) {
		$this->attachments[] = $filename;
	}
	
	/**
     * 
     *
     */

	/**
	 * Export only message data that is safe to persist in the CodeCart queue.
	 * Transport credentials are deliberately excluded and resolved by the worker
	 * from the selected store configuration at delivery time.
	 */
	public function exportQueueData() {
		return array(
			'to' => $this->to,
			'from' => (string)$this->from,
			'sender' => (string)$this->sender,
			'reply_to' => (string)$this->reply_to,
			'subject' => (string)$this->subject,
			'text' => (string)$this->text,
			'html' => (string)$this->html,
			'attachments' => array_values($this->attachments),
			'parameter' => (string)$this->parameter
		);
	}

	public function send() {
		$this->assertSafeHeaders();

		if (!$this->to) {
			throw new \Exception('Error: E-Mail to required!');
		}

		if (!$this->from) {
			throw new \Exception('Error: E-Mail from required!');
		}

		if (!$this->sender) {
			throw new \Exception('Error: E-Mail sender required!');
		}

		if (!$this->subject) {
			throw new \Exception('Error: E-Mail subject required!');
		}

		if ((!$this->text) && (!$this->html)) {
			throw new \Exception('Error: E-Mail message required!');
		}
		
		$fingerprint = $this->messageFingerprint();
		if (isset(self::$sentFingerprints[$fingerprint])) {
			return true;
		}

		foreach (get_object_vars($this) as $key => $value) {
			$this->adaptor->$key = $value;
		}

		$result = $this->adaptor->send();
		if ($result !== false) {
			self::$sentFingerprints[$fingerprint] = true;
		}

		return $result;
	}

	private function messageFingerprint() {
		$to = is_array($this->to) ? $this->to : array($this->to);
		$to = array_values(array_filter(array_map('strval', $to), 'strlen'));
		sort($to, SORT_STRING);

		$attachments = array();
		foreach ($this->attachments as $attachment) {
			$attachment = (string)$attachment;
			$attachments[] = array($attachment, is_file($attachment) ? (int)filesize($attachment) : -1, is_file($attachment) ? (int)filemtime($attachment) : 0);
		}

		$payload = array(
			'adaptor' => is_object($this->adaptor) ? get_class($this->adaptor) : '',
			'to' => $to,
			'from' => (string)$this->from,
			'sender' => (string)$this->sender,
			'reply_to' => (string)$this->reply_to,
			'subject' => (string)$this->subject,
			'text' => (string)$this->text,
			'html' => (string)$this->html,
			'attachments' => $attachments,
			'parameter' => (string)$this->parameter
		);

		$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($encoded === false) {
			$encoded = serialize($payload);
		}

		return hash('sha256', $encoded);
	}

	private function assertSafeHeaders() {
		$values = array($this->from, $this->reply_to, $this->sender, $this->subject);
		$recipients = is_array($this->to) ? $this->to : array($this->to);
		$values = array_merge($values, $recipients);

		foreach ($values as $value) {
			if ($value !== null && preg_match('/[\r\n]/', (string)$value)) {
				throw new \InvalidArgumentException('Mail header values must not contain line breaks.');
			}
		}
	}
}