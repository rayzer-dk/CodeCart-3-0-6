<?php
class ControllerCommonCodecartForm extends Controller {
    private static $assetsAdded = false;
    private static $instanceCounter = 0;

    public function index($settings = array()) {
        if (!is_array($settings)) { $settings = array(); }
        $form_id = isset($settings['form_id']) ? (int)$settings['form_id'] : 0;
        if ($form_id < 1) { return ''; }
        $this->load->language('common/codecart_form');
        $this->load->model('design/form');
        $form = $this->model_design_form->getForm($form_id);
        if (!$form) { return ''; }
        $kind = isset($form['kind']) && (string)$form['kind'] === 'info' ? 'info' : 'request';

        if (!self::$assetsAdded) {
            $this->document->addStyle('catalog/view/javascript/codecart/forms/forms.css?v=3.0.6.0-b128', 'stylesheet', 'screen', 'footer');
            $this->document->addScript('catalog/view/javascript/codecart/forms/forms.js?v=3.0.6.0', 'footer');
            self::$assetsAdded = true;
        }

        self::$instanceCounter++;
        $instance = 'ccp-form-' . $form_id . '-' . self::$instanceCounter;
        $mode = isset($settings['mode']) && (string)$settings['mode'] === 'button' ? 'button' : 'inline';
        $button_text = isset($settings['button_text']) ? trim(strip_tags((string)$settings['button_text'])) : '';
        if ($button_text === '') { $button_text = $form['title'] !== '' ? $form['title'] : $this->language->get('text_default_submit'); }

        $token = '';
        $honeypot_name = '';
        if ($kind === 'request') {
            $token = bin2hex(random_bytes(24));
            $honeypot_name = 'ccp_' . bin2hex(random_bytes(8));
            if (!isset($this->session->data['codecart_form_tokens']) || !is_array($this->session->data['codecart_form_tokens'])) { $this->session->data['codecart_form_tokens'] = array(); }
            $now = time();
            foreach ($this->session->data['codecart_form_tokens'] as $key => $row) {
                if (!is_array($row) || empty($row['created']) || (int)$row['created'] < $now - 7200) { unset($this->session->data['codecart_form_tokens'][$key]); }
            }
            while (count($this->session->data['codecart_form_tokens']) >= 30) { array_shift($this->session->data['codecart_form_tokens']); }
            $this->session->data['codecart_form_tokens'][$token] = array('form_id'=>$form_id,'created'=>$now,'honeypot'=>$honeypot_name);
        }

        $data = array();
        $data['form'] = $form;
        $data['kind'] = $kind;
        $data['form_id'] = $form_id;
        $data['fields'] = $form['fields'];
        $data['mode'] = $mode;
        $data['button_text'] = utf8_substr($button_text, 0, 100);
        $button_icon = isset($settings['button_icon']) && $settings['button_icon'] !== '' ? trim((string)$settings['button_icon']) : (isset($form['button_icon']) ? trim((string)$form['button_icon']) : 'fa fa-envelope-o');
        if ($button_icon === '') { $button_icon = 'fa fa-envelope-o'; }
        if (!preg_match('/^(?:fa(?:-[a-z]+)?\s+)?fa-[a-z0-9-]+(?:\s+fa-[a-z0-9-]+)*$/i', $button_icon) || strlen($button_icon) > 64) { $button_icon = 'fa fa-envelope-o'; }
        if (strpos($button_icon, ' ') === false) { $button_icon = 'fa ' . $button_icon; }
        $data['button_icon'] = $button_icon;
        if (preg_match('/\bfa-(?:solid|regular|brands)\b/', $button_icon)) {
            $this->document->addStyle('catalog/view/javascript/font-awesome/css/font-awesome.min.css?v=6.7.2', 'stylesheet', 'screen', 'footer');
        }
        foreach (array('button_bg'=>'#0b6fd3','button_text_color'=>'#ffffff','button_hover_bg'=>'#095eb4') as $style_key => $style_default) {
            $value = isset($settings[$style_key]) && $settings[$style_key] !== '' ? strtolower((string)$settings[$style_key]) : (isset($form[$style_key]) ? strtolower((string)$form[$style_key]) : $style_default);
            $data[$style_key] = preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $style_default;
        }
        $data['button_style'] = '--ccp-form-button-bg:' . $data['button_bg'] . ';--ccp-form-button-text:' . $data['button_text_color'] . ';--ccp-form-button-hover:' . $data['button_hover_bg'] . ';';
        $data['instance'] = $instance;
        $data['token'] = $token;
        $data['honeypot_name'] = $honeypot_name;
        $data['action'] = $this->url->link('common/codecart_form/submit', '', true);
        $data['context_type'] = isset($settings['context_type']) ? preg_replace('/[^a-z0-9_\-]/i', '', (string)$settings['context_type']) : '';
        $data['context_id'] = isset($settings['context_id']) ? (int)$settings['context_id'] : 0;
        $data['context_url'] = isset($settings['context_url']) ? utf8_substr((string)$settings['context_url'], 0, 2048) : '';
        $data['text_required'] = $this->language->get('text_required');
        $data['text_select'] = $this->language->get('text_select');
        $data['submit_text'] = $form['submit_text'] !== '' ? $form['submit_text'] : $this->language->get('text_default_submit');
        $data['text_client_error'] = $this->language->get('text_client_error');
        $data['text_close'] = $this->language->get('text_close');
        return $this->load->view('common/codecart_form', $data);
    }

    public function shortcodes($payload = array()) {
        $html = is_array($payload) && isset($payload['html']) ? (string)$payload['html'] : '';
        if ($html === '' || stripos($html, '[ccp_form') === false) { return $html; }
        $context = is_array($payload) && isset($payload['context']) && is_array($payload['context']) ? $payload['context'] : array();
        $self = $this;
        return preg_replace_callback('/\[ccp_form\s+([^\]]+)\]/i', function($match) use ($self, $context) {
            $attrs = array();
            if (preg_match_all('/([a-z_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/i', $match[1], $parts, PREG_SET_ORDER)) {
                foreach ($parts as $part) { $attrs[strtolower($part[1])] = isset($part[2]) && $part[2] !== '' ? $part[2] : (isset($part[3]) && $part[3] !== '' ? $part[3] : (isset($part[4]) ? $part[4] : '')); }
            }
            $form_id = isset($attrs['id']) ? (int)$attrs['id'] : 0;
            if ($form_id < 1) { return ''; }
            $settings = $context;
            $settings['form_id'] = $form_id;
            $settings['mode'] = isset($attrs['mode']) && strtolower((string)$attrs['mode']) === 'button' ? 'button' : 'inline';
            if (isset($attrs['text'])) { $settings['button_text'] = $attrs['text']; }
            return $self->index($settings);
        }, $html);
    }

    public function submit() {
        $this->load->language('common/codecart_form');
        $json = array();
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->response->setStatusCode(405);
            $json['error'] = $this->language->get('text_form_error');
            return $this->json($json);
        }

        $form_id = isset($this->request->post['form_id']) ? (int)$this->request->post['form_id'] : 0;
        $token = isset($this->request->post['form_token']) ? (string)$this->request->post['form_token'] : '';
        $token_row = ($token !== '' && isset($this->session->data['codecart_form_tokens'][$token]) && is_array($this->session->data['codecart_form_tokens'][$token])) ? $this->session->data['codecart_form_tokens'][$token] : array();
        $age = isset($token_row['created']) ? time() - (int)$token_row['created'] : -1;
        if (!$token_row || (int)$token_row['form_id'] !== $form_id || $age < 1 || $age > 7200) {
            $this->response->setStatusCode(403);
            $json['error'] = $this->language->get('text_security_error');
            return $this->json($json);
        }
        $honeypot_name = isset($token_row['honeypot']) && preg_match('/^ccp_[a-f0-9]{16}$/', (string)$token_row['honeypot']) ? (string)$token_row['honeypot'] : '';
        if ($honeypot_name !== '' && !empty($this->request->post[$honeypot_name])) {
            unset($this->session->data['codecart_form_tokens'][$token]);
            $json['success'] = $this->language->get('text_default_success');
            return $this->json($json);
        }

        $guard = new \CodeCart\Core\FormGuard($this->registry);
        $rate = $guard->consume('custom_form_' . $form_id, 12, 600, 2);
        if (empty($rate['allowed'])) {
            $this->response->setStatusCode(429);
            if (!empty($rate['retry_after'])) { $this->response->addHeader('Retry-After: ' . (int)$rate['retry_after']); }
            $json['error'] = $this->language->get('text_rate_error');
            return $this->json($json);
        }

        $this->load->model('design/form');
        $form = $this->model_design_form->getForm($form_id);
        if (!$form) {
            $this->response->setStatusCode(404);
            $json['error'] = $this->language->get('text_form_error');
            return $this->json($json);
        }
        if (isset($form['kind']) && (string)$form['kind'] === 'info') {
            $this->response->setStatusCode(405);
            $json['error'] = $this->language->get('text_form_error');
            return $this->json($json);
        }

        $posted = isset($this->request->post['field']) && is_array($this->request->post['field']) ? $this->request->post['field'] : array();
        $values = array();
        $errors = array();
        $reply_to = '';
        foreach ($form['fields'] as $field) {
            $key = (string)$field['key'];
            $raw = isset($posted[$key]) ? $posted[$key] : '';
            if (is_array($raw)) { $raw = ''; }
            // Request::clean() HTML-escapes POST values; the lead e-mail is plain text and
            // select options are stored unescaped, so compare/send the real characters.
            $value = trim(html_entity_decode((string)$raw, ENT_QUOTES, 'UTF-8'));
            if ($field['type'] === 'checkbox') { $value = $value !== '' ? '1' : ''; }
            if (!empty($field['required']) && $value === '') { $errors[$key] = $this->language->get('text_required'); continue; }
            if ($value === '') { $values[$key] = ''; continue; }
            if ($field['type'] === 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL) || utf8_strlen($value) > 254) { $errors[$key] = $this->language->get('text_form_error'); continue; }
                if ($reply_to === '') { $reply_to = $value; }
            } elseif ($field['type'] === 'tel') {
                if (utf8_strlen($value) < 5 || utf8_strlen($value) > 50) { $errors[$key] = $this->language->get('text_form_error'); continue; }
            } elseif ($field['type'] === 'textarea') {
                if (utf8_strlen($value) > 5000) { $errors[$key] = $this->language->get('text_form_error'); continue; }
            } elseif ($field['type'] === 'select') {
                if (!isset($field['options']) || !in_array($value, $field['options'], true)) { $errors[$key] = $this->language->get('text_form_error'); continue; }
            } elseif ($field['type'] !== 'checkbox' && utf8_strlen($value) > 255) {
                $errors[$key] = $this->language->get('text_form_error'); continue;
            }
            $values[$key] = $value;
        }
        if ($errors) { $json['error'] = $this->language->get('text_form_error'); $json['fields'] = $errors; return $this->json($json); }

        $lines = array();
        $lines[] = $form['title'] !== '' ? $form['title'] : $form['name'];
        $lines[] = str_repeat('-', 40);
        foreach ($form['fields'] as $field) {
            $value = isset($values[$field['key']]) ? $values[$field['key']] : '';
            if ($field['type'] === 'checkbox') { $value = $value !== '' ? $this->language->get('text_yes') : $this->language->get('text_no'); }
            $lines[] = $field['label'] . ': ' . $value;
        }
        $context_type = isset($this->request->post['context_type']) ? preg_replace('/[^a-z0-9_\-]/i', '', (string)$this->request->post['context_type']) : '';
        $context_id = isset($this->request->post['context_id']) ? (int)$this->request->post['context_id'] : 0;
        $context_url = isset($this->request->post['context_url']) ? trim(html_entity_decode((string)$this->request->post['context_url'], ENT_QUOTES, 'UTF-8')) : '';
        if ($context_type !== '') { $lines[] = ''; $lines[] = 'Context: ' . $context_type . ($context_id ? ' #' . $context_id : ''); }
        if ($context_url !== '' && preg_match('#^https?://#i', $context_url)) { $lines[] = 'URL: ' . utf8_substr($context_url, 0, 2048); }

        $to = !empty($form['recipient']) ? $form['recipient'] : $this->config->get('config_email');
        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo($to);
        $mail->setFrom($this->config->get('config_email'));
        if ($reply_to !== '') { $mail->setReplyTo($reply_to); }
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject('[' . html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8') . '] ' . ($form['title'] !== '' ? $form['title'] : $form['name']));
        $mail->setText(implode("\n", $lines));
        if (!\CodeCart\Core\MailDelivery::send($this->registry, $mail, 'custom_form')) {
            $this->response->setStatusCode(503);
            $json['error'] = $this->language->get('text_send_error');
            return $this->json($json);
        }

        unset($this->session->data['codecart_form_tokens'][$token]);
        $json['success'] = $form['success_text'] !== '' ? $form['success_text'] : $this->language->get('text_default_success');
        return $this->json($json);
    }

    private function json($json) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return '';
    }
}
