<?php
namespace Mail;
class Smtp extends \stdClass {
	public $smtp_hostname;
	public $smtp_username;
	public $smtp_password;
	public $smtp_port = 25;
	public $smtp_timeout = 5;
	public $max_attempts = 3;
	public $verp = false;

	public function send() {
		if (is_array($this->to)) {
			$to = implode(',', $this->to);
		} else {
			$to = $this->to;
		}

		$boundary = '----=_NextPart_' . bin2hex(random_bytes(16));

		$header = 'MIME-Version: 1.0' . PHP_EOL;
		$header .= 'To: <' . $to . '>' . PHP_EOL;
		$header .= 'Subject: =?UTF-8?B?' . base64_encode($this->subject) . '?=' . PHP_EOL;
		$header .= 'Date: ' . date('D, d M Y H:i:s O') . PHP_EOL;
		$header .= 'From: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->from . '>' . PHP_EOL;

		if (!$this->reply_to) {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->from . '>' . PHP_EOL;
		} else {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->reply_to . '>' . PHP_EOL;
		}

		$domain = strpos((string)$this->from, '@') !== false ? substr((string)$this->from, strrpos((string)$this->from, '@')) : '@localhost';
		$header .= 'Message-ID: <' . bin2hex(random_bytes(16)) . $domain . '>' . PHP_EOL;
		$header .= 'Return-Path: ' . $this->from . PHP_EOL;
		$header .= 'X-Mailer: PHP/' . phpversion() . PHP_EOL;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . PHP_EOL . PHP_EOL;

		if (!$this->html) {
			$message = '--' . $boundary . PHP_EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . PHP_EOL;
			$message .= 'Content-Transfer-Encoding: base64' . PHP_EOL . PHP_EOL;
			$message .= chunk_split(base64_encode($this->text)) . PHP_EOL;
		} else {
			$message = '--' . $boundary . PHP_EOL;
			$message .= 'Content-Type: multipart/related; boundary="' . $boundary . '_rel"' . PHP_EOL . PHP_EOL;
			$message .= '--' . $boundary . '_rel' . PHP_EOL;
			$message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . PHP_EOL . PHP_EOL;
			$message .= '--' . $boundary . '_alt' . PHP_EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . PHP_EOL;
			$message .= 'Content-Transfer-Encoding: base64' . PHP_EOL . PHP_EOL;

			if ($this->text) {
				$message .= chunk_split(base64_encode($this->text)) . PHP_EOL;
			} else {
				$message .= chunk_split(base64_encode('This is a HTML email and your email client software does not support HTML email!')) . PHP_EOL;
			}

			$message .= '--' . $boundary . '_alt' . PHP_EOL;
			$message .= 'Content-Type: text/html; charset="utf-8"' . PHP_EOL;
			$message .= 'Content-Transfer-Encoding: base64' . PHP_EOL . PHP_EOL;
			$message .= chunk_split(base64_encode($this->html)) . PHP_EOL;
			$message .= '--' . $boundary . '_alt--' . PHP_EOL;
		}

		if (!empty($this->inline_images) && is_array($this->inline_images)) {
			foreach ($this->inline_images as $inline) {
				if (!is_array($inline)) { continue; }
				$filename = isset($inline['filename']) ? (string)$inline['filename'] : '';
				$cid = isset($inline['cid']) ? preg_replace('/[^A-Za-z0-9._-]/', '', (string)$inline['cid']) : '';
				$mime = isset($inline['mime']) ? (string)$inline['mime'] : 'image/png';
				if ($filename === '' || $cid === '' || !is_file($filename) || !is_readable($filename)) { continue; }
				if (strpos($mime, 'image/') !== 0) { $mime = 'image/png'; }
				$content = file_get_contents($filename);
				if ($content === false) { continue; }
				$message .= '--' . $boundary . '_rel' . PHP_EOL;
				$message .= 'Content-Type: ' . $mime . '; name="' . $cid . '"' . PHP_EOL;
				$message .= 'Content-Transfer-Encoding: base64' . PHP_EOL;
				$message .= 'Content-Disposition: inline; filename="' . $cid . '"' . PHP_EOL;
				$message .= 'Content-ID: <' . $cid . '>' . PHP_EOL;
				$message .= 'X-Attachment-Id: ' . $cid . PHP_EOL . PHP_EOL;
				$message .= chunk_split(base64_encode($content));
			}
		}

		if ($this->html) {
			$message .= '--' . $boundary . '_rel--' . PHP_EOL;
		}

		foreach ($this->attachments as $attachment) {
			if (is_file($attachment) && is_readable($attachment)) {
				$handle = fopen($attachment, 'r');

				$content = fread($handle, filesize($attachment));

				fclose($handle);

				$attachment_name = str_replace(array("\r", "\n", '"'), '', basename($attachment));
				$message .= '--' . $boundary . PHP_EOL;
				$message .= 'Content-Type: application/octet-stream; name="' . $attachment_name . '"' . PHP_EOL;
				$message .= 'Content-Transfer-Encoding: base64' . PHP_EOL;
				$message .= 'Content-Disposition: attachment; filename="' . $attachment_name . '"' . PHP_EOL;
				$message .= 'Content-ID: <' . rawurlencode($attachment_name) . '>' . PHP_EOL;
				$message .= 'X-Attachment-Id: ' . rawurlencode($attachment_name) . PHP_EOL . PHP_EOL;
				$message .= chunk_split(base64_encode($content));
			}
		}

		$message .= '--' . $boundary . '--' . PHP_EOL;

		$ehlo = !empty($_SERVER['SERVER_NAME']) ? (string)$_SERVER['SERVER_NAME'] : gethostname();
		$ehlo = preg_replace('/[^A-Za-z0-9.-]/', '', (string)$ehlo);
		if ($ehlo === '') { $ehlo = 'localhost'; }

		$smtp_hostname = trim((string)$this->smtp_hostname);
		$starttls = false;
		$implicit_tls = false;

		if (stripos($smtp_hostname, 'tls://') === 0) {
			$hostname = substr($smtp_hostname, 6);
			$starttls = true;
		} elseif (stripos($smtp_hostname, 'ssl://') === 0) {
			// OpenCart historically uses ssl:// to mean implicit SMTP TLS.
			// The connection is still restricted to TLS 1.2/1.3 below.
			$hostname = substr($smtp_hostname, 6);
			$implicit_tls = true;
		} else {
			$hostname = $smtp_hostname;
		}

		$hostname = trim((string)$hostname);

		if ($hostname === '') {
			throw new \Exception('Error: SMTP hostname is empty!');
		}

		$context = stream_context_create(array(
			'ssl' => \CodeCart\Core\TlsPolicy::streamContextOptions($hostname)
		));

		$remote = ($implicit_tls ? 'tls://' : 'tcp://') . $hostname . ':' . (int)$this->smtp_port;
		$handle = @stream_socket_client($remote, $errno, $errstr, $this->smtp_timeout, STREAM_CLIENT_CONNECT, $context);

		if (!$handle) {
			throw new \Exception('Error: ' . $errstr . ' (' . $errno . ')');
		} else {
			if (substr(PHP_OS, 0, 3) != 'WIN') {
				stream_set_timeout($handle, $this->smtp_timeout, 0);
			}

			while ($line = fgets($handle, 515)) {
				if (substr($line, 3, 1) == ' ') {
					break;
				}
			}

			fputs($handle, 'EHLO ' . $ehlo . "\r\n");

			$reply = '';

			while ($line = fgets($handle, 515)) {
				$reply .= $line;

				//some SMTP servers respond with 220 code before responding with 250. hence, we need to ignore 220 response string
				if (substr($reply, 0, 3) == 220 && substr($line, 3, 1) == ' ') {
					$reply = '';

					continue;
				} else if (substr($line, 3, 1) == ' ') {
					break;
				}
			}

			if (substr($reply, 0, 3) != 250) {
				throw new \Exception('Error: EHLO not accepted from server!');
			}

			if ($starttls) {
				fputs($handle, 'STARTTLS' . "\r\n");

				$this->handleReply($handle, 220, 'Error: STARTTLS not accepted from server!');

				if (stream_socket_enable_crypto($handle, true, \CodeCart\Core\TlsPolicy::streamCryptoMethod()) !== true) {
					throw new \Exception('Error: TLS could not be established!');
				}

				fputs($handle, 'EHLO ' . $ehlo . "\r\n");

				$this->handleReply($handle, 250, 'Error: EHLO not accepted from server!');
			}

			if (!empty($this->smtp_username) && !empty($this->smtp_password)) {
				fputs($handle, 'AUTH LOGIN' . "\r\n");

				$this->handleReply($handle, 334, 'Error: AUTH LOGIN not accepted from server!');

				fputs($handle, base64_encode($this->smtp_username) . "\r\n");

				$this->handleReply($handle, 334, 'Error: Username not accepted from server!');

				fputs($handle, base64_encode($this->smtp_password) . "\r\n");

				$this->handleReply($handle, 235, 'Error: Password not accepted from server!');

			} else {
				fputs($handle, 'HELO ' . $ehlo . "\r\n");

				$this->handleReply($handle, 250, 'Error: HELO not accepted from server!');
			}

			if ($this->verp) {
				fputs($handle, 'MAIL FROM: <' . $this->from . '>XVERP' . "\r\n");
			} else {
				fputs($handle, 'MAIL FROM: <' . $this->from . '>' . "\r\n");
			}

			$this->handleReply($handle, 250, 'Error: MAIL FROM not accepted from server!');

			if (!is_array($this->to)) {
				fputs($handle, 'RCPT TO: <' . $this->to . '>' . "\r\n");

				$reply = $this->handleReply($handle, false, 'RCPT TO [!array]');

				if ((substr($reply, 0, 3) != 250) && (substr($reply, 0, 3) != 251)) {
					throw new \Exception('Error: RCPT TO not accepted from server!');
				}
			} else {
				foreach ($this->to as $recipient) {
					fputs($handle, 'RCPT TO: <' . $recipient . '>' . "\r\n");

					$reply = $this->handleReply($handle, false, 'RCPT TO [array]');

					if ((substr($reply, 0, 3) != 250) && (substr($reply, 0, 3) != 251)) {
						throw new \Exception('Error: RCPT TO not accepted from server!');
					}
				}
			}

			fputs($handle, 'DATA' . "\r\n");

			$this->handleReply($handle, 354, 'Error: DATA not accepted from server!');

			// According to rfc 821 we should not send more than 1000 including the CRLF
			$message = str_replace("\r\n", "\n", $header . $message);
			$message = str_replace("\r", "\n", $message);

			$lines = explode("\n", $message);

			foreach ($lines as $line) {
				// $results = str_split($line, $length);
				// see https://php.watch/versions/8.2/str_split-empty-string-empty-array
				$results = ($line === '') ? [''] : str_split($line, 998);

				foreach ($results as $result) {
					// SMTP DATA dot-stuffing (RFC 5321): a line beginning with a dot
					// must be escaped or it can prematurely terminate the message.
					if (isset($result[0]) && $result[0] === '.') { $result = '.' . $result; }
					fputs($handle, $result . "\r\n");
				}
			}

			fputs($handle, '.' . "\r\n");

			$this->handleReply($handle, 250, 'Error: DATA not accepted from server!');

			fputs($handle, 'QUIT' . "\r\n");

			$this->handleReply($handle, 221, 'Error: QUIT not accepted from server!');

			fclose($handle);
			return true;
		}
	}

	private function handleReply($handle, $status_code = false, $error_text = false, $counter = 0) {
		$reply = '';

		while (($line = fgets($handle, 515)) !== false) {
			$reply .= $line;

			if (substr($line, 3, 1) == ' ') {
				break;
			}
		}

		// Handle slowish server responses (generally due to policy servers)
		if (!$line && empty($reply) && $counter < $this->max_attempts) {
			sleep(1);

			$counter++;

			return $this->handleReply($handle, $status_code, $error_text, $counter);
		}

		if ($status_code) {
			if (substr($reply, 0, 3) != $status_code) {
				throw new \Exception($error_text);
			}
		}

		return $reply;
	}
}
