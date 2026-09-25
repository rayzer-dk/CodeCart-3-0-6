<?php
class ControllerEventActivity extends Controller {
	// model/account/customer/addCustomer/after
	public function addCustomer(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');

			$activity_data = array(
				'customer_id' => $output,
				'name'        => $args[0]['firstname'] . ' ' . $args[0]['lastname']
			);

			$this->model_account_activity->addActivity('register', $activity_data);
		}
	}
	
	// model/account/customer/editCustomer/after
	public function editCustomer(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');

			$activity_data = array(
				'customer_id' => $this->customer->getId(),
				'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
			);

			$this->model_account_activity->addActivity('edit', $activity_data);
		}
	}
	
	// model/account/customer/editPassword/after
	public function editPassword(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');
			
			if ($this->customer->isLogged()) {
				$activity_data = array(
					'customer_id' => $this->customer->getId(),
					'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
				);
	
				$this->model_account_activity->addActivity('password', $activity_data);
			} else {
				$customer_info = $this->model_account_customer->getCustomerByEmail($args[0]);
		
				if ($customer_info) {
					$activity_data = array(
						'customer_id' => $customer_info['customer_id'],
						'name'        => $customer_info['firstname'] . ' ' . $customer_info['lastname']
					);
	
					$this->model_account_activity->addActivity('reset', $activity_data);
				}
			}	
		}
	}

		
	// model/account/customer/deleteLoginAttempts/after
	public function login(&$route, &$args, &$output) {
		if (isset($this->request->get['route']) && ($this->request->get['route'] == 'account/login' || $this->request->get['route'] == 'checkout/login/save') && $this->config->get('config_customer_activity')) {
			$customer_info = $this->model_account_customer->getCustomerByEmail($args[0]);

			if ($customer_info) {
				$this->load->model('account/activity');
	
				$activity_data = array(
					'customer_id' => $customer_info['customer_id'],
					'name'        => $customer_info['firstname'] . ' ' . $customer_info['lastname']
				);
	
				$this->model_account_activity->addActivity('login', $activity_data);
			}
		}	
	}
	
	// model/account/customer/editCode/after
	public function forgotten(&$route, &$args, &$output) {
		if (isset($this->request->get['route']) && $this->request->get['route'] == 'account/forgotten' && $this->config->get('config_customer_activity')) {
			$this->load->model('account/customer');
			
			$customer_info = $this->model_account_customer->getCustomerByEmail($args[0]);

			if ($customer_info) {
				$this->load->model('account/activity');

				$activity_data = array(
					'customer_id' => $customer_info['customer_id'],
					'name'        => $customer_info['firstname'] . ' ' . $customer_info['lastname']
				);

				$this->model_account_activity->addActivity('forgotten', $activity_data);
			}
		}	
	}
	
	// model/account/customer/addTransaction/after
	public function addTransaction(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/customer');
			
			$customer_info = $this->model_account_customer->getCustomer($args[0]);

			if ($customer_info) {
				$this->load->model('account/activity');
	
				$activity_data = array(
					'customer_id' => $customer_info['customer_id'],
					'name'        => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
					'order_id'    => $args[3]
				);
	
				$this->model_account_activity->addActivity('transaction', $activity_data);
			}
		}
	}	
	
	// model/account/customer/addAffiliate/after
	public function addAffiliate(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');

			$activity_data = array(
				'customer_id' => $output,
				'name'        => $args[1]['firstname'] . ' ' . $args[1]['lastname']
			);

			$this->model_account_activity->addActivity('affiliate_add', $activity_data);
		}
	}	
	
	// model/account/customer/editAffiliate/after
	public function editAffiliate(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity') && $output) {
			$this->load->model('account/activity');

			$activity_data = array(
				'customer_id' => $this->customer->getId(),
				'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
			);

			$this->model_account_activity->addActivity('affiliate_edit', $activity_data);
		}
	}
	
	// model/account/address/addAddress/after
	public function addAddress(&$route, &$args, &$output) { 
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');

			if ($this->customer->getId()) {
				$activity_data = array(
					'customer_id' => $this->customer->getId(),
					'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
				);
			} else {
				$activity_data = array(
					'customer_id' => $args[0],
					'name'        => $args[1]['firstname'] . ' ' . $args[1]['lastname']
				);
			}

			$this->model_account_activity->addActivity('address_add', $activity_data);
		}	
	}
	
	// model/account/address/editAddress/after
	public function editAddress(&$route, &$args, &$output) { 
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');

			$activity_data = array(
				'customer_id' => $this->customer->getId(),
				'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
			);

			$this->model_account_activity->addActivity('address_edit', $activity_data);
		}	
	}
	
	// model/account/address/deleteAddress/after
	public function deleteAddress(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity')) {
			$this->load->model('account/activity');

			$activity_data = array(
				'customer_id' => $this->customer->getId(),
				'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
			);
			
			$this->model_account_activity->addActivity('address_delete', $activity_data);
		}
	}
	
	// model/account/return/addReturn/after
	public function addReturn(&$route, &$args, &$output) {
		if ($this->config->get('config_customer_activity') && $output) {
			$this->load->model('account/activity');

			if ($this->customer->isLogged()) {
				$activity_data = array(
					'customer_id' => $this->customer->getId(),
					'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
					'return_id'   => $output
				);

				$this->model_account_activity->addActivity('return_account', $activity_data);
			} else {
				$activity_data = array(
					'name'      => $args[0]['firstname'] . ' ' . $args[0]['lastname'],
					'return_id' => $output
				);

				$this->model_account_activity->addActivity('return_guest', $activity_data);
			}
		}
	}	
	
	// model/checkout/order/addOrderHistory/after
	public function addOrderHistory(&$route, &$args, &$output = null) {
		if (!$this->config->get('config_customer_activity')) {
			return;
		}

		$transition = $this->resolveOrderTransition($args, $output);
		if (!$transition || $transition['old_order_status_id'] || !$transition['new_order_status_id']) {
			return;
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($transition['order_id']);
		if (!$order_info) {
			return;
		}

		$this->load->model('account/activity');
		$activity_data = array(
			'name'     => $order_info['firstname'] . ' ' . $order_info['lastname'],
			'order_id' => $transition['order_id']
		);

		if ($order_info['customer_id']) {
			$activity_data['customer_id'] = $order_info['customer_id'];
			$this->model_account_activity->addActivity('order_account', $activity_data);
		} else {
			$this->model_account_activity->addActivity('order_guest', $activity_data);
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
