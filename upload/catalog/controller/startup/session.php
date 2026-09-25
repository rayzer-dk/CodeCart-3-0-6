<?php
class ControllerStartupSession extends Controller {
	public function index() {
		if (isset($this->request->get['route']) && $this->request->get['route'] === 'cron/codecart') {
			return;
		}

		$route = isset($this->request->get['route']) ? (string)$this->request->get['route'] : '';
		$route_normalized = strtolower($route);

		if (isset($this->request->get['api_token']) && $route !== '' && substr($route_normalized, 0, 4) === 'api/') {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "api_session` WHERE date_modified < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
					
			// Make sure the IP is allowed
			$api_query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "api` `a` LEFT JOIN `" . DB_PREFIX . "api_session` `as` ON (a.api_id = as.api_id) LEFT JOIN " . DB_PREFIX . "api_ip `ai` ON (a.api_id = ai.api_id) WHERE a.status = '1' AND `as`.`session_id` = '" . $this->db->escape($this->request->get['api_token']) . "' AND ai.ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "'");
		 
			if ($api_query->num_rows) {
				$this->session->start($this->request->get['api_token']);
				
				// keep the session alive
				$this->db->query("UPDATE `" . DB_PREFIX . "api_session` SET `date_modified` = NOW() WHERE `api_session_id` = '" . (int)$api_query->row['api_session_id'] . "'");
			}
		} else {
			if (isset($_COOKIE[$this->config->get('session_name')])) {
				$session_id = $_COOKIE[$this->config->get('session_name')];
			} else {
				$session_id = '';
			}
			
			$this->session->start($session_id);
			
			$cookie_lifetime = $this->config->get('session_cookie_lifetime');
			codecart_set_session_cookie($this->config->get('session_name'), $this->session->getId(), $cookie_lifetime !== null ? (int)$cookie_lifetime : null);
		}
	}
}
