<?php
class ControllerCronMailDelivery extends Controller {
    public function index($args = array()) {
        if (!is_array($args) || empty($args['message']) || !is_array($args['message'])) {
            throw new RuntimeException('Invalid queued mail payload.');
        }

        $message = $args['message'];
        $store_id = isset($args['store_id']) ? max(0, (int)$args['store_id']) : 0;
        $context = isset($args['context']) ? (string)$args['context'] : 'queued-mail';
        $settings = $this->mailSettings($store_id);
        $engine = isset($settings['config_mail_engine']) && $settings['config_mail_engine'] !== '' ? (string)$settings['config_mail_engine'] : 'mail';

        $mail = new Mail($engine);
        $mail->parameter = isset($message['parameter']) && (string)$message['parameter'] !== '' ? (string)$message['parameter'] : (string)$this->setting($settings, 'config_mail_parameter', '');
        $mail->smtp_hostname = (string)$this->setting($settings, 'config_mail_smtp_hostname', '');
        $mail->smtp_username = (string)$this->setting($settings, 'config_mail_smtp_username', '');
        $mail->smtp_password = html_entity_decode((string)$this->setting($settings, 'config_mail_smtp_password', ''), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = (int)$this->setting($settings, 'config_mail_smtp_port', 25);
        $mail->smtp_timeout = (int)$this->setting($settings, 'config_mail_smtp_timeout', 5);

        $mail->setTo(isset($message['to']) ? $message['to'] : '');
        $mail->setFrom(isset($message['from']) ? (string)$message['from'] : '');
        $mail->setSender(isset($message['sender']) ? (string)$message['sender'] : '');
        if (!empty($message['reply_to'])) { $mail->setReplyTo((string)$message['reply_to']); }
        $mail->setSubject(isset($message['subject']) ? (string)$message['subject'] : '');
        if (!empty($message['text'])) { $mail->setText((string)$message['text']); }
        if (!empty($message['html'])) { $mail->setHtml((string)$message['html']); }
        if (!empty($message['inline_images']) && is_array($message['inline_images'])) {
            foreach ($message['inline_images'] as $inline) {
                if (!is_array($inline) || empty($inline['filename']) || empty($inline['cid'])) { continue; }
                $mail->addInlineImage((string)$inline['filename'], (string)$inline['cid']);
            }
        }

        if (!\CodeCart\Core\MailDelivery::sendNow($this->registry, $mail, $context)) {
            throw new RuntimeException('Queued mail transport failed.');
        }
        return true;
    }

    private function mailSettings($store_id) {
        $keys = array('config_mail_engine','config_mail_parameter','config_mail_smtp_hostname','config_mail_smtp_username','config_mail_smtp_password','config_mail_smtp_port','config_mail_smtp_timeout');
        $settings = array();
        foreach ($keys as $key) { $settings[$key] = $this->config->get($key); }

        if ($store_id > 0) {
            $query = $this->db->query("SELECT `key`, `value`, serialized FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND code = 'config' AND `key` IN ('config_mail_engine','config_mail_parameter','config_mail_smtp_hostname','config_mail_smtp_username','config_mail_smtp_password','config_mail_smtp_port','config_mail_smtp_timeout')");
            foreach ($query->rows as $row) {
                $settings[(string)$row['key']] = (string)$row['value'];
            }
        }
        return $settings;
    }

    private function setting(array $settings, $key, $default) {
        return array_key_exists($key, $settings) && $settings[$key] !== null ? $settings[$key] : $default;
    }
}
