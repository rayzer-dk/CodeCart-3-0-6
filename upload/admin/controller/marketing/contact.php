<?php
class ControllerMarketingContact extends Controller {
	private $error = array();
	private $templateSettingCode = 'codecart_mail_templates';
	private $templateSettingKey = 'codecart_mail_templates';

	public function index() {
		$this->load->language('marketing/contact');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['user_token'] = $this->session->data['user_token'];
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketing/contact', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('setting/store');
		$data['stores'] = $this->model_setting_store->getStores();

		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['mail_templates'] = $this->getMailTemplates();
		$this->load->model('marketing/mail_campaign');
		$data['recent_campaigns'] = $this->model_marketing_mail_campaign->getRecentCampaigns(20);
		$data['template_load_url'] = str_replace('&amp;', '&', $this->url->link('marketing/contact/loadTemplate', 'user_token=' . $this->session->data['user_token'], true));
		$data['template_save_url'] = str_replace('&amp;', '&', $this->url->link('marketing/contact/saveTemplate', 'user_token=' . $this->session->data['user_token'], true));
		$data['template_delete_url'] = str_replace('&amp;', '&', $this->url->link('marketing/contact/deleteTemplate', 'user_token=' . $this->session->data['user_token'], true));
		$data['template_import_url'] = str_replace('&amp;', '&', $this->url->link('marketing/contact/importTemplate', 'user_token=' . $this->session->data['user_token'], true));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketing/contact', $data));
	}

	public function send() {
		$this->load->language('marketing/contact');
		$json = array();
		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->response->setStatusCode(405);
			$json['error']['warning'] = $this->language->get('error_permission');
		} elseif (!$this->user->hasPermission('modify', 'marketing/contact')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			$subject = trim(isset($this->request->post['subject']) ? (string)$this->request->post['subject'] : '');
			$message = isset($this->request->post['message']) ? (string)$this->request->post['message'] : '';
			$audience = isset($this->request->post['to']) ? (string)$this->request->post['to'] : '';
			$allowed = array('newsletter','customer_all','customer_group','customer','affiliate_all','affiliate','product');
			if ($subject === '') { $json['error']['subject'] = $this->language->get('error_subject'); }
			if ($message === '') { $json['error']['message'] = $this->language->get('error_message'); }
			if (!in_array($audience, $allowed, true)) { $json['error']['email'] = $this->language->get('error_email'); }
			if ($audience === 'customer' && empty($this->request->post['customer'])) { $json['error']['email'] = $this->language->get('error_email'); }
			if ($audience === 'affiliate' && empty($this->request->post['affiliate'])) { $json['error']['email'] = $this->language->get('error_email'); }
			if ($audience === 'product' && empty($this->request->post['product'])) { $json['error']['email'] = $this->language->get('error_email'); }
			if (!$json) {
				$this->load->model('marketing/mail_campaign');
				$result = $this->model_marketing_mail_campaign->createCampaign(array(
					'store_id' => isset($this->request->post['store_id']) ? (int)$this->request->post['store_id'] : 0,
					'audience' => $audience,
					'customer_group_id' => isset($this->request->post['customer_group_id']) ? (int)$this->request->post['customer_group_id'] : 0,
					'customer' => isset($this->request->post['customer']) ? (array)$this->request->post['customer'] : array(),
					'affiliate' => isset($this->request->post['affiliate']) ? (array)$this->request->post['affiliate'] : array(),
					'product' => isset($this->request->post['product']) ? (array)$this->request->post['product'] : array(),
					'subject' => html_entity_decode($subject, ENT_QUOTES, 'UTF-8'),
					'message' => html_entity_decode($message, ENT_QUOTES, 'UTF-8')
				));
				if ((int)$result['total'] < 1) { $json['error']['email'] = $this->language->get('error_email'); }
				else { $json['success'] = sprintf($this->language->get('text_campaign_queued'), (int)$result['campaign_id'], (int)$result['total']); $json['campaign_id']=(int)$result['campaign_id']; }
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function loadTemplate() {
		$this->load->language('marketing/contact');
		$json = array();
		$id = isset($this->request->get['template_id']) ? (string)$this->request->get['template_id'] : '';
		$templates = $this->getMailTemplates();

		if ($id !== '' && isset($templates[$id])) {
			$json['template'] = $templates[$id];
		} else {
			$json['error'] = $this->language->get('error_template_not_found');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function saveTemplate() {
		$this->load->language('marketing/contact');
		$json = array();

		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify', 'marketing/contact')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$name = trim(html_entity_decode(isset($this->request->post['template_name']) ? (string)$this->request->post['template_name'] : '', ENT_QUOTES, 'UTF-8'));
			$subject = html_entity_decode(isset($this->request->post['subject']) ? (string)$this->request->post['subject'] : '', ENT_QUOTES, 'UTF-8');
			$message = html_entity_decode(isset($this->request->post['message']) ? (string)$this->request->post['message'] : '', ENT_QUOTES, 'UTF-8');
			$id = isset($this->request->post['template_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$this->request->post['template_id']) : '';

			if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
				$json['error'] = $this->language->get('error_template_name');
			} elseif ($subject === '' || mb_strlen($subject, 'UTF-8') > 255) {
				$json['error'] = $this->language->get('error_subject');
			} elseif ($message === '' || strlen($message) > 1000000) {
				$json['error'] = $this->language->get('error_message');
			} else {
				try {
					$message = $this->sanitizeMailHtml($message);
					if (trim($message) === '') { $json['error'] = $this->language->get('error_message'); }
				} catch (Throwable $e) {
					$this->log->write('Mail template sanitization failed: ' . $e->getMessage());
					$json['error'] = $this->language->get('error_template_html');
				}
				if (!isset($json['error'])) {
					$templates = $this->getMailTemplates();
					if ($id === '' || !isset($templates[$id])) {
						$id = 'tpl_' . substr(hash('sha256', microtime(true) . '|' . $name . '|' . mt_rand()), 0, 16);
					}
					$templates[$id] = array('id' => $id, 'name' => $name, 'subject' => $subject, 'message' => $message, 'html_encoding' => 'raw');
					$this->saveMailTemplates($templates);
					$json['success'] = $this->language->get('text_template_saved');
					$json['template'] = $templates[$id];
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function deleteTemplate() {
		$this->load->language('marketing/contact');
		$json = array();

		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify', 'marketing/contact')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$id = isset($this->request->post['template_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$this->request->post['template_id']) : '';
			$templates = $this->getMailTemplates();
			if ($id !== '' && isset($templates[$id])) {
				unset($templates[$id]);
				$this->saveMailTemplates($templates);
				$json['success'] = $this->language->get('text_template_deleted');
			} else {
				$json['error'] = $this->language->get('error_template_not_found');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function importTemplate() {
		$this->load->language('marketing/contact');
		$json = array();
		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->user->hasPermission('modify', 'marketing/contact')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$file = isset($this->request->files['template_file']) ? $this->request->files['template_file'] : array();
			if (!isset($file['name'], $file['tmp_name'], $file['error'], $file['size']) || !is_string($file['name']) || !is_string($file['tmp_name']) || (int)$file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || !in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), array('html', 'htm'), true)) {
				$json['error'] = $this->language->get('error_template_file');
			} elseif ((int)$file['size'] < 1 || (int)$file['size'] > 1000000 || filesize($file['tmp_name']) > 1000000) {
				$json['error'] = $this->language->get('error_template_size');
			} else {
				$html = file_get_contents($file['tmp_name']);
				$mime = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
				if (!in_array($mime, array('text/html', 'text/plain', 'application/xhtml+xml'), true)) {
					$json['error'] = $this->language->get('error_template_file');
				} elseif ($html === false || !mb_check_encoding($html, 'UTF-8')) {
					$json['error'] = $this->language->get('error_template_encoding');
				} else {
					try {
						$message = $this->sanitizeMailHtml($html);
						if (trim($message) === '') {
							$json['error'] = $this->language->get('error_message');
						} else {
							$json['template'] = array('name' => pathinfo($file['name'], PATHINFO_FILENAME), 'message' => $message);
							$json['success'] = $this->language->get('text_template_imported');
						}
					} catch (Throwable $e) {
						$this->log->write('Mail template import failed: ' . $e->getMessage());
						$json['error'] = $this->language->get('error_template_import');
					}
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function customerRecipient($customer) {
		$name = trim((isset($customer['firstname']) ? $customer['firstname'] : '') . ' ' . (isset($customer['lastname']) ? $customer['lastname'] : ''));
		return array('email' => isset($customer['email']) ? $customer['email'] : '', 'name' => $name);
	}

	private function sanitizeMailHtml($html) {
		// Entity text cannot create elements when assigned to the editor's HTML.
		// Preserve its exact representation instead of re-encoding plain messages.
		if (strpos($html, '<') === false) { return $html; }
		require_once DIR_SYSTEM . 'helper/HTMLPurifierauto.php';
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$config->set('URI.DisableExternalResources', false);
		$purifier = new HTMLPurifier($config);
		return $purifier->purify($html);
	}

	private function getMailTemplates() {
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting($this->templateSettingCode, 0);
		$templates = isset($settings[$this->templateSettingKey]) && is_array($settings[$this->templateSettingKey]) ? $settings[$this->templateSettingKey] : array();
		$clean = array();
		foreach ($templates as $id => $template) {
			if (!is_array($template)) {
				continue;
			}
			$id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$id);
			if ($id === '') {
				continue;
			}
			// Older saves stored Request::clean() output instead of HTML. Decode
			// once only for those records; valid raw entities must remain intact.
			if (!isset($template['html_encoding'])) {
				foreach (array('name', 'subject') as $field) {
					if (isset($template[$field])) { $template[$field] = html_entity_decode((string)$template[$field], ENT_QUOTES, 'UTF-8'); }
				}
				if (isset($template['message']) && !preg_match('/<[a-z][^>]*>/i', (string)$template['message'])) {
					$template['message'] = html_entity_decode((string)$template['message'], ENT_QUOTES, 'UTF-8');
				}
			}
			try {
				$template['message'] = $this->sanitizeMailHtml(isset($template['message']) ? (string)$template['message'] : '');
			} catch (Throwable $e) {
				$this->log->write('Mail template sanitization failed: ' . $e->getMessage());
				continue;
			}
			$clean[$id] = array(
				'id' => $id,
				'name' => isset($template['name']) ? (string)$template['name'] : $id,
				'subject' => isset($template['subject']) ? (string)$template['subject'] : '',
				'message' => isset($template['message']) ? (string)$template['message'] : '',
				'html_encoding' => 'raw'
			);
		}
		return $clean;
	}

	private function saveMailTemplates($templates) {
		$this->load->model('setting/setting');
		$this->model_setting_setting->setSettingValue($this->templateSettingCode, $this->templateSettingKey, $templates, 0);
	}
}
