<?php
class ControllerEventDebug extends Controller {
	public function before(&$route, &$args) {
		if ($route == 'common/home') { // add the route you want to test
			$this->session->data['debug'][$route] = microtime(true);
		}
	}
	
	public function after($route, &$args, &$output) {
		if ($route == 'common/home') { // add the route you want to test
			if (isset($this->session->data['debug'][$route])) {
				$log_data = array(
					'route' => $route,
					'time'  => microtime(true) - $this->session->data['debug'][$route]
				);
				
				$this->log->write(sprintf('%s: %.6f s', $log_data['route'], $log_data['time']));
			}
		}
	}	
}
