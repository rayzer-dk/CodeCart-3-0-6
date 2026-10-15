<?php
/**
 * Modifcation XML Documentation can be found here:
 *
 * https://github.com/opencart/opencart/wiki/Modification-System
 */
class ControllerMarketplaceModification extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('marketplace/modification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');
		$this->model_setting_modification->ensureCompatibilitySchema();

		$this->getList();
	}

    public function edit() {
        $this->load->language('marketplace/modification');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/modification');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

            $modification = $this->model_setting_modification->getModification($this->request->get['modification_id']);

            if ($modification) {
                $this->model_setting_modification->addModificationBackup($this->request->get['modification_id'], $modification);
            }

            $this->model_setting_modification->editModification($this->request->get['modification_id'], $this->request->post);

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



            if (!isset($this->request->get['update'])) {
                $this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
            } else {
                $this->refresh();
                $this->response->redirect($this->url->link('marketplace/modification/edit', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . $url, true));
            }
        }

        $this->getForm();
    }

    public function restore() {
        $this->load->language('marketplace/extension');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/modification');

        if (isset($this->request->get['modification_id']) AND isset($this->request->get['backup_id'])) {

            $backup = $this->model_setting_modification->getModificationBackup($this->request->get['modification_id'],$this->request->get['backup_id']);

            $url = '';

            if ($backup) {
                $this->model_setting_modification->setModificationRestore($this->request->get['modification_id'], $backup['xml']);
                $this->refresh();
                $this->response->redirect($this->url->link('marketplace/modification/edit', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . $url, true));
            } else {
                $this->response->redirect($this->url->link('marketplace/modification/edit', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . $url, true));
            }
        }

        $this->getForm();
    }

    public function clearHistory() {

        // Check user has permission
        if (!$this->user->hasPermission('modify', 'marketplace/modification')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->load->model('setting/modification');
        $this->model_setting_modification->deleteModificationBackups($this->request->get['modification_id']);

        $this->response->redirect($this->url->link('marketplace/modification/edit', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'], true));
    }

    public function download() {
        $this->load->model('setting/modification');

        $modification = $this->model_setting_modification->getModification($this->request->get['modification_id']);

        if ($modification) {
            $xml = $modification['xml'];
        } else  {
            $xml = '';
        }

        $this->response->addHeader('Content-Type: application/xml');
        $this->response->setOutput($xml);
    }

    public function upload() {
        $this->load->language('marketplace/installer');
        $json = array();

        if (!$this->user->hasPermission('modify', 'marketplace/modification')) {
            $json['error'] = $this->language->get('error_permission');
        }
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = $this->language->get('error_upload');
        }

        $this->load->model('setting/modification');
        $modification_id = isset($this->request->get['modification_id']) ? (int)$this->request->get['modification_id'] : 0;
        $modification = $this->model_setting_modification->getModification($modification_id);
        if (!$modification) {
            $json['error'] = $this->language->get('error_file');
        }

        if (!$json) {
            $file_upload = isset($this->request->files['file']) && is_array($this->request->files['file']) ? $this->request->files['file'] : array();
            $expected = (string)$modification['code'] . '.ocmod.xml';
            $check = \CodeCart\Core\UploadGuard::validateModificationXml($file_upload, $expected);
            if (!$check['ok']) {
                $code = isset($check['code']) ? (string)$check['code'] : 'upload';
                if (strpos($code, 'upload_') === 0) { $json['error'] = $this->language->get('error_' . $code); }
                elseif ($code === 'filesize') { $json['error'] = $this->language->get('error_filesize'); }
                elseif ($code === 'filetype') { $json['error'] = $this->language->get('error_filetype'); }
                elseif ($code === 'xml') { $json['error'] = $this->language->get('error_xml'); }
                else { $json['error'] = $this->language->get('error_upload'); }
            }
        }

        if (!$json) {
            $path = 'temp-' . token(32);
            $directory = DIR_UPLOAD . $path;
            if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
                $json['error'] = $this->language->get('error_file');
            }
        }

        if (!$json) {
            $file = $directory . '/install.xml';
            if (!move_uploaded_file($this->request->files['file']['tmp_name'], $file) || !is_file($file)) {
                $json['error'] = $this->language->get('error_file');
            } else {
                $json['step'] = array(
                    array('text' => $this->language->get('text_xml'), 'url' => str_replace('&amp;', '&', $this->url->link('marketplace/modification/xml', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . (int)$modification['modification_id'], true)), 'path' => $path),
                    array('text' => $this->language->get('text_remove'), 'url' => str_replace('&amp;', '&', $this->url->link('marketplace/modification/remove', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . (int)$modification['modification_id'], true)), 'path' => $path)
                );
                $json['overwrite'] = array();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function xml() {
        $this->load->language('marketplace/installer');

        $this->load->model('setting/modification');

        $modification = $this->model_setting_modification->getModification($this->request->get['modification_id']);

        $json = array();

        if (!$this->user->hasPermission('modify', 'marketplace/modification')) {
            $json['error'] = $this->language->get('error_permission');
        }

        
        if (!class_exists('DOMDocument')) {
            $json['error'] = $this->language->get('error_dom_extension');
        }

$file = DIR_UPLOAD . $this->request->post['path'] . '/install.xml';

        if (!is_file($file) || substr(str_replace('\\', '/', realpath($file)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
            $json['error'] = $this->language->get('error_file');
        }

        if (!$json) {
            $this->load->model('setting/modification');

            // If xml file just put it straight into the DB
            $xml = file_get_contents($file);

            if ($xml) {
                try {
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $previous = libxml_use_internal_errors(true);
                    $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous);
                    if (!$loaded) { throw new \RuntimeException($this->language->get('error_xml')); }

                    $name = $dom->getElementsByTagName('name')->item(0);

                    if ($name) {
                        $name = $name->nodeValue;
                    } else {
                        $name = '';
                    }

                    $code = $dom->getElementsByTagName('code')->item(0);

                    if (!$code) {
                        $json['error'] = $this->language->get('error_code');
                    }

                    $author = $dom->getElementsByTagName('author')->item(0);

                    if ($author) {
                        $author = $author->nodeValue;
                    } else {
                        $author = '';
                    }

                    $version = $dom->getElementsByTagName('version')->item(0);

                    if ($version) {
                        $version = $version->nodeValue;
                    } else {
                        $version = '';
                    }

                    $link = $dom->getElementsByTagName('link')->item(0);

                    if ($link) {
                        $link = $link->nodeValue;
                    } else {
                        $link = '';
                    }

                    $modification_data = array(
                        'name'    => $name,
                        'code'    => $code,
                        'author'  => $author,
                        'version' => $version,
                        'link'    => $link,
                        'xml'     => $xml,
                        'status'  => 1
                    );

                    if (!$json) {
                        $this->model_setting_modification->editModification($modification['modification_id'], $modification_data);
                    }
                } catch(Exception $exception) {
                    $json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function remove() {
        $this->load->language('marketplace/modification');

        $json = array();

        if (!$this->user->hasPermission('modify', 'marketplace/modification')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $directory = DIR_UPLOAD . $this->request->post['path'];

        if (!is_dir($directory) || substr(str_replace('\\', '/', realpath($directory)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
            $json['error'] = $this->language->get('error_directory');
        }

        if (!$json) {
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

            if (file_exists($directory)) {
                rmdir($directory);
            }

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


	public function delete() {
		$this->load->language('marketplace/modification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');

		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $modification_id) {
				$this->model_setting_modification->deleteModification($modification_id);
                $this->model_setting_modification->deleteModificationBackups($modification_id);
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

			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function refresh($data = array()) {
		$this->load->language('marketplace/modification');

		$is_ajax = isset($this->request->get['ajax']) && (string)$this->request->get['ajax'] === '1';
		if ($is_ajax) {
			unset($this->session->data['success'], $this->session->data['error_warning']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');
		$this->model_setting_modification->ensureCompatibilitySchema();
        $this->load->model('design/theme');

		if ($this->validate()) {
            // DOM is required for OCMOD XML parsing. Check before maintenance mode
            // or deleting the currently working modification cache.
            if (!class_exists('DOMDocument')) {
                $message = $this->language->get('error_dom_extension');
                $this->session->data['error_warning'] = $message;

                if ($is_ajax) {
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode(array('error' => $message)));
                    return;
                }

                // A direct click on Refresh must return to the list instead of an
                // empty response. Internal edit/restore calls can simply abort.
                if (isset($this->request->get['route']) && $this->request->get['route'] === 'marketplace/modification/refresh') {
                    $this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true));
                    return;
                }
                return false;
            }

			// Clear log before refresh modifications
			$handle = fopen(DIR_LOGS . 'ocmod.log', 'w+');
			fclose($handle);

			// Just before files are deleted, if config settings say maintenance mode is off then turn it on
			$maintenance = $this->config->get('config_maintenance');

			$this->load->model('setting/setting');

			// Compatibility analysis is read-only. Keep the storefront available while the
			// OCMOD plan is being built and enter maintenance only for the short publish phase.

			// Build a source list with modification IDs so compatibility errors can be
			// attributed to the exact extension instead of only appearing in ocmod.log.
			$sources = array();

			$core_xml = file_get_contents(DIR_SYSTEM . 'modification.xml');
			$sources[] = array('modification_id' => 0, 'code' => '__core__', 'name' => 'Core modification.xml', 'xml' => $core_xml, 'source' => 'core');

			$developer_files = glob(DIR_SYSTEM . '*.ocmod.xml');
			if ($developer_files) {
				foreach ($developer_files as $developer_file) {
					$sources[] = array('modification_id' => 0, 'code' => basename($developer_file), 'name' => basename($developer_file), 'xml' => file_get_contents($developer_file), 'source' => 'system');
				}
			}

			$results = $this->model_setting_modification->getModifications();
			foreach ($results as $result) {
				if ($result['status']) {
					$sources[] = array('modification_id' => (int)$result['modification_id'], 'code' => (string)$result['code'], 'name' => (string)$result['name'], 'xml' => (string)$result['xml'], 'source' => 'database');
				}
			}

			$modification = array();
			$original = array();
			$build_errors = 0;
			$failed_modifications = 0;
			$compatibility = array();
			$log[] = '=== CodeCart PRO OCMOD refresh ===';
			$log[] = 'DATE: ' . date('c');
			$log[] = 'CORE: ' . (defined('CODECART_BUILD') ? (string)CODECART_BUILD : 'unknown') . '; PACKAGE: ' . (defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : 'unknown');
			$log[] = 'SOURCES: ' . count($sources);
			$log[] = '================================================================';

			foreach ($sources as $source) {
				$xml = isset($source['xml']) ? (string)$source['xml'] : '';
				$current_id = isset($source['modification_id']) ? (int)$source['modification_id'] : 0;
				$current_code = isset($source['code']) ? (string)$source['code'] : '';
				$current_name = isset($source['name']) ? (string)$source['name'] : $current_code;
				$source_type = isset($source['source']) ? (string)$source['source'] : 'database';
				$source_key = $current_id > 0 ? (string)$current_id : $source_type . ':' . $current_code;
				$source_file = $source_type === 'core' ? 'system/modification.xml' : ($source_type === 'system' ? 'system/' . basename($current_code) : '');
				$mod_failed = false;
				$mod_issue = '';
				$mod_issue_file = '';
				$skipped_operations = 0;
				$ignored_operations = 0;
				$applied_matches = 0;
				$declared_operations = 0;
				$target_files_seen = array();
				$changed_files_for_mod = array();
				$issues = array();
				$operation_index = 0;

				if ($xml === '') {
					$build_errors++;
					$failed_modifications++;
					$mod_issue = 'Empty or unreadable modification XML';
					$issues[] = array('severity'=>'error','operation'=>0,'file'=>$source_file,'reason'=>$mod_issue,'search'=>'');
					$compatibility[$source_key] = array('state'=>'error','name'=>$current_name,'code'=>$current_code,'source'=>$source_type,'source_file'=>$source_file,'message'=>$mod_issue,'file'=>$source_file,'declared_operations'=>0,'applied_matches'=>0,'ignored_operations'=>0,'skipped_operations'=>0,'target_files'=>0,'changed_files'=>array(),'changed_files_count'=>0,'rolled_back'=>true,'issues'=>$issues,'issues_count'=>1,'warning_issues_count'=>0,'info_issues_count'=>0,'checked_at'=>date('c'));
					$log[] = 'MOD: ' . $current_name . ($current_id > 0 ? ' [ID ' . $current_id . ']' : '');
					$log[] = 'ERROR: ' . $mod_issue;
					$log[] = 'RESULT: NOT APPLIED - empty XML source';
					$log[] = '----------------------------------------------------------------';
					continue;
				}

				$dom = new DOMDocument('1.0', 'UTF-8');
				$dom->preserveWhiteSpace = false;
				$previous = libxml_use_internal_errors(true);
				$loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
				libxml_clear_errors();
				libxml_use_internal_errors($previous);

				if (!$loaded) {
					$build_errors++; $mod_issue = 'Invalid modification XML';
					$log[] = 'MOD: ' . $current_name; $log[] = 'ERROR: ' . $mod_issue;
					$failed_modifications++;
					$issues[] = array('severity'=>'error','operation'=>0,'file'=>$source_file,'reason'=>$mod_issue,'search'=>'');
					$compatibility[$source_key] = array('state'=>'error','name'=>$current_name,'code'=>$current_code,'source'=>$source_type,'source_file'=>$source_file,'message'=>$mod_issue,'file'=>$source_file,'declared_operations'=>0,'applied_matches'=>0,'ignored_operations'=>0,'skipped_operations'=>0,'target_files'=>0,'changed_files'=>array(),'changed_files_count'=>0,'rolled_back'=>true,'issues'=>$issues,'issues_count'=>count($issues),'warning_issues_count'=>0,'info_issues_count'=>0,'checked_at'=>date('c'));
					$log[] = 'RESULT: INCOMPATIBLE - modification was not applied'; $log[] = '----------------------------------------------------------------';
					continue;
				}

				$name_node = $dom->getElementsByTagName('name')->item(0);
				$code_node = $dom->getElementsByTagName('code')->item(0);
				if ($name_node && trim((string)$name_node->textContent) !== '') { $current_name = trim((string)$name_node->textContent); }
				if ($code_node && trim((string)$code_node->textContent) !== '') { $current_code = trim((string)$code_node->textContent); }
				$log[] = 'MOD: ' . $current_name . ($current_id > 0 ? ' [ID ' . $current_id . ']' : '');

				// Atomic per-extension snapshot. A blocking error rolls back the entire OCMOD,
				// preventing half-applied changes from breaking the admin or storefront.
				$recovery = $modification;
				$theme = $this->config->get('config_theme') == 'default' ? $this->config->get('theme_default_directory') : $this->config->get('config_theme');
				$store_id = (int)$this->config->get('config_store_id');
				$modification_element = $dom->getElementsByTagName('modification')->item(0);

				if (!$modification_element) {
					$build_errors++; $mod_failed = true; $mod_issue = 'Missing <modification> element'; $issues[] = array('severity'=>'error','operation'=>0,'file'=>'','reason'=>$mod_issue,'search'=>''); $log[] = 'ERROR: ' . $mod_issue;
				} else {
					$file_nodes = $modification_element->getElementsByTagName('file');
					foreach ($file_nodes as $count_file_node) {
						$declared_operations += $count_file_node->getElementsByTagName('operation')->length;
					}
					foreach ($file_nodes as $file_node) {
						$operations = $file_node->getElementsByTagName('operation');
						$path_patterns = explode('|', str_replace('\\', '/', $file_node->getAttribute('path')));
						$file_node_matched = false;
						foreach ($path_patterns as $path_pattern) {
							$path = '';
							if (substr($path_pattern,0,7) == 'catalog') { $path = DIR_CATALOG . substr($path_pattern,8); }
							if (substr($path_pattern,0,5) == 'admin') { $path = DIR_APPLICATION . substr($path_pattern,6); }
							if (substr($path_pattern,0,6) == 'system') { $path = DIR_SYSTEM . substr($path_pattern,7); }
							if (!$path) { continue; }
							$target_files = safe_glob($path, GLOB_BRACE);
							if (!$target_files) { continue; }
							$file_node_matched = true;

							foreach ($target_files as $target_file) {
								$key = '';
								if (substr($target_file,0,strlen(DIR_CATALOG)) == DIR_CATALOG) { $key = 'catalog/' . substr($target_file,strlen(DIR_CATALOG)); }
								if (substr($target_file,0,strlen(DIR_APPLICATION)) == DIR_APPLICATION) { $key = 'admin/' . substr($target_file,strlen(DIR_APPLICATION)); }
								if (substr($target_file,0,strlen(DIR_SYSTEM)) == DIR_SYSTEM) { $key = 'system/' . substr($target_file,strlen(DIR_SYSTEM)); }
								if ($key === '') { continue; }
								$target_files_seen[$key] = true;

								if (!isset($modification[$key])) {
									$content = false; $template_pos = strpos($key, '/view/template/');
									if ($template_pos !== false && substr($key,-5) === '.twig') {
										$route = substr($key,$template_pos + strlen('/view/template/'),-5);
										$theme_info = $this->model_design_theme->getTheme($store_id,$theme,$route);
										if ($theme_info) { $content = html_entity_decode($theme_info['code'], ENT_QUOTES, 'UTF-8'); }
									}
									if ($content === false) { $content = file_get_contents($target_file); }
									if ($content === false) {
										$build_errors++; $mod_failed=true; $mod_issue='Unable to read target file'; $mod_issue_file=$key; $issues[] = array('severity'=>'error','operation'=>$operation_index,'file'=>$key,'reason'=>$mod_issue,'search'=>''); $log[]='ERROR: '.$mod_issue.' '.$key; break 3;
									}
									$normalized = preg_replace('~\r?\n~', "\n", $content);
									$modification[$key]=$normalized; $original[$key]=$normalized; $log[]=PHP_EOL.'FILE: '.$key;
								} else { $log[] = PHP_EOL . 'FILE: (sub modification) ' . $key; }

								foreach ($operations as $operation) {
									$operation_index++;
									$error = $operation->getAttribute('error');
									$ignoreif = $operation->getElementsByTagName('ignoreif')->item(0);
									if ($ignoreif) {
										if ($ignoreif->getAttribute('regex') != 'true') {
											if (strpos($modification[$key], $ignoreif->textContent) !== false) { $ignored_operations++; $log[]='IGNORED: condition already satisfied'; continue; }
										} else {
											$ignore_match=@preg_match($ignoreif->textContent,$modification[$key]);
											if ($ignore_match===1) { $ignored_operations++; $log[]='IGNORED: condition already satisfied (regex)'; continue; }
										}
									}

									$status=false;
									$search_element=$operation->getElementsByTagName('search')->item(0);
									$add_element=$operation->getElementsByTagName('add')->item(0);
									if (!$search_element || !$add_element) { $build_errors++; $mod_failed=true; $mod_issue='Invalid operation: missing <search> or <add>'; $mod_issue_file=$key; $issues[] = array('severity'=>'error','operation'=>$operation_index,'file'=>$key,'reason'=>$mod_issue,'search'=>''); $log[]='ERROR: '.$mod_issue; break 4; }

									if ($search_element->getAttribute('regex') != 'true') {
										$search=$search_element->textContent; $trim=$search_element->getAttribute('trim'); $index=$search_element->getAttribute('index');
										if (!$trim || $trim=='true') { $search=trim($search); }
										$add=$add_element->textContent; $trim=$add_element->getAttribute('trim'); $position=$add_element->getAttribute('position'); $offset=$add_element->getAttribute('offset');
										$offset=$offset===''?0:(int)$offset; if ($trim=='true') { $add=trim($add); }
										$log[]='CODE: '.$search; $indexes=$index!==''?explode(',',$index):array(); $i=0; $lines=explode("\n",$modification[$key]);
										for ($line_id=0;$line_id<count($lines);$line_id++) {
											$line=$lines[$line_id]; $match=false;
											if (stripos($line,$search)!==false) { if (!$indexes || in_array($i,$indexes)) { $match=true; } $i++; }
											if ($match) {
												switch ($position) {
													default: case 'replace': if ($offset<0) { array_splice($lines,$line_id+$offset,abs($offset)+1,array(str_replace($search,$add,$line))); $line_id-=$offset; } else { array_splice($lines,$line_id,$offset+1,array(str_replace($search,$add,$line))); } break;
													case 'before': $new_lines=explode("\n",$add); array_splice($lines,$line_id-$offset,0,$new_lines); $line_id+=count($new_lines); break;
													case 'after': $new_lines=explode("\n",$add); array_splice($lines,($line_id+1)+$offset,0,$new_lines); $line_id+=count($new_lines); break;
												}
												$log[]='LINE: '.$line_id; $status=true;
											}
										}
										$modification[$key]=implode("\n",$lines);
									} else {
										$search=trim($search_element->textContent); $limit=$search_element->getAttribute('limit'); $replace=trim($add_element->textContent); $limit=$limit?(int)$limit:-1; $match=array();
										$match_count=@preg_match_all($search,$modification[$key],$match,PREG_OFFSET_CAPTURE);
										if ($match_count===false) { $build_errors++; $mod_failed=true; $mod_issue='Invalid regular expression'; $mod_issue_file=$key; $issues[] = array('severity'=>'error','operation'=>$operation_index,'file'=>$key,'reason'=>$mod_issue,'search'=>$this->shortCompatibilityMessage($search)); $log[]='ERROR: '.$mod_issue.' in '.$key; break 4; }
										if ($limit>0) { $match[0]=array_slice($match[0],0,$limit); }
										if (!empty($match[0])) { $log[]='REGEX: '.$search; foreach ($match[0] as $match_item) { $log[]='LINE: '.(substr_count(substr($modification[$key],0,$match_item[1]),"\n")+1); } $status=true; }
										$replaced=@preg_replace($search,$replace,$modification[$key],$limit);
										if ($replaced===null) { $build_errors++; $mod_failed=true; $mod_issue='Regular expression replacement failed'; $mod_issue_file=$key; $issues[] = array('severity'=>'error','operation'=>$operation_index,'file'=>$key,'reason'=>$mod_issue,'search'=>$this->shortCompatibilityMessage($search)); $log[]='ERROR: '.$mod_issue.' in '.$key; break 4; }
										$modification[$key]=$replaced;
									}

									if ($status) { $applied_matches++; }

									if (!$status) {
										$search_preview = $search_element ? $this->shortCompatibilityMessage((string)$search_element->textContent) : '';
										if ($this->isCodeCartCompatibilitySatisfied($current_code, $key, (string)$search_element->textContent)) {
											$ignored_operations++;
											$issues[] = array('severity'=>'info','operation'=>$operation_index,'file'=>$key,'reason'=>'Satisfied by CodeCart compatibility layer','search'=>$search_preview);
											$log[]='COMPATIBILITY SATISFIED [OP '.$operation_index.']: CodeCart layer provides equivalent behavior';
											continue;
										}
										if ($error=='skip') {
											$skipped_operations++;
											$issues[] = array('severity'=>$this->isLanguageTargetFile($key) ? 'info' : 'warning','operation'=>$operation_index,'file'=>$key,'reason'=>'Optional search code not found','search'=>$search_preview);
											$log[]='SKIPPED OPTIONAL [OP '.$operation_index.']: search code not found';
											continue;
										}
										$build_errors++; $mod_failed=true; $mod_issue=$error=='abort'?'Required search code not found (abort)':'Required search code not found'; $mod_issue_file=$key;
										$issues[] = array('severity'=>'error','operation'=>$operation_index,'file'=>$key,'reason'=>$mod_issue,'search'=>$search_preview);
										$log[]='NOT FOUND [OP '.$operation_index.'] - ENTIRE MODIFICATION ROLLED BACK!'; break 4;
									}
								}
							}
						}
						if (!$file_node_matched) {
							$required_target=false; $optional_count=0;
							foreach ($operations as $operation) { if ($operation->getAttribute('error') === 'skip') { $optional_count++; } else { $required_target=true; } }
							$missing_target = implode(' | ',$path_patterns);
							if ($required_target) { $build_errors++; $mod_failed=true; $mod_issue='Required target file not found'; $mod_issue_file=$missing_target; $issues[] = array('severity'=>'error','operation'=>0,'file'=>$missing_target,'reason'=>$mod_issue,'search'=>''); $log[]='ERROR: '.$mod_issue.' '.$mod_issue_file; break; }
							if ($optional_count>0) { $skipped_operations += $optional_count; $issues[] = array('severity'=>$this->isLanguageTargetFile($missing_target) ? 'info' : 'warning','operation'=>0,'file'=>$missing_target,'reason'=>$optional_count.' optional operation(s): target file not found','search'=>''); $log[]='TARGET NOT FOUND - '.$optional_count.' OPTIONAL OPERATION(S) SKIPPED: '.$missing_target; }
						}
					}
				}

				if (!$mod_failed) {
					foreach ($modification as $changed_key=>$changed_value) {
						$before=array_key_exists($changed_key,$recovery)?$recovery[$changed_key]:(isset($original[$changed_key])?$original[$changed_key]:null);
						if ($before===$changed_value || substr($changed_key,-4)!=='.php') { continue; }
						try { token_get_all($changed_value,TOKEN_PARSE); } catch (\Throwable $e) {
							$build_errors++; $mod_failed=true; $mod_issue='Generated PHP syntax is invalid: '.$this->shortCompatibilityMessage($e->getMessage()); $mod_issue_file=$changed_key; $issues[] = array('severity'=>'error','operation'=>0,'file'=>$changed_key,'reason'=>$mod_issue,'search'=>''); $log[]='ERROR: '.$mod_issue.' in '.$changed_key; break;
						}
					}
				}

				if ($mod_failed) {
					$modification = $recovery;
					$failed_modifications++;
					$log[] = 'RESULT: NOT APPLIED - entire modification rolled back';
				} else {
					foreach ($modification as $changed_key => $changed_value) {
						$before = array_key_exists($changed_key, $recovery) ? $recovery[$changed_key] : (isset($original[$changed_key]) ? $original[$changed_key] : null);
						if ($before !== $changed_value) { $changed_files_for_mod[$changed_key] = true; }
					}
					$log[] = 'RESULT: ' . ($skipped_operations > 0 ? 'APPLIED WITH OPTIONAL SKIPS' : 'APPLIED');
				}

				$changed_file_list = array_keys($changed_files_for_mod);
				$log[] = 'SUMMARY: declared=' . $declared_operations . '; applied_matches=' . $applied_matches . '; ignored=' . $ignored_operations . '; optional_skipped=' . $skipped_operations . '; target_files=' . count($target_files_seen) . '; changed_files=' . count($changed_file_list);
				if ($changed_file_list) {
					$visible_changed = array_slice($changed_file_list, 0, 20);
					$log[] = 'CHANGED: ' . implode(', ', $visible_changed) . (count($changed_file_list) > count($visible_changed) ? ' +' . (count($changed_file_list) - count($visible_changed)) : '');
				}

				{
					$compatibility[$source_key] = array(
						'source' => $source_type,
						'source_file' => $source_file,
						'state' => $mod_failed ? 'error' : ($this->hasCompatibilityWarnings($issues) ? 'warning' : 'ok'),
						'name' => $current_name,
						'code' => $current_code,
						'message' => $mod_failed ? $this->shortCompatibilityMessage($mod_issue) : '',
						'file' => $mod_failed ? $mod_issue_file : '',
						'declared_operations' => $declared_operations,
						'applied_matches' => $applied_matches,
						'ignored_operations' => $ignored_operations,
						'skipped_operations' => $skipped_operations,
						'target_files' => count($target_files_seen),
						'changed_files' => $mod_failed ? array() : array_slice($changed_file_list, 0, 50),
						'changed_files_count' => $mod_failed ? 0 : count($changed_file_list),
						'rolled_back' => $mod_failed,
						'issues' => array_slice($issues, 0, 50),
						'issues_count' => count($issues),
						'checked_at' => date('c')
					);
				}
				$log[]='----------------------------------------------------------------';
			}

			$this->writeModificationCompatibilityState($compatibility);

			// Log
			$ocmod = new Log('ocmod.log');
			$ocmod->write(implode("\n", $log));


            // The plan is complete. Enter maintenance only for the filesystem publish window.
            $this->model_setting_setting->editSettingValue('config', 'config_maintenance', true);

            // Publish only after the complete modification plan has been built. The previous
            // working tree remains active while compatibility is analysed. Immediately before
            // publishing, remove the build marker first; if filesystem writing then fails,
            // startup falls back to original Core files instead of loading a half-built tree.
            $build_marker_ok = false;
            $publish_error = '';

            try {
                $build_marker = rtrim(DIR_MODIFICATION, '/\\') . '/.codecart-build';
                if (is_file($build_marker) && !@unlink($build_marker)) {
                    throw new \RuntimeException('Unable to invalidate previous OCMOD build marker');
                }

                \CodeCart\Core\FontAwesomeManager::invalidateCache();

                $files = array();
                $path = array(DIR_MODIFICATION . '*');
                while (count($path) != 0) {
                    $next = array_shift($path);
                    $matches = glob($next);
                    if (!$matches) { continue; }
                    foreach ($matches as $file) {
                        if (is_dir($file)) { $path[] = $file . '/*'; }
                        $files[] = $file;
                    }
                }
                rsort($files);
                foreach ($files as $file) {
                    if ($file == DIR_MODIFICATION . 'index.html') { continue; }
                    if (is_file($file) && !@unlink($file) && is_file($file)) {
                        throw new \RuntimeException('Unable to remove old modification file: ' . $file);
                    }
                    if (is_dir($file) && !@rmdir($file) && is_dir($file)) {
                        throw new \RuntimeException('Unable to remove old modification directory: ' . $file);
                    }
                }

                // Write all generated modification files. Any filesystem failure keeps the
                // build marker absent, therefore startup uses original Core files safely.
                foreach ($modification as $key => $value) {
                    if (!isset($original[$key]) || $original[$key] == $value) { continue; }

                    $target = DIR_MODIFICATION . $key;
                    $directory = dirname($target);
                    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
                        throw new \RuntimeException('Unable to create modification directory: ' . $directory);
                    }

                    if (@file_put_contents($target, $value, LOCK_EX) === false) {
                        throw new \RuntimeException('Unable to write modification file: ' . $target);
                    }
                }

                // Reaching this point means the generated tree is complete. One incompatible
                // extension does not disable every other successfully generated modification.
                $build_marker_ok = true;
                if (defined('CODECART_BUILD')) {
                    $build_marker_ok = $this->writeBuildMarker(defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : (string)CODECART_BUILD);
                }
                if (!$build_marker_ok) {
                    throw new \RuntimeException('Unable to write OCMOD build marker');
                }
            } catch (\Throwable $publish_exception) {
                $build_marker_ok = false;
                $publish_error = $this->shortCompatibilityMessage($publish_exception->getMessage());
                $publish_log = new Log('ocmod.log');
                $publish_log->write('PUBLISH ERROR: ' . $publish_error . ' - generated OCMOD tree is inactive; original Core files will be used.');
            }

            \CodeCart\Core\FontAwesomeManager::invalidateCache();

            try {
                if ($build_marker_ok) {
                    \CodeCart\Core\OcmodState::markClean(array(
                        'error' => $failed_modifications,
                        'build_errors' => $build_errors,
                        'sources' => count($sources)
                    ));
                } else {
                    \CodeCart\Core\OcmodState::markDirty('refresh_publish_failed', array('build_errors' => $build_errors));
                }
            } catch (\Throwable $e) {
                // OCMOD state tracking is advisory and cannot invalidate a successful refresh.
            }

			// Maintance mode back to original settings
			$this->model_setting_setting->editSettingValue('config', 'config_maintenance', $maintenance);

			if (!$build_marker_ok) {
				$this->error['warning'] = $publish_error !== '' ? sprintf($this->language->get('error_publish_failed'), $publish_error) : $this->language->get('error_build_marker');
			} elseif ($build_errors > 0) {
				$this->error['warning'] = sprintf($this->language->get('warning_refresh_partial'), $build_errors, $failed_modifications);
			} elseif (!$is_ajax) {
				// A normal page refresh gets a success message only when all operations
				// were applied without blocking errors.
				$this->session->data['success'] = $this->language->get('text_success');
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

			//$this->response->redirect($this->url->link(!empty($data['redirect']) ? $data['redirect'] : 'marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		if ($is_ajax) {
			$json = array();

			if (!empty($this->error['warning'])) {
				$json['error'] = $this->error['warning'];
			} else {
				$json['success'] = $this->language->get('text_success');
			}

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$this->getList();
	}



    public function details() {
        $this->load->language('marketplace/modification');
        $json = array();

        if (!$this->user->hasPermission('access', 'marketplace/modification')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $modification_id = isset($this->request->get['modification_id']) ? (int)$this->request->get['modification_id'] : 0;
        if (!$json && $modification_id < 1) {
            $json['error'] = $this->language->get('error_not_found');
        }

        if (!$json) {
            $this->load->model('setting/modification');
            $modification = $this->model_setting_modification->getModification($modification_id);
            if (!$modification) {
                $json['error'] = $this->language->get('error_not_found');
            } else {
                $state = $this->readModificationCompatibilityState();
                $map = isset($state['modifications']) && is_array($state['modifications']) ? $state['modifications'] : array();
                $details = isset($map[(string)$modification_id]) && is_array($map[(string)$modification_id]) ? $map[(string)$modification_id] : array();
                $json['name'] = (string)$modification['name'];
                $json['code'] = (string)$modification['code'];
                $json['enabled'] = !empty($modification['status']);
                $json['details'] = $details;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


	private function isLanguageTargetFile($path) {
		$path = str_replace('\\', '/', strtolower((string)$path));
		return strpos($path, '/language/') !== false || strpos($path, '/language/') === 0 || strpos($path, 'language/') === 0;
	}

	private function countCompatibilityIssues(array $issues, $severity) {
		$count = 0;
		foreach ($issues as $issue) {
			if (is_array($issue) && isset($issue['severity']) && $issue['severity'] === $severity) { $count++; }
		}
		return $count;
	}

	private function hasCompatibilityWarnings(array $issues) {
		return $this->countCompatibilityIssues($issues, 'warning') > 0;
	}

	private function shortCompatibilityMessage($message) {
		$message = trim(preg_replace('/\s+/u', ' ', (string)$message));
		return function_exists('mb_substr') ? mb_substr($message, 0, 240, 'UTF-8') : substr($message, 0, 240);
	}

	private function isCodeCartCompatibilitySatisfied($code, $file, $search) {
		$code = trim((string)$code);
		$file = str_replace('\\', '/', (string)$file);
		$search = trim((string)$search);

		$framework = $this->registry->get('codecart_compatibility_framework');
		if ($framework && method_exists($framework, 'ocmodSatisfied') && $framework->ocmodSatisfied($code, $file, $search)) {
			return true;
		}

		if ($code === 'UniShop2 fix') {
			$contracts = array(
				'admin/controller/marketplace/install.php' => array('\'image/catalog/\''),
				'catalog/controller/event/theme.php' => array('$twig = new'),
				'system/library/template/twig.php' => array(
					'$this->twig = new',
					'$loader = new \\Twig\\Loader\\ArrayLoader(array($filename . \' .twig\' => $code));'
				)
			);
			return isset($contracts[$file]) && in_array($search, $contracts[$file], true);
		}

		return false;
	}

	private function modificationCompatibilityPath() {
		return rtrim(DIR_STORAGE, '/\\') . '/codecart/modification_compatibility.json';
	}

	private function writeModificationCompatibilityState(array $modifications) {
		$path = $this->modificationCompatibilityPath();
		$directory = dirname($path);
		if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) { return false; }

		$summary = array('total'=>0,'ok'=>0,'warning'=>0,'error'=>0,'declared_operations'=>0,'applied_matches'=>0,'ignored_operations'=>0,'skipped_operations'=>0,'target_files'=>0,'changed_files'=>0,'issues'=>0);
		foreach ($modifications as $item) {
			if (!is_array($item)) { continue; }
			$summary['total']++;
			$state = isset($item['state']) ? (string)$item['state'] : '';
			if (isset($summary[$state])) { $summary[$state]++; }
			foreach (array('declared_operations','applied_matches','ignored_operations','skipped_operations','target_files') as $metric) {
				$summary[$metric] += isset($item[$metric]) ? (int)$item[$metric] : 0;
			}
			$summary['changed_files'] += isset($item['changed_files_count']) ? (int)$item['changed_files_count'] : 0;
			$summary['issues'] += isset($item['issues_count']) ? (int)$item['issues_count'] : 0;
		}

		$payload = array(
			'generated_at'=>date('c'),
			'core_build'=>defined('CODECART_BUILD') ? (string)CODECART_BUILD : '',
			'package_build'=>defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : '',
			'summary'=>$summary,
			'modifications'=>$modifications
		);
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($json === false) { return false; }
		$temp = $path . '.tmp-' . bin2hex(random_bytes(6));
		if (file_put_contents($temp, $json, LOCK_EX) === false) { @unlink($temp); return false; }
		@chmod($temp, 0644);
		if (!@rename($temp, $path)) { @unlink($temp); return false; }
		return true;
	}

	private function readModificationCompatibilityState() {
		$path=$this->modificationCompatibilityPath(); if (!is_file($path)||!is_readable($path)) { return array(); }
		$json=file_get_contents($path); if ($json===false||$json==='') { return array(); }
		$data=json_decode($json,true); return is_array($data)?$data:array();
	}

	private function writeBuildMarker($build) {
		$marker = rtrim(DIR_MODIFICATION, '/\\') . '/.codecart-build';
		$temp = $marker . '.tmp-' . bin2hex(random_bytes(6));

		if (file_put_contents($temp, (string)$build, LOCK_EX) === false) {
			@unlink($temp);
			return false;
		}

		@chmod($temp, 0644);

		if (!@rename($temp, $marker)) {
			@unlink($temp);
			return false;
		}

		return true;
	}

	public function clear() {
		$this->load->language('marketplace/modification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');

		if ($this->validate()) {
			$build_marker = rtrim(DIR_MODIFICATION, '/\\') . '/.codecart-build';
			if (is_file($build_marker)) {
				unlink($build_marker);
			}

			\CodeCart\Core\FontAwesomeManager::invalidateCache();

			$files = array();

			// Make path into an array
			$path = array(DIR_MODIFICATION . '*');

			// While the path array is still populated keep looping through
			while (count($path) != 0) {
				$next = array_shift($path);

				foreach (glob($next) as $file) {
					// If directory add to path array
					if (is_dir($file)) {
						$path[] = $file . '/*';
					}

					// Add the file to the files to be deleted array
					$files[] = $file;
				}
			}

			// Reverse sort the file array
			rsort($files);

			// Clear all modification files
			foreach ($files as $file) {
				if ($file != DIR_MODIFICATION . 'index.html') {
					// If file just delete
					if (is_file($file)) {
						unlink($file);

					// If directory use the remove directory function
					} elseif (is_dir($file)) {
						rmdir($file);
					}
				}
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

			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function enable() {
		$this->load->language('marketplace/modification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');

		if (isset($this->request->get['modification_id']) && $this->validate()) {
			$this->model_setting_modification->enableModification($this->request->get['modification_id']);

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

			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function disable() {
		$this->load->language('marketplace/modification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');

		if (isset($this->request->get['modification_id']) && $this->validate()) {
			$this->model_setting_modification->disableModification($this->request->get['modification_id']);

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

			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function clearlog() {
		$this->load->language('marketplace/modification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/modification');

		if ($this->validate()) {
			$handle = fopen(DIR_LOGS . 'ocmod.log', 'w+');

			fclose($handle);

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

			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}


	public function downloadlog() {
		$this->load->language('marketplace/modification');
		if (!$this->user->hasPermission('access', 'marketplace/modification')) {
			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$file = DIR_LOGS . 'ocmod.log';
		if (!is_file($file) || !is_readable($file)) {
			$this->session->data['error_warning'] = $this->language->get('error_file');
			$this->response->redirect($this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->response->addHeader('Pragma: public');
		$this->response->addHeader('Expires: 0');
		$this->response->addHeader('Content-Description: File Transfer');
		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="ocmod_' . date('Y-m-d_H-i-s') . '.log"');
		$this->response->setOutput((string)file_get_contents($file));
	}

	private function compactOcmodLog($raw) {
		$raw = str_replace(array("\r\n", "\r"), "\n", (string)$raw);
		$out = array();
		foreach (explode("\n", $raw) as $line) {
			$trim = trim($line);
			if ($trim === '') { continue; }
			if (preg_match('/^(===|DATE:|CORE:|SOURCES:|MOD:|RESULT:|SUMMARY:|ERROR:|PUBLISH ERROR:|SKIPPED OPTIONAL|TARGET NOT FOUND|NOT FOUND)/i', $trim)) {
				$out[] = $line;
			}
		}
		return implode("\n", $out);
	}

	protected function getList() {
		// Pass the complete active language dictionary to the list template.
		// The template uses tab/button/text keys directly; loading the language file
		// into Language alone does not make those keys Twig variables.
		$data = $this->load->language('marketplace/modification');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = max(1, (int)$this->request->get['page']);
		} else {
			$page = 1;
		}

		$compatibility_filter = isset($this->request->get['compatibility_filter']) ? (string)$this->request->get['compatibility_filter'] : '';
		$allowed_filters = array('', 'active', 'ok', 'warning', 'error', 'issues');
		if (!in_array($compatibility_filter, $allowed_filters, true)) { $compatibility_filter = ''; }

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
		if ($compatibility_filter !== '') {
			$url .= '&compatibility_filter=' . rawurlencode($compatibility_filter);
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['refresh'] = $this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['clear'] = $this->url->link('marketplace/modification/clear', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('marketplace/modification/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['details_url'] = str_replace('&amp;', '&', $this->url->link('marketplace/modification/details', 'user_token=' . $this->session->data['user_token'], true));

		$data['modifications'] = array();

		$limit = max(1, (int)$this->config->get('config_limit_admin'));
		$compatibility_state = $this->readModificationCompatibilityState();
		$compatibility_map = isset($compatibility_state['modifications']) && is_array($compatibility_state['modifications']) ? $compatibility_state['modifications'] : array();

		if ($compatibility_filter !== '') {
			$all_results = $this->model_setting_modification->getModifications(array('sort' => $sort, 'order' => $order));
			$filtered_results = array();
			foreach ($all_results as $candidate) {
				$id = (string)$candidate['modification_id'];
				$item = isset($compatibility_map[$id]) && is_array($compatibility_map[$id]) ? $compatibility_map[$id] : array();
				$state = !empty($candidate['status']) && isset($item['state']) ? (string)$item['state'] : (!empty($candidate['status']) ? 'unchecked' : 'disabled');
				$match = false;
				if ($compatibility_filter === 'active') { $match = !empty($candidate['status']); }
				elseif ($compatibility_filter === 'issues') { $match = !empty($candidate['status']) && ($state === 'warning' || $state === 'error'); }
				else { $match = !empty($candidate['status']) && $state === $compatibility_filter; }
				if ($match) { $filtered_results[] = $candidate; }
			}
			$modification_total = count($filtered_results);
			$max_page = max(1, (int)ceil($modification_total / $limit));
			if ($page > $max_page) { $page = $max_page; }
			$results = array_slice($filtered_results, ($page - 1) * $limit, $limit);
		} elseif ($sort === 'compatibility') {
			$all_results = $this->model_setting_modification->getModifications(array('sort' => 'name', 'order' => 'ASC'));
			$stateRank = array('error'=>0, 'warning'=>1, 'unchecked'=>2, 'disabled'=>3, 'ok'=>4);
			usort($all_results, function($a, $b) use ($compatibility_map, $stateRank) {
				$ia = isset($compatibility_map[(string)$a['modification_id']]) && is_array($compatibility_map[(string)$a['modification_id']]) ? $compatibility_map[(string)$a['modification_id']] : array();
				$ib = isset($compatibility_map[(string)$b['modification_id']]) && is_array($compatibility_map[(string)$b['modification_id']]) ? $compatibility_map[(string)$b['modification_id']] : array();
				$sa = !empty($a['status']) ? (string)($ia['state'] ?? 'unchecked') : 'disabled';
				$sb = !empty($b['status']) ? (string)($ib['state'] ?? 'unchecked') : 'disabled';
				$ra = $stateRank[$sa] ?? 9; $rb = $stateRank[$sb] ?? 9;
				if ($ra === $rb) return strcasecmp((string)$a['name'], (string)$b['name']);
				return $ra <=> $rb;
			});
			if (strtoupper((string)$order) === 'DESC') $all_results = array_reverse($all_results);
			$modification_total = count($all_results);
			$max_page = max(1, (int)ceil($modification_total / $limit));
			if ($page > $max_page) $page = $max_page;
			$results = array_slice($all_results, ($page - 1) * $limit, $limit);
		} else {
			$filter_data = array('sort' => $sort, 'order' => $order, 'start' => ($page - 1) * $limit, 'limit' => $limit);
			$modification_total = $this->model_setting_modification->getTotalModifications();
			$results = $this->model_setting_modification->getModifications($filter_data);
		}

		// File-based OCMOD sources are not rows in the modification DB, but
		// they participate in the same refresh and must appear in diagnostics.
		$data['filesystem_modifications'] = array();
		foreach ($compatibility_map as $source_item) {
			if (!is_array($source_item)) { continue; }
			$source_type = isset($source_item['source']) ? (string)$source_item['source'] : 'database';
			if ($source_type !== 'system' && $source_type !== 'core') { continue; }
			$source_state = isset($source_item['state']) ? (string)$source_item['state'] : 'unchecked';
			$source_matches = $compatibility_filter === '' || $compatibility_filter === 'active' || $compatibility_filter === $source_state || ($compatibility_filter === 'issues' && ($source_state === 'error' || $source_state === 'warning'));
			if (!$source_matches) { continue; }
			$data['filesystem_modifications'][] = array(
				'name' => (string)($source_item['name'] ?? ''),
				'path' => (string)($source_item['source_file'] ?? ''),
				'source_type' => $source_type,
				'state' => $source_state,
				'message' => (string)($source_item['message'] ?? ''),
				'file' => (string)($source_item['file'] ?? ''),
				'issues_count' => (int)($source_item['issues_count'] ?? 0),
				'skipped_operations' => (int)($source_item['skipped_operations'] ?? 0)
			);
		}
		usort($data['filesystem_modifications'], function ($a, $b) {
			if ($a['state'] !== $b['state']) {
				$ranking = array('error'=>0, 'warning'=>1, 'ok'=>2);
				return ($ranking[$a['state']] ?? 3) <=> ($ranking[$b['state']] ?? 3);
			}
			return strcasecmp($a['name'], $b['name']);
		});
		$data['compatibility_summary'] = isset($compatibility_state['summary']) && is_array($compatibility_state['summary']) ? $compatibility_state['summary'] : array();
		$data['compatibility_generated_at'] = '';
		if (!empty($compatibility_state['generated_at'])) {
			$generated = strtotime((string)$compatibility_state['generated_at']);
			if ($generated) { $data['compatibility_generated_at'] = date($this->language->get('date_format_short') . ' H:i:s', $generated); }
		}
		$data['compatibility_core_build'] = isset($compatibility_state['core_build']) ? (string)$compatibility_state['core_build'] : '';
		$data['compatibility_package_build'] = isset($compatibility_state['package_build']) ? (string)$compatibility_state['package_build'] : '';
		$data['compatibility_filter'] = $compatibility_filter;
		$filter_base = 'user_token=' . $this->session->data['user_token'];
		$data['filter_all'] = $this->url->link('marketplace/modification', $filter_base, true);
		$data['filter_active'] = $this->url->link('marketplace/modification', $filter_base . '&compatibility_filter=active', true);
		$data['filter_ok'] = $this->url->link('marketplace/modification', $filter_base . '&compatibility_filter=ok', true);
		$data['filter_warning'] = $this->url->link('marketplace/modification', $filter_base . '&compatibility_filter=warning', true);
		$data['filter_error'] = $this->url->link('marketplace/modification', $filter_base . '&compatibility_filter=error', true);
		$data['filter_issues'] = $this->url->link('marketplace/modification', $filter_base . '&compatibility_filter=issues', true);

		foreach ($results as $result) {
			$compatibility_item = isset($compatibility_map[(string)$result['modification_id']]) && is_array($compatibility_map[(string)$result['modification_id']]) ? $compatibility_map[(string)$result['modification_id']] : array();
			$compatibility_label = '';
			$compatibility_class = '';
			$compatibility_title = '';
			$compatibility_state_name = 'unchecked';
			if (!$result['status']) {
				$compatibility_label = $this->language->get('text_not_active');
				$compatibility_class = 'label-default';
				$compatibility_title = $this->language->get('text_not_active_title');
				$compatibility_state_name = 'disabled';
			} elseif (isset($compatibility_item['state']) && $compatibility_item['state'] === 'error') {
				$compatibility_label = $this->language->get('text_not_applied');
				$compatibility_class = 'label-danger';
				$compatibility_title = isset($compatibility_item['message']) ? (string)$compatibility_item['message'] : '';
				if (!empty($compatibility_item['file'])) { $compatibility_title .= ($compatibility_title !== '' ? ' — ' : '') . (string)$compatibility_item['file']; }
				$compatibility_state_name = 'error';
			} elseif (isset($compatibility_item['state']) && $compatibility_item['state'] === 'warning') {
				$compatibility_label = $this->language->get('text_applied_skips');
				$compatibility_class = 'label-warning';
				$compatibility_title = sprintf($this->language->get('text_compatible_skips_title'), (int)($compatibility_item['warning_issues_count'] ?? 0));
				$compatibility_state_name = 'warning';
			} elseif (isset($compatibility_item['state']) && $compatibility_item['state'] === 'ok') {
				$compatibility_label = $this->language->get('text_applied');
				$compatibility_class = 'label-success';
				$compatibility_title = $this->language->get('text_applied_title');
				$compatibility_state_name = 'ok';
			} else {
				$compatibility_label = $this->language->get('text_not_checked');
				$compatibility_class = 'label-info';
				$compatibility_title = $this->language->get('text_not_checked_title');
			}

			$metrics = '';
			if ($compatibility_item && $result['status']) {
				$metrics = sprintf(
					$this->language->get('text_apply_metrics'),
					(int)($compatibility_item['applied_matches'] ?? 0),
					(int)($compatibility_item['skipped_operations'] ?? 0),
					(int)($compatibility_item['changed_files_count'] ?? 0)
				);
				if (!empty($compatibility_item['changed_files']) && is_array($compatibility_item['changed_files'])) {
					$visible_files = array_slice($compatibility_item['changed_files'], 0, 5);
					$files_text = implode(', ', $visible_files);
					if ((int)($compatibility_item['changed_files_count'] ?? 0) > count($visible_files)) {
						$files_text .= ' +' . ((int)$compatibility_item['changed_files_count'] - count($visible_files));
					}
					$compatibility_title .= ($compatibility_title !== '' ? ' — ' : '') . sprintf($this->language->get('text_changed_files_title'), $files_text);
				}
			}
			$data['modifications'][] = array(
				'modification_id' => $result['modification_id'],
				'name'            => $result['name'],
				'compatibility_label' => $compatibility_label,
				'compatibility_class' => $compatibility_class,
				'compatibility_title' => $compatibility_title,
				'compatibility_state' => $compatibility_state_name,
				'compatibility_metrics' => $metrics,
				'compatibility_details' => $compatibility_item,
				'author'          => $result['author'],
                'filename'        => $result['code'].".ocmod.xml",
				'version'         => $result['version'],
				'status'          => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'date_added'      => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'link'            => $result['link'],
                'edit'            => $this->url->link('marketplace/modification/edit', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $result['modification_id'], true),
                'download'        => $this->url->link('marketplace/modification/download', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $result['modification_id'], true),
                'enable'          => $this->url->link('marketplace/modification/enable', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $result['modification_id'], true),
				'disable'         => $this->url->link('marketplace/modification/disable', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $result['modification_id'], true),
				'enabled'         => $result['status']
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

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

		$data['sort_name'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_author'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . '&sort=author' . $url, true);
		$data['sort_version'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . '&sort=version' . $url, true);
		$data['sort_status'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);
		$data['sort_compatibility'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . '&sort=compatibility' . $url, true);
		$data['sort_date_added'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		if ($compatibility_filter !== '') {
			$url .= '&compatibility_filter=' . rawurlencode($compatibility_filter);
		}

		$pagination = new Pagination();
		$pagination->total = $modification_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($modification_total) ? (($page - 1) * $limit) + 1 : 0, min($modification_total, (($page - 1) * $limit) + $limit), $modification_total, max(1, (int)ceil($modification_total / $limit)));

		$data['sort'] = $sort;
		$data['order'] = $order;

		// Log
		$file = DIR_LOGS . 'ocmod.log';

		if (is_file($file) && is_readable($file)) {
			$size = (int)filesize($file);
			$limit = 2097152;
			if ($size > $limit) {
				$handle = fopen($file, 'rb');
				if ($handle) {
					fseek($handle, -$limit, SEEK_END);
					$raw = (string)stream_get_contents($handle);
					fclose($handle);
					$first_break = strpos($raw, "\n");
					if ($first_break !== false) { $raw = substr($raw, $first_break + 1); }
					$data['log'] = $this->language->get('text_log_tail') . "\n" . $this->compactOcmodLog($raw);
				} else { $data['log'] = ''; }
			} else {
				$data['log'] = $this->compactOcmodLog((string)file_get_contents($file));
			}
		} else {
			$data['log'] = '';
		}

		$data['clear_log'] = $this->url->link('marketplace/modification/clearlog', 'user_token=' . $this->session->data['user_token'], true);
		$data['download_log'] = $this->url->link('marketplace/modification/downloadlog', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketplace/modification', $data));
	}

    protected function getForm() {

        $this->load->language('marketplace/modification');

        $this->document->addStyle('view/javascript/codemirror/lib/codemirror.css');
        $this->document->addStyle('view/javascript/codemirror/theme/xq-dark.css');
        $this->document->addScript('view/javascript/codemirror/lib/codemirror.js');
        $this->document->addScript('view/javascript/codemirror/lib/xml.js');
        $this->document->addScript('view/javascript/codemirror/lib/formatting.js');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = $this->language->get('text_form');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_xml'] = $this->language->get('entry_xml');

        $data['column_id'] = $this->language->get('column_id');
        $data['column_code'] = $this->language->get('column_code');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_restore'] = $this->language->get('column_restore');

        $data['button_update'] = $this->language->get('button_update');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_restore'] = $this->language->get('button_restore');
        $data['button_history'] = $this->language->get('button_history');

        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_backup'] = $this->language->get('tab_backup');

        $data['user_token'] = $this->session->data['user_token'];

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

        $url = '';

        if (!isset($this->request->get['modification_id'])) {
            $data['action'] = $this->url->link('marketplace/modification/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('marketplace/modification/edit', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . $url, true);
        }

        $data['restore'] = $this->url->link('marketplace/modification/restore', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . $url, true);
        $data['history'] = $this->url->link('marketplace/modification/clearhistory', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . $url, true);
        $data['cancel'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $this->load->model('setting/modification');

        $backups = $this->model_setting_modification->getModificationBackups($this->request->get['modification_id']);

        $data['backups'] = array();

        if ($backups) {
            foreach ($backups as $backup) {
                $data['backups'][] = array(
                    'backup_id'     => $backup['backup_id'],
                    'code'          => $backup['code'],
                    'date_added'    => $backup['date_added'],
                    'restore'       => $this->url->link('marketplace/modification/restore', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $this->request->get['modification_id'] . '&backup_id=' . $backup['backup_id'] . $url, true)
                );
            }
        }

        $modification = $this->model_setting_modification->getModification($this->request->get['modification_id']);

        if (isset($this->request->post['name'])) {
            $data['name'] = htmlentities(ltrim($this->request->post['name']));
        } elseif (isset($modification)) {
            $data['name'] = htmlentities(ltrim($modification['name']));
        }

        if (isset($this->request->post['xml'])) {
            $data['xml'] = htmlentities(ltrim($this->request->post['xml'], "﻿"));
        } elseif (isset($modification)) {
            $data['xml'] = htmlentities(ltrim($modification['xml'], "﻿"));
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('marketplace/modification_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'marketplace/modification')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 2)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'marketplace/modification')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
