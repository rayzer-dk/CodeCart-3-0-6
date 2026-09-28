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
	protected $inline_images = array();
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
		$this->html = $this->embedLocalImages((string)$html);
	}

	public function addInlineImage($filename, $cid = '') {
		$filename = (string)$filename;
		if ($filename === '' || !is_file($filename) || !is_readable($filename)) { return false; }
		$cid = preg_replace('/[^A-Za-z0-9._-]/', '', (string)$cid);
		if ($cid === '') {
			$mime_guess = function_exists('mime_content_type') ? (string)@mime_content_type($filename) : '';
			$ext = $mime_guess === 'image/jpeg' ? '.jpg' : ($mime_guess === 'image/gif' ? '.gif' : '.png');
			$cid = 'ccp-' . substr(sha1($filename . '|' . (int)@filemtime($filename) . '|' . (int)@filesize($filename)), 0, 24) . $ext;
		}
		foreach ($this->inline_images as $item) {
			if (isset($item['cid']) && $item['cid'] === $cid) { return $cid; }
		}
		$mime = function_exists('mime_content_type') ? (string)@mime_content_type($filename) : '';
		if (strpos($mime, 'image/') !== 0) { $mime = 'image/png'; }
		$this->inline_images[] = array('filename' => $filename, 'cid' => $cid, 'mime' => $mime);
		return $cid;
	}

	private function embedLocalImages($html) {
		if ($html === '' || !defined('DIR_IMAGE')) { return $html; }
		$self = $this;
		return preg_replace_callback("/(<img\\b[^>]*\\bsrc\\s*=\\s*)([\"'])([^\"']+)\\2/i", function ($m) use ($self) {
			$url = html_entity_decode((string)$m[3], ENT_QUOTES, 'UTF-8');
			if (stripos($url, 'cid:') === 0 || stripos($url, 'data:') === 0) { return $m[0]; }
			$url_host = strtolower((string)parse_url($url, PHP_URL_HOST));
			if ($url_host !== '') {
				$allowed_hosts = array();
				foreach (array('HTTP_SERVER', 'HTTPS_SERVER') as $constant) {
					if (defined($constant)) {
						$host = strtolower((string)parse_url((string)constant($constant), PHP_URL_HOST));
						if ($host !== '') { $allowed_hosts[$host] = true; }
					}
				}
				if ($allowed_hosts && !isset($allowed_hosts[$url_host])) { return $m[0]; }
			}
			$path = parse_url($url, PHP_URL_PATH);
			if (!is_string($path)) { return $m[0]; }
			$pos = strpos($path, '/image/');
			if ($pos === false) { return $m[0]; }
			$relative = rawurldecode(substr($path, $pos + 7));
			$relative = ltrim(str_replace('\\', '/', $relative), '/');
			if ($relative === '' || strpos($relative, "\0") !== false || preg_match('#(^|/)\\.\\.(/|$)#', $relative)) { return $m[0]; }
			$root = realpath(DIR_IMAGE);
			$source = realpath(DIR_IMAGE . $relative);
			if ($root === false || $source === false || !is_file($source)) { return $m[0]; }
			$root = rtrim(str_replace('\\', '/', $root), '/') . '/';
			if (strpos(str_replace('\\', '/', $source), $root) !== 0) { return $m[0]; }
			$inline = $self->persistentEmailImage($source);
			if ($inline === '') { return $m[0]; }
			$cid = $self->addInlineImage($inline);
			if (!$cid) { return $m[0]; }
			return $m[1] . $m[2] . 'cid:' . $cid . $m[2];
		}, $html);
	}

	private function persistentEmailImage($source) {
		if (!defined('DIR_STORAGE')) { return ''; }
		$info = @getimagesize($source);
		if (!$info || empty($info[0]) || empty($info[1])) { return ''; }
		$mime = isset($info['mime']) ? strtolower((string)$info['mime']) : '';
		$dir = rtrim(DIR_STORAGE, '/\\') . '/codecart/email-assets/';
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) { return ''; }
		$safe_native = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif');
		if (isset($safe_native[$mime])) {
			$hash = sha1($source . '|' . (int)@filemtime($source) . '|' . (int)@filesize($source) . '|native-v1');
			$target = $dir . 'img-' . substr($hash, 0, 24) . '.' . $safe_native[$mime];
			if (is_file($target) && filesize($target) > 0) { return $target; }
			$tmp = $target . '.tmp-' . bin2hex(random_bytes(4));
			if (!@copy($source, $tmp)) { return ''; }
			if (!@rename($tmp, $target)) { @unlink($tmp); if (!is_file($target)) { return ''; } }
			@chmod($target, 0644);
			return $target;
		}

		$hash = sha1($source . '|' . (int)@filemtime($source) . '|' . (int)@filesize($source) . '|png-v1');
		$target = $dir . 'img-' . substr($hash, 0, 24) . '.png';
		if (is_file($target) && filesize($target) > 0) { return $target; }
		if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) { return ''; }
		$image = false;
		if ($mime === 'image/png' && function_exists('imagecreatefrompng')) { $image = @imagecreatefrompng($source); }
		elseif ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) { $image = @imagecreatefromjpeg($source); }
		elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) { $image = @imagecreatefromgif($source); }
		elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) { $image = @imagecreatefromwebp($source); }
		elseif ($mime === 'image/avif' && function_exists('imagecreatefromavif')) { $image = @imagecreatefromavif($source); }
		if (!$image) { return ''; }
		$sw = max(1, (int)$info[0]); $sh = max(1, (int)$info[1]);
		$scale = min(1200 / $sw, 1200 / $sh, 1);
		$dw = max(1, (int)round($sw * $scale)); $dh = max(1, (int)round($sh * $scale));
		$canvas = imagecreatetruecolor($dw, $dh);
		if (!$canvas) { imagedestroy($image); return ''; }
		$white = imagecolorallocate($canvas, 255, 255, 255);
		imagefill($canvas, 0, 0, $white);
		imagecopyresampled($canvas, $image, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
		$tmp = $target . '.tmp-' . bin2hex(random_bytes(4));
		$ok = @imagepng($canvas, $tmp, 6);
		imagedestroy($canvas); imagedestroy($image);
		if (!$ok) { @unlink($tmp); return ''; }
		if (!@rename($tmp, $target)) { @unlink($tmp); if (!is_file($target)) { return ''; } }
		@chmod($target, 0644);
		return $target;
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
			'inline_images' => array_values($this->inline_images),
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

		$inline_images = array();
		foreach ($this->inline_images as $item) {
			if (!is_array($item)) { continue; }
			$filename = isset($item['filename']) ? (string)$item['filename'] : '';
			$inline_images[] = array(isset($item['cid']) ? (string)$item['cid'] : '', $filename, is_file($filename) ? (int)filesize($filename) : -1, is_file($filename) ? (int)filemtime($filename) : 0);
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
			'inline_images' => $inline_images,
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