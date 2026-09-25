<?php
namespace CodeCart\Core;

final class SystemMailer {
    private $config;
    public function __construct($registry) { $this->config=$registry->get('config'); }
    public function sendText(string $to,string $subject,string $text): bool {
        if (!filter_var($to,FILTER_VALIDATE_EMAIL) || trim($subject)==='' || trim($text)==='') { return false; }
        try {
            $mail=new \Mail($this->config->get('config_mail_engine'));
            $mail->parameter=$this->config->get('config_mail_parameter');
            $mail->smtp_hostname=$this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username=$this->config->get('config_mail_smtp_username');
            $mail->smtp_password=html_entity_decode((string)$this->config->get('config_mail_smtp_password'),ENT_QUOTES,'UTF-8');
            $mail->smtp_port=$this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout=$this->config->get('config_mail_smtp_timeout');
            $mail->setTo($to);
            $mail->setFrom((string)$this->config->get('config_email'));
            $mail->setSender(html_entity_decode((string)$this->config->get('config_name'),ENT_QUOTES,'UTF-8'));
            $mail->setSubject($subject);
            $mail->setText($text);
            return $mail->send() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
