<?php
namespace Mail;
class Mail extends \stdClass {
	public function send() {
		if (is_array($this->to)) {
			$to = implode(',', $this->to);
		} else {
			$to = $this->to;
		}

		if (version_compare(phpversion(), '8.0', '>=') || substr(PHP_OS, 0, 3) == 'WIN') {
			$eol = "\r\n";
		} else {
			$eol = PHP_EOL;
		}

		$boundary = '----=_NextPart_' . bin2hex(random_bytes(16));

		$header  = 'MIME-Version: 1.0' . $eol;
		$header .= 'Date: ' . date('D, d M Y H:i:s O') . $eol;
		$header .= 'From: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->from . '>' . $eol;
		
		if (!$this->reply_to) {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->from . '>' . $eol;
		} else {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->reply_to) . '?= <' . $this->reply_to . '>' . $eol;
		}
		
		$header .= 'Return-Path: ' . $this->from . $eol;
		$header .= 'X-Mailer: PHP/' . phpversion() . $eol;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $eol . $eol;

		if (!$this->html) {
			$message  = '--' . $boundary . $eol;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
			$message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
			$message .= chunk_split(base64_encode($this->text)) . $eol;
		} else {
			$message  = '--' . $boundary . $eol;
			$message .= 'Content-Type: multipart/related; boundary="' . $boundary . '_rel"' . $eol . $eol;
			$message .= '--' . $boundary . '_rel' . $eol;
			$message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . $eol . $eol;
			$message .= '--' . $boundary . '_alt' . $eol;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
			$message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;

			if ($this->text) {
				$message .= chunk_split(base64_encode($this->text)) . $eol;
			} else {
				$message .= chunk_split(base64_encode('This is a HTML email and your email client software does not support HTML email!')) . $eol;
			}

			$message .= '--' . $boundary . '_alt' . $eol;
			$message .= 'Content-Type: text/html; charset="utf-8"' . $eol;
			$message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
			$message .= chunk_split(base64_encode($this->html)) . $eol;
			$message .= '--' . $boundary . '_alt--' . $eol;
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
				$message .= '--' . $boundary . '_rel' . $eol;
				$message .= 'Content-Type: ' . $mime . '; name="' . $cid . '"' . $eol;
				$message .= 'Content-Transfer-Encoding: base64' . $eol;
				$message .= 'Content-Disposition: inline; filename="' . $cid . '"' . $eol;
				$message .= 'Content-ID: <' . $cid . '>' . $eol;
				$message .= 'X-Attachment-Id: ' . $cid . $eol . $eol;
				$message .= chunk_split(base64_encode($content));
			}
		}

		if ($this->html) {
			$message .= '--' . $boundary . '_rel--' . $eol;
		}

		foreach ($this->attachments as $attachment) {
			if (is_file($attachment) && is_readable($attachment)) {
				$handle = fopen($attachment, 'r');

				$content = fread($handle, filesize($attachment));

				fclose($handle);

				$attachment_name = str_replace(array("\r", "\n", '"'), '', basename($attachment));
				$message .= '--' . $boundary . $eol;
				$message .= 'Content-Type: application/octet-stream; name="' . $attachment_name . '"' . $eol;
				$message .= 'Content-Transfer-Encoding: base64' . $eol;
				$message .= 'Content-Disposition: attachment; filename="' . $attachment_name . '"' . $eol;
				$message .= 'Content-ID: <' . rawurlencode($attachment_name) . '>' . $eol;
				$message .= 'X-Attachment-Id: ' . rawurlencode($attachment_name) . $eol . $eol;
				$message .= chunk_split(base64_encode($content));
			}
		}

		$message .= '--' . $boundary . '--' . $eol;

		ini_set('sendmail_from', $this->from);

		$extraParam = !empty($this->parameter) ? (string)$this->parameter : '';

		if ($extraParam !== '') {
			return mail($to, '=?UTF-8?B?' . base64_encode($this->subject) . '?=', $message, $header, $extraParam);
		}

		return mail($to, '=?UTF-8?B?' . base64_encode($this->subject) . '?=', $message, $header);
	}
}
