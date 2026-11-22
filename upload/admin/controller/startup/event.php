<?php
class ControllerStartupEvent extends Controller {
	public function index() {
		// Add events from the DB
		$this->load->model('setting/event');
		
		$results = $this->model_setting_event->getEvents();
		
		foreach ($results as $result) {
			if (!empty($result['status']) && strpos((string)$result['trigger'], 'admin/') === 0 && strlen((string)$result['trigger']) > 6) {
				$this->event->register(substr($result['trigger'], 6), new Action($result['action']), $result['sort_order'], $result['event_id']);
			}
		}		
	}
}