<?php
class ModelInstallInstall extends Model {
	public function database($data) {
		$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);

		$this->assertPrefixAvailable($db, $data['db_prefix']);

		try {
			$file = DIR_APPLICATION . 'opencart.sql';

			if (!file_exists($file)) {
				throw new \RuntimeException('Could not load SQL file: ' . $file);
			}

			$script = file_get_contents($file);

			if ($script === false) {
				throw new \RuntimeException('Could not read SQL file: ' . $file);
			}

			foreach (\CodeCart\Core\SqlScript::statements($script) as $statement) {
				$db->query(\CodeCart\Core\SqlScript::applyPrefix($statement, (string)$data['db_prefix']));
			}

			$db->query("SET CHARACTER SET utf8mb4");
			$db->query("SET collation_connection = 'utf8mb4_unicode_ci'");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE user_id = '1'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '1', user_group_id = '1', username = '" . $db->escape($data['username']) . "', salt = '', password = '" . $db->escape(codecart_password_hash(codecart_password_input($data['password']))) . "', firstname = 'CodeCart', lastname = 'Pro', email = '" . $db->escape($data['email']) . "', status = '1', image = 'catalog/profile-pic.webp', code = '', ip = '', date_added = NOW()");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $db->escape($data['email']) . "', serialized = '0'");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $db->escape(token(1024)) . "', serialized = '0'");

			// Production-safe defaults derived from the actual installer URL.
			$secure = (defined('HTTP_OPENCART') && stripos((string)HTTP_OPENCART, 'https://') === 0) ? '1' : '0';
			$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = '" . $secure . "' WHERE store_id = '0' AND `key` = 'config_secure'");
			$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = '0' WHERE store_id = '0' AND `key` = 'config_error_display'");

			$db->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");

			$db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET username = 'Default', `key` = '" . $db->escape(token(256)) . "', status = 1, date_added = NOW(), date_modified = NOW()");
			$api_id = $db->getLastId();

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', value = '" . (int)$api_id . "', serialized = '0'");

			$scheduler_key = bin2hex(random_bytes(32));
			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'codecart_scheduler_key'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_scheduler_key', value = '" . $db->escape($scheduler_key) . "', serialized = '0'");

			// robots.txt advertises /sitemap.xml on a clean install, so the sitemap feed must be active.
			$db->query("DELETE FROM `" . $data['db_prefix'] . "extension` WHERE `type` = 'feed' AND `code` = 'google_sitemap'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "extension` SET `type` = 'feed', `code` = 'google_sitemap'");
			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'feed_google_sitemap_status'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET store_id = '0', code = 'feed_google_sitemap', `key` = 'feed_google_sitemap_status', value = '1', serialized = '0'");

			$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'codecart_db_modernization_required'");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET store_id = '0', code = 'codecart_core', `key` = 'codecart_db_modernization_required', value = '0', serialized = '0'");

			// Register lightweight core maintenance tasks immediately on a fresh install.
			$db->query("DELETE FROM `" . $data['db_prefix'] . "codecart_scheduler` WHERE code IN ('core.currency.refresh','core.cart.cleanup')");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "codecart_scheduler` SET code = 'core.currency.refresh', route = 'cron/currency', args = '{}', interval_seconds = '86400', status = '1', date_next = NOW(), date_added = NOW(), date_modified = NOW()");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "codecart_scheduler` SET code = 'core.cart.cleanup', route = 'cron/cart', args = '{}', interval_seconds = '3600', status = '1', date_next = NOW(), date_added = NOW(), date_modified = NOW()");

			// Generate Administrator permissions from controllers that actually ship in
			// this build. This prevents removed legacy extensions leaving dead routes.
			$permission = $this->buildAdministratorPermission();
			$db->query("UPDATE `" . $data['db_prefix'] . "user_group` SET permission = '" . $db->escape(json_encode($permission, JSON_UNESCAPED_SLASHES)) . "' WHERE user_group_id = '1'");

			// Set the current year's invoice prefix.
			$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = 'INV-" . date('Y') . "-00' WHERE `key` = 'config_invoice_prefix'");

			// Fresh-install only: seed default branding from immutable system assets.
			// UPDATE packages intentionally do not ship mutable catalog logo/favicon files.
			$this->seedDefaultBranding();
		} catch (\Throwable $e) {
			$this->cleanupFailedInstall($db, $data['db_prefix']);
			throw new \RuntimeException('Database installation failed: ' . $e->getMessage(), 0, $e);
		}
	}

	private function seedDefaultBranding() {
		$assetRoot = rtrim(DIR_SYSTEM, '/\\') . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'codecart' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
		$imageRoot = rtrim(DIR_OPENCART, '/\\') . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR;
		$copies = array(
			$assetRoot . 'default-logo.webp' => $imageRoot . 'catalog' . DIRECTORY_SEPARATOR . 'logo.webp',
			$assetRoot . 'default-favicon.webp' => $imageRoot . 'catalog' . DIRECTORY_SEPARATOR . 'favicon.webp',
			$assetRoot . 'default-profile.webp' => $imageRoot . 'profile.webp'
		);

		foreach ($copies as $source => $target) {
			if (!is_file($source)) {
				throw new \RuntimeException('Default branding asset is missing: ' . basename($source));
			}

			if (is_file($target)) {
				continue;
			}

			$directory = dirname($target);
			if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
				throw new \RuntimeException('Cannot create branding image directory.');
			}

			if (!@copy($source, $target)) {
				throw new \RuntimeException('Cannot install default branding asset: ' . basename($target));
			}
		}
	}



	public function rollbackDatabase($data) {
		try {
			$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);
			$this->cleanupFailedInstall($db, $data['db_prefix']);
		} catch (\Throwable $e) {
			// Configuration failure remains the primary error; rollback is best effort.
		}
	}

	private function buildAdministratorPermission() {
		$root = DIR_OPENCART . 'admin/controller/';
		$routes = array();
		if (is_dir($root)) {
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
			foreach ($iterator as $file) {
				if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') { continue; }
				$relative = substr(str_replace('\\', '/', $file->getPathname()), strlen(str_replace('\\', '/', $root)));
				$route = preg_replace('/\.php$/i', '', $relative);
				if ($route !== '') { $routes[] = $route; }
			}
		}
		$routes = array_values(array_unique($routes));
		sort($routes, SORT_STRING);
		return array('access' => $routes, 'modify' => $routes);
	}

	private function cleanupFailedInstall($db, $prefix) {
		try {
			$query = $db->query('SHOW TABLES');
			$tables = array();

			foreach ($query->rows as $row) {
				$table = (string)reset($row);

				if ($table !== '' && strpos($table, $prefix) === 0) {
					$tables[] = $table;
				}
			}

			if (!$tables) {
				return;
			}

			$db->query('SET FOREIGN_KEY_CHECKS = 0');

			try {
				foreach ($tables as $table) {
					$db->query('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
				}
			} finally {
				$db->query('SET FOREIGN_KEY_CHECKS = 1');
			}
		} catch (\Throwable $cleanup_error) {
			// Keep the original installation exception. Cleanup is best-effort only.
		}
	}

	private function assertPrefixAvailable($db, $prefix) {
		$query = $db->query('SHOW TABLES');

		foreach ($query->rows as $row) {
			$table = (string)reset($row);

			if ($table !== '' && strpos($table, $prefix) === 0) {
				throw new \RuntimeException('Refusing to install: the selected database already contains tables using prefix ' . $prefix . '.');
			}
		}
	}

}
