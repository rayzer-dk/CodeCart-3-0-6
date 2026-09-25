<?php
class ControllerExtensionModuleCodecartForm extends Controller {
    public function index($setting) {
        if (!is_array($setting) || empty($setting['status'])) { return ''; }
        $form_id = isset($setting['form_id']) ? (int)$setting['form_id'] : 0;
        if ($form_id < 1) { return ''; }
        $language_id = (int)$this->config->get('config_language_id');
        $button_text = '';
        if (isset($setting['button_text']) && is_array($setting['button_text']) && isset($setting['button_text'][$language_id])) {
            $button_text = (string)$setting['button_text'][$language_id];
        }
        return $this->load->controller('common/codecart_form', array(
            'form_id' => $form_id,
            'mode' => isset($setting['mode']) && (string)$setting['mode'] === 'button' ? 'button' : 'inline',
            'button_text' => $button_text,
            'context_type' => 'layout_module'
        ));
    }
}
