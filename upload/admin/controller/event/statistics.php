<?php
class ControllerEventStatistics extends Controller {
	// model/catalog/review/deleteReview/after
	public function deleteReview(&$route, &$args, &$output) {
		$this->load->model('report/statistics');
		$this->model_report_statistics->addValue('review', 1);
	}

	// Backward-compatible alias for older custom event registrations.
	public function removeReview(&$route, &$args, &$output) {
		return $this->deleteReview($route, $args, $output);
	}

	// model/sale/return/deleteReturn/after
	public function deleteReturn(&$route, &$args, &$output) {
		$this->load->model('report/statistics');
		$this->model_report_statistics->addValue('return', 1);
	}

	// Backward-compatible alias for older custom event registrations.
	public function removeReturn(&$route, &$args, &$output) {
		return $this->deleteReturn($route, $args, $output);
	}
}
