<?php
class ControllerToolBackup extends Controller {
	public function index() {
		$this->load->language('tool/backup');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['export'] = $this->url->link('tool/backup/export', 'user_token=' . $this->session->data['user_token'], true);
		
		$this->load->model('tool/backup');

		$data['tables'] = $this->model_tool_backup->getTables();
		$data['volatile_backup_tables'] = array(DB_PREFIX . 'session', DB_PREFIX . 'api_session');

		$data['catalog_export'] = $this->url->link('tool/catalog_transfer/export', 'user_token=' . $this->session->data['user_token'], true);
		$data['catalog_preview'] = $this->url->link('tool/catalog_transfer/preview', 'user_token=' . $this->session->data['user_token'], true);
		$data['catalog_import'] = $this->url->link('tool/catalog_transfer/import', 'user_token=' . $this->session->data['user_token'], true);
		$data['merchant_feed_settings'] = $this->url->link('extension/feed/google_base', 'user_token=' . $this->session->data['user_token'], true);
		$data['catalog_entities'] = array('products','categories','manufacturers','options','attributes','filters');
		// XLSX capability is a PHP runtime property and must not be hidden by an
		// unrelated catalog-count/database error. Detect it independently.
		$data['catalog_xlsx_export_available'] = \CodeCart\Core\SimpleXlsx::writeAvailable();
		$data['catalog_xlsx_import_available'] = \CodeCart\Core\SimpleXlsx::readAvailable();
		$data['catalog_xlsx_available'] = $data['catalog_xlsx_import_available'];
		$data['catalog_counts'] = array();
		try {
			$catalogTransfer = new \CodeCart\Core\CatalogTransfer($this->registry);
			$data['catalog_counts'] = $catalogTransfer->entityCounts();
		} catch (\Throwable $e) {
			$this->log->write('CodeCart PRO catalog transfer counters unavailable: ' . $e->getMessage());
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('tool/backup', $data));
	}
	
	public function import() {
		$this->load->language('tool/backup');
		
		$json = array();
		
		if (!$this->user->hasPermission('modify', 'tool/backup')) {
			$json['error'] = $this->language->get('error_permission');
		}
		
		$filename = '';

		if (!$json && isset($this->request->files['import']) && is_array($this->request->files['import'])) {
			$check = \CodeCart\Core\UploadGuard::validateSqlBackup($this->request->files['import']);
			if (!$check['ok']) {
				$code = isset($check['code']) ? (string)$check['code'] : 'upload';
				if ($code === 'filesize') {
					$json['error'] = $this->language->get('error_filesize');
				} elseif ($code === 'filetype' || $code === 'filename') {
					$json['error'] = $this->language->get('error_filetype');
				} else {
					$json['error'] = $this->language->get('error_file');
				}
			} else {
				$filename = tempnam(DIR_UPLOAD, 'bac');
				if (!$filename || !move_uploaded_file($this->request->files['import']['tmp_name'], $filename)) {
					if ($filename && is_file($filename)) { @unlink($filename); }
					$filename = '';
					$json['error'] = $this->language->get('error_file');
				}
			}
		} elseif (!$json && isset($this->request->get['import'])) {
			$filename = DIR_UPLOAD . basename(html_entity_decode($this->request->get['import'], ENT_QUOTES, 'UTF-8'));
		}

		if (!$json && !is_file($filename)) {
			$json['error'] = $this->language->get('error_file');
		}

		$position = isset($this->request->get['position']) ? max(0, (int)$this->request->get['position']) : 0;

		$this->load->model('tool/backup');
		$allowed_restore_tables = array_flip($this->model_tool_backup->getTables());
	
		if (!$json) {
			// Restore in small batches. Generated backups are intentionally limited to
			// TRUNCATE/INSERT and values use hex literals, so they are independent from
			// NO_BACKSLASH_ESCAPES and other SQL string modes.
			$i = 0;
			$start = false;
			$sql = '';
			$protected_tables = array(
				DB_PREFIX . 'user' => true,
				DB_PREFIX . 'user_group' => true
			);

			$handle = @fopen($filename, 'r');

			if (!$handle) {
				$json['error'] = $this->language->get('error_file');
			} else {
				fseek($handle, $position, SEEK_SET);

				try {
					// A database backup may contain related tables in any order. Disable FK
					// checks for this request only; the next request/connection starts clean.
					$this->db->query("SET FOREIGN_KEY_CHECKS=0");
				} catch (\Throwable $e) {
					$this->log->write('CodeCart PRO SQL restore could not disable foreign key checks: ' . $e->getMessage());
				}

				while (!feof($handle) && $i < 100 && !$json) {
					$line_position = ftell($handle);
					$line = fgets($handle, 1000000);

					if ($line === false) {
						break;
					}

					// Strip UTF-8 BOM from the first line of manually supplied backups.
					if ($line_position === 0 && substr($line, 0, 3) === "\xEF\xBB\xBF") {
						$line = substr($line, 3);
					}

					if (!$start && preg_match('/^\s*(TRUNCATE\s+TABLE|INSERT\s+INTO)\b/i', $line)) {
						$sql = '';
						$start = true;
						$position = $line_position;
					}

					if (!$start) {
						continue;
					}

					$sql .= $line;

					if (!preg_match('/;\s*$/', $sql)) {
						continue;
					}

					$statement = preg_replace('/;\s*$/', '', trim($sql));
					$start = false;
					$sql = '';

					if (!preg_match('/^\s*(TRUNCATE\s+TABLE|INSERT\s+INTO)\s+`([^`]+)`/i', $statement, $restore_match) || !isset($allowed_restore_tables[$restore_match[2]])) {
						$json['error'] = $this->language->get('error_restore_table');
						break;
					}

					$table = (string)$restore_match[2];
					$verb = stripos($restore_match[1], 'TRUNCATE') === 0 ? 'TRUNCATE' : 'INSERT';

					// Never overwrite administrator identities during an in-session restore.
					// The old implementation stopped at these tables and could loop forever;
					// skipping both their TRUNCATE and INSERT statements keeps the current
					// administrator usable while the rest of the database is restored.
					if (isset($protected_tables[$table])) {
						$i++;
						continue;
					}

					// Accept backups generated by earlier CodeCart PRO builds that used X'ABCD'
					// literals. MariaDB/MySQL both accept 0xABCD in value context and this
					// avoids parser differences around quoted binary literals.
					$statement = preg_replace_callback("/X'([0-9A-Fa-f]*)'/", static function($match) {
						return $match[1] === '' ? "''" : '0x' . $match[1];
					}, $statement);

					try {
						$this->db->query($statement);
					} catch (\Throwable $e) {
						$excerpt = preg_replace('/\s+/', ' ', $statement);
						$excerpt = utf8_substr((string)$excerpt, 0, 600);
						$this->log->write('CodeCart PRO SQL restore failed [' . $verb . ' ' . $table . '] at byte ' . (int)$position . ': ' . $e->getMessage() . ' | SQL: ' . $excerpt);
						$json['error'] = $this->language->get('error_restore_query') . ' [' . $table . ']';
						break;
					}

					$i++;
				}

				$position = ftell($handle);
				$size = (int)filesize($filename);

				try {
					$this->db->query("SET FOREIGN_KEY_CHECKS=1");
				} catch (\Throwable $e) {
					$this->log->write('CodeCart PRO SQL restore could not re-enable foreign key checks: ' . $e->getMessage());
				}

				if ($size <= 0) {
					$json['error'] = $this->language->get('error_file');
					fclose($handle);
					@unlink($filename);
				} elseif (!$json) {
					$json['total'] = min(100, round(($position / $size) * 100));

					if ($position < $size && !feof($handle)) {
						$json['next'] = str_replace('&amp;', '&', $this->url->link('tool/backup/import', 'user_token=' . $this->session->data['user_token'] . '&import=' . basename($filename) . '&position=' . $position, true));
						fclose($handle);
					} else {
						fclose($handle);
						@unlink($filename);
						$json['success'] = $this->language->get('text_success');
						$this->cache->delete('*');
					}
				} else {
					fclose($handle);
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function export() {
		// Defensive format routing: even if a stale/modified admin template posts the
		// catalog form to the SQL endpoint, never return SQL for an XLSX request.
		if (isset($this->request->post['catalog_export_format']) && (string)$this->request->post['catalog_export_format'] === 'xlsx') {
			$this->catalogExport();
			return;
		}

		$this->load->language('tool/backup');

		if (!isset($this->request->post['backup_export_format']) || (string)$this->request->post['backup_export_format'] !== 'sql') {
			$this->session->data['error'] = $this->language->get('error_export');
			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if (!isset($this->request->post['backup'])) {
			$this->session->data['error'] = $this->language->get('error_export');

			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
		} elseif (!$this->user->hasPermission('modify', 'tool/backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');

			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
		} else {
			$this->response->addheader('Pragma: public');
			$this->response->addheader('Expires: 0');
			$this->response->addheader('Content-Description: File Transfer');
			$this->response->addheader('Content-Type: application/sql; charset=UTF-8');
			$this->response->addheader('X-CodeCart-Export: database-sql');
			$this->response->addheader('Content-Disposition: attachment; filename="' . DB_DATABASE . '_' . date('Y-m-d_H-i-s', time()) . '_backup.sql"');
			$this->response->addheader('Content-Transfer-Encoding: binary');

			$this->load->model('tool/backup');
			$tmp = tempnam(DIR_UPLOAD, 'ccbackup_');
			if (!$tmp) { throw new \RuntimeException('Unable to create database backup file.'); }
			$this->model_tool_backup->backupToFile($this->request->post['backup'], $tmp);
			$this->response->addHeader('Content-Length: ' . (int)filesize($tmp));
			$this->response->setFile($tmp, true);
		}
	}	

	public function catalogExport() {
		$this->load->language('tool/backup');
		if (!isset($this->request->post['catalog_export_format']) || (string)$this->request->post['catalog_export_format'] !== 'xlsx') {
			$this->session->data['error'] = $this->language->get('error_catalog_export');
			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		if (!$this->user->hasPermission('modify', 'tool/backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$entities = isset($this->request->post['catalog_entities']) && is_array($this->request->post['catalog_entities']) ? $this->request->post['catalog_entities'] : array();
		$portable = !empty($this->request->post['portable']);
		if (!$entities) {
			$this->session->data['error'] = $this->language->get('error_catalog_entities');
			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$tmp = tempnam(DIR_UPLOAD, 'ccpexport_');
		if (!$tmp) { throw new \RuntimeException('Unable to create export file.'); }
		$filename = 'codecart_catalog_' . date('Y-m-d_H-i-s') . ($portable ? '.zip' : '.xlsx');
		$path = $tmp . ($portable ? '.zip' : '.xlsx');
		@unlink($tmp);
		$displayErrors = ini_get('display_errors');
		@ini_set('display_errors', '0');
		try {
			$transfer = new \CodeCart\Core\CatalogTransfer($this->registry);
			$transfer->export($entities, $path, $portable);
			if (!is_file($path) || (int)filesize($path) < 32) { throw new \RuntimeException($this->language->get('error_catalog_export')); }
			if (!$portable) {
				$check = new \ZipArchive();
				if ($check->open($path) !== true || $check->locateName('xl/workbook.xml') === false || $check->locateName('[Content_Types].xml') === false) {
					if ($check instanceof \ZipArchive) { @$check->close(); }
					throw new \RuntimeException('Generated XLSX archive is incomplete.');
				}
				$check->close();
			}
			while (ob_get_level() > 0) { @ob_end_clean(); }
			$this->response->addHeader('Pragma: public');
			$this->response->addHeader('Expires: 0');
			$this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate');
			$this->response->addHeader('Content-Type: ' . ($portable ? 'application/zip' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
			$this->response->addHeader('X-CodeCart-Export: ' . ($portable ? 'catalog-zip' : 'catalog-xlsx')); 
			$this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
			$this->response->addHeader('Content-Length: ' . (int)filesize($path));
			$this->response->addHeader('X-Content-Type-Options: nosniff');
			$this->response->setFile($path, true);
			$path = ''; // Response owns cleanup after streaming.
		} catch (\Throwable $e) {
			$this->log->write('CodeCart PRO catalog export failed: ' . $e->getMessage());
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true));
		} finally {
			if ($displayErrors !== false) { @ini_set('display_errors', (string)$displayErrors); }
			if (is_file($path)) { @unlink($path); }
		}
	}

	public function catalogPreview() {
		$this->load->language('tool/backup');
		$json = array();
		if (!$this->user->hasPermission('modify', 'tool/backup')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['error'] = $this->language->get('error_catalog_file');
		} elseif (!isset($this->request->files['catalog_import']) || !is_array($this->request->files['catalog_import'])) {
			$json['error'] = $this->language->get('error_catalog_file');
		} else {
			try {
				$transfer = new \CodeCart\Core\CatalogTransfer($this->registry);
				$json['preview'] = $transfer->prepareUpload($this->request->files['catalog_import']);
			} catch (\Throwable $e) {
				$json['error'] = $e->getMessage();
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function catalogImport() {
		$this->load->language('tool/backup');
		$json = array();
		if (!$this->user->hasPermission('modify', 'tool/backup')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$json['error'] = $this->language->get('error_catalog_file');
		} else {
			$token = isset($this->request->post['token']) ? (string)$this->request->post['token'] : '';
			$overwrite = !empty($this->request->post['overwrite_images']);
			try {
				$transfer = new \CodeCart\Core\CatalogTransfer($this->registry);
				$json['result'] = $transfer->importToken($token, $overwrite);
				$json['success'] = $this->language->get('text_catalog_import_success');
				$this->cache->delete('*');
			} catch (\Throwable $e) {
				$json['error'] = $e->getMessage();
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

}
