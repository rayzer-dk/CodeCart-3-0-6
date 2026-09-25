<?php
class ControllerStartupMaintenance extends Controller {
	public function index() {
		if ($this->config->get('config_maintenance')) {
			// Route
			if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
				$route = $this->request->get['route'];
			} else {
				$route = $this->config->get('action_default');
			}			
			
			$ignore = array(
				'common/language/language',
				'common/currency/currency',
				'cron/codecart'
			);
			
			// Route family checks are case-insensitive so API/payment callbacks cannot
			// be misclassified by mixed-case routes on case-insensitive filesystems.
			$route_normalized = strtolower((string)$route);

			// Show site if logged in as admin
			$this->user = new Cart\User($this->registry);

			if ((substr($route_normalized, 0, 17) !== 'extension/payment' && substr($route_normalized, 0, 3) !== 'api') && !in_array($route_normalized, $ignore, true) && !$this->user->isLogged()) {
				return new Action('common/maintenance');
			}
		}
	}
}
