<?php
class ControllerEventStatistics extends Controller {
	// model/catalog/review/addReview/after
	public function addReview(&$route, &$args, &$output) {
		$this->load->model('report/statistics');

		$this->model_report_statistics->addValue('review', 1);	
	}
		
	// model/account/return/addReturn/after
	public function addReturn(&$route, &$args, &$output) {
		$this->load->model('report/statistics');

		$this->model_report_statistics->addValue('return', 1);	
	}
	
	// model/checkout/order/addOrderHistory/after
	public function addOrderHistory(&$route, &$args, &$output = null) {
		$transition = $this->resolveOrderTransition($args, $output);
		if (!$transition) {
			return;
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($transition['order_id']);
		if (!$order_info) {
			return;
		}

		$this->load->model('report/statistics');
		$processing = array_map('intval', (array)$this->config->get('config_processing_status'));
		$complete = array_map('intval', (array)$this->config->get('config_complete_status'));
		$sale = array_values(array_unique(array_merge($processing, $complete)));
		$old_status = (int)$transition['old_order_status_id'];
		$new_status = (int)$transition['new_order_status_id'];

		$old_sale = in_array($old_status, $sale, true);
		$new_sale = in_array($new_status, $sale, true);
		if (!$old_sale && $new_sale) {
			$this->model_report_statistics->addValue('order_sale', $order_info['total']);
		} elseif ($old_sale && !$new_sale) {
			$this->model_report_statistics->removeValue('order_sale', $order_info['total']);
		}

		$old_processing = in_array($old_status, $processing, true);
		$new_processing = in_array($new_status, $processing, true);
		if ($old_processing && !$new_processing) {
			$this->model_report_statistics->removeValue('order_processing', 1);
		} elseif (!$old_processing && $new_processing) {
			$this->model_report_statistics->addValue('order_processing', 1);
		}

		$old_complete = in_array($old_status, $complete, true);
		$new_complete = in_array($new_status, $complete, true);
		if ($old_complete && !$new_complete) {
			$this->model_report_statistics->removeValue('order_complete', 1);
		} elseif (!$old_complete && $new_complete) {
			$this->model_report_statistics->addValue('order_complete', 1);
		}
	}

	private function resolveOrderTransition(array $args, $output) {
		$context = $this->registry->get('codecart_order_transition');

		if (is_array($context) && isset($context['order_id'], $context['old_order_status_id'], $context['new_order_status_id']) && (!isset($args[0]) || (int)$context['order_id'] === (int)$args[0])) {
			return array(
				'order_id' => (int)$context['order_id'],
				'old_order_status_id' => (int)$context['old_order_status_id'],
				'new_order_status_id' => (int)$context['new_order_status_id']
			);
		}

		if (is_array($output) && isset($output['order_id'], $output['old_order_status_id'], $output['new_order_status_id'])) {
			return array(
				'order_id' => (int)$output['order_id'],
				'old_order_status_id' => (int)$output['old_order_status_id'],
				'new_order_status_id' => (int)$output['new_order_status_id']
			);
		}

		$order_id = isset($args[0]) ? (int)$args[0] : 0;
		if (!$order_id) {
			return false;
		}

		$history = $this->db->query("SELECT order_status_id FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . $order_id . "' ORDER BY order_history_id DESC LIMIT 2");
		if (!$history->num_rows) {
			return false;
		}

		return array(
			'order_id' => $order_id,
			'old_order_status_id' => isset($history->rows[1]) ? (int)$history->rows[1]['order_status_id'] : 0,
			'new_order_status_id' => (int)$history->rows[0]['order_status_id']
		);
	}

}