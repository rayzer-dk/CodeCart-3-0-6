<?php
class ControllerMarketplaceInstall extends Controller {
	public function install() {
		$this->load->language('marketplace/install');

		$json = array();
			
		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = $this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}
			
		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// Make sure the file name is stored in the session.
		if (!isset($this->session->data['install'])) {
			$json['error'] = $this->language->get('error_file');
		} elseif (!is_file(DIR_UPLOAD . $this->session->data['install'] . '.tmp')) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			$json['text'] = $this->language->get('text_unzip');

			$json['next'] = str_replace('&amp;', '&', $this->url->link('marketplace/install/unzip', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $extension_install_id, true));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function unzip() {
		$this->load->language('marketplace/install');

		$json = array();

		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = $this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}
		
		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->session->data['install'])) {
			$json['error'] = $this->language->get('error_file');
		} elseif (!is_file(DIR_UPLOAD . $this->session->data['install'] . '.tmp')) {
			$json['error'] = $this->language->get('error_file');
		}
		
		// Sanitize the filename
		if (!$json) {
			$file = DIR_UPLOAD . $this->session->data['install'] . '.tmp';
					
			// Unzip the files
			$zip = new ZipArchive();

			if ($zip->open($file) === true) {
                $target = DIR_UPLOAD . 'tmp-' . $this->session->data['install'];
                try {
                    $this->safeExtractZip($zip, $target);
                    $scan = \CodeCart\Core\UploadGuard::scanExtractedExtension($target);
                    if (!$scan['ok']) {
                        throw new \RuntimeException(isset($scan['message']) ? $scan['message'] : 'Extension security scan failed.');
                    }
                } catch (\Throwable $e) {
                    $json['error'] = $this->language->get('error_unsafe_archive');
                    if ($this->config->get('error_log')) { $this->log->write('Extension installer rejected archive: ' . $e->getMessage()); }
                }
				$zip->close();
			} else {
				$json['error'] = $this->language->get('error_unzip');
			}

			// Remove Zip
			unlink($file);

            if (!$json) {
                $json['text'] = $this->language->get('text_move');
                $json['next'] = str_replace('&amp;', '&', $this->url->link('marketplace/install/preflight', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $extension_install_id, true));
            }
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function preflight() {
		$this->load->language('marketplace/install');
		$json = array();
		$extension_install_id = isset($this->request->get['extension_install_id']) ? (int)$this->request->get['extension_install_id'] : 0;

		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if ($extension_install_id < 1 || !isset($this->session->data['install'])) {
			$json['error'] = $this->language->get('error_directory');
		}
		$directory = isset($this->session->data['install']) ? DIR_UPLOAD . 'tmp-' . $this->session->data['install'] . '/' : '';
		if (!$json && !is_dir($directory)) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			try {
				$operations = $this->collectInstallOperations($directory);
				$conflicts = array();
				foreach ($operations as $operation) {
					if (is_file($operation['destination'])) {
						$conflicts[] = $operation['logical'];
					}
				}

				$confirmed = !empty($this->request->get['confirm_overwrite']);
				if ($conflicts && !$confirmed) {
					$preview = array_slice($conflicts, 0, 20);
					$message = sprintf($this->language->get('text_conflict_preview'), count($conflicts)) . "\n\n" . implode("\n", $preview);
					if (count($conflicts) > count($preview)) {
						$message .= "\n" . sprintf($this->language->get('text_conflict_more'), count($conflicts) - count($preview));
					}
					$json['confirm'] = $message;
					$json['text'] = sprintf($this->language->get('text_preflight_conflicts'), count($operations), count($conflicts));
					$json['next'] = str_replace('&amp;', '&', $this->url->link('marketplace/install/preflight', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $extension_install_id . '&confirm_overwrite=1', true));
				} else {
					$journal = new \CodeCart\Core\ExtensionInstallJournal();
					$journal->prepare($extension_install_id, $this->session->data['install'], $operations);
					$json['text'] = $conflicts ? sprintf($this->language->get('text_preflight_backed_up'), count($conflicts)) : $this->language->get('text_preflight_ok');
					$json['next'] = str_replace('&amp;', '&', $this->url->link('marketplace/install/move', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $extension_install_id, true));
				}
			} catch (\Throwable $e) {
				$json['error'] = $this->language->get('error_preflight');
				if ($this->config->get('error_log')) { $this->log->write('Extension installer preflight failed: ' . $e->getMessage()); }
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function move() {
		$this->load->language('marketplace/install');
		$json = array();
		$extension_install_id = isset($this->request->get['extension_install_id']) ? (int)$this->request->get['extension_install_id'] : 0;

		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if ($extension_install_id < 1 || !isset($this->session->data['install'])) {
			$json['error'] = $this->language->get('error_directory');
		}
		$directory = isset($this->session->data['install']) ? DIR_UPLOAD . 'tmp-' . $this->session->data['install'] . '/' : '';
		if (!$json && !is_dir($directory)) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			$journal = new \CodeCart\Core\ExtensionInstallJournal();
			if (!$journal->has($extension_install_id)) {
				$json['error'] = $this->language->get('error_preflight_required');
			} else {
				try {
					$operations = $this->collectInstallOperations($directory);
					$this->load->model('setting/extension');
					foreach ($operations as $operation) {
						$journal->applyFile($extension_install_id, $operation['logical'], $operation['source'], $operation['destination']);
						$this->model_setting_extension->addExtensionPath($extension_install_id, $operation['logical']);
					}
					$json['text'] = $this->language->get('text_xml');
					$json['next'] = str_replace('&amp;', '&', $this->url->link('marketplace/install/xml', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $extension_install_id, true));
				} catch (\Throwable $e) {
					$rollback_complete = false;
					try {
						$rollback = $journal->rollback($extension_install_id);
						$rollback_complete = empty($rollback['skipped']);
					} catch (\Throwable $rollbackError) {
						if ($this->config->get('error_log')) { $this->log->write('Extension installer rollback failed: ' . $rollbackError->getMessage()); }
					}
					if ($rollback_complete) { $this->cleanupFailedInstall($extension_install_id); }
					$json['error'] = $rollback_complete ? $this->language->get('error_move') : $this->language->get('error_rollback_incomplete');
					if ($this->config->get('error_log')) { $this->log->write('Extension installer move failed: ' . $e->getMessage()); }
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function xml() {
		$this->load->language('marketplace/install');
		$json = array();
		$extension_install_id = isset($this->request->get['extension_install_id']) ? (int)$this->request->get['extension_install_id'] : 0;

		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (!class_exists('DOMDocument')) {
			$json['error'] = $this->language->get('error_dom_extension');
		}
		if (!isset($this->session->data['install']) || !is_dir(DIR_UPLOAD . 'tmp-' . $this->session->data['install'] . '/')) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			$file = DIR_UPLOAD . 'tmp-' . $this->session->data['install'] . '/install.xml';
			if (is_file($file)) {
				$this->load->model('setting/modification');
				try {
					$this->model_setting_modification->ensureCompatibilitySchema();
					$xml = file_get_contents($file);
					if ($xml === false || $xml === '') { throw new \RuntimeException('Modification XML cannot be read.'); }
					$dom = new DOMDocument('1.0', 'UTF-8');
					$previous = libxml_use_internal_errors(true);
					$loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
					libxml_clear_errors();
					libxml_use_internal_errors($previous);
					if (!$loaded || strtolower((string)$dom->documentElement->nodeName) !== 'modification') { throw new \RuntimeException('Invalid modification XML.'); }

					$nameNode = $dom->getElementsByTagName('name')->item(0);
					$codeNode = $dom->getElementsByTagName('code')->item(0);
					$authorNode = $dom->getElementsByTagName('author')->item(0);
					$versionNode = $dom->getElementsByTagName('version')->item(0);
					$linkNode = $dom->getElementsByTagName('link')->item(0);
					$code = $codeNode ? trim((string)$codeNode->nodeValue) : '';
					// OpenCart 3 extensions may use legacy OCMOD codes containing spaces or punctuation.
					// The code is a database identifier, not a filesystem path. Reject only empty,
					// oversized or control-character values so existing extensions remain installable.
					if ($code === '' || strlen($code) > 128 || preg_match('/[\x00-\x1F\x7F]/', $code)) {
						throw new \RuntimeException('Invalid modification code.');
					}
					$modification_data = array(
						'extension_install_id' => $extension_install_id,
						'name' => $nameNode ? (string)$nameNode->nodeValue : '',
						'code' => $code,
						'author' => $authorNode ? (string)$authorNode->nodeValue : '',
						'version' => $versionNode ? (string)$versionNode->nodeValue : '',
						'link' => $linkNode ? (string)$linkNode->nodeValue : '',
						'xml' => $xml,
						'status' => 1
					);
					$existing = $this->model_setting_modification->getModificationByCode($code);
					$journal = new \CodeCart\Core\ExtensionInstallJournal();
					if ($existing && (int)(isset($existing['extension_install_id']) ? $existing['extension_install_id'] : 0) !== $extension_install_id) {
						$journal->backupModification($extension_install_id, $existing);
					}
					$this->db->beginTransaction();
					try {
						if ($existing) { $this->model_setting_modification->deleteModification((int)$existing['modification_id']); }
						$this->model_setting_modification->addModification($modification_data);
						$this->db->commit();
					} catch (\Throwable $transactionError) {
						$this->db->rollback();
						throw $transactionError;
					}
				} catch (\Throwable $e) {
					$json['error'] = $this->language->get('error_xml_install');
					// The administrator needs the actual reason; keep it short and HTML-safe in the UI.
					$detail = trim(preg_replace('/[\r\n\t]+/', ' ', (string)$e->getMessage()));
					if ($detail !== '') { $json['detail'] = mb_substr($detail, 0, 1000, 'UTF-8'); }
					if ($this->config->get('error_log')) { $this->log->write('Extension OCMOD install failed: ' . $e->getMessage()); }
				}
			}
		}

		if (!$json) {
			try { (new \CodeCart\Core\ExtensionInstallJournal())->complete($extension_install_id); }
			catch (\Throwable $e) {
				$json['error'] = $this->language->get('error_journal');
				if ($this->config->get('error_log')) { $this->log->write('Extension installer journal completion failed: ' . $e->getMessage()); }
			}
		}

		if ($json && $extension_install_id > 0 && $this->user->hasPermission('modify', 'marketplace/install')) {
			$rollback_complete = false;
			try {
				$rollback = (new \CodeCart\Core\ExtensionInstallJournal())->rollback($extension_install_id);
				$rollback_complete = empty($rollback['skipped']);
			} catch (\Throwable $e) {
				if ($this->config->get('error_log')) { $this->log->write('Extension installer XML rollback failed: ' . $e->getMessage()); }
			}
			if ($rollback_complete) {
				$this->cleanupFailedInstall($extension_install_id);
			} else {
				$json['error'] = $this->language->get('error_rollback_incomplete');
			}
		}

		if (!$json) {
			$json['text'] = $this->language->get('text_remove');
			$json['next'] = str_replace('&amp;', '&', $this->url->link('marketplace/install/remove', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $extension_install_id, true));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove() {
		$this->load->language('marketplace/install');

		$json = array();
        $extension_install_id = isset($this->request->get['extension_install_id']) ? (int)$this->request->get['extension_install_id'] : 0;

		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->session->data['install'])) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			$directory = DIR_UPLOAD . 'tmp-' . $this->session->data['install'] . '/';
			
			if (is_dir($directory)) {
				// Get a list of files ready to upload
				$files = array();
	
				$path = array($directory);
	
				while (count($path) != 0) {
					$next = array_shift($path);
	
					// We have to use scandir function because glob will not pick up dot files.
					foreach (array_diff(scandir($next), array('.', '..')) as $file) {
						$file = $next . '/' . $file;
	
						if (is_dir($file)) {
							$path[] = $file;
						}
	
						$files[] = $file;
					}
				}
	
				rsort($files);
	
				foreach ($files as $file) {
					if (is_file($file)) {
						unlink($file);
					} elseif (is_dir($file)) {
						rmdir($file);
					}
				}
	
				if (is_dir($directory)) {
					rmdir($directory);
				}
			}
			
			$file = DIR_UPLOAD . $this->session->data['install'] . '.tmp';
			
			if (is_file($file)) {
				unlink($file);
			}

            try {
                \CodeCart\Core\OcmodState::markDirty('extension_installed', array('extension_install_id' => $extension_install_id));
            } catch (\Throwable $e) {
                // Advisory state only; a successful extension installation must remain successful.
            }
							
			$json['success'] = $this->language->get('text_success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function uninstall() {
		$this->load->language('marketplace/install');
		$json = array();
		$extension_install_id = isset($this->request->get['extension_install_id']) ? (int)$this->request->get['extension_install_id'] : 0;

		if (!$this->user->hasPermission('modify', 'marketplace/install')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/extension');
			$journal = new \CodeCart\Core\ExtensionInstallJournal();
			$journalled = $journal->has($extension_install_id);

			if ($journalled) {
				try {
					$changed = $journal->detectChangedFiles($extension_install_id);
					if ($changed) {
						$json['warning'] = sprintf($this->language->get('warning_uninstall_changed'), count($changed));
					} else {
						$result = $journal->uninstall($extension_install_id);
						if (!empty($result['skipped'])) {
							$json['error'] = $this->language->get('error_uninstall_restore');
						}
					}
				} catch (\Throwable $e) {
					$json['error'] = $this->language->get('error_uninstall_restore');
					if ($this->config->get('error_log')) { $this->log->write('Extension uninstall restore failed: ' . $e->getMessage()); }
				}
			} else {
				// Legacy installs have no CodeCart PRO journal. Keep the historical behaviour only
				// for paths explicitly recorded by OpenCart.
				$results = $this->model_setting_extension->getExtensionPathsByExtensionInstallId($extension_install_id);
				rsort($results);
				foreach ($results as $result) {
					$source = $this->logicalToDestination($result['path']);
					if ($source && is_file($source)) { @unlink($source); }
					if ($source && is_dir($source) && $this->isDirEmpty($source)) { @rmdir($source); }
				}
			}

			if (!$json) {
				$this->db->beginTransaction();
				try {
					$this->load->model('setting/modification');
					$this->model_setting_modification->deleteModificationsByExtensionInstallId($extension_install_id);
					if ($journalled) { $this->restorePreviousModification($extension_install_id); }
					$paths = $this->model_setting_extension->getExtensionPathsByExtensionInstallId($extension_install_id);
					foreach ($paths as $path) { $this->model_setting_extension->deleteExtensionPath((int)$path['extension_path_id']); }
					$this->model_setting_extension->deleteExtensionInstall($extension_install_id);
					$this->db->commit();
                    try {
                        \CodeCart\Core\OcmodState::markDirty('extension_uninstalled', array('extension_install_id' => $extension_install_id));
                    } catch (\Throwable $stateError) {
                        // Advisory state only.
                    }
					$json['success'] = $this->language->get('text_success');
				} catch (\Throwable $e) {
					$this->db->rollback();
					$json['error'] = $this->language->get('error_uninstall_restore');
					if ($this->config->get('error_log')) { $this->log->write('Extension uninstall database cleanup failed: ' . $e->getMessage()); }
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function cleanupFailedInstall($extension_install_id) {
		$extension_install_id = (int)$extension_install_id;
		if ($extension_install_id < 1) { return; }
		$this->load->model('setting/extension');
		$this->load->model('setting/modification');
		$this->db->beginTransaction();
		try {
			foreach ($this->model_setting_extension->getExtensionPathsByExtensionInstallId($extension_install_id) as $path) {
				$this->model_setting_extension->deleteExtensionPath((int)$path['extension_path_id']);
			}
			$this->model_setting_modification->deleteModificationsByExtensionInstallId($extension_install_id);
			$this->restorePreviousModification($extension_install_id);
			$this->model_setting_extension->deleteExtensionInstall($extension_install_id);
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollback();
			throw $e;
		}
	}

	private function restorePreviousModification($extension_install_id) {
		$journal = new \CodeCart\Core\ExtensionInstallJournal();
		if (!$journal->has($extension_install_id)) { return; }
		$previous = $journal->getPreviousModification($extension_install_id);
		if (!$previous || empty($previous['code'])) { return; }
		$this->load->model('setting/modification');
		$current = $this->model_setting_modification->getModificationByCode((string)$previous['code']);
		if ($current) {
			if ((int)$current['extension_install_id'] === (int)(isset($previous['extension_install_id']) ? $previous['extension_install_id'] : 0)) { return; }
			if ((int)$current['extension_install_id'] !== $extension_install_id) {
				throw new \RuntimeException('A different modification now uses the backed-up code.');
			}
			$this->model_setting_modification->deleteModification((int)$current['modification_id']);
		}
		$previous['extension_install_id'] = isset($previous['extension_install_id']) ? (int)$previous['extension_install_id'] : 0;
		$previous['status'] = isset($previous['status']) ? (int)$previous['status'] : 1;
		$this->model_setting_modification->addModification($previous);
	}

	private function collectInstallOperations($directory) {
		$root = rtrim((string)$directory, '/\\') . '/upload/';
		if (!is_dir($root)) { return array(); }
		$operations = array();
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $item) {
			if (!$item->isFile()) { continue; }
			$logical = str_replace('\\', '/', substr($item->getPathname(), strlen($root)));
			if (!$this->isAllowedLogicalPath($logical)) {
				throw new \RuntimeException(sprintf($this->language->get('error_allowed'), $logical));
			}
			$destination = $this->logicalToDestination($logical);
			if ($destination === '') { throw new \RuntimeException('Unable to resolve extension target.'); }
			$operations[] = array('logical' => $logical, 'source' => $item->getPathname(), 'destination' => $destination);
		}
		usort($operations, function($a, $b) { return strcmp($a['logical'], $b['logical']); });
		return $operations;
	}

	private function isAllowedLogicalPath($path) {
		$path = ltrim(str_replace('\\', '/', (string)$path), '/');
		if ($path === '' || strpos($path, "\0") !== false || preg_match('#(?:^|/)\\.\\.(?:/|$)#', $path)) { return false; }
		$allowed = array(
			// Preserve the OpenCart 3 extension contract: extensions may legitimately add
			// controllers/models outside extension/* (common, product, checkout, setting, etc.).
			// Path traversal is rejected above and overwritten files are journalled before activation.
			'admin/controller/', 'admin/language/', 'admin/model/',
			'admin/view/image/', 'admin/view/javascript/', 'admin/view/stylesheet/', 'admin/view/template/',
			'catalog/controller/', 'catalog/language/', 'catalog/model/',
			'catalog/view/javascript/', 'catalog/view/theme/',
			// Native CodeCart Modern Extensions live in system/extension/<code>/.
			// The path still passes traversal checks and the installer journals overwritten files.
			'system/config/', 'system/library/', 'system/extension/', 'image/catalog/'
		);
		foreach ($allowed as $prefix) { if (strpos($path, $prefix) === 0) { return true; } }
		return false;
	}

	private function logicalToDestination($path) {
		$path = ltrim(str_replace('\\', '/', (string)$path), '/');
		if (strpos($path, 'admin/') === 0) { return DIR_APPLICATION . substr($path, 6); }
		if (strpos($path, 'catalog/') === 0) { return DIR_CATALOG . substr($path, 8); }
		if (strpos($path, 'image/') === 0) { return DIR_IMAGE . substr($path, 6); }
		if (strpos($path, 'system/') === 0) { return DIR_SYSTEM . substr($path, 7); }
		return '';
	}

	private function isDirEmpty ($dir_name) {
		if (!is_dir($dir_name)) {
			return false;
		}
		foreach (scandir($dir_name) as $dir_file)
		{
			if (!in_array($dir_file, array('.','..'))) {
				return false;
			}
		}
		return true;
	}
    private function safeExtractZip(\ZipArchive $zip, $target) {
        $target = rtrim((string)$target, '/\\');
        if ($target === '') { throw new \RuntimeException('Empty extraction target.'); }
        if (!is_dir($target) && !mkdir($target, 0750, true) && !is_dir($target)) { throw new \RuntimeException('Unable to create extraction directory.'); }
        $targetReal = realpath($target);
        if ($targetReal === false) { throw new \RuntimeException('Unable to resolve extraction directory.'); }
        $targetPrefix = rtrim(str_replace('\\', '/', $targetReal), '/') . '/';
        // Prevent archive bombs while keeping limits high enough for real themes/extensions.
        $maxEntries = 20000;
        $maxUncompressed = 512 * 1024 * 1024;
        if ($zip->numFiles > $maxEntries) { throw new \RuntimeException('Archive contains too many entries.'); }
        $totalUncompressed = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $totalUncompressed += isset($stat['size']) ? max(0, (int)$stat['size']) : 0;
            if ($totalUncompressed > $maxUncompressed) { throw new \RuntimeException('Archive uncompressed size exceeds the safe limit.'); }
            $name = isset($stat['name']) ? str_replace('\\', '/', (string)$stat['name']) : '';
            if ($name === '' || strpos($name, "\0") !== false || $name[0] === '/' || preg_match('/^[A-Za-z]:\//', $name)) { throw new \RuntimeException('Unsafe ZIP entry.'); }
            $parts = array();
            foreach (explode('/', $name) as $part) {
                if ($part === '' || $part === '.') { continue; }
                if ($part === '..') { throw new \RuntimeException('Path traversal entry rejected.'); }
                $parts[] = $part;
            }
            if (!$parts) { continue; }
            $destination = $targetPrefix . implode('/', $parts);
            $parent = dirname($destination);
            if (!is_dir($parent) && !mkdir($parent, 0750, true) && !is_dir($parent)) { throw new \RuntimeException('Unable to create archive directory.'); }
            $parentReal = realpath($parent);
            if ($parentReal === false || strpos(rtrim(str_replace('\\', '/', $parentReal), '/') . '/', $targetPrefix) !== 0) { throw new \RuntimeException('Archive entry escapes extraction directory.'); }
            if (substr($name, -1) === '/') { continue; }
            $stream = $zip->getStream($stat['name']);
            if (!is_resource($stream)) { throw new \RuntimeException('Unable to read ZIP entry.'); }
            $out = @fopen($destination, 'xb');
            if (!is_resource($out)) { fclose($stream); throw new \RuntimeException('Unable to create extracted file.'); }
            stream_copy_to_stream($stream, $out); fclose($out); fclose($stream);
        }
    }

}
