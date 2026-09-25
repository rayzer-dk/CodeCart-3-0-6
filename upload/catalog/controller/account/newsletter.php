<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountNewsletter extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/newsletter', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/newsletter');
		$this->load->language('common/security');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$csrf_token = isset($this->request->post['csrf_token']) ? (string)$this->request->post['csrf_token'] : '';
			if (!codecart_csrf_validate($this->session, $csrf_token, 'account_newsletter')) {
				$this->session->data['error'] = $this->language->get('error_csrf');
				$this->response->redirect($this->url->link('account/newsletter', '', true));
			}
			$this->load->model('account/customer');

			$this->model_account_customer->editNewsletter($this->request->post['newsletter']);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_newsletter'),
			'href' => $this->url->link('account/newsletter', '', true)
		);

		$data['action'] = $this->url->link('account/newsletter', '', true);
		$data['csrf_token'] = codecart_csrf_token($this->session, 'account_newsletter');

		$data['newsletter'] = $this->customer->getNewsletter();

		$data['back'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/newsletter', $data));
	}

	public function unsubscribe() {
		$this->load->language('account/newsletter');
		$this->document->setRobots('noindex,nofollow');
		$this->document->setTitle($this->language->get('heading_title'));
		$token = isset($this->request->get['token']) ? strtolower(trim((string)$this->request->get['token'])) : '';
		$ok = false;
		if (preg_match('/^[a-f0-9]{64}$/', $token)) {
			$q = $this->db->query("SELECT mail_queue_id,customer_id,email FROM `" . DB_PREFIX . "mail_campaign_queue` WHERE unsubscribe_token='" . $this->db->escape($token) . "' LIMIT 1");
			if ($q->num_rows) {
				$email = utf8_strtolower(trim((string)$q->row['email']));
				if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "mail_suppression` SET email='" . $this->db->escape($email) . "', reason='unsubscribe', date_added=NOW() ON DUPLICATE KEY UPDATE reason='unsubscribe', date_added=VALUES(date_added)");
					if ((int)$q->row['customer_id'] > 0) { $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET newsletter='0' WHERE customer_id='" . (int)$q->row['customer_id'] . "'"); }
					$this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign_queue` SET status=IF(status='waiting','suppressed',status), date_modified=NOW() WHERE LOWER(email)='" . $this->db->escape($email) . "' AND status='waiting'");
					$ok = true;
				}
			}
		}
		$data['heading_title'] = $this->language->get('heading_title');
		$data['message'] = $ok ? $this->language->get('text_unsubscribe_success') : $this->language->get('text_unsubscribe_invalid');
		$data['continue'] = $this->url->link('common/home');
		$data['button_continue'] = $this->language->get('button_continue');
		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('account/newsletter_unsubscribe', $data));
	}

}