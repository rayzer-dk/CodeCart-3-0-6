<?php
class ControllerStartupEvent extends Controller {
	public function index() {
		// Add events from the DB
		$this->load->model('setting/event');
		
		$results = $this->model_setting_event->getEvents();
		
		foreach ($results as $result) {
			// Only enabled catalog-scoped events are valid for the storefront.
			if (!empty($result['status']) && strpos((string)$result['trigger'], 'catalog/') === 0 && strlen((string)$result['trigger']) > 8) {
				$this->event->register(substr($result['trigger'], 8), new Action($result['action']), $result['sort_order'], $result['event_id']);
			}
		}
	}
}