<?php
class ControllerMarketplaceEvent extends Controller {
	private $error = array();
	
	public function index() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

        // Static handler checks run ONLY when the administrator explicitly requests them.
        $report = null;
        if (isset($this->request->get['diagnose']) && $this->request->get['diagnose'] === '1'
            && $this->user->hasPermission('access', 'marketplace/event')) {
            $this->load->model('setting/event_diagnostics');
            require_once DIR_SYSTEM . 'library/codecart/event_diagnostics.php';
            $report = $this->model_setting_event_diagnostics->inspect(
                $this->model_setting_event->getEvents(), CodeCartEventDiagnostics::read()
            );
        }
        $this->getList($report);
    }

    /** POST-only, administrator-only capture controls. */
    public function trace() {
        $this->load->language('marketplace/event');
        if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST'
            || !$this->validate()
            || !isset($this->request->get['user_token'], $this->session->data['user_token'])
            || !hash_equals((string)$this->session->data['user_token'], (string)$this->request->get['user_token'])) {
            $this->response->setStatusCode(403);
            return;
        }
        require_once DIR_SYSTEM . 'library/codecart/event_diagnostics.php';
        $command = (string)($this->request->post['command'] ?? '');
        $done = false;
        if ($command === 'start') { $done = CodeCartEventDiagnostics::start(); }
        elseif ($command === 'stop') { $done = CodeCartEventDiagnostics::stop(); }
        elseif ($command === 'clear') { $done = CodeCartEventDiagnostics::clear(); }
        $this->session->data[$done ? 'success' : 'error'] = $this->language->get($done ? 'text_trace_saved' : 'error_trace_failed');
        $this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&diagnose=1', true));
    }

    public function enable() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

		if (isset($this->request->get['event_id']) && $this->validate()) {
			$this->model_setting_event->enableEvent($this->request->get['event_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function disable() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

		if (isset($this->request->get['event_id']) && $this->validate()) {
			$this->model_setting_event->disableEvent($this->request->get['event_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}
	
	public function delete() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $event_id) {
				$this->model_setting_event->deleteEvent($event_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}	
	
	public function getList($report = null) {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'code';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['delete'] = $this->url->link('marketplace/event/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        // No new page, DB tables or polling; controls live on the existing Events page.
        $data['event_diagnostic_ran'] = is_array($report);
        $data['event_diagnostic_rows'] = array();
        $data['event_diagnostic_summary'] = is_array($report) ? $report['summary'] : array();
        if (is_array($report)) {
            foreach ($report['rows'] as $row) {
                $row['state_label'] = $this->language->get('diag_state_' . $row['state']);
                $data['event_diagnostic_rows'][] = $row;
            }
        }
        $data['diagnose_url'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&diagnose=1', true);
        $data['trace_url'] = $this->url->link('marketplace/event/trace', 'user_token=' . $this->session->data['user_token'], true);
        require_once DIR_SYSTEM . 'library/codecart/event_diagnostics.php';
        $data['event_trace_until'] = CodeCartEventDiagnostics::expiration();
        $data['event_trace_active'] = $data['event_trace_until'] > 0;
        $trace_file = CodeCartEventDiagnostics::tracePath();
        $data['event_trace_has_data'] = is_file($trace_file) && @filesize($trace_file) > 0;

		$data['events'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$event_total = $this->model_setting_event->getTotalEvents();

		$results = $this->model_setting_event->getEvents($filter_data);

		foreach ($results as $result) {
			$data['events'][] = array(
				'event_id'   => $result['event_id'],
				'code'       => $result['code'],
				'trigger'    => $result['trigger'],
				'action'     => $result['action'],
				'sort_order' => $result['sort_order'],
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'enable'     => $this->url->link('marketplace/event/enable', 'user_token=' . $this->session->data['user_token'] . '&event_id=' . $result['event_id'] . $url, true),
				'disable'    => $this->url->link('marketplace/event/disable', 'user_token=' . $this->session->data['user_token'] . '&event_id=' . $result['event_id'] . $url, true),
				'enabled'    => $result['status']
			);
		}

		if (isset($this->session->data['error'])) {
			$this->error['warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		}
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_code'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&sort=code' . $url, true);
		$data['sort_sort_order'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
		$data['sort_status'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $event_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($event_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($event_total - $this->config->get('config_limit_admin'))) ? $event_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $event_total, ceil($event_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketplace/event', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'marketplace/event')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}