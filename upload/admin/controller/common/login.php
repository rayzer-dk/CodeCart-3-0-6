<?php
class ControllerCommonLogin extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('common/login');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->session->data['user_token'] = token(32);

			if (isset($this->request->post['redirect']) && codecart_is_safe_redirect($this->request->post['redirect'], array(HTTP_SERVER, HTTPS_SERVER))) {
				$separator = strpos($this->request->post['redirect'], '?') === false ? '?' : '&';
				$this->response->redirect($this->request->post['redirect'] . $separator . 'user_token=' . $this->session->data['user_token']);
			} else {
				$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
			}
		}

		if ((isset($this->session->data['user_token']) && !isset($this->request->get['user_token'])) || ((isset($this->request->get['user_token']) && (isset($this->session->data['user_token']) && ($this->request->get['user_token'] != $this->session->data['user_token']))))) {
			$this->error['warning'] = $this->language->get('error_token');
		}

		if (isset($this->error['error_attempts'])) {
			$data['error_warning'] = $this->error['error_attempts'];
		} elseif (isset($this->error['warning'])) {
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

		$data['action'] = $this->url->link('common/login', '', true);

		if (isset($this->request->post['username'])) {
			$data['username'] = $this->request->post['username'];
		} else {
			$data['username'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->get['route'])) {
			$route = $this->request->get['route'];

			unset($this->request->get['route']);
			unset($this->request->get['user_token']);

			$url = '';

			if ($this->request->get) {
				$url .= http_build_query($this->request->get);
			}

			$data['redirect'] = $this->url->link($route, $url, true);
		} else {
			$data['redirect'] = '';
		}

		if ($this->config->get('config_password')) {
			$data['forgotten'] = $this->url->link('common/forgotten', '', true);
		} else {
			$data['forgotten'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/login', $data));
	}



	protected function validate() {
		$username = isset($this->request->post['username']) ? trim((string)$this->request->post['username']) : '';
		$password = isset($this->request->post['password']) ? (string)$this->request->post['password'] : '';
		$protection = new \CodeCart\Core\LoginProtection($this->registry);
		$audit = new \CodeCart\Core\SecurityAudit($this->registry);

		if ($username === '' || $password === '') {
			$this->error['warning'] = $this->language->get('error_login');
		} elseif ($protection->isBlocked($username)) {
			$this->error['error_attempts'] = $this->language->get('error_attempts');
			$audit->add('admin.login.blocked', 'blocked', 'warning', 'admin', 0, $username, array(
				'attempts' => $protection->attemptsForCurrentSource($username),
				'window_minutes' => $protection->windowMinutes()
			));
		}

		if (!$this->error) {
			if (!$this->user->login($username, $password)) {
				$this->error['warning'] = $this->language->get('error_login');
				$protection->recordFailure($username);
				$audit->add('admin.login.failed', 'failed', 'warning', 'admin', 0, $username, array(
					'attempts' => $protection->attemptsForCurrentSource($username)
				));
				unset($this->session->data['user_token']);
				unset($this->session->data['codecart_totp_verified_user_id']);
			} else {
				$protection->recordSuccess($username);
				unset($this->session->data['codecart_totp_verified_user_id']);
				$audit->add('admin.login.success', 'success', 'info', 'admin', (int)$this->user->getId(), $username);
			}
		}

		return !$this->error;
	}
}
