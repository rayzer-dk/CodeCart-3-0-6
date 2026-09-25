<?php
class ControllerExtensionAnalyticsGoogle extends Controller {
    public function index() {
        if (!$this->config->get('analytics_google_status')) { return ''; }
        $consent = new \CodeCart\Core\CookieConsent($this->registry);
        if ($consent->enabled() && !$consent->allowed('analytics')) { return ''; }
		return html_entity_decode($this->config->get('analytics_google_code'), ENT_QUOTES, 'UTF-8');
	}
}
