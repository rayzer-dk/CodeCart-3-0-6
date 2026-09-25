<?php
class ControllerInstallStep3 extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('install/step_3');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->load->model('install/install');

			try {
				// A fresh production install must never keep writable runtime/vendor data
				// below the public document root. Prepare protected storage before touching DB.
				$storage_path = $this->prepareSecureStorage();
			} catch (\Throwable $e) {
				$this->log->write('Installer secure storage: ' . $e->getMessage());
				$this->error['warning'] = $this->language->get('error_secure_storage');
			}

			if (!$this->error) {
				try {
					$this->model_install_install->database($this->request->post);
				} catch (\Throwable $e) {
					$this->log->write('Installer database: ' . $e->getMessage());
					$this->error['warning'] = $this->language->get('error_install_database');
				}
			}

			if (!$this->error) {
				try {
					// Catalog config.php
					$output  = '<?php' . "\n";
					$output .= '// HTTP' . "\n";
					$output .= 'define(\'HTTP_SERVER\', \'' . addslashes(HTTP_OPENCART) . '\');' . "\n\n";
					$output .= '// HTTPS' . "\n";
					$output .= 'define(\'HTTPS_SERVER\', \'' . addslashes(HTTP_OPENCART) . '\');' . "\n\n";
					$output .= '// DIR' . "\n";
					$output .= 'define(\'DIR_APPLICATION\', \'' . addslashes(DIR_OPENCART) . 'catalog/\');' . "\n";
					$output .= 'define(\'DIR_SYSTEM\', \'' . addslashes(DIR_OPENCART) . 'system/\');' . "\n";
					$output .= 'define(\'DIR_IMAGE\', \'' . addslashes(DIR_OPENCART) . 'image/\');' . "\n";
					$output .= 'define(\'DIR_STORAGE\', \'' . addslashes($storage_path) . '\');' . "\n";
					$output .= 'define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');' . "\n";
					$output .= 'define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/theme/\');' . "\n";
					$output .= 'define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');' . "\n";
					$output .= 'define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');' . "\n";
					$output .= 'define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');' . "\n";
					$output .= 'define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');' . "\n";
					$output .= 'define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');' . "\n";
					$output .= 'define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');' . "\n";
					$output .= 'define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');' . "\n\n";
					$output .= '// DB' . "\n";
					$output .= 'define(\'DB_DRIVER\', \'' . addslashes($this->request->post['db_driver']) . '\');' . "\n";
					$output .= 'define(\'DB_HOSTNAME\', \'' . addslashes($this->request->post['db_hostname']) . '\');' . "\n";
					$output .= 'define(\'DB_USERNAME\', \'' . addslashes($this->request->post['db_username']) . '\');' . "\n";
					$output .= 'define(\'DB_PASSWORD\', \'' . addslashes(html_entity_decode($this->request->post['db_password'], ENT_QUOTES, 'UTF-8')) . '\');' . "\n";
					$output .= 'define(\'DB_DATABASE\', \'' . addslashes($this->request->post['db_database']) . '\');' . "\n";
					$output .= 'define(\'DB_PORT\', \'' . addslashes($this->request->post['db_port']) . '\');' . "\n";
					$output .= 'define(\'DB_PREFIX\', \'' . addslashes($this->request->post['db_prefix']) . '\');';
					$this->writeConfigFile(DIR_OPENCART . 'config.php', $output);

					// Admin config.php
					$output  = '<?php' . "\n";
					$output .= '// HTTP' . "\n";
					$output .= 'define(\'HTTP_SERVER\', \'' . addslashes(HTTP_OPENCART) . 'admin/\');' . "\n";
					$output .= 'define(\'HTTP_CATALOG\', \'' . addslashes(HTTP_OPENCART) . '\');' . "\n\n";
					$output .= '// HTTPS' . "\n";
					$output .= 'define(\'HTTPS_SERVER\', \'' . addslashes(HTTP_OPENCART) . 'admin/\');' . "\n";
					$output .= 'define(\'HTTPS_CATALOG\', \'' . addslashes(HTTP_OPENCART) . '\');' . "\n\n";
					$output .= '// DIR' . "\n";
					$output .= 'define(\'DIR_APPLICATION\', \'' . addslashes(DIR_OPENCART) . 'admin/\');' . "\n";
					$output .= 'define(\'DIR_SYSTEM\', \'' . addslashes(DIR_OPENCART) . 'system/\');' . "\n";
					$output .= 'define(\'DIR_IMAGE\', \'' . addslashes(DIR_OPENCART) . 'image/\');' . "\n";
					$output .= 'define(\'DIR_STORAGE\', \'' . addslashes($storage_path) . '\');' . "\n";
					$output .= 'define(\'DIR_CATALOG\', \'' . addslashes(DIR_OPENCART) . 'catalog/\');' . "\n";
					$output .= 'define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');' . "\n";
					$output .= 'define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/template/\');' . "\n";
					$output .= 'define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');' . "\n";
					$output .= 'define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');' . "\n";
					$output .= 'define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');' . "\n";
					$output .= 'define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');' . "\n";
					$output .= 'define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');' . "\n";
					$output .= 'define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');' . "\n";
					$output .= 'define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');' . "\n\n";
					$output .= '// DB' . "\n";
					$output .= 'define(\'DB_DRIVER\', \'' . addslashes($this->request->post['db_driver']) . '\');' . "\n";
					$output .= 'define(\'DB_HOSTNAME\', \'' . addslashes($this->request->post['db_hostname']) . '\');' . "\n";
					$output .= 'define(\'DB_USERNAME\', \'' . addslashes($this->request->post['db_username']) . '\');' . "\n";
					$output .= 'define(\'DB_PASSWORD\', \'' . addslashes(html_entity_decode($this->request->post['db_password'], ENT_QUOTES, 'UTF-8')) . '\');' . "\n";
					$output .= 'define(\'DB_DATABASE\', \'' . addslashes($this->request->post['db_database']) . '\');' . "\n";
					$output .= 'define(\'DB_PORT\', \'' . addslashes($this->request->post['db_port']) . '\');' . "\n";
					$output .= 'define(\'DB_PREFIX\', \'' . addslashes($this->request->post['db_prefix']) . '\');' . "\n\n";
					$output .= '// OpenCart API' . "\n";
					$output .= 'define(\'OPENCART_SERVER\', \'https://www.opencart.com/\');' . "\n";
					$output .= 'define(\'OPENCARTFORUM_SERVER\', \'https://opencartforum.com/\');' . "\n";
					$this->writeConfigFile(DIR_OPENCART . 'admin/config.php', $output);
					$this->updateRobotsSitemap(HTTP_OPENCART);
				} catch (\Throwable $e) {
					$this->model_install_install->rollbackDatabase($this->request->post);
					$this->log->write('Installer configuration: ' . $e->getMessage());
					$this->error['warning'] = $this->language->get('error_install_configuration');
				}

				if (!$this->error) {
					$this->session->data['codecart_install_admin_username'] = trim((string)$this->request->post['username']);
					$this->response->redirect($this->url->link('install/step_4'));
				}
			}
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_step_3'] = $this->language->get('text_step_3');
		$data['text_db_connection'] = $this->language->get('text_db_connection');
		$data['text_db_administration'] = $this->language->get('text_db_administration');
		$data['text_mysqli'] = $this->language->get('text_mysqli');

		$data['entry_db_driver'] = $this->language->get('entry_db_driver');
		$data['entry_db_hostname'] = $this->language->get('entry_db_hostname');
		$data['entry_db_username'] = $this->language->get('entry_db_username');
		$data['entry_db_password'] = $this->language->get('entry_db_password');
		$data['entry_db_database'] = $this->language->get('entry_db_database');
		$data['entry_db_port'] = $this->language->get('entry_db_port');
		$data['entry_db_prefix'] = $this->language->get('entry_db_prefix');
		$data['entry_username'] = $this->language->get('entry_username');
		$data['entry_password'] = $this->language->get('entry_password');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['text_show_password'] = $this->language->get('text_show_password');
		$data['help_password'] = $this->language->get('help_password');
		$data['help_username'] = $this->language->get('help_username');

		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_back'] = $this->language->get('button_back');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['db_driver'])) {
			$data['error_db_driver'] = $this->error['db_driver'];
		} else {
			$data['error_db_driver'] = '';
		}

		if (isset($this->error['db_hostname'])) {
			$data['error_db_hostname'] = $this->error['db_hostname'];
		} else {
			$data['error_db_hostname'] = '';
		}

		if (isset($this->error['db_username'])) {
			$data['error_db_username'] = $this->error['db_username'];
		} else {
			$data['error_db_username'] = '';
		}

		if (isset($this->error['db_database'])) {
			$data['error_db_database'] = $this->error['db_database'];
		} else {
			$data['error_db_database'] = '';
		}
		
		if (isset($this->error['db_port'])) {
			$data['error_db_port'] = $this->error['db_port'];
		} else {
			$data['error_db_port'] = '';
		}
		
		if (isset($this->error['db_prefix'])) {
			$data['error_db_prefix'] = $this->error['db_prefix'];
		} else {
			$data['error_db_prefix'] = '';
		}

		if (isset($this->error['username'])) {
			$data['error_username'] = $this->error['username'];
		} else {
			$data['error_username'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		$data['action'] = $this->url->link('install/step_3');

		$db_drivers = array(
			'mysqli' => 'mysqli',
			'pdo' => 'pdo_mysql'
		);

		$data['drivers'] = array();

		foreach ($db_drivers as $db_driver => $extension) {
			if (extension_loaded($extension)) {
				$data['drivers'][] = array(
					'text'  => $this->language->get('text_' . $db_driver),
					'value' => $db_driver
				);
			}
		}

		if (isset($this->request->post['db_driver'])) {
			$data['db_driver'] = $this->request->post['db_driver'];
		} elseif (!empty($data['drivers'])) {
			$data['db_driver'] = $data['drivers'][0]['value'];
		} else {
			$data['db_driver'] = '';
		}

		if (isset($this->request->post['db_hostname'])) {
			$data['db_hostname'] = $this->request->post['db_hostname'];
		} else {
			$data['db_hostname'] = 'localhost';
		}

		if (isset($this->request->post['db_username'])) {
			$data['db_username'] = $this->request->post['db_username'];
		} else {
			$data['db_username'] = 'root';
		}

		if (isset($this->request->post['db_password'])) {
			$data['db_password'] = $this->request->post['db_password'];
		} else {
			$data['db_password'] = '';
		}

		if (isset($this->request->post['db_database'])) {
			$data['db_database'] = $this->request->post['db_database'];
		} else {
			$data['db_database'] = '';
		}

		if (isset($this->request->post['db_port'])) {
			$data['db_port'] = $this->request->post['db_port'];
		} else {
			$data['db_port'] = 3306;
		}
		
		if (isset($this->request->post['db_prefix'])) {
			$data['db_prefix'] = $this->request->post['db_prefix'];
		} else {
			$data['db_prefix'] = 'oc_';
		}

		if (isset($this->request->post['username'])) {
			$data['username'] = $this->request->post['username'];
		} else {
			$data['username'] = 'admin';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		$data['back'] = $this->url->link('install/step_2');

		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');

		$this->response->setOutput($this->load->view('install/step_3', $data));
	}

	private function prepareSecureStorage() {
		$source = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
		$root = rtrim(str_replace('\\', '/', DIR_OPENCART), '/');
		$parent = dirname($root);

		if (!is_dir($source)) {
			throw new \RuntimeException('Installer storage source is missing.');
		}
		if (!is_dir($parent) || !is_writable($parent)) {
			throw new \RuntimeException('The directory above the public web root is not writable. Configure hosting permissions so protected storage can be created outside the site directory.');
		}

		$container = $parent . DIRECTORY_SEPARATOR . 'storage';
		if (!is_dir($container) && !@mkdir($container, 0750, true) && !is_dir($container)) {
			throw new \RuntimeException('Cannot create the protected storage container outside the public web root.');
		}

		$name = preg_replace('/[^A-Za-z0-9._-]/', '-', basename($root));
		$target = str_replace('\\', '/', $container . DIRECTORY_SEPARATOR . $name . '-' . substr(hash('sha256', $root), 0, 8)) . '/';
		$this->copyStorageDirectory($source, $target);
		$this->writePhpReturnFile(DIR_APPLICATION . 'storage-path.php', $target);
		return $target;
	}

	private function copyStorageDirectory($source, $target) {
		$source = rtrim($source, '/\\') . DIRECTORY_SEPARATOR;
		$target = rtrim($target, '/\\') . DIRECTORY_SEPARATOR;
		if (!is_dir($target) && !@mkdir($target, 0750, true) && !is_dir($target)) {
			throw new \RuntimeException('Cannot create protected storage directory.');
		}

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
		foreach ($iterator as $item) {
			if ($item->isLink()) { continue; }
			$relative = substr($item->getPathname(), strlen($source));
			$destination = $target . $relative;
			if ($item->isDir()) {
				if (!is_dir($destination) && !@mkdir($destination, 0750, true) && !is_dir($destination)) {
					throw new \RuntimeException('Cannot create protected storage subdirectory.');
				}
			} else {
				$directory = dirname($destination);
				if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
					throw new \RuntimeException('Cannot create protected storage subdirectory.');
				}
				if (!@copy($item->getPathname(), $destination)) {
					throw new \RuntimeException('Cannot copy protected storage data.');
				}
				@chmod($destination, 0640);
			}
		}
	}

	private function writePhpReturnFile($path, $value) {
		$content = "<?php\nreturn " . var_export((string)$value, true) . ";\n";
		$temp = tempnam(dirname($path), '.cc-storage-');
		if ($temp === false || file_put_contents($temp, $content, LOCK_EX) === false || !@rename($temp, $path)) {
			if ($temp && is_file($temp)) { @unlink($temp); }
			throw new \RuntimeException('Cannot save protected storage pointer.');
		}
		@chmod($path, 0640);
	}

	private function updateRobotsSitemap($baseUrl) {
		$path = DIR_OPENCART . 'robots.txt';
		if (!is_file($path) || !is_readable($path)) { return; }
		$content = @file_get_contents($path);
		if (!is_string($content)) { return; }
		$baseUrl = rtrim(trim((string)$baseUrl), '/');
		if (!preg_match('#^https?://#i', $baseUrl)) { return; }
		$sitemap = 'Sitemap: ' . $baseUrl . '/sitemap.xml';
		$content = preg_replace('/^\s*Sitemap:\s*https?:\/\/.*$/mi', '', $content);
		$content = preg_replace('/^\s*#\s*Sitemap:\s*https?:\/\/example\.com\/sitemap\.xml\s*$/mi', '', $content);
		$content = rtrim((string)$content) . "\n\n" . $sitemap . "\n";
		if (@file_put_contents($path, $content, LOCK_EX) === false) {
			$this->log->write('Installer robots.txt: unable to write Sitemap directive.');
		}
	}

	private function writeConfigFile($path, $content) {
		$directory = dirname($path);
		$temp = tempnam($directory, '.codecart-config-');

		if ($temp === false) {
			throw new \RuntimeException('Could not create temporary configuration file in ' . $directory . '.');
		}

		$written = file_put_contents($temp, $content, LOCK_EX);

		if ($written === false || $written !== strlen($content)) {
			if (file_exists($temp)) {
				unlink($temp);
			}

			throw new \RuntimeException('Could not write configuration file ' . $path . '.');
		}

		if (!rename($temp, $path)) {
			if (file_exists($temp)) {
				unlink($temp);
			}

			throw new \RuntimeException('Could not replace configuration file ' . $path . '.');
		}

		@chmod($path, 0640);
	}

	private function validate() {
		$post = $this->request->post;

		$db_hostname = isset($post['db_hostname']) ? trim((string)$post['db_hostname']) : '';
		$db_username = isset($post['db_username']) ? (string)$post['db_username'] : '';
		$db_password = isset($post['db_password']) ? (string)$post['db_password'] : '';
		$db_database = isset($post['db_database']) ? trim((string)$post['db_database']) : '';
		$db_port = isset($post['db_port']) ? (string)$post['db_port'] : '';
		$db_prefix = isset($post['db_prefix']) ? (string)$post['db_prefix'] : '';
		$db_driver = isset($post['db_driver']) ? (string)$post['db_driver'] : '';
		$username = isset($post['username']) ? trim((string)$post['username']) : '';
		$password = isset($post['password']) ? (string)$post['password'] : '';
		$email = isset($post['email']) ? trim((string)$post['email']) : '';

		if ($db_hostname === '') {
			$this->error['db_hostname'] = $this->language->get('error_db_hostname');
		}

		if ($db_username === '') {
			$this->error['db_username'] = $this->language->get('error_db_username');
		}

		if ($db_database === '') {
			$this->error['db_database'] = $this->language->get('error_db_database');
		}

		if (!ctype_digit($db_port) || (int)$db_port < 1 || (int)$db_port > 65535) {
			$this->error['db_port'] = $this->language->get('error_db_port');
		}

		if (!preg_match('/^[A-Za-z0-9_]*$/', $db_prefix) || strlen($db_prefix) > 32) {
			$this->error['db_prefix'] = $this->language->get('error_db_prefix');
		}

		$db_drivers = array('mysqli', 'pdo');

		if (!in_array($db_driver, $db_drivers, true)) {
			$this->error['db_driver'] = $this->language->get('error_db_driver');
		} elseif (($db_driver === 'mysqli' && !extension_loaded('mysqli')) || ($db_driver === 'pdo' && !extension_loaded('pdo_mysql'))) {
			$this->error['db_driver'] = $this->language->get('error_db_driver');
		} elseif ($db_hostname !== '' && $db_username !== '' && $db_database !== '' && !isset($this->error['db_port']) && !isset($this->error['db_prefix'])) {
			try {
				$db = new \DB($db_driver, html_entity_decode($db_hostname, ENT_QUOTES, 'UTF-8'), html_entity_decode($db_username, ENT_QUOTES, 'UTF-8'), html_entity_decode($db_password, ENT_QUOTES, 'UTF-8'), html_entity_decode($db_database, ENT_QUOTES, 'UTF-8'), $db_port);

				if (!$this->supportsModernDatabaseFoundation($db)) {
					$this->error['warning'] = $this->language->get('error_db_requirements');
				} elseif ($this->hasTablesWithPrefix($db, $db_prefix)) {
					$this->error['db_prefix'] = $this->language->get('error_db_prefix_in_use');
				}
			} catch (\Throwable $e) {
				$this->error['warning'] = $this->language->get('error_db_connect');
			}
		}

		if ($username === '') {
			$this->error['username'] = $this->language->get('error_username');
		}

		if ((utf8_strlen($email) > 96) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (utf8_strlen($password) < 10) {
			$this->error['password'] = $this->language->get('error_password');
		}

		if (!$this->isWritableFileTarget(DIR_OPENCART . 'config.php')) {
			$this->error['warning'] = $this->language->get('error_config') . DIR_OPENCART . 'config.php!';
		}

		if (!$this->isWritableFileTarget(DIR_OPENCART . 'admin/config.php')) {
			$this->error['warning'] = $this->language->get('error_config') . DIR_OPENCART . 'admin/config.php!';
		}

		return !$this->error;
	}


	private function isWritableFileTarget($path) {
		if (is_file($path)) { return is_writable($path); }
		$directory = dirname($path);
		return is_dir($directory) && is_writable($directory);
	}

	private function supportsModernDatabaseFoundation($db) {
		$utf8mb4 = $db->query("SHOW CHARACTER SET WHERE Charset = 'utf8mb4'");
		if (!$utf8mb4->num_rows) { return false; }
		$engines = $db->query('SHOW ENGINES');
		foreach ($engines->rows as $row) {
			$engine = isset($row['Engine']) ? (string)$row['Engine'] : (isset($row['engine']) ? (string)$row['engine'] : '');
			$support = isset($row['Support']) ? strtoupper((string)$row['Support']) : (isset($row['support']) ? strtoupper((string)$row['support']) : '');
			if (strcasecmp($engine, 'InnoDB') === 0 && in_array($support, array('YES','DEFAULT'), true)) { return true; }
		}
		return false;
	}

	private function hasTablesWithPrefix($db, $prefix) {
		$query = $db->query('SHOW TABLES');

		foreach ($query->rows as $row) {
			$table = (string)reset($row);

			if ($table !== '' && strpos($table, $prefix) === 0) {
				return true;
			}
		}

		return false;
	}
}
