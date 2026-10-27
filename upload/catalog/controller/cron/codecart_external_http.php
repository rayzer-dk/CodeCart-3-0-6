<?php
class ControllerCronCodecartExternalHttp extends Controller {
    public function index($args = array()) {
        if (strlen((string)$this->config->get('codecart_scheduler_key')) < 32 || !is_array($args) || empty($args['_codecart_external_dispatch']) || !hash_equals(
            hash_hmac('sha256', (string)($args['url'] ?? ''), (string)$this->config->get('codecart_scheduler_key')),
            (string)$args['_codecart_external_dispatch'])) {
            throw new \RuntimeException('External Cron can only be invoked by the protected scheduler.');
        }
        if (!is_array($args) || empty($args['key_enc']) || empty($args['url'])) {
            throw new \InvalidArgumentException('External Cron task configuration missing.');
        }
        return (new \CodeCart\Core\ExternalHttpTask($this->registry))->execute($args);
    }
}
