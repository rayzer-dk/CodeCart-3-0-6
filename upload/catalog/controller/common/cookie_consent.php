<?php
class ControllerCommonCookieConsent extends Controller {
    public function index() {
        if (!(int)$this->config->get('config_cookie_consent_status')) { return ''; }
        $this->load->language('common/cookie_consent');
        $days=(int)$this->config->get('config_cookie_consent_days'); if($days<30||$days>365)$days=180;
        $policy=''; $privacy='';
        $informationId=(int)$this->config->get('config_cookie_consent_information_id');
        $privacyId=(int)$this->config->get('config_cookie_consent_privacy_information_id');
        if($informationId>0)$policy=$this->url->link('information/information','information_id='.$informationId);
        if($privacyId>0)$privacy=$this->url->link('information/information','information_id='.$privacyId);
        $accent=(string)$this->config->get('config_cookie_consent_accent_color'); if(!preg_match('/^#[0-9a-fA-F]{6}$/',$accent))$accent='#0b6fd3';
        $icon=(string)$this->config->get('config_cookie_consent_icon');
        $customIcon=trim((string)$this->config->get('config_cookie_consent_custom_icon'));
        $presetIcons=array(
            'cookie_orbit' => 'catalog/view/javascript/codecart/consent/icons/cookie-orbit.webp',
            'cookie_document' => 'catalog/view/javascript/codecart/consent/icons/cookie-document.webp',
            'shield_lock_check' => 'catalog/view/javascript/codecart/consent/icons/shield-lock-check.webp',
            'lock_circle_check' => 'catalog/view/javascript/codecart/consent/icons/lock-circle-check.webp',
            'shield_cookie_check' => 'catalog/view/javascript/codecart/consent/icons/shield-cookie-check.webp',
            'hand_shield_check' => 'catalog/view/javascript/codecart/consent/icons/hand-shield-check.webp',
            'shield_lock' => 'catalog/view/javascript/codecart/consent/icons/shield-lock.webp'
        );
        if ($icon !== 'custom' && !isset($presetIcons[$icon])) { $icon = 'shield_cookie_check'; }
        $data['icon_url']='';
        if ($icon === 'custom' && $customIcon !== '' && is_file(DIR_IMAGE . $customIcon)) {
            $data['icon_url'] = 'image/' . ltrim(str_replace('\\', '/', $customIcon), '/');
        } elseif (isset($presetIcons[$icon])) {
            $data['icon_url'] = $presetIcons[$icon];
        } else {
            $data['icon_url'] = $presetIcons['shield_cookie_check'];
        }
        $payload=array('days'=>$days,'policy'=>$policy,'privacy'=>$privacy,'title'=>$this->language->get('text_cookie_title'),'message'=>$this->language->get('text_cookie_message'),'necessary'=>$this->language->get('text_cookie_necessary'),'necessary_help'=>$this->language->get('text_cookie_necessary_help'),'analytics'=>$this->language->get('text_cookie_analytics'),'analytics_help'=>$this->language->get('text_cookie_analytics_help'),'marketing'=>$this->language->get('text_cookie_marketing'),'marketing_help'=>$this->language->get('text_cookie_marketing_help'),'accept'=>$this->language->get('button_cookie_accept'),'reject'=>$this->language->get('button_cookie_reject'),'settings'=>$this->language->get('button_cookie_settings'),'save'=>$this->language->get('button_cookie_save'),'reopen'=>$this->language->get('button_cookie_reopen'),'policy_text'=>$this->language->get('text_cookie_policy'),'privacy_text'=>$this->language->get('text_cookie_privacy_policy'),'accent'=>$accent);
        $data['config_json']=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        return $this->load->view('common/cookie_consent',$data);
    }
}
